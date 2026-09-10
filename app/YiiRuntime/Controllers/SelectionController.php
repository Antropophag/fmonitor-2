<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\YiiRuntime\Models\SelectionForm;
use FMonitor2\YiiRuntime\PreopeningResources;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class SelectionController extends PreopeningController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['GET', 'HEAD', 'POST'], 'installers' => ['GET', 'HEAD']],
        ]]);
    }

    public function actionIndex(string $id): string|Response
    {
        $id = $this->canonicalId($id);
        if ($id === null) return $this->status(404);
        $path = '/pilot/objects/'.$id.'/assignment-order/selection';
        $resources = null;
        try {
            $resources = new PreopeningResources(Yii::$app->db);
            $portal = $resources->portal();
            if (!$this->processCap('assignment_order.composition.select') || $portal->authorizeActor($this->actor())['status'] !== 'allowed') return $this->status(403);
            if (Yii::$app->request->isPost) return $this->save($resources, $id, $path);
            $model = $portal->readSelectionPortal($id, $this->actor());
            if ($model['status'] !== 'found') return $this->domain($model);
            $documentAccess = $resources->originalAccess()->readAccess($this->actor(), $id);
            if ($documentAccess['status'] === 'unavailable') return $this->pageError(503, 'Не удалось проверить доступ к документам.', $path, 'Повторить');
            return $this->render('@app/app/YiiRuntime/Views/selection', $model + [
                'identity' => Yii::$app->user->identity,
                'csrf' => Yii::$app->request->csrfToken,
                'canUpload' => $documentAccess['canUpload'],
                'canCorrect' => $documentAccess['canCorrect'],
                'canReadOriginal' => $documentAccess['canRead'],
            ]);
        } catch (\Throwable) {
            return $this->pageError(503, 'Не удалось получить актуальные данные. Повторите попытку.', $path, 'Повторить');
        } finally {
            $resources?->close();
        }
    }

    private function save(PreopeningResources $resources, int $id, string $path): string|Response
    {
        if (!$this->formMedia()) return $this->status(415);
        try { $fields = SelectionForm::parse(Yii::$app->request->rawBody)->fields; }
        catch (\LengthException) { return $this->status(413); }
        catch (\InvalidArgumentException) { return $this->status(400); }
        $command = $this->command($fields, $id);
        if ($command === null) return $this->status(400);
        $result = $resources->selectAssignmentOrderComposition($command);
        return in_array($result['status'], ['selected', 'replayed'], true)
            ? $this->redirect303($path)
            : $this->selectionFailure($result, $path);
    }

    public function actionInstallers(string $id): Response
    {
        if ($this->canonicalId($id) === null) return $this->status(404);
        $resources = null;
        try {
            $resources = new PreopeningResources();
            if (!$this->processCap('assignment_order.composition.select')) return $this->status(403);
            parse_str((string) ($_SERVER['QUERY_STRING'] ?? ''), $query);
            if (array_diff(array_keys($query), ['q', 'page']) !== []) return $this->status(400);
            $term = $query['q'] ?? null;
            $page = $query['page'] ?? '1';
            if (!is_string($term) || !is_string($page) || !preg_match('/^[1-9][0-9]*$/D', $page)) return $this->status(400);
            $result = $resources->portal()->searchEligibleInstallers($this->actor(), $term, (int) $page);
            if (($result['reasonCode'] ?? null) === 'invalid_query') return $this->status(400);
            return $result['status'] === 'found'
                ? $this->json(200, ['items' => $result['items'], 'page' => $result['page'], 'hasMore' => $result['hasMore']])
                : $this->domain($result);
        } catch (\Throwable) {
            return $this->status(503, true);
        } finally {
            $resources?->close();
        }
    }

    private function command(array $fields, int $id): ?C\SelectAssignmentOrderCompositionCommand
    {
        try {
            $installers = array_map(static fn(string $value): C\InstallerTabId => new C\InstallerTabId((int) $value), $fields['installerTabIds[]'] ?? []);
            return new C\SelectAssignmentOrderCompositionCommand(
                new C\SelectionRequestId($fields['requestId']),
                C\AssignmentOrderCompositionMode::from($fields['mode']),
                new C\InstallationObjectId($id),
                new C\UserId($this->actor()),
                new C\InstallerTabIdList($installers),
                new C\UserId((int) $fields['controlEngineerUserId']),
                new C\SelectionRevision((int) $fields['expectedSelectionRevision']),
            );
        } catch (\Throwable) { return null; }
    }

    private function selectionFailure(array $result, string $path): string|Response
    {
        $reason = $result['reasonCode'] ?? '';
        $status = match (true) {
            ($result['status'] ?? '') === 'conflict' => 409,
            ($result['status'] ?? '') === 'failed' => 503,
            $reason === 'authorization_denied' => 403,
            $reason === 'object_not_found' => 404,
            default => 422,
        };
        $message = match ($status) {
            409 => 'Состав уже изменился. Обновите форму и повторите действие.',
            503 => 'Не удалось подтвердить результат. Проверьте актуальный состав перед повтором.',
            default => 'Состав не сохранён. Проверьте выбранных сотрудников и актуальность данных.',
        };
        return $this->pageError($status, $message, $path, $status === 503 ? 'Проверить и повторить' : 'Вернуться к составу');
    }

    private function formMedia(): bool
    {
        return preg_match('~^application/x-www-form-urlencoded(?:\s*;\s*charset=utf-8)?$~iD', (string) Yii::$app->request->contentType) === 1;
    }

    public function actionIndexMethod(string $id): Response { return $this->canonicalId($id) === null ? $this->status(404) : $this->methodNotAllowed('GET, HEAD, POST'); }
    public function actionInstallersMethod(string $id): Response { return $this->canonicalId($id) === null ? $this->status(404) : $this->methodNotAllowed('GET, HEAD'); }
}
