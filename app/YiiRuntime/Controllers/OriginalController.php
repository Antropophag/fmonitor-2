<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\YiiRuntime\Models\OriginalMetadata;
use FMonitor2\YiiRuntime\PreopeningResources;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class OriginalController extends PreopeningController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['form' => ['GET', 'HEAD'], 'upload' => ['POST']],
        ]]);
    }

    public function beforeAction($action): bool
    {
        if ($action->id === 'upload') Yii::$app->request->setBodyParams([]);
        return parent::beforeAction($action);
    }

    public function actionForm(string $id, string $orderId): string|Response
    {
        $ids = $this->ids($id, $orderId);
        if ($ids === null) return $this->status(404);
        [$id, $orderId] = $ids;
        $resources = null;
        try {
            $resources = new PreopeningResources(Yii::$app->db);
            $result = $resources->originalAccess()->readSubmissionForm($this->actor(), $id, $orderId);
            return $result['status'] === 'found'
                ? $this->render('@app/app/YiiRuntime/Views/original', $result + ['identity' => Yii::$app->user->identity, 'csrf' => Yii::$app->request->csrfToken])
                : $this->domain($result);
        } catch (\Throwable) {
            return $this->status(503, true);
        } finally {
            $resources?->close();
        }
    }

    public function actionUpload(string $id, string $orderId): Response
    {
        $ids = $this->ids($id, $orderId);
        if ($ids === null) return $this->json(404, ['error' => 'NOT_FOUND']);
        [$id, $orderId] = $ids;
        $resources = null; $stream = null;
        try {
            $admission = $this->admit();
            if (isset($admission['error'])) return $this->json($admission['status'], ['error' => $admission['error']]);
            $fields = $admission['fields'];
            if (!hash_equals((string) Yii::$app->request->headers->get('X-CSRF-Token'), $fields['csrfToken'])) return $this->json(400, ['error' => 'CSRF_INVALID']);
            $resources = new PreopeningResources();
            $context = $resources->submission()->resolveSubmissionContext($this->actor(), $id, $orderId, $fields['mode']);
            if ($context['status'] !== 'found') return $this->contextFailure($context);
            $bytes = Yii::$app->request->rawBody;
            if (strlen($bytes) !== $admission['length']) return $this->json(400, ['error' => 'INVALID_REQUEST']);
            $stream = new O\AssignmentOrderOriginalMemoryStream($bytes);
            $command = $this->command($fields, $context, $stream);
            return $this->result($resources->original()->submitAssignmentOrderOriginal($command));
        } catch (\Throwable) {
            return $this->json(503, ['error' => 'SERVICE_UNAVAILABLE'], true);
        } finally {
            $stream?->close(); $resources?->close();
        }
    }

    private function admit(): array
    {
        if (Yii::$app->request->headers->has('Transfer-Encoding')) return ['error' => 'INVALID_REQUEST', 'status' => 400];
        $type = strtolower(trim(explode(';', (string) Yii::$app->request->contentType, 2)[0]));
        if ($type !== 'application/pdf') return ['error' => 'UNSUPPORTED_MEDIA_TYPE', 'status' => 415];
        $length = Yii::$app->request->headers->get('Content-Length');
        if ($length === null) return ['error' => 'LENGTH_REQUIRED', 'status' => 411];
        if (!is_string($length) || !preg_match('/^(0|[1-9][0-9]*)$/D', $length)) return ['error' => 'INVALID_REQUEST', 'status' => 400];
        if ((int) $length > 20971520) return ['error' => 'REQUEST_TOO_LARGE', 'status' => 413];
        $header = Yii::$app->request->headers->get('X-FMonitor-Original');
        try {
            return ['fields' => OriginalMetadata::fromHeader(is_string($header) ? $header : '')->fields, 'length' => (int) $length];
        } catch (\InvalidArgumentException) {
            return ['error' => 'INVALID_REQUEST', 'status' => 400];
        }
    }

    private function command(array $fields, array $context, O\AssignmentOrderOriginalMemoryStream $stream): O\SubmitAssignmentOrderOriginalCommand
    {
        return new O\SubmitAssignmentOrderOriginalCommand(
            $fields['requestId'], O\AssignmentOrderOriginalMode::from($fields['mode']),
            (int) $context['caseId'], (int) $context['orderId'], $this->actor(),
            $fields['documentDate'], $fields['compositionConfirmed'], $fields['rootOriginalId'],
            $fields['targetRevisionId'], $fields['expectedCurrentRevisionId'], $fields['correctionReason'],
            new O\AssignmentOrderOriginalUpload($stream, $fields['originalFilename'], 'application/pdf'),
        );
    }

    private function result(O\AssignmentOrderOriginalResult $result): Response
    {
        $status = match ($result->status()) {
            O\AssignmentOrderOriginalStatus::ACCEPTED => 201,
            O\AssignmentOrderOriginalStatus::REPLAYED => 200,
            O\AssignmentOrderOriginalStatus::CONFLICT => 409,
            O\AssignmentOrderOriginalStatus::FAILED => 503,
            O\AssignmentOrderOriginalStatus::REJECTED => match ($result->reasonCode()) {
                O\AssignmentOrderOriginalReason::AUTHORIZATION_DENIED => 403,
                O\AssignmentOrderOriginalReason::ORDER_NOT_FOUND => 404,
                O\AssignmentOrderOriginalReason::FILE_TOO_LARGE => 413,
                default => 422,
            },
        };
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW; $response->statusCode = $status;
        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        if ($status === 503) $response->headers->set('Retry-After', '60');
        $response->content = O\AssignmentOrderOriginalWorkerResultEncoder::encode($result);
        return $response;
    }

    private function ids(string $id, string $orderId): ?array
    {
        $id = $this->canonicalId($id); $orderId = $this->canonicalId($orderId);
        return $id === null || $orderId === null ? null : [$id, $orderId];
    }

    private function contextFailure(array $result): Response
    {
        $reason = $result['reasonCode'] ?? 'SERVICE_UNAVAILABLE';
        $status = match ($reason) { 'ACCESS_DENIED' => 403, 'NOT_FOUND' => 404, default => 503 };
        return $this->json($status, ['error' => $reason], $status === 503);
    }

    public function actionFormMethod(string $id, string $orderId): Response { return $this->invalidIds($id, $orderId) ? $this->status(404) : $this->methodNotAllowed('GET, HEAD'); }
    public function actionUploadMethod(string $id, string $orderId): Response { return $this->invalidIds($id, $orderId) ? $this->status(404) : $this->methodNotAllowed('POST'); }
    private function invalidIds(string $id, string $orderId): bool { return $this->canonicalId($id) === null || $this->canonicalId($orderId) === null; }
}
