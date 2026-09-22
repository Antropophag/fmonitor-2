<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime;
use FMonitor2\InstallationProcess\MariaDbFeedback;
use yii\base\Component;
use yii\db\Connection;

final class FeedbackApplication extends Component
{
    public Connection|string $db = "db";
    public string $tablePrefix = "";
    public string $buildIdentityFile = "";
    public ?object $buildIdentityFilesystem = null;
    public string $appVersion = "unknown";
    private MariaDbFeedback $store;
    public function init(): void
    {
        parent::init();
        if (is_string($this->db)) {
            $this->db = \Yii::$app->get($this->db);
        }
        if (!($this->db instanceof Connection)) {
            throw new \RuntimeException("Invalid feedback configuration.");
        }
        $this->appVersion = $this->readBuildIdentity($this->buildIdentityFile);
        $this->store = new MariaDbFeedback($this->db, $this->tablePrefix);
    }
    public function submit(int $actorId, string $requestId, string $description, string $pagePath): array
    {
        if (!$this->store->active($actorId)) {
            return ["status" => "access_denied"];
        }
        $description = trim($description);
        if (!$this->valid($requestId, $description, 4000)) {
            return ["status" => "invalid"];
        }
        [$path, $object] = $this->context($pagePath);
        $fingerprint = hash("sha256", json_encode([$description, $path, $object], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $this->store->append("root", $actorId, $requestId, $fingerprint, [
            "description" => $description,
            "page_path" => $path,
            "object_id" => $object,
            "app_version" => $this->appVersion,
        ]);
    }
    public function listing(int $actorId, ?int $beforeId = null): array
    {
        if (!$this->store->admin($actorId)) {
            throw new \DomainException("ACCESS_DENIED");
        }
        return $this->store->listing($beforeId !== null && $beforeId > 0 ? $beforeId : null);
    }
    public function recordResult(int $actorId, int $feedbackId, string $requestId, string $result): array
    {
        if (!$this->store->admin($actorId)) {
            return ["status" => "access_denied"];
        }
        $result = trim($result);
        if (!$this->valid($requestId, $result, 2000)) {
            return ["status" => "invalid"];
        }
        if (!$this->store->exists($feedbackId)) {
            return ["status" => "not_found"];
        }
        $fingerprint = hash("sha256", json_encode([$feedbackId, $result], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $this->store->append("result", $actorId, $requestId, $fingerprint, ["feedback_id" => $feedbackId, "result" => $result]);
    }
    public function normalizePath(string $raw): string
    {
        return $this->context($raw)[0];
    }
    private function valid(string $id, string $text, int $max): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/Di', $id) === 1 &&
            mb_check_encoding($text, "UTF-8") &&
            mb_strlen($text) <= $max &&
            $text !== "" &&
            preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $text) !== 1;
    }
    private function context(string $raw): array
    {
        $fallback = ["/pilot/objects", null];
        if (str_contains($raw, "\\") || preg_match('/[\x00-\x1F\x7F]/', $raw) || preg_match("#^(?:[a-z][a-z0-9+.-]*:)?//#i", $raw)) {
            return $fallback;
        }
        $path = parse_url($raw, PHP_URL_PATH);
        if (!is_string($path) || strlen($path) > 255 || str_contains($path, "%") || !str_starts_with($path, "/")) {
            return $fallback;
        }
        $simple = [
            "/pilot/objects",
            "/pilot/installers",
            "/pilot/construction-control",
            "/pilot/calendar",
            "/pilot/dashboard",
            "/pilot/admin/users",
            "/pilot/users",
            "/pilot/admin/roles",
            "/pilot/feedback",
            "/pilot/admin/feedback",
            "/pilot/otiz",
            "/pilot/otiz/objects",
            "/pilot/otiz/payments",
            "/pilot/otiz/history",
        ];
        if (in_array($path, $simple, true)) {
            return [$path, null];
        }
        $patterns = [
            '#^/pilot/objects/([1-9]\d{0,18})(?:/(?:checklist|assignment-order/(?:prepare|selection)|execution|deadline-certificates))?$#',
            '#^/pilot/construction-control/objects/([1-9]\d{0,18})/checklist$#',
            '#^/pilot/objects/([1-9]\d{0,18})/assignment-orders/([1-9]\d{0,18})/originals/(?:submit|history)$#',
            '#^/pilot/otiz/snapshots/([1-9]\d{0,18})$#',
        ];
        foreach ($patterns as $p) {
            if (
                preg_match($p, $path, $m) === 1 &&
                filter_var($m[1], FILTER_VALIDATE_INT) !== false &&
                (!isset($m[2]) || filter_var($m[2], FILTER_VALIDATE_INT) !== false)
            ) {
                return [$path, str_starts_with($path, '/pilot/objects/') || str_starts_with($path, '/pilot/construction-control/objects/') ? (int) $m[1] : null];
            }
        }
        return $fallback;
    }
    private function readBuildIdentity(string $path): string
    {
        if ($path === '' || !str_starts_with($path, '/') || str_contains($path, "\0")) {
            return 'unknown';
        }
        $filesystem = $this->buildIdentityFilesystem ?? new class {
            public function open(string $path): mixed { return @fopen($path, 'rb'); }
            public function stat(mixed $handle): array|false { return @fstat($handle); }
            public function read(mixed $handle, int $length): string|false { return @fread($handle, $length); }
            public function pathStat(string $path): array|false { return @lstat($path); }
            public function close(mixed $handle): void { @fclose($handle); }
        };
        $handle = null;
        try {
            $handle = $filesystem->open($path);
            if (!is_resource($handle)) {
                return 'unknown';
            }
            $before = $filesystem->stat($handle);
            $value = $filesystem->read($handle, 66);
            $after = $filesystem->stat($handle);
            $final = $filesystem->pathStat($path);
            if (!is_array($before) || !is_array($after) || !is_array($final) || !is_string($value)) {
                return 'unknown';
            }
            $keys = ['mode', 'nlink', 'size', 'dev', 'ino', 'mtime', 'ctime'];
            foreach ($keys as $key) {
                if (!array_key_exists($key, $before) || !array_key_exists($key, $after) || !array_key_exists($key, $final)
                    || $before[$key] !== $after[$key] || $before[$key] !== $final[$key]) {
                    return 'unknown';
                }
            }
            $permissions = $before['mode'] & 0777;
            if (($before['mode'] & 0170000) !== 0100000 || $before['nlink'] !== 1 || $before['size'] !== 65
                || ($permissions & 0222) !== 0 || ($permissions & 0444) === 0
                || preg_match('/^[0-9a-f]{64}\n$/D', $value) !== 1) {
                return 'unknown';
            }
            return substr($value, 0, 64);
        } catch (\Throwable) {
            return 'unknown';
        } finally {
            if (is_resource($handle)) {
                try {
                    $filesystem->close($handle);
                } catch (\Throwable) {
                }
            }
        }
    }
}
