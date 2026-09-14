<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use Yii;

trait FeedbackControllerSupport
{
    private function adminError(string $error, int $id, string $request, string $result): string
    {
        return $this->render('@app/app/YiiRuntime/Views/feedback-admin', [
            'identity'=>Yii::$app->user->identity,
            'listing'=>Yii::$app->feedback->listing((int) Yii::$app->user->id),
            'error'=>$error,
            'retry'=>['feedbackId'=>$id, 'requestId'=>$request, 'result'=>$result],
        ]);
    }

    private function cursor(): ?int
    {
        $value = Yii::$app->request->get('before');
        $valid = is_scalar($value) && filter_var($value, FILTER_VALIDATE_INT,
            ['options'=>['min_range'=>1]]) !== false;
        return $valid ? (int) $value : null;
    }
}
