<?php declare(strict_types=1);
use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;
ViewSupport::begin($this, "Обратная связь", $identity);
?>
<div class="fm2-page-header"><div><h1>Обратная связь</h1><p>Расскажите о проблеме или пожелании на тестовом стенде</p></div></div>
<?php if (
    $error !== ""
): ?><p class="shlz-alert shlz-alert--danger"><?= Html::encode(
    $error,
) ?></p><?php endif; ?>
<section class="fm2-list-surface fm2-feedback-surface"><form class="fm2-feedback-form" method="post" action="/pilot/feedback"><?=
Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken)
?><?=
Html::hiddenInput("requestId", $requestId)
?><?=
Html::hiddenInput("pagePath", $pagePath)
?><label class="shlz-field shlz-field--textarea" for="feedback-description"><span class="shlz-field__label">Описание</span><span class="shlz-field__control"><textarea class="shlz-textarea fm2-feedback-textarea" id="feedback-description" name="description" rows="6" maxlength="4000" required><?= Html::encode(
    $description,
) ?></textarea></span></label><p>Не указывайте пароли и содержимое документов. Сохраняются описание, безопасный адрес страницы, ваш ID и версия приложения.</p><button class="shlz-button shlz-button--primary" type="submit">Отправить обращение</button> <a class="shlz-button shlz-button--secondary" href="<?= Html::encode(
    $pagePath,
) ?>">Вернуться</a></form></section>
<?php if (
    Yii::$app->canonicalAccess->checkAccess(
        (int) $identity->id,
        "access.administer",
    )
): ?><p><a class="shlz-link" href="/pilot/admin/feedback">Перейти к разбору обращений</a></p><?php endif; ?>
<?php ViewSupport::end($this);
