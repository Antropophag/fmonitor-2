## Why

Пользователям тестового стенда нужен простой способ сообщить о проблеме, а ответственному сотруднику — сохранить результат разбора. Основание и behavioral oracle: issue #31 и поручение владельца от 2026-09-14 реализовать минимальный механизм от актуального main до PR-ready.

## What Changes

- Срез FEEDBACK-001: активный пользователь отправляет описание с безопасным контекстом страницы и версии; получает подтверждение и возврат к работе.
- Сотрудник с существующим полномочием access.administer просматривает обращения и добавляет результат разбора с сохранением истории.
- Единый application owner `FMonitor2\YiiRuntime\FeedbackApplication`; Yii2 HTTP, существующая MariaDB persistence и публичные shlz-ui классы.
- Идентификатор попытки защищает от дублей после повторной отправки и потери ответа.

## Capabilities

### New Capabilities
- `runtime/test-stand-feedback`: минимальная обратная связь тестового стенда.

### Modified Capabilities

Нет.

## Impact

Yii2 routes/component/views/navigation, компактный application owner, additive migration на текущем frontier, обязательные прямые schema inventory consumers, focused tests и delivery artifacts. Без notification/telemetry framework, внешних отправок, GitHub issues, вложений или сбора содержимого документов. Checklist/process owners #40, OTIZ #66 и общие verification/CI files не меняются без конкретно доказанной зависимости.
