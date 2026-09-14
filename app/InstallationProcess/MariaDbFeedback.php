<?php
declare(strict_types=1);
namespace FMonitor2\InstallationProcess;

use FMonitor2\IdentityAccess\MariaDbPilotAccessPolicy;
use yii\db\Connection;

final class MariaDbFeedback
{
    public function __construct(private Connection $db, private string $prefix)
    {
        if (preg_match('/^[A-Za-z0-9_]{0,25}$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException();
        }
    }
    public function active(int $id): bool
    {
        return $id > 0 &&
            (bool) $this->db
                ->createCommand(
                    "SELECT 1 FROM `{$this->prefix}fm2_pilot_users` WHERE user_id=:id AND status=1 AND activation_state='active'",
                    [":id" => $id],
                )
                ->queryScalar();
    }
    public function admin(int $id): bool
    {
        if (!$this->active($id)) {
            return false;
        }
        $sql = "SELECT 1 FROM `{$this->prefix}fm2_pilot_user_roles` ur JOIN `{$this->prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$this->prefix}fm2_pilot_role_permissions` p ON p.role_id=r.role_id WHERE ur.user_id=:id AND r.status=1 AND p.permission=:permission LIMIT 1";
        return (bool) $this->db
            ->createCommand($sql, [
                ":id" => $id,
                ":permission" => MariaDbPilotAccessPolicy::ADMINISTER_ACCESS,
            ])
            ->queryScalar();
    }
    public function append(string $kind, int $actor, string $request, string $fingerprint, array $values): array
    {
        $table = $this->prefix . ($kind === "root" ? "fm2_feedback" : "fm2_feedback_results");
        $existing = $this->receipt($table, $actor, $request);
        if ($existing !== null) {
            return hash_equals($existing["request_fingerprint"], $fingerprint)
                ? ["status" => "saved", "id" => (int) $existing["id"]]
                : ["status" => "conflict"];
        }
        $tx = $this->db->beginTransaction();
        try {
            $this->db
                ->createCommand()
                ->insert(
                    $table,
                    $values + [
                        "request_id" => $request,
                        "request_fingerprint" => $fingerprint,
                        "actor_user_id" => $actor,
                        "created_at" => gmdate("Y-m-d H:i:s.u"),
                    ],
                )
                ->execute();
            $id = (int) $this->db->getLastInsertID();
            $tx->commit();
            return ["status" => "saved", "id" => $id];
        } catch (\yii\db\IntegrityException $e) {
            $tx->rollBack();
            $existing = $this->receipt($table, $actor, $request);
            if ($existing !== null) {
                return hash_equals($existing["request_fingerprint"], $fingerprint)
                    ? ["status" => "saved", "id" => (int) $existing["id"]]
                    : ["status" => "conflict"];
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($tx->isActive) {
                $tx->rollBack();
            }
            throw $e;
        }
    }
    public function exists(int $id): bool
    {
        return (bool) $this->db->createCommand("SELECT 1 FROM `{$this->prefix}fm2_feedback` WHERE id=:id", [":id" => $id])->queryScalar();
    }
    public function listing(?int $before): array
    {
        $where = $before === null ? "" : "WHERE id<:before";
        $params = $before === null ? [] : [":before" => $before];
        $rows = $this->db
            ->createCommand("SELECT * FROM `{$this->prefix}fm2_feedback` $where ORDER BY id DESC LIMIT 51", $params)
            ->queryAll();
        $more = count($rows) > 50;
        if ($more) {
            array_pop($rows);
        }
        $ids = array_column($rows, "id");
        $results = [];
        if ($ids !== []) {
            $in = implode(",", array_map("intval", $ids));
            foreach (
                $this->db
                    ->createCommand(
                        "SELECT id,feedback_id,result,actor_user_id,created_at FROM `{$this->prefix}fm2_feedback_results` WHERE feedback_id IN ($in) ORDER BY id",
                    )
                    ->queryAll()
                as $r
            ) {
                $results[(int) $r["feedback_id"]][] = [
                    "id" => (int) $r["id"],
                    "result" => $r["result"],
                    "actorId" => (int) $r["actor_user_id"],
                    "createdAt" => $r["created_at"],
                ];
            }
        }
        $items = array_map(
            fn($r) => [
                "id" => (int) $r["id"],
                "description" => $r["description"],
                "pagePath" => $r["page_path"],
                "objectId" => $r["object_id"] === null ? null : (int) $r["object_id"],
                "appVersion" => $r["app_version"],
                "actorId" => (int) $r["actor_user_id"],
                "createdAt" => $r["created_at"],
                "results" => $results[(int) $r["id"]] ?? [],
            ],
            $rows,
        );
        return [
            "items" => $items,
            "nextBeforeId" => $more ? (int) end($rows)["id"] : null,
        ];
    }
    private function receipt(string $table, int $actor, string $request): ?array
    {
        $row = $this->db
            ->createCommand("SELECT id,request_fingerprint FROM `$table` WHERE actor_user_id=:actor AND request_id=:request", [
                ":actor" => $actor,
                ":request" => $request,
            ])
            ->queryOne();
        return $row === false ? null : $row;
    }
}
