<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\Runtime\SafeRuntimeFailure;
use FMonitor2\YiiRuntime\Models\ExecutionForm;
use FMonitor2\YiiRuntime\PreopeningResources;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class ExecutionController extends PreopeningController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['GET', 'HEAD', 'POST']],
        ]]);
    }

    public function actionIndex(string $id): string|Response
    {
        $id = $this->canonicalId($id);
        if ($id === null) return $this->status(404);
        $resources = null;
        try {
            $resources = new PreopeningResources();
            if (Yii::$app->request->isPost) {
                if (!$this->cap('installation.open') && !$this->cap('assignment_order.composition.apply')) return $this->status(403);
                return $this->execute($resources, $id);
            }
            $model = $resources->portal()->readSelectionPortal($id, $this->actor());
            if ($model['status'] === 'found') {
                return $this->render('@app/app/YiiRuntime/Views/execution', ['identity' => Yii::$app->user->identity, 'objectId' => $id, 'model' => $model, 'csrf' => Yii::$app->request->csrfToken]);
            }
            if (($model['status'] ?? '') === 'failed' || ($model['reasonCode'] ?? '') === 'SERVICE_UNAVAILABLE') {
                $this->reportResult((string) ($model['reasonCode'] ?? ''));
            }
            return $this->domain($model);
        } catch (\Throwable $error) {
            Yii::$app->response->headers->set('X-FMonitor-Error-ID', SafeRuntimeFailure::report($error, 'execution_controller'));
            return $this->pageError(503, 'Результат операции неизвестен. Проверьте карточку объекта перед повтором.', '/pilot/objects/'.$id, 'Вернуться к карточке');
        } finally {
            $resources?->close();
        }
    }

    private function execute(PreopeningResources $resources, int $id): string|Response
    {
        try { $fields = ExecutionForm::parse(Yii::$app->request->rawBody)->fields; }
        catch (\InvalidArgumentException) { return $this->status(400); }
        $action = $fields['action'] ?? '';
        if ($action === 'apply') return $this->apply($resources, $id, $fields);
        if ($action === 'open' || $action === 'open_confirmed') return $this->status(410);
        return $this->status(400);
    }

    private function apply(PreopeningResources $resources, int $id, array $fields): Response
    {
        if (!$this->cap('assignment_order.composition.apply')) return $this->status(403);
        $orderId = $this->positive($fields['orderId'] ?? null);
        $sequence = $this->sequence($fields['sequence'] ?? null);
        if ($orderId === null || $sequence === null || !$this->revision($fields['revisionId'] ?? null)) return $this->status(400);
        $command = new C\ApplyAssignmentOrderOriginalCommand($fields['requestId'] ?? '', $id, $orderId, $fields['revisionId'], $sequence, $this->actor());
        $result = C\ProductionAssignmentOrderApplicationFactory::create($resources->db, $resources->prefix)->applyAssignmentOrderOriginal($command);
        if (in_array($result->status, ['applied', 'replayed'], true)) return $this->redirect303('/pilot/objects/'.$id.'/execution');
        if ($result->status === 'failed') $this->reportResult((string) $result->reason);
        return $this->status($result->status === 'failed' ? 503 : 422, $result->status === 'failed');
    }

    private function reportResult(string $reason): void
    {
        $category = in_array($reason, ['persistence_failure', 'persistence_outcome_unknown'], true) ? 'database'
            : ($reason === 'dependency_unavailable' ? 'dependency' : 'unexpected');
        Yii::$app->response->headers->set(
            'X-FMonitor-Error-ID',
            SafeRuntimeFailure::report(null, 'execution_controller', $category),
        );
    }

    private function positive(mixed $value): ?int
    {
        if (!is_string($value) || !preg_match('/^[1-9][0-9]*$/D', $value)) return null;
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    private function sequence(mixed $value): ?int
    {
        if (!is_string($value) || !preg_match('/^(0|[1-9][0-9]{0,9})$/D', $value)) return null;
        $sequence = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
        return $sequence === false ? null : $sequence;
    }

    private function revision(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,79}$/D', $value) === 1;
    }

    public function actionMethod(string $id): Response
    {
        return $this->canonicalId($id) === null ? $this->status(404) : $this->methodNotAllowed('GET, HEAD, POST');
    }
}
