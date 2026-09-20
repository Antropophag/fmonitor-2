## Why

Yii2-стенд не показывал завершённые объекты: server queue исключала их до выполнения существующего переключателя «Показывать завершённые».

## What Changes

- Возвращать полностью завершённые `working` cases с `completed=true`.
- Оставить PTO-only document closeout вне очереди.
- Проверить Yii HTTP, pagination и полный цикл клиентского переключателя.
- Не менять writers, schema, completion facts или JavaScript production asset.

## Capabilities

### New Capabilities

- `construction-control/completed-filter`: Yii2 queue публикует завершённые строки для существующего фильтра.

### Modified Capabilities

Нет.

## Impact

Yii2 construction-control read model, нормативный контракт и его интеграционный тест. Миграции не требуются.
