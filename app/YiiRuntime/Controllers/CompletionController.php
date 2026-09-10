<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\IdentityAccess\MariaDbYiiLocalIdentityStore;
use FMonitor2\InstallationProcess\MariaDbInstallationCompletion;
use FMonitor2\YiiRuntime\Models\CompletionForm;
use FMonitor2\YiiRuntime\Models\CompletionFormError;
use FMonitor2\YiiRuntime\PreopeningResources;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class CompletionController extends PreopeningController
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['POST']],
        ]]);
    }

    public function actionIndex(string $id): Response
    {
        $objectId = $this->canonicalId($id);
        if ($objectId === null) return $this->status(404);
        try {
            $form = CompletionForm::parse((string) Yii::$app->request->contentType, Yii::$app->request->rawBody, Yii::$app->request->headers->get('Content-Length'));
        } catch (\LengthException) { return $this->status(413); }
        catch (\InvalidArgumentException) { return $this->status(400); }
        if (!Yii::$app->request->validateCsrfToken($form->fields['_csrf'] ?? '')) return $this->status(400);
        try { $command = $form->command((new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format('Y-m-d')); }
        catch (CompletionFormError $error) { return $this->text(422, $error->getMessage()); }
        $identityStore = Yii::$app->localIdentity;
        if (!$identityStore instanceof MariaDbYiiLocalIdentityStore || !$identityStore->grants($this->actor(), $command['capability'])) {
            return $this->text(403, 'Действие недоступно для вашей роли.');
        }
        $resources = null;
        try {
            $resources = new PreopeningResources();
            $owner = new MariaDbInstallationCompletion($resources->db, $resources->prefix);
            $now = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format(DATE_ATOM);
            if (str_starts_with($command['action'], 'record_')) {
                $owner->record($objectId, $this->actor(), $command['type'], $command['date'], $command['details'], $now);
            } else {
                $owner->correct($objectId, $this->actor(), $command['factId'], $command['type'], $command['date'], $command['details'], $command['reason'], $now);
            }
            return $this->redirect303('/pilot/objects/' . $objectId . '#completion');
        } catch (\DomainException $error) {
            return $this->domainError($error->getMessage(), $command['action']);
        } catch (\Throwable) {
            return $this->text(503, 'Service unavailable.', true);
        } finally {
            if ($resources instanceof PreopeningResources) $resources->close();
        }
    }

    public function actionMethod(string $id): Response
    {
        return $this->canonicalId($id) === null ? $this->status(404) : $this->methodNotAllowed('POST');
    }

    private function domainError(string $code, string $action): Response
    {
        return match ($code) {
            'CASE_NOT_FOUND' => $this->text(404, 'Объект не найден.'),
            'CASE_NOT_WORKING' => $this->text(409, 'Работы по объекту не открыты.'),
            'CHECKLIST_INCOMPLETE' => $this->text(409, 'Сначала завершите монтажные работы до 85%.'),
            'PTO_REQUIRED' => $this->text(409, 'Сначала зафиксируйте дату акта ПТО.'),
            'FACT_ALREADY_RECORDED' => $this->text(409, 'Документ уже зафиксирован.'),
            'ACTOR_NOT_AUTHORIZED' => $this->text(403, 'Действие недоступно для вашей роли.'),
            'FACT_NOT_FOUND' => $this->text(409, 'Исправляемая запись не найдена.'),
            'REASON_REQUIRED' => $this->status(422),
            'INVALID_FACT' => $this->validationError($action),
            default => $this->text(503, 'Service unavailable.', true),
        };
    }

    private function validationError(string $action): Response
    {
        return $this->text(422, str_contains($action, 'declaration') ? 'Укажите дату и реквизиты декларации.' : 'Укажите дату акта ПТО не позже сегодняшней.');
    }

    private function text(int $status, string $message, bool $retry = false): Response
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->statusCode = $status;
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        if ($retry) $response->headers->set('Retry-After', '60');
        $response->content = $message . "\n";
        return $response;
    }
}
