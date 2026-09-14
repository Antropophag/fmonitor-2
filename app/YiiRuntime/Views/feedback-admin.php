<?php declare(strict_types=1);
use FMonitor2\YiiRuntime\ViewSupport;
use yii\helpers\Html;
ViewSupport::begin($this, "Разбор обратной связи", $identity);
?><div class="fm2-page-header"><div><h1>Разбор обратной связи</h1><p>Обращения тестового стенда</p></div></div><?php
if ($error !== ""): ?><p class="shlz-alert shlz-alert--danger"><?= Html::encode(
    $error,
) ?></p><?php endif;
if (
    $retry !== []
): ?><form method="post" action="/pilot/admin/feedback/<?= intval(
    $retry["feedbackId"],
) ?>/result"><?=
Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken)
?><?=
Html::hiddenInput("requestId", $retry["requestId"])
?><label class="shlz-field shlz-field--textarea" for="retry-result"><span class="shlz-field__label">Результат разбора</span><span class="shlz-field__control"><textarea class="shlz-textarea fm2-feedback-textarea" id="retry-result" name="result" rows="5" maxlength="2000" required><?= Html::encode(
    $retry["result"],
) ?></textarea></span></label><button class="shlz-button shlz-button--primary" type="submit">Повторить сохранение</button></form><?php endif;
if ($listing["items"] === []): ?><p>Обращений пока нет</p><?php endif;
foreach (
    $listing["items"]
    as $item
): ?><section class="fm2-list-surface fm2-feedback-surface"><h2>Обращение №<?= intval(
    $item["id"],
) ?></h2><p><?= nl2br(
    Html::encode($item["description"]),
) ?></p><p><a class="shlz-link" href="<?= Html::encode(
    $item["pagePath"],
) ?>"><?= Html::encode($item["pagePath"]) ?></a> · пользователь <?= intval(
    $item["actorId"],
) ?> · <?= Html::encode($item["appVersion"]) ?> · <?= Html::encode(
     $item["createdAt"],
 ) ?></p><?php foreach (
    $item["results"]
    as $result
): ?><p><strong>Результат:</strong> <?= nl2br(
    Html::encode($result["result"]),
) ?> · пользователь <?= intval($result["actorId"]) ?> · <?= Html::encode(
     $result["createdAt"],
 ) ?></p><?php endforeach; ?><form method="post" action="/pilot/admin/feedback/<?= intval(
    $item["id"],
) ?>/result"><?=
Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken)
?><?=
Html::hiddenInput("requestId", ViewSupport::uuid())
?><label class="shlz-field shlz-field--textarea" for="result-<?= intval(
    $item["id"],
) ?>"><span class="shlz-field__label">Результат разбора</span><span class="shlz-field__control"><textarea class="shlz-textarea fm2-feedback-textarea" id="result-<?= intval(
    $item["id"],
) ?>" name="result" rows="5" maxlength="2000" required></textarea></span></label><button class="shlz-button shlz-button--primary" type="submit">Сохранить результат</button></form></section><?php endforeach;
if (
    $listing["nextBeforeId"] !== null
): ?><a class="shlz-link" href="/pilot/admin/feedback?before=<?= intval(
    $listing["nextBeforeId"],
) ?>">Показать более ранние</a><?php endif;
ViewSupport::end($this);
