<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use mysqli;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;

final class IntegrationStatusController extends PilotController
{
    private const PAGE_SIZE = 25;

    public $layout = false;

    public function behaviors(): array
    {
        return ['access' => ['class' => AccessControl::class, 'rules' => [[
            'allow' => true,
            'actions' => ['index'],
            'roles' => ['access.administer'],
        ]], 'denyCallback' => function (): void {
            if (Yii::$app->user->isGuest) {
                Yii::$app->user->setReturnUrl(Yii::$app->request->url);
                Yii::$app->response->statusCode = 303;
                Yii::$app->response->headers->set('Location', '/pilot/login');
                return;
            }
            throw new ForbiddenHttpException();
        }]];
    }

    public function actionIndex(): string
    {
        $pages = [
            'workforce' => $this->page('workforcePage'),
            'equipment' => $this->page('equipmentPage'),
            'jobs' => $this->page('jobsPage'),
        ];
        $db = $this->connection();
        try {
            $workforce = $this->safeRead(fn(): array => $this->workforce($db, $pages['workforce']));
            $equipment = $this->safeRead(fn(): array => $this->equipment($db, $pages['equipment']));
            $jobs = $this->safeRead(fn(): array => $this->jobs($db, $pages['jobs']));
        } finally {
            $db->close();
        }
        return $this->render('@app/app/YiiRuntime/Views/integration-status', [
            'identity' => Yii::$app->user->identity,
            'workforce' => $workforce,
            'equipment' => $equipment,
            'jobs' => $jobs,
            'pages' => $pages,
            'pageSize' => self::PAGE_SIZE,
        ]);
    }

    private function connection(): mysqli
    {
        $db = new mysqli((string) getenv('FMONITOR_DB_HOST'), (string) getenv('FMONITOR_DB_USER'), (string) getenv('FMONITOR_DB_PASSWORD'), (string) getenv('FMONITOR_DB_NAME'), (int) getenv('FMONITOR_DB_PORT'));
        try {
            $db->set_charset('utf8mb4');
        } catch (Throwable $error) {
            $db->close();
            throw $error;
        }
        return $db;
    }

    private function page(string $name): int
    {
        $value = Yii::$app->request->get($name, '1');
        if (!is_string($value) || preg_match('/^[1-9]\d{0,8}$/D', $value) !== 1) {
            return 1;
        }
        return min((int) $value, 1000000);
    }

    private function safeRead(callable $reader): array
    {
        try {
            return $reader();
        } catch (Throwable) {
            return ['available' => false, 'rows' => [], 'total' => 0, 'attempt' => null, 'success' => null];
        }
    }

    private function workforce(mysqli $db, int $page): array
    {
        $runs = $this->rows($db, 'SELECT status,started_at,observed_at,completed_at,failure_code,page_count,delivered_count,material_change_count,missing_count FROM '.$this->table('fm2_workforce_sync_runs').' ORDER BY started_at DESC,run_id DESC LIMIT 1');
        $success = $this->rows($db, "SELECT status,started_at,observed_at,completed_at,failure_code,page_count,delivered_count,material_change_count,missing_count FROM ".$this->table('fm2_workforce_sync_runs')." WHERE status='completed' ORDER BY completed_at DESC,run_id DESC LIMIT 1");
        $where = "reconciliation_state='missing_from_delivery'";
        $total = (int) ($this->rows($db, 'SELECT COUNT(*) AS total FROM '.$this->table('fm2_workforce_catalog').' WHERE '.$where)[0]['total'] ?? 0);
        $page = $this->boundedPage($page, $total);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $rows = $this->rows($db, 'SELECT installer_tab_id,fio,position,employment_status,workforce_source_updated_at FROM '.$this->table('fm2_workforce_catalog').' WHERE '.$where.' ORDER BY installer_tab_id ASC LIMIT '.self::PAGE_SIZE.' OFFSET '.$offset);
        return ['available' => true, 'attempt' => $runs[0] ?? null, 'success' => $success[0] ?? null, 'rows' => $rows, 'total' => $total, 'page' => $page];
    }

    private function equipment(mysqli $db, int $page): array
    {
        $runs = $this->rows($db, 'SELECT status,reason,observed_at,receipt_json FROM '.$this->table('fm2_equipment_fact_runs').' ORDER BY observed_at DESC,run_id DESC LIMIT 1');
        $success = $this->rows($db, "SELECT status,reason,observed_at,receipt_json FROM ".$this->table('fm2_equipment_fact_runs')." WHERE status='completed' ORDER BY observed_at DESC,run_id DESC LIMIT 1");
        foreach ([$runs, $success] as $set) {
            if (isset($set[0]['receipt_json'])) {
                json_decode((string) $set[0]['receipt_json'], true, 16, JSON_THROW_ON_ERROR);
            }
        }
        $where = "reason IN('OBJECT_NOT_FOUND','OBJECT_AMBIGUOUS','object_not_found','object_ambiguous')";
        $total = (int) ($this->rows($db, 'SELECT COUNT(*) AS total FROM '.$this->table('fm2_equipment_fact_diagnostics').' WHERE '.$where)[0]['total'] ?? 0);
        $page = $this->boundedPage($page, $total);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $rows = $this->rows($db, 'SELECT source_order_hmac,reason,created_at FROM '.$this->table('fm2_equipment_fact_diagnostics').' WHERE '.$where.' ORDER BY id ASC LIMIT '.self::PAGE_SIZE.' OFFSET '.$offset);
        return ['available' => true, 'attempt' => $this->equipmentRun($runs[0] ?? null), 'success' => $this->equipmentRun($success[0] ?? null), 'rows' => $rows, 'total' => $total, 'page' => $page];
    }

    private function equipmentRun(?array $run): ?array
    {
        if ($run === null) return null;
        $receipt = json_decode((string) $run['receipt_json'], true, 16, JSON_THROW_ON_ERROR);
        $run['counts'] = [];
        foreach (['matched', 'changed', 'unchanged', 'unmatched', 'ambiguous'] as $key) {
            if (isset($receipt[$key]) && is_int($receipt[$key]) && $receipt[$key] >= 0) $run['counts'][$key] = $receipt[$key];
        }
        unset($run['receipt_json']);
        return $run;
    }

    private function jobs(mysqli $db, int $page): array
    {
        $where = "status='dead'";
        $total = (int) ($this->rows($db, 'SELECT COUNT(*) AS total FROM '.$this->table('fm2_jobs').' WHERE '.$where)[0]['total'] ?? 0);
        $page = $this->boundedPage($page, $total);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $rows = $this->rows($db, 'SELECT job_id,job_type,attempt,created_at_utc,completed_at_utc,failure_code FROM '.$this->table('fm2_jobs').' WHERE '.$where.' ORDER BY job_id ASC LIMIT '.self::PAGE_SIZE.' OFFSET '.$offset);
        return ['available' => true, 'rows' => $rows, 'total' => $total, 'page' => $page, 'attempt' => null, 'success' => null];
    }

    private function boundedPage(int $page, int $total): int
    {
        return min($page, max(1, (int) ceil($total / self::PAGE_SIZE)));
    }

    private function table(string $name): string
    {
        $prefix = (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX');
        if (preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) throw new \RuntimeException('Invalid table prefix.');
        return '`'.$prefix.$name.'`';
    }

    private function rows(mysqli $db, string $sql): array
    {
        return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
}
