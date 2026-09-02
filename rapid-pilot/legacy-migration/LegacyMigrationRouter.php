<?php

declare(strict_types=1);

require_once __DIR__ . '/LegacyObjectClassification.php';

spl_autoload_register(static function(string $class):void {
    $namespace='FMonitor2\\InstallationProcess\\';
    if(!str_starts_with($class,$namespace))return;
    $file=dirname(__DIR__,2).'/app/InstallationProcess/'.str_replace('\\','/',substr($class,strlen($namespace))).'.php';
    if(is_file($file))require_once $file;
});

final class LegacyMigrationRoute
{
    /** @param array<string,mixed> $classification */
    public static function decide(array $classification): array
    {
        if (($classification['classificationVersion'] ?? null) !== LegacyObjectClassification::VERSION) throw new InvalidArgumentException('Unsupported classification version');
        $route = match ($classification['category'] ?? null) {
            'native_candidate' => 'operational_case_import',
            'legacy_active' => 'cutover_baseline',
            'legacy_historical' => 'historical_reconstruction',
            default => throw new InvalidArgumentException('Unsupported classification category'),
        };
        return ['route' => $route, 'applyBlocked' => ($classification['quarantineCodes'] ?? []) !== [],
            'classification' => $classification];
    }
}

final class LegacyObjectMySqlClassificationSource
{
    public function __construct(private mysqli $db) {}

    public function read(int $objectId, string $cutover, bool $manageTransaction = true): array
    {
        $sql = <<<'SQL'
SELECT m.id,m.ordadr_address,m.entrance,m.regnumber,
       CASE WHEN m.factworkstartdate<=? THEN m.factworkstartdate ELSE NULL END factworkstartdate,
       CASE WHEN m.ptoactdate<=? THEN m.ptoactdate ELSE NULL END ptoactdate,m.object_status,m.fact_percent,m.workstarted,
       (SELECT COUNT(*) FROM fm_install_checklists_values_log e WHERE e.value_id=m.id AND e.ctime<=?) checklist_event_count,
       (SELECT COUNT(*) FROM fm_install_checklists_values_installators_log ai JOIN fm_install_checklists_values v ON v.id=ai.checklist_value_id WHERE v.value_id=m.id AND ai.ctime<=?) attribution_count
FROM fm_maintable m WHERE m.id=? LIMIT 1
SQL;
        if ($manageTransaction) { $this->db->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ'); $this->db->query('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY'); }
        try {
            $statement = $this->db->prepare($sql); $statement->bind_param('ssssi', $cutover, $cutover, $cutover, $cutover, $objectId); $statement->execute();
            $row = $statement->get_result()->fetch_assoc();
            if ($manageTransaction) $this->db->commit();
            if ($row === null) throw new OutOfBoundsException('LEGACY_OBJECT_NOT_FOUND');
            return $row;
        } catch (Throwable $error) { if ($manageTransaction) try { $this->db->rollback(); } catch (Throwable) {} throw $error; }
    }
}

final class LegacyActiveBaselineTarget
{
    public const CONTRACT_VERSION = 'legacy-active-cutover-baseline-v1';
    private bool $schemaReady = false;

    public function __construct(private mysqli $db, private string $prefix)
    {
        if (preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) throw new InvalidArgumentException('Invalid local table prefix');
    }

    /** @param array<string,mixed> $sourceRow @param array<string,mixed> $classification */
    public function apply(array $sourceRow, array $classification, string $cutover, string $createdAt): array
    {
        $route = LegacyMigrationRoute::decide($classification);
        if ($route['route'] !== 'cutover_baseline' || $route['applyBlocked']) throw new DomainException('BASELINE_APPLY_NOT_ALLOWED');
        self::timestamp($cutover); self::timestamp($createdAt);
        $payload = self::canonical(['contractVersion' => self::CONTRACT_VERSION, 'sourceSystem' => 'legacy_fmonitor',
            'cutover' => $cutover, 'legacyObject' => $sourceRow, 'classification' => $classification]);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $json); $id = (int)$sourceRow['id']; $p = $this->prefix;
        if (!$this->schemaReady) { $this->db->query("CREATE TABLE IF NOT EXISTS `{$p}fm2_legacy_active_baselines` (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,legacy_object_id BIGINT UNSIGNED NOT NULL,contract_version VARCHAR(80) NOT NULL,cutover_at DATETIME NOT NULL,content_sha256 CHAR(64) NOT NULL,payload_json LONGTEXT NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uq_legacy_object(legacy_object_id),UNIQUE KEY uq_content(content_sha256)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); $this->schemaReady = true; }
        $this->db->begin_transaction();
        try {
            $insert = $this->db->prepare("INSERT IGNORE INTO `{$p}fm2_legacy_active_baselines`(legacy_object_id,contract_version,cutover_at,content_sha256,payload_json,created_at) VALUES(?,?,?,?,?,?)");
            $version = self::CONTRACT_VERSION; $insert->bind_param('isssss', $id, $version, $cutover, $hash, $json, $createdAt); $insert->execute();
            $created = $insert->affected_rows === 1;
            $lookup = $this->db->prepare("SELECT id,content_sha256 FROM `{$p}fm2_legacy_active_baselines` WHERE legacy_object_id=?"); $lookup->bind_param('i', $id); $lookup->execute();
            $stored = $lookup->get_result()->fetch_assoc();
            if ($stored === null || !hash_equals($hash, (string)$stored['content_sha256'])) throw new DomainException('BASELINE_ALREADY_EXISTS_WITH_DIFFERENT_CONTENT');
            $this->db->commit();
            return ['baselineId' => (int)$stored['id'], 'contentSha256' => $hash, 'created' => $created];
        } catch (Throwable $error) { $this->db->rollback(); throw $error; }
    }

    private static function timestamp(string $value): void
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC')); $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d H:i:s') !== $value) throw new InvalidArgumentException('Invalid exact timestamp');
    }
    private static function canonical(mixed $value): mixed { if (!is_array($value)) return $value; if (!array_is_list($value)) ksort($value, SORT_STRING); foreach ($value as $k => $v) $value[$k] = self::canonical($v); return $value; }
}

final class MigrationClassificationProvenanceTarget
{
    public function __construct(private mysqli $db, private string $prefix)
    {
        if (strlen($prefix) > 25 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) throw new InvalidArgumentException('Invalid local table prefix');
        $this->assertSchemaAvailable();
    }

    public function assertSchemaAvailable(): void
    {
        $collation=\FMonitor2\InstallationProcess\IdentityAccessDefinitionSchemaMigration::databaseCollation($this->db);
        $definition=\FMonitor2\InstallationProcess\ClassificationProvenanceDefinitionSchemaMigration::definition($this->prefix,$collation);
        $table=$this->prefix.\FMonitor2\InstallationProcess\ClassificationProvenanceDefinitionSchemaMigration::TABLE;
        if(!\FMonitor2\InstallationProcess\MariaDbSchemaInspector::tableExists($this->db,$table)
            || !\FMonitor2\InstallationProcess\MariaDbClassificationProvenanceSchemaFingerprint::matches($this->db,$table,$definition,$collation)) {
            throw new RuntimeException('Classification provenance schema unavailable');
        }
    }

    /** @param array<string,mixed> $classification */
    public function reconcile(string $outputKind, int $legacyObjectId, int $outputId, string $cutoff, array $classification, string $createdAt): array
    {
        $json = json_encode($classification, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $json); $p = $this->prefix;
        $reasons = json_encode($classification['reasonCodes'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $version = (string)$classification['classificationVersion']; $category = (string)$classification['category'];
        $insert = $this->db->prepare("INSERT IGNORE INTO `{$p}fm2_migration_classification_provenance`(output_kind,legacy_object_id,output_id,source_cutoff_at,classification_version,category,reason_codes_json,classification_sha256,created_at) VALUES(?,?,?,?,?,?,?,?,?)");
        $insert->bind_param('siissssss', $outputKind, $legacyObjectId, $outputId, $cutoff, $version, $category, $reasons, $hash, $createdAt); $insert->execute();
        $created = $insert->affected_rows === 1;
        $lookup = $this->db->prepare("SELECT id,legacy_object_id,source_cutoff_at,classification_sha256 FROM `{$p}fm2_migration_classification_provenance` WHERE output_kind=? AND output_id=?");
        $lookup->bind_param('si', $outputKind, $outputId); $lookup->execute(); $stored = $lookup->get_result()->fetch_assoc();
        if ($stored === null || (int)$stored['legacy_object_id'] !== $legacyObjectId || $stored['source_cutoff_at'] !== $cutoff || !hash_equals($hash, (string)$stored['classification_sha256'])) throw new DomainException('PROVENANCE_CONFLICT');
        return ['provenanceId'=>(int)$stored['id'],'provenanceCreated'=>$created,'classificationSha256'=>$hash];
    }
}

function assertClassificationProvenanceSchemaAvailable(mysqli $db,string $prefix):MigrationClassificationProvenanceTarget
{
    return new MigrationClassificationProvenanceTarget($db,$prefix);
}
