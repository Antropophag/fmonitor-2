<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use Throwable;
use Yii;
use yii\db\Connection;
use yii\db\Query;
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
            'documents' => $this->page('documentsPage'),
            'workforce' => $this->page('workforcePage'),
            'equipment' => $this->page('equipmentPage'),
            'jobs' => $this->page('jobsPage'),
        ];
        $db = Yii::$app->db;
        try {
            $documents = $this->safeRead(fn(): array => (new BitrixDocumentationStatusRead($db, (string) getenv('FMONITOR_PROCESS_TABLE_PREFIX')))->read($pages['documents']));
            $workforce = $this->safeRead(fn(): array => $this->workforce($db, $pages['workforce']));
            $equipment = $this->safeRead(fn(): array => $this->equipment($db, $pages['equipment']));
            $jobs = $this->safeRead(fn(): array => $this->jobs($db, $pages['jobs']));
        } finally {
            $db->close();
        }
        return $this->render('@app/app/YiiRuntime/Views/integration-status', [
            'identity' => Yii::$app->user->identity,
            'documents' => $documents,
            'workforce' => $workforce,
            'equipment' => $equipment,
            'jobs' => $jobs,
            'pages' => $pages,
            'pageSize' => self::PAGE_SIZE,
        ]);
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

    private function workforce(Connection $db, int $page): array
    {
        $columns = ['status','started_at','observed_at','completed_at','failure_code','page_count','delivered_count','material_change_count','missing_count'];
        $runs = (new Query())->addSelect($columns)->from($this->table('fm2_workforce_sync_runs'))->orderBy(['started_at' => SORT_DESC, 'run_id' => SORT_DESC])->limit(1)->all($db);
        $success = (new Query())->addSelect($columns)->from($this->table('fm2_workforce_sync_runs'))->where(['status' => 'completed'])->orderBy(['completed_at' => SORT_DESC, 'run_id' => SORT_DESC])->limit(1)->all($db);
        $catalog = (new Query())->from($this->table('fm2_workforce_catalog'))->where(['reconciliation_state' => 'missing_from_delivery']);
        $total = (int) $catalog->count('*', $db);
        $page = $this->boundedPage($page, $total);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $rows = $catalog->addSelect(['installer_tab_id','fio','position','employment_status','workforce_source_updated_at'])->orderBy(['installer_tab_id' => SORT_ASC])->limit(self::PAGE_SIZE)->offset($offset)->all($db);
        return ['available' => true, 'attempt' => $runs[0] ?? null, 'success' => $success[0] ?? null, 'rows' => $rows, 'total' => $total, 'page' => $page];
    }

    private function equipment(Connection $db, int $page): array
    {
        $columns = ['status','reason','observed_at','receipt_json'];
        $runs = (new Query())->addSelect($columns)->from($this->table('fm2_equipment_fact_runs'))->orderBy(['observed_at' => SORT_DESC, 'run_id' => SORT_DESC])->limit(1)->all($db);
        $success = (new Query())->addSelect($columns)->from($this->table('fm2_equipment_fact_runs'))->where(['status' => 'completed'])->orderBy(['observed_at' => SORT_DESC, 'run_id' => SORT_DESC])->limit(1)->all($db);
        foreach ([$runs, $success] as $set) {
            if (isset($set[0]['receipt_json'])) {
                json_decode((string) $set[0]['receipt_json'], true, 16, JSON_THROW_ON_ERROR);
            }
        }
        $diagnostics = (new Query())->from($this->table('fm2_equipment_fact_diagnostics'))->where(['reason' => ['OBJECT_NOT_FOUND','OBJECT_AMBIGUOUS','object_not_found','object_ambiguous']]);
        $total = (int) $diagnostics->count('*', $db);
        $page = $this->boundedPage($page, $total);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $rows = $diagnostics->addSelect(['source_order_hmac','reason','created_at'])->orderBy(['id' => SORT_ASC])->limit(self::PAGE_SIZE)->offset($offset)->all($db);
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

    private function jobs(Connection $db, int $page): array
    {
        $jobs = (new Query())->from($this->table('fm2_jobs'))->where(['status' => 'dead']);
        $total = (int) $jobs->count('*', $db);
        $page = $this->boundedPage($page, $total);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $rows = $jobs->addSelect(['job_id','job_type','attempt','created_at_utc','completed_at_utc','failure_code'])->orderBy(['job_id' => SORT_ASC])->limit(self::PAGE_SIZE)->offset($offset)->all($db);
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
        return $prefix.$name;
    }
}
