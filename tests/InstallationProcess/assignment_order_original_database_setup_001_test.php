<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/AssignmentOrderOriginalDatabaseSetupV1.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigration;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationDatabaseFixture;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFixtureConflict;
use FMonitor2\Tests\Support\AssignmentOrderOriginalDatabaseSetupV1 as Contract;

// Specification: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v12, setup Gate 2.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('FMONITOR_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FMONITOR_TEST_DB_PORT') ?: 23306);
$user = getenv('FMONITOR_TEST_DB_ADMIN_USER') ?: 'root';
$password = getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD') ?: 'fmonitor2_test_root_local';
$token = bin2hex(random_bytes(6));
$databases = array_map(static fn (string $axis): string => "t_aoou_{$axis}_{$token}", ['clean','partial','populated','conflict','fixture']);
$prefix = 'aoou_';
$admin = new mysqli($host, $user, $password, '', $port);
$admin->set_charset('utf8mb4');

$quote = static fn (string $name): string => '`' . str_replace('`', '``', $name) . '`';
$connect = static function (string $database) use ($host, $port, $user, $password): mysqli {
    $db = new mysqli($host, $user, $password, $database, $port);
    $db->set_charset('utf8mb4');
    return $db;
};
$tableNames = static function (mysqli $db, string $like = '%'): array {
    $statement = $db->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME LIKE ? ORDER BY BINARY TABLE_NAME');
    $statement->bind_param('s', $like); $statement->execute();
    return array_column($statement->get_result()->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
};
$columns = static function (mysqli $db, string $table): array {
    $statement = $db->prepare('SELECT COLUMN_NAME,LOWER(COLUMN_TYPE) COLUMN_TYPE,IS_NULLABLE,CHARACTER_SET_NAME,COLLATION_NAME,COLUMN_DEFAULT,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
    $statement->bind_param('s', $table); $statement->execute();
    return array_map(static function(array$row):array{if($row['IS_NULLABLE']==='YES'&&$row['COLUMN_DEFAULT']==='NULL')$row['COLUMN_DEFAULT']=null;return array_values($row);},$statement->get_result()->fetch_all(MYSQLI_ASSOC));
};
$keys = static function (mysqli $db, string $table): array {
    $statement = $db->prepare("SELECT INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') COLUMNS FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? GROUP BY INDEX_NAME,NON_UNIQUE");
    $statement->bind_param('s', $table); $statement->execute();
    $rows = array_map(static fn (array $row): array => [$row['INDEX_NAME']==='PRIMARY'?'PRIMARY':((int)$row['NON_UNIQUE']===0?'UNIQUE':'INDEX'),$row['COLUMNS']], $statement->get_result()->fetch_all(MYSQLI_ASSOC));
    sort($rows); return $rows;
};
$foreignKeys = static function (mysqli $db, string $table, string $prefix): array {
    $statement = $db->prepare('SELECT k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME=? AND k.REFERENCED_TABLE_NAME IS NOT NULL');
    $statement->bind_param('s', $table); $statement->execute();
    $rows = array_map(static fn(array $row):array => [$row['COLUMN_NAME'],substr($row['REFERENCED_TABLE_NAME'],strlen($prefix)),$row['REFERENCED_COLUMN_NAME'],$row['UPDATE_RULE'].'/'.$row['DELETE_RULE']], $statement->get_result()->fetch_all(MYSQLI_ASSOC));
    sort($rows); return $rows;
};
$checkCount = static function (mysqli $db, string $table): int {
    $statement = $db->prepare('SELECT COUNT(*) n FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_TYPE=\'CHECK\'');
    $statement->bind_param('s', $table); $statement->execute();
    return (int) $statement->get_result()->fetch_assoc()['n'];
};
$validateBooleanLexical=static function(string$value):void{$depth=0;$quoted=false;for($i=0,$length=strlen($value);$i<$length;$i++){$char=$value[$i];if($quoted){if($char==="\\"){if($i+1>=$length)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.');$i++;continue;}if($char==="'"){if($i+1<$length&&$value[$i+1]==="'"){$i++;continue;}$quoted=false;}continue;}if($char==="'"){$quoted=true;continue;}if($char==='('){$depth++;continue;}if($char===')'){if($depth===0)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.');$depth--;continue;}if($char===';')throw new TestFailure('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.');}if($quoted||$depth!==0)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.');};
$foldUnquotedAscii=static function(string$value):string{$folded='';$quoted=false;for($i=0,$length=strlen($value);$i<$length;$i++){$char=$value[$i];if($quoted){$folded.=$char;if($char==="\\"&&$i+1<$length){$folded.=$value[++$i];continue;}if($char==="'"&&$i+1<$length&&$value[$i+1]==="'"){$folded.=$value[++$i];continue;}if($char==="'")$quoted=false;continue;}if($char==="'"){$quoted=true;$folded.=$char;continue;}$ord=ord($char);$folded.=$ord>=65&&$ord<=90?chr($ord+32):$char;}return$folded;};
$stripBooleanOuter=static function(string$value):string{while(str_starts_with($value,'(')&&str_ends_with($value,')')){$depth=0;$quote=false;$whole=true;for($i=0,$n=strlen($value);$i<$n;$i++){$char=$value[$i];if($quote){if($char==="\\"){$i++;continue;}if($char==="'"){if($i+1<$n&&$value[$i+1]==="'"){$i++;continue;}$quote=false;}continue;}if($char==="'"){$quote=true;continue;}if($char==='(')$depth++;elseif($char===')')$depth--;if($depth===0&&$i<$n-1){$whole=false;break;}if($depth<0)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: unbalanced expression.');}if($quote||$depth!==0)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: malformed expression.');if(!$whole)break;$value=trim(substr($value,1,-1));}return$value;};
$splitBoolean=static function(string$value,string$operator):array{$parts=[];$start=0;$depth=0;$quote=false;$between=false;for($i=0,$n=strlen($value);$i<$n;){$char=$value[$i];if($quote){if($char==="\\"){$i+=2;continue;}if($char==="'"){if($i+1<$n&&$value[$i+1]==="'"){$i+=2;continue;}$quote=false;}$i++;continue;}if($char==="'"){$quote=true;$i++;continue;}if($char==='('){$depth++;$i++;continue;}if($char===')'){$depth--;$i++;continue;}if($depth===0&&preg_match('/\G([a-z_][a-z0-9_]*)/A',$value,$match,0,$i)===1){$word=$match[1];$length=strlen($word);if($word==='between')$between=true;if($word===$operator){if($operator==='and'&&$between){$between=false;}else{$parts[]=trim(substr($value,$start,$i-$start));$start=$i+$length;}}$i+=$length;continue;}$i++;}if($parts===[])return[$value];$parts[]=trim(substr($value,$start));return$parts;};
$parseBoolean=null;
$parseBoolean=static function(string$value)use(&$parseBoolean,$stripBooleanOuter,$splitBoolean):array{$value=$stripBooleanOuter(trim($value));$or=$splitBoolean($value,'or');if(count($or)>1)return['or',array_map($parseBoolean,$or)];$and=$splitBoolean($value,'and');if(count($and)>1)return['and',array_map($parseBoolean,$and)];$atom=str_replace(['`',' ',"\n","\r","\t"],'',$value);$atom=str_replace("'[[:cntrl:]/\\\\\\\\]'","'[[:cntrl:]/\\\\]'",$atom);$atom=(string)preg_replace("/!\\(([^()]+)regexp('[^']*')\\)/","$1notregexp$2",$atom);if($atom===''||str_contains($atom,';')||str_contains($atom,'xor'))throw new TestFailure('CHECK_NORMALIZATION_FAILURE: unsupported atom.');return['atom',$atom];};
$serializeBoolean=null;
$serializeBoolean=static function(array$node,bool$insideOr=false)use(&$serializeBoolean):string{if($node[0]==='atom')return$node[1];if(!in_array($node[0],['and','or'],true)||!isset($node[1])||count($node[1])<2)throw new TestFailure('CHECK_NORMALIZATION_FAILURE: unsupported AST.');if($node[0]==='and'){$value=implode('and',array_map(static fn(array$child):string=>$serializeBoolean($child,false),$node[1]));return$insideOr?'('.$value.')':$value;}return'('.implode('or',array_map(static fn(array$child):string=>$serializeBoolean($child,true),$node[1])).')';};
$normalizeCheck=static function(string$value)use($validateBooleanLexical,$foldUnquotedAscii,$serializeBoolean,$parseBoolean):string{$validateBooleanLexical($value);return$serializeBoolean($parseBoolean($foldUnquotedAscii(str_replace('`','',$value))));};
$checks = static function (mysqli $db, string $table) use ($normalizeCheck): array {
    $statement=$db->prepare('SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY BINARY CHECK_CLAUSE');
    $statement->bind_param('s',$table);$statement->execute();
    $result=array_map(static fn(array$row):string=>$normalizeCheck($row['CHECK_CLAUSE']),$statement->get_result()->fetch_all(MYSQLI_ASSOC));sort($result,SORT_STRING);return$result;
};
$tableProperties = static function (mysqli $db, string $table): array {
    $statement = $db->prepare('SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $statement->bind_param('s', $table); $statement->execute();
    return array_values($statement->get_result()->fetch_assoc());
};
$snapshot = static function (mysqli $db) use ($tableNames,$columns,$keys,$foreignKeys,$checks,$tableProperties): string {
    $structure=[];
    foreach($tableNames($db)as$table){
        $structure[$table]=[
            'table'=>$tableProperties($db,$table),
            'columns'=>$columns($db,$table),
            'keys'=>$keys($db,$table),
            'foreignKeys'=>$foreignKeys($db,$table,''),
            'checks'=>$checks($db,$table),
        ];
    }
    return json_encode($structure,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
};
$stateSnapshot = static function (mysqli $db) use ($tableNames, $quote, $snapshot): string {
    $rows = [];
    foreach ($tableNames($db) as $table) {
        $tableRows = $db->query('SELECT * FROM ' . $quote($table))->fetch_all(MYSQLI_ASSOC);
        foreach ($tableRows as &$row) ksort($row, SORT_STRING);
        unset($row);
        usort($tableRows, static fn (array $left, array $right): int => json_encode($left, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) <=> json_encode($right, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $rows[$table] = $tableRows;
    }
    return json_encode(['schema' => $snapshot($db), 'rows' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
};

$created = [];
try {
    foreach ($databases as $database) {
        assertSameValue(1, preg_match('/^t_aoou_[a-z]+_[0-9a-f]{12}$/D', $database), 'Database cleanup target is independently bounded.');
        $admin->query('CREATE DATABASE ' . $quote($database) . ' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $created[] = $database;
    }
    $preflight = $connect($databases[0]);
    assertSameValue('1', (string) $preflight->query('SELECT 1 ready')->fetch_assoc()['ready'], 'Isolated MariaDB setup is live before intended RED.');
    $preflight->query(Contract::rootsDdl('probe_'));
    $roundTripExpected=Contract::checks()[Contract::TABLES[0]];sort($roundTripExpected,SORT_STRING);
    assertSameValue($roundTripExpected,$checks($preflight,'probe_'.Contract::TABLES[0]),'Approved roots DDL CHECKs survive MariaDB SQL-literal round trip.');
    $wrongDdl=str_replace("'[[:cntrl:]/\\\\\\\\]'","'[[:cntrl:]/]'",Contract::rootsDdl('wrong_'));
    assertSameValue(false,$wrongDdl===Contract::rootsDdl('wrong_'),'Wrong-pattern round-trip fixture changes the exact regex only.');
    $preflight->query($wrongDdl);
    assertSameValue(false,$roundTripExpected===$checks($preflight,'wrong_'.Contract::TABLES[0]),'Same-count wrong regex remains observable after MariaDB round trip.');
    $preflight->query("CREATE TABLE `default_probe` (implicit_null VARCHAR(20) NULL, explicit_null VARCHAR(20) NULL DEFAULT NULL, wrong_default VARCHAR(20) NULL DEFAULT 'wrong') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $defaultProbe=$columns($preflight,'default_probe');
    assertSameValue(null,$defaultProbe[0][5],'Implicit nullable NULL default canonicalizes to PHP null.');
    assertSameValue(null,$defaultProbe[1][5],'Explicit nullable DEFAULT NULL canonicalizes to the same PHP null.');
    assertSameValue(true,is_string($defaultProbe[2][5])&&$defaultProbe[2][5]!==''&&$defaultProbe[2][5]!=='NULL','Non-null wrong default remains observable and distinct.');
    $revisionChecks=array_values(array_filter(Contract::checks()[Contract::TABLES[1]],static fn(string$value):bool=>str_contains($value,'revision_number=1andprevious_revision_id')));
    assertSameValue(1,count($revisionChecks),'Exactly one approved revision boolean CHECK oracle is selected.');
    $revisionColumns="revision_number INT UNSIGNED NOT NULL, previous_revision_id VARCHAR(80) NULL, correction_reason VARCHAR(500) NULL, event_type VARCHAR(80) NOT NULL";
    $approvedRevisionSql="((revision_number=1 AND previous_revision_id IS NULL AND correction_reason IS NULL AND event_type='assignment_order_original_accepted') OR (revision_number>1 AND previous_revision_id IS NOT NULL AND CHAR_LENGTH(TRIM(correction_reason)) BETWEEN 1 AND 500 AND event_type='assignment_order_original_corrected'))";
    $preflight->query("CREATE TABLE `revision_boolean_probe` ({$revisionColumns}, CHECK ({$approvedRevisionSql})) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    assertSameValue($revisionChecks,$checks($preflight,'revision_boolean_probe'),'Approved revision OR CHECK survives MariaDB redundant-parentheses removal.');
    $wrongRevisionSql=str_replace('revision_number>1','revision_number>=1',$approvedRevisionSql);
    $preflight->query("CREATE TABLE `wrong_revision_boolean_probe` ({$revisionColumns}, CHECK ({$wrongRevisionSql})) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    assertSameValue(false,$revisionChecks===$checks($preflight,'wrong_revision_boolean_probe'),'Changed revision operator remains observable after boolean canonicalization.');
    $uuidCheck="request_id REGEXP '^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'";
    $idCheck=static fn(string$value,int$limit=80):string=>"CHAR_LENGTH({$value}) BETWEEN 1 AND {$limit} AND {$value} NOT REGEXP '[[:cntrl:]/\\\\\\\\]'";
    $createCheckProbe=static function(string$name,string$columns,array$expressions,string$contractTable)use($preflight,$checks):void{$ddl=implode(',',array_map(static fn(string$expression):string=>'CHECK ('.$expression.')',$expressions));$preflight->query("CREATE TABLE `{$name}` ({$columns},{$ddl}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");$expected=Contract::checks()[$contractTable];sort($expected,SORT_STRING);assertSameValue($expected,$checks($preflight,$name),"Every {$contractTable} CHECK survives real MariaDB round trip.");};
    $createCheckProbe('all_revision_checks_probe',"revision_id VARCHAR(80),root_original_id VARCHAR(80),revision_number INT UNSIGNED,previous_revision_id VARCHAR(80),document_date DATE,uploaded_at_utc DATETIME(6),actor_user_id BIGINT UNSIGNED,pdf_sha256 CHAR(64),byte_size INT UNSIGNED,private_content_identity VARCHAR(160),correction_reason VARCHAR(500),request_id CHAR(36),operation_fingerprint CHAR(64),event_type VARCHAR(80)",[
        'revision_number>=1',"pdf_sha256 REGEXP '^[0-9a-f]{64}$'","operation_fingerprint REGEXP '^[0-9a-f]{64}$'",'byte_size BETWEEN 1 AND 20971520',"event_type IN ('assignment_order_original_accepted','assignment_order_original_corrected')",$approvedRevisionSql,$idCheck('revision_id'),$idCheck('root_original_id'),$idCheck('previous_revision_id'),$idCheck('private_content_identity',160),
    ],Contract::TABLES[1]);
    $evidenceNonNull='root_original_id IS NOT NULL AND current_revision_id IS NOT NULL AND revision_number IS NOT NULL AND document_date IS NOT NULL AND sha256 IS NOT NULL AND byte_size IS NOT NULL AND uploaded_at_utc IS NOT NULL';
    $evidenceNull='root_original_id IS NULL AND current_revision_id IS NULL AND revision_number IS NULL AND document_date IS NULL AND sha256 IS NULL AND byte_size IS NULL AND uploaded_at_utc IS NULL';
    $rejectedReasons="reason_code IN ('authorization_denied','invalid_command','order_not_found','composition_not_confirmed','invalid_composition','file_too_large','not_pdf','invalid_pdf','unsafe_pdf','future_document_date','no_changes')";$conflictReasons="reason_code IN ('semantic_collision','stale_revision','target_not_found','target_not_current','initial_already_exists')";
    $createCheckProbe('all_request_checks_probe',"request_id CHAR(36),mode VARCHAR(20),status VARCHAR(20),reason_code VARCHAR(80),retryable TINYINT UNSIGNED,root_original_id VARCHAR(80),current_revision_id VARCHAR(80),revision_number INT UNSIGNED,document_date DATE,sha256 CHAR(64),byte_size INT UNSIGNED,uploaded_at_utc DATETIME(6)",[
        $uuidCheck,"mode IN ('initial','correction')","status IN ('accepted','replayed','rejected','conflict')",'retryable=0',"status NOT IN ('accepted','replayed') OR (reason_code IS NULL AND {$evidenceNonNull})","status <> 'rejected' OR ({$rejectedReasons} AND {$evidenceNull})","status <> 'conflict' OR ({$conflictReasons} AND {$evidenceNull})",$idCheck('root_original_id'),$idCheck('current_revision_id'),
    ],Contract::TABLES[2]);
    $createCheckProbe('all_event_checks_probe',"event_type VARCHAR(80),root_original_id VARCHAR(80),revision_id VARCHAR(80)",["event_type IN ('assignment_order_original_accepted','assignment_order_original_corrected')",$idCheck('root_original_id'),$idCheck('revision_id')],Contract::TABLES[3]);
    $createCheckProbe('all_audit_checks_probe',"request_id CHAR(36),mode VARCHAR(20),status VARCHAR(20),reason_code VARCHAR(80)",[$uuidCheck,"mode IN ('initial','correction')","status IN ('accepted','rejected','conflict')","(status='accepted' AND reason_code IS NULL) OR (status='rejected' AND {$rejectedReasons}) OR (status='conflict' AND {$conflictReasons})"],Contract::TABLES[4]);
    $maintenanceColumns="request_id CHAR(36),status VARCHAR(20),reason_code VARCHAR(80),retryable TINYINT UNSIGNED,scanned INT UNSIGNED,deleted INT UNSIGNED,retained INT UNSIGNED,failed INT UNSIGNED";
    $createCheckProbe('all_maintenance_request_checks_probe',$maintenanceColumns,[$uuidCheck,"status IN ('completed','replayed','rejected','partial')","(status IN ('completed','replayed') AND reason_code IS NULL AND retryable=0) OR (status='rejected' AND reason_code IN ('invalid_command','authorization_denied') AND retryable=0) OR (status='partial' AND reason_code IN ('locked','storage_failure') AND retryable=1)",'scanned=deleted+retained+failed'],Contract::TABLES[5]);
    $createCheckProbe('all_maintenance_audit_checks_probe',$maintenanceColumns,[$uuidCheck,"status IN ('completed','rejected','partial')","(status='completed' AND reason_code IS NULL AND retryable=0) OR (status='rejected' AND reason_code IN ('invalid_command','authorization_denied') AND retryable=0) OR (status='partial' AND reason_code IN ('locked','storage_failure') AND retryable=1)",'scanned=deleted+retained+failed'],Contract::TABLES[6]);
    $wrongAudit="(status='accepted' AND reason_code IS NULL) AND ((status='rejected' AND {$rejectedReasons}) OR (status='conflict' AND {$conflictReasons}))";
    $preflight->query("CREATE TABLE `wrong_grouping_probe` (status VARCHAR(20),reason_code VARCHAR(80),CHECK ({$wrongAudit})) ENGINE=InnoDB");
    $approvedAuditChecks=Contract::checks()[Contract::TABLES[4]];assertSameValue(false,in_array($checks($preflight,'wrong_grouping_probe')[0],$approvedAuditChecks,true),'Changed AND/OR grouping and precedence remains observable.');
    $preflight->query("CREATE TABLE `wrong_request_literal_probe` (status VARCHAR(20),CHECK (status IN ('accepted','replayed','rejected','wrong_conflict'))) ENGINE=InnoDB");
    assertSameValue(false,in_array($checks($preflight,'wrong_request_literal_probe')[0],Contract::checks()[Contract::TABLES[2]],true),'Changed request status literal remains observable.');
    $preflight->query("CREATE TABLE `wrong_maintenance_operand_probe` (scanned INT,deleted INT,retained INT,failed INT,CHECK (scanned=deleted+retained)) ENGINE=InnoDB");
    assertSameValue(false,in_array($checks($preflight,'wrong_maintenance_operand_probe')[0],Contract::checks()[Contract::TABLES[5]],true),'Changed maintenance accounting operand remains observable.');
    try{$normalizeCheck("status='accepted' XOR reason_code IS NULL");throw new TestFailure('Unsupported boolean grammar must fail closed.');}catch(TestFailure$error){assertSameValue('CHECK_NORMALIZATION_FAILURE: unsupported atom.',$error->getMessage(),'Unsupported boolean grammar has fixed fail-closed result.');}
    try{$normalizeCheck("status='accepted';status='rejected'");throw new TestFailure('Semicolon grammar must fail closed.');}catch(TestFailure$error){assertSameValue('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.',$error->getMessage(),'Semicolon is rejected by centralized lexical validation.');}
    foreach(["(status='accepted'"=> 'unmatched opening parenthesis',"status='accepted')"=>'unmatched closing parenthesis',"status='accepted"=>'unterminated quote']as$malformed=>$label){try{$normalizeCheck($malformed);throw new TestFailure("{$label} must fail closed.");}catch(TestFailure$error){assertSameValue('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.',$error->getMessage(),"{$label} has fixed lexical failure.");}}
    $danglingBackslash="reason_code='dangling"."\\";
    try{$normalizeCheck($danglingBackslash);throw new TestFailure('Dangling quoted backslash must fail closed.');}catch(TestFailure$error){assertSameValue('CHECK_NORMALIZATION_FAILURE: invalid lexical structure.',$error->getMessage(),'Quoted literal ending in one backslash reaches fixed lexical failure.');}
    assertSameValue("reason_code='can''t'",$normalizeCheck("reason_code = 'can''t'"),'Doubled SQL quote escape is lexically complete and byte-preserved.');
    assertSameValue("reason_code='can\\'t'",$normalizeCheck("reason_code = 'can\\'t'"),'Backslash SQL quote escape is lexically complete and byte-preserved.');
    assertSameValue("statusin('accepted','rejected')",$normalizeCheck("StAtUs IN ('accepted','rejected')"),'Mixed-case unquoted identifier and keyword fold to canonical ASCII lower-case.');
    assertSameValue(false,$normalizeCheck("status='accepted'")===$normalizeCheck("status='ACCEPTED'"),'Quoted status literal case remains byte-sensitive.');
    assertSameValue(false,$normalizeCheck("sha256 REGEXP '^[0-9a-f]{64}$'")===$normalizeCheck("sha256 REGEXP '^[0-9A-F]{64}$'"),'Quoted regex case remains byte-sensitive.');
    foreach(Contract::foreignKeys()as$table=>$foreignManifest)foreach($foreignManifest as[$localColumn]){
        $covered=false;foreach(Contract::keys()[$table]as[, $keyColumns])if(explode(',',$keyColumns)[0]===$localColumn){$covered=true;break;}
        assertSameValue(true,$covered,"Every approved {$table}.{$localColumn} FK has a leading support index in the seven-table manifest.");
    }
    $preflight->query("CREATE TABLE `fk_probe_parent` (id BIGINT UNSIGNED PRIMARY KEY) ENGINE=InnoDB");
    $preflight->query("CREATE TABLE `fk_probe_correct` (id BIGINT UNSIGNED PRIMARY KEY,parent_id BIGINT UNSIGNED NULL,FOREIGN KEY(parent_id) REFERENCES `fk_probe_parent`(id) ON UPDATE RESTRICT ON DELETE RESTRICT) ENGINE=InnoDB");
    $supportKeys=[['INDEX','parent_id'],['PRIMARY','id']];
    assertSameValue($supportKeys,$keys($preflight,'fk_probe_correct'),'MariaDB automatic exact-column FK support index matches the oracle.');
    $preflight->query("CREATE TABLE `fk_probe_missing` (id BIGINT UNSIGNED PRIMARY KEY,parent_id BIGINT UNSIGNED NULL) ENGINE=InnoDB");
    assertSameValue(false,$supportKeys===$keys($preflight,'fk_probe_missing'),'Missing FK support index remains observable.');
    $preflight->query("CREATE TABLE `fk_probe_extra` (id BIGINT UNSIGNED PRIMARY KEY,parent_id BIGINT UNSIGNED NULL,extra_id BIGINT UNSIGNED NULL,KEY(extra_id),FOREIGN KEY(parent_id) REFERENCES `fk_probe_parent`(id) ON UPDATE RESTRICT ON DELETE RESTRICT) ENGINE=InnoDB");
    assertSameValue(false,$supportKeys===$keys($preflight,'fk_probe_extra'),'Extra support index remains observable.');
    $preflight->query("CREATE TABLE `fk_probe_wrong` (id BIGINT UNSIGNED PRIMARY KEY,parent_id BIGINT UNSIGNED NULL,wrong_id BIGINT UNSIGNED NULL,KEY(wrong_id),FOREIGN KEY(parent_id) REFERENCES `fk_probe_parent`(id) ON UPDATE RESTRICT ON DELETE RESTRICT) ENGINE=InnoDB");
    assertSameValue(false,$supportKeys===$keys($preflight,'fk_probe_wrong'),'Wrong-column index cannot stand in for the automatic exact FK support index.');
    $preflight->close();

    foreach (Contract::PROJECTIONS as $name => [$expectedHash, $literal]) {
        assertSameValue($expectedHash, hash('sha256', $literal), "{$name} is independently derived from its literal projection.");
    }
    $approvedNotRegexp="root_original_idnotregexp'[[:cntrl:]/\\\\]'";
    assertSameValue($approvedNotRegexp,$normalizeCheck("!(`root_original_id` REGEXP '[[:cntrl:]/\\\\]')"),'MariaDB negated REGEXP normalizes to approved NOT REGEXP oracle.');
    assertSameValue(false,$approvedNotRegexp===$normalizeCheck("!(`root_original_id` REGEXP '[[:cntrl:]/]')"),'Normalizer preserves a materially wrong REGEXP pattern.');
    assertSameValue(true,function_exists('proc_open')&&function_exists('proc_terminate')&&function_exists('proc_close')&&function_exists('pcntl_signal')&&function_exists('pcntl_async_signals'),'Serializable contention verifier requires bounded process control.');
    $controlParent=$connect($databases[0]);
    $cleanupTrace=[];
    $runWorkerControl=static function(string$control)use($host,$port,$user,$password,$databases,$prefix,$controlParent,$admin,&$cleanupTrace):void{
        $process=null;$pipes=[];
        try{
            $environment=getenv();if(!is_array($environment))$environment=[];$environment=array_replace($environment,['AOOU_FIXTURE_WORKER_MODE'=>'seed','AOOU_FIXTURE_WORKER_CONTROL'=>$control,'AOOU_FIXTURE_WORKER_HOST'=>$host,'AOOU_FIXTURE_WORKER_PORT'=>(string)$port,'AOOU_FIXTURE_WORKER_USER'=>$user,'AOOU_FIXTURE_WORKER_PASSWORD'=>$password,'AOOU_FIXTURE_WORKER_DATABASE'=>$databases[0],'AOOU_FIXTURE_WORKER_PREFIX'=>$prefix]);
            $process=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/assignment_order_original_fixture_worker.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2),$environment);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: control worker process.');stream_set_timeout($pipes[1],5);$ready=fgets($pipes[1]);assertSameValue(1,preg_match('/^READY seed [1-9][0-9]*\n$/D',(string)$ready),"{$control} worker becomes ready.");fwrite($pipes[0],"ENTER seed\n");fflush($pipes[0]);assertSameValue("ENTERED seed\n",fgets($pipes[1]),"{$control} worker reaches pre-seam control.");
            if($control==='fail_before_seam'){fclose($pipes[0]);fclose($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);$pipes=[];$exit=proc_close($process);$process=null;assertSameValue(70,$exit,'Pre-seam failure exits with fixed code 70.');assertSameValue("FIXTURE_WORKER_FAILED\n",$stderr,'Pre-seam failure exposes only fixed stderr.');}
            else{$status=proc_get_status($process);throw new TestFailure('CONTROL_TRANSFER_LIVE_WORKER:'.(int)$status['pid']);}
            assertSameValue('1',(string)$controlParent->query('SELECT 1 alive')->fetch_assoc()['alive'],"Parent fixture connection survives {$control} cleanup.");assertSameValue('1',(string)$admin->query('SELECT 1 alive')->fetch_assoc()['alive'],"Parent admin connection survives {$control} cleanup.");
        }finally{foreach($pipes as$pipe)if(is_resource($pipe))fclose($pipe);if(is_resource($process)){$status=proc_get_status($process);$pid=(int)$status['pid'];$cleanupTrace[]="FINALLY:{$pid}";if($status['running']){$cleanupTrace[]="TERM:{$pid}";@proc_terminate($process,15);$deadline=hrtime(true)+300_000_000;do{usleep(10_000);$status=proc_get_status($process);}while($status['running']&&hrtime(true)<$deadline);$cleanupTrace[]=$status['running']?"TERM_LIVE:{$pid}":"TERM_STOPPED:{$pid}";if($status['running']){$cleanupTrace[]="KILL:{$pid}";@proc_terminate($process,9);$deadline=hrtime(true)+5_000_000_000;do{usleep(10_000);$status=proc_get_status($process);}while($status['running']&&hrtime(true)<$deadline);$cleanupTrace[]=$status['running']?"KILL_LIVE:{$pid}":"STOPPED:{$pid}";}}proc_close($process);$cleanupTrace[]="REAP:{$pid}";}}
    };
    $runWorkerControl('fail_before_seam');$cleanupTrace=[];$hangPid=null;try{$runWorkerControl('hang_before_seam');throw new TestFailure('Live hang control must transfer through finally.');}catch(TestFailure$error){assertSameValue(1,preg_match('/^CONTROL_TRANSFER_LIVE_WORKER:([1-9][0-9]*)$/D',$error->getMessage(),$hangMatch),'Hang control transfers a live exact PID into finally.');$hangPid=(int)$hangMatch[1];}
    assertSameValue(["FINALLY:{$hangPid}","TERM:{$hangPid}","TERM_LIVE:{$hangPid}","KILL:{$hangPid}","STOPPED:{$hangPid}","REAP:{$hangPid}"],$cleanupTrace,'Shared finally performs exact TERM-live-KILL-stopped-reap sequence.');assertSameValue('1',(string)$controlParent->query('SELECT 1 alive')->fetch_assoc()['alive'],'Parent fixture connection survives exceptional finally cleanup.');assertSameValue('1',(string)$admin->query('SELECT 1 alive')->fetch_assoc()['alive'],'Parent admin connection survives exceptional finally cleanup.');$controlParent->close();
    if (!class_exists(AssignmentOrderOriginalSchemaMigration::class)) {
        throw new TestFailure('INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.');
    }
    if (!class_exists(AssignmentOrderOriginalVerificationDatabaseFixture::class)) {
        throw new TestFailure('INTENDED_RED: approved AssignmentOrderOriginalVerificationDatabaseFixture production seam is absent.');
    }

    $clean = $connect($databases[0]);
    $result = AssignmentOrderOriginalSchemaMigration::apply($clean, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::APPLIED, $result->status(), 'Clean schema applies.');
    assertSameValue(Contract::VERSION, $result->schemaVersion(), 'Migration reports exact version 1.');
    assertSameValue(Contract::TABLES, $result->affectedTables(), 'Clean migration reports exact manifest order.');
    $expectedOwnedTables = array_map(static fn ($name) => $prefix . $name, Contract::TABLES);
    sort($expectedOwnedTables, SORT_STRING);
    assertSameValue($expectedOwnedTables, $tableNames($clean, $prefix . '%'), 'Only exact owned manifest exists.');
    foreach (Contract::columnManifest() as $table => $expected) {
        assertSameValue($expected, $columns($clean, $prefix . $table), "Exact ordered columns for {$table}.");
        assertSameValue(Contract::keys()[$table], $keys($clean, $prefix . $table), "Exact PK/unique/index column order for {$table}.");
        assertSameValue(Contract::foreignKeys()[$table], $foreignKeys($clean, $prefix . $table, $prefix), "Exact FK columns/actions for {$table}.");
        assertSameValue(Contract::checkCounts()[$table], $checkCount($clean, $prefix . $table), "Exact complete CHECK expression count for {$table}.");
        $expectedChecks=Contract::checks()[$table];sort($expectedChecks,SORT_STRING);
        assertSameValue($expectedChecks,$checks($clean,$prefix.$table),"Every normalized CHECK expression for {$table} is exact.");
        assertSameValue(['InnoDB','utf8mb4_unicode_ci'], $tableProperties($clean, $prefix . $table), "Exact engine and database-default collation for {$table}.");
    }
    $cleanBeforeRepeat = $snapshot($clean);
    $repeat = AssignmentOrderOriginalSchemaMigration::apply($clean, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::UNCHANGED, $repeat->status(), 'Exact repeat is unchanged.');
    assertSameValue([], $repeat->affectedTables(), 'Exact repeat has empty affected list.');
    assertSameValue($cleanBeforeRepeat, $snapshot($clean), 'Repeat preserves exact schema bytes.');
    $clean->close();

    $partial = $connect($databases[1]);
    $partial->query(Contract::rootsDdl($prefix));
    $partialBefore = $columns($partial, $prefix . Contract::TABLES[0]);
    $partialResult = AssignmentOrderOriginalSchemaMigration::apply($partial, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::APPLIED, $partialResult->status(), 'Leading compatible partial reconciles.');
    assertSameValue(array_slice(Contract::TABLES, 1), $partialResult->affectedTables(), 'Only missing trailing tables are affected.');
    assertSameValue($partialBefore, $columns($partial, $prefix . Contract::TABLES[0]), 'Existing leading table is preserved.');
    $partial->close();

    $populated = $connect($databases[2]);
    AssignmentOrderOriginalSchemaMigration::apply($populated, $prefix);
    $populated->query("INSERT INTO `{$prefix}fm2_assignment_order_original_roots` VALUES ('pop-root',77,88,'pop-revision','pop-composition','" . str_repeat('1',64) . "','2026-09-02 09:00:00.000000')");
    $populated->query("INSERT INTO `{$prefix}fm2_assignment_order_original_revisions` VALUES ('pop-revision','pop-root',1,NULL,'2026-09-01','2026-09-02 09:00:00.000000',18,'" . str_repeat('2',64) . "',327,'pop-content',NULL,'00000000-0000-4000-8000-000000000099','" . str_repeat('3',64) . "','assignment_order_original_accepted')");
    $rowsBefore = json_encode([$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_roots`")->fetch_all(MYSQLI_ASSOC),$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_revisions`")->fetch_all(MYSQLI_ASSOC)], JSON_THROW_ON_ERROR);
    $populatedResult = AssignmentOrderOriginalSchemaMigration::apply($populated, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::UNCHANGED, $populatedResult->status(), 'Populated exact schema is unchanged.');
    $rowsAfter = json_encode([$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_roots`")->fetch_all(MYSQLI_ASSOC),$populated->query("SELECT * FROM `{$prefix}fm2_assignment_order_original_revisions`")->fetch_all(MYSQLI_ASSOC)], JSON_THROW_ON_ERROR);
    assertSameValue($rowsBefore, $rowsAfter, 'Populated exact rows remain byte-identical.');
    $populated->close();

    $conflict = $connect($databases[3]);
    $conflict->query(Contract::rootsDdl($prefix));
    $conflict->query("ALTER TABLE `{$prefix}fm2_assignment_order_original_roots` ADD CONSTRAINT verifier_extra_semantic_check CHECK (installation_case_id > 0)");
    $conflict->query("CREATE TABLE `{$prefix}fm2_assignment_order_original_audits` (wrong INT NOT NULL) ENGINE=InnoDB");
    $conflictBefore = $snapshot($conflict);
    $conflictResult = AssignmentOrderOriginalSchemaMigration::apply($conflict, $prefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT, $conflictResult->status(), 'Near-equivalent and gross incompatible owned tables conflict together.');
    $expectedConflicts=[Contract::TABLES[4],Contract::TABLES[0]];sort($expectedConflicts,SORT_STRING);
    assertSameValue($expectedConflicts, $conflictResult->affectedTables(), 'Conflict returns every incompatible logical name in binary order.');
    assertSameValue($conflictBefore, $snapshot($conflict), 'Conflict beside five missing trailing tables performs zero DDL.');

    $nearPrefix='near_';AssignmentOrderOriginalSchemaMigration::apply($conflict,$nearPrefix);
    $conflict->query("ALTER TABLE `{$nearPrefix}fm2_assignment_order_original_roots` MODIFY current_revision_id VARCHAR(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wrong-default'");
    $nearBefore=$snapshot($conflict);$nearResult=AssignmentOrderOriginalSchemaMigration::apply($conflict,$nearPrefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT,$nearResult->status(),'Opaque-ID collation/default near mismatch conflicts.');
    assertSameValue([Contract::TABLES[0]],$nearResult->affectedTables(),'Collation/default mismatch identifies roots.');
    assertSameValue($nearBefore,$snapshot($conflict),'Collation/default conflict performs zero DDL.');

    $checkPrefix='check_';AssignmentOrderOriginalSchemaMigration::apply($conflict,$checkPrefix);
    $checkName=$conflict->query("SELECT CONSTRAINT_NAME FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{$checkPrefix}fm2_assignment_order_original_roots' AND CHECK_CLAUSE LIKE '%composition_sha256%' LIMIT 1")->fetch_assoc()['CONSTRAINT_NAME']??null;
    assertSameValue(true,is_string($checkName)&&preg_match('/^[A-Za-z0-9_$]{1,64}$/D',$checkName)===1,'Material CHECK sensitivity resolves one safe generated name.');
    $conflict->query("ALTER TABLE `{$checkPrefix}fm2_assignment_order_original_roots` DROP CONSTRAINT `{$checkName}`, ADD CONSTRAINT verifier_wrong_hash_check CHECK (composition_sha256 REGEXP '^[0-9A-F]{64}$')");
    $checkBefore=$snapshot($conflict);$checkResult=AssignmentOrderOriginalSchemaMigration::apply($conflict,$checkPrefix);
    assertSameValue(AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT,$checkResult->status(),'Wrong hash CHECK expression conflicts without count change.');
    assertSameValue([Contract::TABLES[0]],$checkResult->affectedTables(),'Wrong CHECK identifies roots.');
    assertSameValue($checkBefore,$snapshot($conflict),'Wrong CHECK conflict performs zero DDL.');
    $conflict->close();

    $fixture = $connect($databases[4]);
    FMonitor2\InstallationProcess\ProductionProcessSchemaMigration::apply($fixture, $prefix);
    FMonitor2\InstallationProcess\IdentityAccessSchemaMigration::apply($fixture, $prefix);
    FMonitor2\InstallationProcess\ProcessUserCapabilitiesSchemaMigration::apply($fixture, $prefix);
    FMonitor2\InstallationProcess\ProcessCommandCapabilitiesSchemaMigration::apply($fixture, $prefix);
    AssignmentOrderOriginalSchemaMigration::apply($fixture, $prefix);
    $fixtureBaseline = $stateSnapshot($fixture);
    $contendedFixtureCall=static function(string$mode,string$lockSql)use($fixture,$connect,$databases,$prefix,$host,$port,$user,$password,$admin):void{
        $process=null;$pipes=[];$transaction=false;$observer=null;
        try{
            $fixture->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');$fixture->begin_transaction();$transaction=true;$fixture->query($lockSql);$blockingConnectionId=(int)$fixture->thread_id;
            $environment=getenv();if(!is_array($environment))$environment=[];$environment=array_replace($environment,['AOOU_FIXTURE_WORKER_MODE'=>$mode,'AOOU_FIXTURE_WORKER_HOST'=>$host,'AOOU_FIXTURE_WORKER_PORT'=>(string)$port,'AOOU_FIXTURE_WORKER_USER'=>$user,'AOOU_FIXTURE_WORKER_PASSWORD'=>$password,'AOOU_FIXTURE_WORKER_DATABASE'=>$databases[4],'AOOU_FIXTURE_WORKER_PREFIX'=>$prefix]);
            $process=proc_open([PHP_BINARY,dirname(__DIR__).'/Support/assignment_order_original_fixture_worker.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2),$environment);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: contention worker process.');
            stream_set_timeout($pipes[1],5);$ready=fgets($pipes[1]);assertSameValue(false,stream_get_meta_data($pipes[1])['timed_out'],"{$mode} READY is bounded.");assertSameValue(1,preg_match('/^READY '.preg_quote($mode,'/').' ([1-9][0-9]*)\n$/D',(string)$ready,$readyMatch),"{$mode} worker reports exact MariaDB connection identity.");$workerConnectionId=(int)$readyMatch[1];fwrite($pipes[0],"ENTER {$mode}\n");fflush($pipes[0]);$entered=fgets($pipes[1]);assertSameValue(false,stream_get_meta_data($pipes[1])['timed_out'],"{$mode} ENTERED is bounded.");assertSameValue("ENTERED {$mode}\n",$entered,"{$mode} worker entered the controlled fixture attempt.");
            $observer=$connect($databases[4]);$deadline=hrtime(true)+5_000_000_000;$waitRow=null;do{$wait=$observer->prepare("SELECT requesting.trx_state,requesting.trx_isolation_level,requesting.trx_query FROM information_schema.INNODB_LOCK_WAITS waits JOIN information_schema.INNODB_TRX requesting ON requesting.trx_id=waits.requesting_trx_id JOIN information_schema.INNODB_TRX blocking ON blocking.trx_id=waits.blocking_trx_id WHERE requesting.trx_mysql_thread_id=? AND blocking.trx_mysql_thread_id=?");$wait->bind_param('ii',$workerConnectionId,$blockingConnectionId);$wait->execute();$waitRow=$wait->get_result()->fetch_assoc();$wait->close();if(is_array($waitRow))break;usleep(10_000);}while(hrtime(true)<$deadline);
            assertSameValue(true,is_array($waitRow),"{$mode} exact worker connection is independently observed in MariaDB lock wait.");assertSameValue('LOCK WAIT',$waitRow['trx_state'],"{$mode} transaction is actually waiting.");assertSameValue('SERIALIZABLE',$waitRow['trx_isolation_level'],"{$mode} public fixture transaction uses SERIALIZABLE.");$expectedLockedTable=$mode==='seed'?$prefix.'fm2_pilot_users':$prefix.'fm2_process_tasks';assertSameValue(true,str_contains((string)$waitRow['trx_query'],$expectedLockedTable),"{$mode} waits inside exact fixture identity operation.");stream_set_blocking($pipes[1],false);$read=[$pipes[1]];$write=$except=[];assertSameValue(0,stream_select($read,$write,$except,0,200000),"{$mode} worker has no terminal result while blocked.");
            $fixture->commit();$transaction=false;stream_set_blocking($pipes[1],true);stream_set_timeout($pipes[1],5);$line=fgets($pipes[1]);assertSameValue(false,stream_get_meta_data($pipes[1])['timed_out'],"{$mode} worker result is bounded.");assertSameValue("OK {$mode}\n",$line,"{$mode} succeeds after lock release.");fclose($pipes[0]);fclose($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);$pipes=[];$exit=proc_close($process);$process=null;assertSameValue(0,$exit,"{$mode} worker exits cleanly.");assertSameValue('',$stderr,"{$mode} worker has no stderr noise.");assertSameValue('1',(string)$fixture->query('SELECT 1 alive')->fetch_assoc()['alive'],"Parent fixture connection remains usable after {$mode} worker.");assertSameValue('1',(string)$admin->query('SELECT 1 alive')->fetch_assoc()['alive'],"Parent admin connection remains usable after {$mode} worker.");
        }finally{
            if($transaction){try{$fixture->rollback();}catch(Throwable){}}if($observer instanceof mysqli){try{$observer->close();}catch(Throwable){}}foreach($pipes as$pipe)if(is_resource($pipe))fclose($pipe);if(is_resource($process)){$status=proc_get_status($process);if($status['running']){@proc_terminate($process,15);$deadline=hrtime(true)+500_000_000;do{usleep(10_000);$status=proc_get_status($process);}while($status['running']&&hrtime(true)<$deadline);if($status['running'])@proc_terminate($process,9);}proc_close($process);}
        }
    };
    $contendedFixtureCall('seed',"SELECT user_id FROM `{$prefix}fm2_pilot_users` WHERE user_id=18 FOR UPDATE");
    assertSameValue(
        [
            ['user_id'=>'18','full_name'=>'Тестовый Оператор ФКР','email'=>'test-fkr@example.invalid','status'=>'1','activation_state'=>'active','session_version'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
            ['user_id'=>'31','full_name'=>'Тестовый Инженер','email'=>'test-engineer@example.invalid','status'=>'1','activation_state'=>'active','session_version'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
        ],
        $fixture->query("SELECT user_id,full_name,email,status,activation_state,session_version,source_updated_at FROM `{$prefix}fm2_pilot_users` WHERE user_id IN (18,31) ORDER BY user_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact fictional users without credentials.',
    );
    assertSameValue(
        [
            ['role_id'=>'5301','code'=>'fkr_operator','name'=>'Сотрудник ФКР','status'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
            ['role_id'=>'5302','code'=>'control_engineer','name'=>'Инженер строительного контроля','status'=>'1','source_updated_at'=>'2026-09-02T09:00:00Z'],
        ],
        $fixture->query("SELECT role_id,code,name,status,source_updated_at FROM `{$prefix}fm2_pilot_roles` WHERE role_id IN (5301,5302) ORDER BY role_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact active role identities.',
    );
    assertSameValue(
        [['user_id'=>'18','role_id'=>'5301','assigned_at'=>'2026-09-02T09:00:00Z','assigned_by_user_id'=>null],['user_id'=>'31','role_id'=>'5302','assigned_at'=>'2026-09-02T09:00:00Z','assigned_by_user_id'=>null]],
        $fixture->query("SELECT user_id,role_id,assigned_at,assigned_by_user_id FROM `{$prefix}fm2_pilot_user_roles` WHERE user_id IN (18,31) ORDER BY user_id,role_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact actor and engineer role assignments.',
    );
    assertSameValue(0,(int)$fixture->query("SELECT COUNT(*) n FROM `{$prefix}fm2_pilot_auth_credentials` WHERE user_id IN (18,31)")->fetch_assoc()['n'],'Fictional fixture users have no credential.');
    assertSameValue(
        [
            ['user_id'=>'18','capability'=>'assignment_order.original.correct','position_snapshot'=>null],
            ['user_id'=>'18','capability'=>'assignment_order.original.upload','position_snapshot'=>null],
        ],
        $fixture->query("SELECT user_id,capability,position_snapshot FROM `{$prefix}fm2_process_user_capabilities` WHERE user_id=18 ORDER BY BINARY capability")->fetch_all(MYSQLI_ASSOC),
        'Actor has exactly upload and correct grants.',
    );
    assertSameValue(
        [['user_id'=>'31','capability'=>'construction_control_engineer','position_snapshot'=>'Инженер строительного контроля']],
        $fixture->query("SELECT user_id,capability,position_snapshot FROM `{$prefix}fm2_process_user_capabilities` WHERE user_id=31 ORDER BY BINARY capability")->fetch_all(MYSQLI_ASSOC),
        'Engineer has exact configured process capability and position.',
    );
    assertSameValue(
        [
            ['id'=>'4512','legacy_installation_object_id'=>'94512','process_state'=>'prepared','actual_start_date'=>null,'opened_at'=>null,'opened_by_user_id'=>null,'created_at'=>'2026-09-02T09:00:00Z','updated_at'=>'2026-09-02T09:00:00Z','lock_version'=>'1'],
            ['id'=>'9999','legacy_installation_object_id'=>'99999','process_state'=>'fixture-decoy-v1','actual_start_date'=>null,'opened_at'=>null,'opened_by_user_id'=>null,'created_at'=>'2026-09-02T09:00:00Z','updated_at'=>'2026-09-02T09:00:00Z','lock_version'=>'1'],
        ],
        $fixture->query("SELECT id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id,created_at,updated_at,lock_version FROM `{$prefix}fm2_installation_cases` WHERE id IN (4512,9999) ORDER BY id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact target and unrelated decoy cases.',
    );
    assertSameValue(
        [['id'=>'81','installation_case_id'=>'4512','version_no'=>'1','kind'=>'initial','status'=>'prepared','order_date'=>'2026-09-01','registration_number'=>null,'registered_at'=>null,'registration_actor_type'=>null,'registration_actor_id'=>null,'registration_source'=>null,'external_registration_id'=>null,'control_engineer_user_id'=>'31','control_engineer_fio_snapshot'=>'Тестовый Инженер','control_engineer_position_snapshot'=>'Инженер строительного контроля','organization_form'=>'brigade','previous_assignment_order_id'=>null,'object_address_snapshot'=>'Тестовая улица, 1','entrance_snapshot'=>'1','object_registration_number_snapshot'=>'TEST-4512','planned_start_date_snapshot'=>'2026-10-01','planned_finish_date_snapshot'=>'2026-10-31','pto_act_date_snapshot'=>null,'prepared_at'=>'2026-09-01T09:00:00Z','prepared_by_user_id'=>'18']],
        $fixture->query("SELECT * FROM `{$prefix}fm2_assignment_orders` WHERE id=81")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact Example-A order.',
    );
    assertSameValue(
        [
            ['installer_tab_id'=>'7001','fio_snapshot'=>'Тестовый Монтажник 7001','position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2026-01-01','employed_to_snapshot'=>null,'workforce_source_snapshot'=>'TEST-USER','workforce_source_updated_at_snapshot'=>'2026-09-01T00:00:00Z','valid_from'=>'2026-09-01','valid_to'=>null,'change_action'=>'assign'],
            ['installer_tab_id'=>'7002','fio_snapshot'=>'Тестовый Монтажник 7002','position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2026-01-01','employed_to_snapshot'=>null,'workforce_source_snapshot'=>'TEST-USER','workforce_source_updated_at_snapshot'=>'2026-09-01T00:00:00Z','valid_from'=>'2026-09-01','valid_to'=>null,'change_action'=>'assign'],
        ],
        $fixture->query("SELECT installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot,employed_from_snapshot,employed_to_snapshot,workforce_source_snapshot,workforce_source_updated_at_snapshot,valid_from,valid_to,change_action FROM `{$prefix}fm2_order_installers` WHERE assignment_order_id=81 ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact installer snapshots in binary identity order.',
    );
    assertSameValue(
        [['id'=>'9001','installation_case_id'=>'4512','task_type'=>'assignment_order_original_upload','assignee_user_id'=>null,'assignee_role'=>'fkr_operator','due_date'=>null,'status'=>'open','completed_at'=>null,'completed_by_user_id'=>null,'created_at'=>'2026-09-02T09:00:00Z']],
        $fixture->query("SELECT * FROM `{$prefix}fm2_process_tasks` WHERE id=9001")->fetch_all(MYSQLI_ASSOC),
        'Fixture seeds exact open upload task.',
    );
    $projectionRows=[];
    $caseRow=$fixture->query("SELECT id,process_state,actual_start_date,opened_at,opened_by_user_id FROM `{$prefix}fm2_installation_cases` WHERE id=4512")->fetch_assoc();
    $orderRow=$fixture->query("SELECT id,installation_case_id,control_engineer_user_id FROM `{$prefix}fm2_assignment_orders` WHERE id=81")->fetch_assoc();
    $installerIds=array_map('intval',array_column($fixture->query("SELECT installer_tab_id FROM `{$prefix}fm2_order_installers` WHERE assignment_order_id=81 ORDER BY installer_tab_id")->fetch_all(MYSQLI_ASSOC),'installer_tab_id'));
    $projectionRows['orderCompositionSha256']=['caseId'=>(int)$orderRow['installation_case_id'],'compositionIdentity'=>'composition-81-v1','engineerUserId'=>(int)$orderRow['control_engineer_user_id'],'installers'=>$installerIds,'orderId'=>(int)$orderRow['id']];
    $projectionRows['caseSha256']=['actualStartDate'=>$caseRow['actual_start_date'],'caseId'=>(int)$caseRow['id'],'processState'=>$caseRow['process_state']];
    $projectionRows['openingSha256']=['actualStartDate'=>$caseRow['actual_start_date'],'openedAt'=>$caseRow['opened_at'],'openedByUserId'=>$caseRow['opened_by_user_id']===null?null:(int)$caseRow['opened_by_user_id']];
    $tasks=array_map(static fn(array$row):array=>['assigneeRole'=>$row['assignee_role'],'status'=>$row['status'],'taskId'=>(int)$row['id'],'taskType'=>$row['task_type']],$fixture->query("SELECT id,task_type,assignee_role,status FROM `{$prefix}fm2_process_tasks` WHERE installation_case_id=4512 ORDER BY id")->fetch_all(MYSQLI_ASSOC));
    $projectionRows['tasksSha256']=['items'=>$tasks];
    $available=$caseRow['process_state']==='working'&&$caseRow['actual_start_date']!==null&&$caseRow['opened_at']!==null&&$caseRow['opened_by_user_id']!==null;
    $projectionRows['checklistSha256']=['items'=>[['availability'=>$available?'available':'blocked_until_opening','checklistIdentity'=>'installation-case-'.(int)$caseRow['id']]]];
    $decoys=array_map(static fn(array$row):array=>['caseId'=>(int)$row['id'],'marker'=>$row['process_state']],$fixture->query("SELECT id,process_state FROM `{$prefix}fm2_installation_cases` WHERE id<>4512 ORDER BY id")->fetch_all(MYSQLI_ASSOC));
    $projectionRows['decoySha256']=['items'=>$decoys];
    foreach(Contract::PROJECTIONS as$name=>[$expectedDigest,$expectedJson]){
        $actualJson=json_encode($projectionRows[$name],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        assertSameValue($expectedJson,$actualJson,"{$name} canonical JSON is derived from seeded rows.");
        assertSameValue($expectedDigest,hash('sha256',$actualJson),"{$name} digest is derived from seeded rows.");
    }
    $seeded = $stateSnapshot($fixture);
    AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture, $prefix);
    assertSameValue($seeded, $stateSnapshot($fixture), 'Exact fixture repeat is a no-op.');
    foreach (Contract::TABLES as $originalTable) {
        $count = (int) $fixture->query('SELECT COUNT(*) n FROM ' . $quote($prefix . $originalTable))->fetch_assoc()['n'];
        assertSameValue(0, $count, "Fixture creates no original fact in {$originalTable}.");
    }

    $fixtureDrifts=[
        ['actor user','fm2_pilot_users','user_id=18','full_name','Тестовый Оператор ФКР','Drift Actor'],
        ['engineer user','fm2_pilot_users','user_id=31','email','test-engineer@example.invalid','drift-engineer@example.invalid'],
        ['operator role','fm2_pilot_roles','role_id=5301','name','Сотрудник ФКР','Drift Role'],
        ['engineer role','fm2_pilot_roles','role_id=5302','status','1','0'],
        ['role assignment','fm2_pilot_user_roles','user_id=18 AND role_id=5301','assigned_at','2026-09-02T09:00:00Z','2026-09-02T09:00:01Z'],
        ['actor capability','fm2_process_user_capabilities',"user_id=18 AND capability='assignment_order.original.upload'",'position_snapshot',null,'Drift'],
        ['engineer capability','fm2_process_user_capabilities',"user_id=31 AND capability='construction_control_engineer'",'position_snapshot','Инженер строительного контроля','Drift Engineer'],
        ['target case','fm2_installation_cases','id=4512','updated_at','2026-09-02T09:00:00Z','2026-09-02T09:00:01Z'],
        ['decoy case','fm2_installation_cases','id=9999','legacy_installation_object_id','99999','99998'],
        ['order snapshots','fm2_assignment_orders','id=81','object_address_snapshot','Тестовая улица, 1','Drift Address'],
        ['installer 7001','fm2_order_installers','assignment_order_id=81 AND installer_tab_id=7001','fio_snapshot','Тестовый Монтажник 7001','Drift Installer'],
        ['installer 7002','fm2_order_installers','assignment_order_id=81 AND installer_tab_id=7002','valid_from','2026-09-01','2026-09-02'],
        ['task','fm2_process_tasks','id=9001','created_at','2026-09-02T09:00:00Z','2026-09-02T09:00:01Z'],
    ];
    $setFixtureValue=static function(mysqli$db,string$table,string$where,string$column,mixed$value)use($prefix):void{$sql="UPDATE `{$prefix}{$table}` SET `{$column}`=? WHERE {$where}";$statement=$db->prepare($sql);$statement->bind_param('s',$value);$statement->execute();assertSameValue(1,$statement->affected_rows,"Exactly one {$table}.{$column} fixture row is changed.");};
    foreach($fixtureDrifts as[$label,$table,$where,$column,$exact,$drift]){
        $setFixtureValue($fixture,$table,$where,$column,$drift);$beforeConflict=$stateSnapshot($fixture);
        try{AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture,$prefix);throw new TestFailure("INTENDED_RED: {$label} drift was accepted by seedExampleA.");}catch(AssignmentOrderOriginalVerificationFixtureConflict$error){assertSameValue('AssignmentOrderOriginalVerificationFixtureConflict',$error->getMessage(),"{$label} seed conflict has fixed message.");}
        assertSameValue($beforeConflict,$stateSnapshot($fixture),"{$label} seed conflict performs zero DML.");$setFixtureValue($fixture,$table,$where,$column,$exact);
    }
    foreach($fixtureDrifts as[$label,$table,$where,$column,$exact,$drift]){
        $setFixtureValue($fixture,$table,$where,$column,$drift);$beforeConflict=$stateSnapshot($fixture);
        try{AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture,$prefix);throw new TestFailure("INTENDED_RED: {$label} drift was deleted by cleanupExampleA.");}catch(AssignmentOrderOriginalVerificationFixtureConflict$error){assertSameValue('AssignmentOrderOriginalVerificationFixtureConflict',$error->getMessage(),"{$label} cleanup conflict has fixed message.");}
        assertSameValue($beforeConflict,$stateSnapshot($fixture),"{$label} cleanup conflict performs zero DML.");$setFixtureValue($fixture,$table,$where,$column,$exact);
    }
    $ownedRows=[
        ['fm2_pilot_users','user_id=18',['user_id']],['fm2_pilot_users','user_id=31',['user_id']],
        ['fm2_pilot_roles','role_id=5301',['role_id']],['fm2_pilot_roles','role_id=5302',['role_id']],
        ['fm2_pilot_user_roles','user_id=18 AND role_id=5301',['user_id','role_id']],['fm2_pilot_user_roles','user_id=31 AND role_id=5302',['user_id','role_id']],
        ['fm2_process_user_capabilities',"user_id=18 AND capability='assignment_order.original.upload'",['user_id','capability']],['fm2_process_user_capabilities',"user_id=18 AND capability='assignment_order.original.correct'",['user_id','capability']],['fm2_process_user_capabilities',"user_id=31 AND capability='construction_control_engineer'",['user_id','capability']],
        ['fm2_installation_cases','id=4512',['id']],['fm2_installation_cases','id=9999',['id']],['fm2_assignment_orders','id=81',['id','installation_case_id','previous_assignment_order_id']],
        ['fm2_order_installers','assignment_order_id=81 AND installer_tab_id=7001',['assignment_order_id','installer_tab_id']],['fm2_order_installers','assignment_order_id=81 AND installer_tab_id=7002',['assignment_order_id','installer_tab_id']],['fm2_process_tasks','id=9001',['id','installation_case_id']],
    ];
    $fieldMutation=static function(mysqli$db,string$table,string$column,mixed$value)use($prefix):mixed{$statement=$db->prepare('SELECT LOWER(COLUMN_TYPE) t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');$name=$prefix.$table;$statement->bind_param('ss',$name,$column);$statement->execute();$type=(string)$statement->get_result()->fetch_assoc()['t'];if($value===null){if(str_contains($type,'date'))return'2026-09-03';if(str_contains($type,'int'))return'1';return'Drift-'.$column;}if(str_starts_with($type,'enum'))return$value==='active'?'blocked':'active';if(str_contains($type,'int'))return(string)(((int)$value)+1);if(str_contains($type,'date'))return str_starts_with((string)$value,'2026-09-03')?'2026-09-04':'2026-09-03';return(string)$value.'-drift';};
    foreach($ownedRows as[$table,$where,$identityColumns]){$row=$fixture->query("SELECT * FROM `{$prefix}{$table}` WHERE {$where}")->fetch_assoc();assertSameValue(true,is_array($row),"Full field matrix finds {$table} {$where}.");foreach($row as$column=>$exact){if(in_array($column,$identityColumns,true))continue;$drift=$fieldMutation($fixture,$table,$column,$exact);$setFixtureValue($fixture,$table,$where,$column,$drift);$before=$stateSnapshot($fixture);try{AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture,$prefix);throw new TestFailure("INTENDED_RED: {$table}.{$column} drift was accepted by seed.");}catch(AssignmentOrderOriginalVerificationFixtureConflict){}assertSameValue($before,$stateSnapshot($fixture),"{$table}.{$column} seed conflict is zero DML.");$setFixtureValue($fixture,$table,$where,$column,$exact);$setFixtureValue($fixture,$table,$where,$column,$drift);$before=$stateSnapshot($fixture);try{AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture,$prefix);throw new TestFailure("INTENDED_RED: {$table}.{$column} drift was deleted by cleanup.");}catch(AssignmentOrderOriginalVerificationFixtureConflict){}assertSameValue($before,$stateSnapshot($fixture),"{$table}.{$column} cleanup conflict is zero DML.");$setFixtureValue($fixture,$table,$where,$column,$exact);}}
    AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture,$prefix);
    $fixture->query("INSERT INTO `{$prefix}fm2_pilot_users`(user_id,full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES(18,'Foreign Occupant','foreign@example.invalid','',1,'active',1,'2026-09-02T09:00:00Z')");
    $partialBefore=$stateSnapshot($fixture);
    try{AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture,$prefix);throw new TestFailure('INTENDED_RED: partially occupied users family was silently filled.');}catch(AssignmentOrderOriginalVerificationFixtureConflict$error){assertSameValue('AssignmentOrderOriginalVerificationFixtureConflict',$error->getMessage(),'Partial users family has fixed conflict.');}
    assertSameValue($partialBefore,$stateSnapshot($fixture),'Partial multi-row family conflict performs zero DML.');$fixture->query("DELETE FROM `{$prefix}fm2_pilot_users` WHERE user_id=18");AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture,$prefix);
    $fixture->query("DELETE FROM `{$prefix}fm2_process_tasks` WHERE id=9001");$fixture->query("INSERT INTO `{$prefix}fm2_installation_cases` VALUES(7777,97777,'foreign-preserved',NULL,NULL,NULL,'2026-09-02T09:00:00Z','2026-09-02T09:00:00Z',1)");
    AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture,$prefix);
    foreach([['fm2_pilot_users','user_id IN (18,31)'],['fm2_pilot_roles','role_id IN (5301,5302)'],['fm2_process_user_capabilities','user_id IN (18,31)'],['fm2_installation_cases','id IN (4512,9999)'],['fm2_assignment_orders','id=81'],['fm2_order_installers','assignment_order_id=81']]as[$table,$where])assertSameValue(0,(int)$fixture->query("SELECT COUNT(*) n FROM `{$prefix}{$table}` WHERE {$where}")->fetch_assoc()['n'],"Partially absent cleanup removes remaining exact {$table} rows.");
    assertSameValue(1,(int)$fixture->query("SELECT COUNT(*) n FROM `{$prefix}fm2_installation_cases` WHERE id=7777 AND process_state='foreign-preserved'")->fetch_assoc()['n'],'Partially absent cleanup preserves foreign case.');$partialAbsentClean=$stateSnapshot($fixture);AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture,$prefix);assertSameValue($partialAbsentClean,$stateSnapshot($fixture),'Partially absent cleanup repeat is no-op.');$fixture->query("DELETE FROM `{$prefix}fm2_installation_cases` WHERE id=7777");AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture,$prefix);

    $fixture->query("UPDATE `{$prefix}fm2_installation_cases` SET process_state='foreign-drift' WHERE id=4512");
    $drift = $stateSnapshot($fixture);
    try {
        AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($fixture, $prefix);
        throw new TestFailure('Different occupied fixture identity must conflict.');
    } catch (AssignmentOrderOriginalVerificationFixtureConflict $error) {
        assertSameValue('AssignmentOrderOriginalVerificationFixtureConflict', $error->getMessage(), 'Conflict exception has fixed message.');
        assertSameValue(0, $error->getCode(), 'Conflict exception has fixed code.');
        assertSameValue(null, $error->getPrevious(), 'Conflict exception has no previous diagnostic.');
    }
    assertSameValue($drift, $stateSnapshot($fixture), 'Fixture conflict performs zero DML.');
    try {
        AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture,$prefix);
        throw new TestFailure('Cleanup must reject a drifted owned fixture row.');
    } catch (AssignmentOrderOriginalVerificationFixtureConflict $error) {
        assertSameValue('AssignmentOrderOriginalVerificationFixtureConflict',$error->getMessage(),'Cleanup conflict exception has fixed message.');
        assertSameValue(0,$error->getCode(),'Cleanup conflict exception has fixed code.');
        assertSameValue(null,$error->getPrevious(),'Cleanup conflict has no previous diagnostic.');
    }
    assertSameValue($drift,$stateSnapshot($fixture),'Cleanup drift conflict rolls back before deleting any owned row.');
    $fixture->query("UPDATE `{$prefix}fm2_installation_cases` SET process_state='prepared' WHERE id=4512");
    $contendedFixtureCall('cleanup',"SELECT id FROM `{$prefix}fm2_process_tasks` WHERE id=9001 FOR UPDATE");
    AssignmentOrderOriginalVerificationDatabaseFixture::cleanupExampleA($fixture, $prefix);
    assertSameValue($fixtureBaseline, $stateSnapshot($fixture), 'Cleanup and exact repeat restore the full prerequisite database byte-for-byte.');
    foreach ([['fm2_pilot_users','user_id IN (18,31)'],['fm2_pilot_roles','role_id IN (5301,5302)'],['fm2_process_user_capabilities','user_id IN (18,31)'],['fm2_installation_cases','id IN (4512,9999)'],['fm2_assignment_orders','id=81'],['fm2_order_installers','assignment_order_id=81'],['fm2_process_tasks','id=9001']] as [$logicalTable,$where]) {
        $remaining = (int) $fixture->query("SELECT COUNT(*) n FROM `{$prefix}{$logicalTable}` WHERE {$where}")->fetch_assoc()['n'];
        assertSameValue(0, $remaining, "Bounded cleanup removes exact {$logicalTable} fixture identities.");
    }
    $fixture->close();

    fwrite(STDOUT, "ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK\n");
} finally {
    $failures = [];
    foreach (array_reverse($created) as $database) {
        try { $admin->query('DROP DATABASE ' . $quote($database)); } catch (Throwable $error) { $failures[] = $database; }
    }
    $admin->close();
    if ($failures !== []) throw new TestFailure('SETUP_CLEANUP_FAILURE: owned databases remain.');
}
