## Why

Основной OTIZ HTTP-путь всё ещё частично принадлежит `rapid-pilot/Otiz.php`: расчёт, публикация, принятие снимка и часть read/UI adapters остаются во временном runtime, хотя выплаты, удержания и сторно уже переданы Yii2/application owner. Срез нужен сейчас как независимая от console/import работ вертикаль №76, чтобы завершить один финансовый пользовательский путь без конфликта с параллельным `yii2-imports-workforce`.

## What Changes

- Перевести существующие OTIZ calculate → inspect → accept → export HTTP-действия и связанные queue/snapshot/history/register read adapters на Yii2 routing/controllers/views.
- Сохранить текущие роли, CSRF/admission, формулы, snapshot contents, blocker semantics, redirects, XLSX и append-only audit/history без продуктового изменения.
- Вызывать существующих application owners для state-changing операций; Yii2 controller не получает SQL-транзакции или финансовые правила.
- Удалить из production routing только фактически заменённые OTIZ handlers/assets/decorators `rapid-pilot`; characterization/verifier файлы остаются историческим oracle.
- Добавить focused HTTP/browser и boundary verification, включая отказ, replay/concurrency, отсутствие новых фактов при rejection и возврат пользователя в Yii2 UI.
- Не изменять console/import/workforce, migration ledger, jobs, общую web-runtime retirement или stand deployment.

## Capabilities

### New Capabilities

Нет. Это перенос владельца framework/runtime с сохранением уже специфицированного поведения.

### Modified Capabilities

Нет. Нормативные OTIZ требования и денежные правила не меняются; change использует `skip_specs: true`.

## Impact

Затрагиваются Yii2 web routing/controllers/views/assets, production OTIZ composition, временный `rapid-pilot/Otiz.php` и verification inventory. Основные источники поведения — существующие OTIZ specs, `rapid-pilot/verify-otiz-workflow.php`, settlement contracts и актуальные Yii2 HTTP routes. Целевые публичные seams — существующие application owners в `app/Otiz`; если инвентаризация обнаружит отсутствующий owner для calculate/publication/accept, его точное имя и контракт должны быть закреплены нормативным spec до RED.

Release value: сотрудник ОТиЗ выполняет целостный действующий финансовый маршрут через единственный Yii2 web runtime, а заменённая часть `RapidPilotOtiz` перестаёт быть production owner.

Явные non-goals: новые формулы/справки №66, пересчёт принятой истории, изменение полномочий или separation of duties, миграция данных, произвольные новые виды выплат, общий cutover, Compose/startup/recovery и deployment. Неопределённые целевые semantics принятия из `CHARACTERIZE-OTIZ-SNAPSHOT-ACCEPTANCE-001` остаются `NEEDS_GRILL`; этот refactor не превращает наблюдаемое pilot-поведение в новую продуктовую норму и не закрывает соответствующий redesign slice.
