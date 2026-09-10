<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\YiiRuntime\PreopeningResources;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class OriginalHistoryController extends PreopeningController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['GET', 'HEAD'], 'download' => ['GET', 'HEAD']],
        ]]);
    }

    public function actionIndex(string $id, string $orderId): string|Response
    {
        $ids = $this->ids($id, $orderId);
        return $ids === null ? $this->status(404) : $this->historyResponse($ids[0], $ids[1], null);
    }

    public function actionDownload(string $id, string $orderId, string $revisionId): string|Response
    {
        $ids = $this->ids($id, $orderId);
        return $ids === null ? $this->status(404) : $this->historyResponse($ids[0], $ids[1], $revisionId);
    }

    private function historyResponse(int $id, int $orderId, ?string $revision): string|Response
    {
        $resources = null;
        try {
            $resources = new PreopeningResources(Yii::$app->db);
            $admission = $resources->originalAccess()->readAccess($this->actor(), $id)['status'];
            if ($admission === 'unavailable') return $this->json(503, ['error' => 'SERVICE_UNAVAILABLE'], true);
            if ($admission !== 'allowed') return $this->json(403, ['error' => 'ACCESS_DENIED']);
            $reader = $resources->history();
            if ($revision !== null) return $this->download($reader, $id, $orderId, $revision);
            $result = $reader->readHistory($id, $orderId, 0, 100);
            if ($result->status !== O\AssignmentOrderOriginalHistoryStatus::FOUND || $result->page === null) return $this->status($result->status === O\AssignmentOrderOriginalHistoryStatus::NOT_FOUND ? 404 : 503, true);
            return $this->render('@app/app/YiiRuntime/Views/original-history', ['identity' => Yii::$app->user->identity, 'history' => $result->page->metadata()]);
        } catch (\Throwable) {
            return $this->status(503, true);
        } finally {
            $resources?->close();
        }
    }

    private function download(O\AssignmentOrderOriginalHistoryReader $reader, int $id, int $orderId, string $revision): Response
    {
        $result = $reader->prepareDownload($id, $orderId, $revision);
        if ($result->status !== O\AssignmentOrderOriginalHistoryStatus::FOUND || $result->download === null) {
            $missing = $result->status === O\AssignmentOrderOriginalHistoryStatus::NOT_FOUND;
            return $this->json($missing ? 404 : 503, ['error' => $missing ? 'NOT_FOUND' : 'SERVICE_UNAVAILABLE'], !$missing);
        }
        $bytes = $result->download->bytes();
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW; $response->statusCode = 200;
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="assignment-order-original.pdf"');
        $response->headers->set('Content-Length', (string) strlen($bytes));
        $response->content = $bytes;
        return $response;
    }

    private function ids(string $id, string $orderId): ?array
    {
        $id = $this->canonicalId($id); $orderId = $this->canonicalId($orderId);
        return $id === null || $orderId === null ? null : [$id, $orderId];
    }

    public function actionIndexMethod(string $id, string $orderId): Response { return $this->invalid($id, $orderId) ? $this->status(404) : $this->methodNotAllowed('GET, HEAD'); }
    public function actionDownloadMethod(string $id, string $orderId): Response { return $this->invalid($id, $orderId) ? $this->status(404) : $this->methodNotAllowed('GET, HEAD'); }
    private function invalid(string $id, string $orderId): bool { return $this->canonicalId($id) === null || $this->canonicalId($orderId) === null; }
}
