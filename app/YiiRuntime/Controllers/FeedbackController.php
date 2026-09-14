<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;
use FMonitor2\YiiRuntime\ViewSupport;
use Yii;
use yii\filters\AccessControl;
use yii\web\{BadRequestHttpException, ForbiddenHttpException, MethodNotAllowedHttpException, Response};

final class FeedbackController extends PilotController
{
    use FeedbackControllerSupport;
    public $layout = false;
    public function beforeAction($action): bool
    {
        if (Yii::$app->request->isPost && Yii::$app->user->isGuest && !Yii::$app->request->validateCsrfToken()) {
            throw new BadRequestHttpException();
        }
        return parent::beforeAction($action);
    }
    public function behaviors(): array
    {
        return [
            "access" => [
                "class" => AccessControl::class,
                "rules" => [["allow" => true, "roles" => ["@"]]],
                "denyCallback" => function (): void {
                    if (Yii::$app->user->isGuest) {
                        Yii::$app->user->setReturnUrl(Yii::$app->request->url);
                        Yii::$app->response->statusCode = 303;
                        Yii::$app->response->headers->set("Location", "/pilot/login");
                    } else {
                        throw new ForbiddenHttpException();
                    }
                },
            ],
        ];
    }
    public function actionForm(): string
    {
        $from = Yii::$app->request->get("from", "/pilot/objects");
        if (!is_string($from)) {
            throw new BadRequestHttpException();
        }
        return $this->renderForm(Yii::$app->feedback->normalizePath($from), ViewSupport::uuid(), "");
    }
    public function actionSubmit(): string
    {
        [$requestId, $description, $path] = $this->fields(["requestId", "description", "pagePath"]);
        try {
            $r = Yii::$app->feedback->submit((int) Yii::$app->user->id, $requestId, $description, $path);
        } catch (\Throwable) {
            Yii::$app->response->statusCode = 503;
            return $this->renderForm(Yii::$app->feedback->normalizePath($path), $requestId, $description, "Не удалось подтвердить сохранение. Повторите отправку — дубликат не появится.");
        }
        if ($r["status"] === "access_denied") {
            throw new ForbiddenHttpException();
        }
        if ($r["status"] === "invalid") {
            Yii::$app->response->statusCode = 422;
            return $this->renderForm(Yii::$app->feedback->normalizePath($path), $requestId, $description, "Проверьте описание и повторите отправку.");
        }
        if ($r["status"] === "conflict") {
            Yii::$app->response->statusCode = 409;
            return $this->renderForm(Yii::$app->feedback->normalizePath($path), $requestId, $description, "Этот идентификатор уже использован для другого обращения.");
        }
        return $this->render("@app/app/YiiRuntime/Views/feedback-confirmation", [
            "identity" => Yii::$app->user->identity,
            "id" => $r["id"],
            "pagePath" => Yii::$app->feedback->normalizePath($path),
        ]);
    }
    public function actionAdmin(): string
    {
        try {
            $listing = Yii::$app->feedback->listing((int) Yii::$app->user->id, $this->cursor());
        } catch (\DomainException) {
            throw new ForbiddenHttpException();
        }
        return $this->render("@app/app/YiiRuntime/Views/feedback-admin", [
            "identity" => Yii::$app->user->identity,
            "listing" => $listing,
            "error" => "",
            "retry" => [],
        ]);
    }
    public function actionResult(int $id): string|Response
    {
        [$requestId, $result] = $this->fields(["requestId", "result"]);
        try {
            $r = Yii::$app->feedback->recordResult((int) Yii::$app->user->id, $id, $requestId, $result);
        } catch (\Throwable) {
            Yii::$app->response->statusCode = 503;
            return $this->adminError("Не удалось подтвердить сохранение. Повторите отправку — дубликат не появится.", $id, $requestId, $result);
        }
        if ($r["status"] === "access_denied") {
            throw new ForbiddenHttpException();
        }
        $codes = ["invalid" => 422, "not_found" => 404, "conflict" => 409];
        if (isset($codes[$r["status"]])) {
            Yii::$app->response->statusCode = $codes[$r["status"]];
            return $this->adminError("Не удалось сохранить результат. Проверьте данные и повторите отправку.", $id, $requestId, $result);
        }
        return $this->redirect("/pilot/admin/feedback", 303);
    }
    public function actionMethod(): never
    {
        throw new MethodNotAllowedHttpException();
    }
    public function actionAdminMethod(): never
    {
        throw new MethodNotAllowedHttpException();
    }
    public function actionResultMethod(): never
    {
        throw new MethodNotAllowedHttpException();
    }
    private function fields(array $names): array
    {
        $out = [];
        foreach ($names as $n) {
            $v = Yii::$app->request->post($n, "");
            if (!is_string($v)) {
                throw new BadRequestHttpException();
            }
            $out[] = $v;
        }
        return $out;
    }
    private function renderForm(string $path, string $request, string $description, string $error = ""): string
    {
        return $this->render("@app/app/YiiRuntime/Views/feedback", [
            "identity" => Yii::$app->user->identity,
            "pagePath" => $path,
            "requestId" => $request,
            "description" => $description,
            "error" => $error,
        ]);
    }
}
