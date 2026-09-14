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
    public string $appVersion = "";
    private MariaDbFeedback $store;
    public function init(): void
    {
        parent::init();
        if (is_string($this->db)) {
            $this->db = \Yii::$app->get($this->db);
        }
        if (!($this->db instanceof Connection) || preg_match('/^[A-Za-z0-9._-]{1,80}$/D', $this->appVersion) !== 1) {
            throw new \RuntimeException("Invalid feedback configuration.");
        }
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
            "/pilot/admin/users",
            "/pilot/admin/roles",
            "/pilot/feedback",
            "/pilot/admin/feedback",
        ];
        if (in_array($path, $simple, true)) {
            return [$path, null];
        }
        $patterns = [
            '#^/pilot/objects/([1-9]\d{0,18})(?:/(?:checklist|assignment-order/selection|execution))?$#',
            '#^/pilot/construction-control/objects/([1-9]\d{0,18})/checklist$#',
            '#^/pilot/objects/([1-9]\d{0,18})/assignment-orders/([1-9]\d{0,18})/originals/(?:submit|history)$#',
        ];
        foreach ($patterns as $p) {
            if (
                preg_match($p, $path, $m) === 1 &&
                filter_var($m[1], FILTER_VALIDATE_INT) !== false &&
                (!isset($m[2]) || filter_var($m[2], FILTER_VALIDATE_INT) !== false)
            ) {
                return [$path, (int) $m[1]];
            }
        }
        return $fallback;
    }
}
