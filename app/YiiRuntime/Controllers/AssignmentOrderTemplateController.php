<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\YiiRuntime\PreopeningResources;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class AssignmentOrderTemplateController extends PreopeningController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['generate' => ['POST']],
        ]]);
    }

    public function actionGenerate(string $id, string $orderId): Response
    {
        $id = $this->canonicalId($id); $orderId = $this->canonicalId($orderId);
        if ($id === null || $orderId === null) return $this->status(404);
        $resources = null;
        try {
            $resources = new PreopeningResources();
            if (!$this->processCap('assignment_order.composition.select') || $resources->portal()->authorizeActor($this->actor())['status'] !== 'allowed') return $this->status(403);
            if (!$this->formMedia()) return $this->status(415);
            if (!$this->closedBody()) return $this->status(400);
            $model = $resources->portal()->readSelectionPortal($id, $this->actor());
            if ($model['status'] !== 'found') return $this->domain($model);
            $result = $resources->template((int) $model['caseId'], $orderId, $this->actor());
            if ($result['status'] !== 'generated') return $this->domain($result);
            $bytes = $result['bytes'];
            $response = Yii::$app->response;
            $response->format = Response::FORMAT_RAW; $response->statusCode = 200;
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'inline; filename="assignment-order.pdf"');
            $response->headers->set('Content-Length', (string) strlen($bytes));
            $response->content = $bytes;
            return $response;
        } catch (\Throwable) {
            return $this->status(503, true);
        } finally {
            $resources?->close();
        }
    }

    private function formMedia(): bool
    {
        return preg_match('~^application/x-www-form-urlencoded(?:\s*;\s*charset=utf-8)?$~iD', (string) Yii::$app->request->contentType) === 1;
    }

    private function closedBody(): bool
    {
        $body = Yii::$app->request->rawBody;
        if ($body === '' || str_ends_with($body, '&')) return false;
        $parts = explode('=', $body, 2);
        return count(explode('&', $body)) === 1
            && !preg_match('/%(?![0-9A-Fa-f]{2})/', implode('', $parts))
            && rawurldecode(str_replace('+', ' ', $parts[0])) === '_csrf';
    }

    public function actionMethod(string $id, string $orderId): Response
    {
        return $this->canonicalId($id) === null || $this->canonicalId($orderId) === null
            ? $this->status(404)
            : $this->methodNotAllowed('POST');
    }
}
