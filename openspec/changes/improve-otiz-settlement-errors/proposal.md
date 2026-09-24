## Why

Сотрудник ОТиЗ сейчас вынужден вводить удержание только в техническом формате `1000.00`, а подтверждённый отказ очищает форму и теряет контекст объекта. Для устаревшего расчёта сохраняется правильный финансовый отказ, но голый ответ 409 не даёт безопасного пути продолжения; #249 устраняет эти пользовательские ошибки в локальном Yii2 HTTP/UI seam на актуальном roadmap #169.

## What Changes

- Сервер строго принимает привычные положительные рублёвые суммы `1000`, `1000,50`, `1000.50`, однозначно сгруппированное `1 000,50` и каноническое `1000.00`, преобразуя их без float и округления в существующие integer cents.
- Известные ошибки суммы или основания возвращают тот же snapshot и object context, сохраняют только разрешённые значения формы, показывают ошибку рядом с полем, открывают нужную панель и переводят фокус к ошибке без помещения чувствительного текста в URL или логи.
- `STALE_CALCULATION` остаётся HTTP 409 без финансовой записи, но получает понятный HTML-экран со ссылками к исходному расчёту и существующему действию подготовки нового.
- Формы блокируют повторный submit, пока запрос выполняется; неизвестный исход не запускает автоматический retry и сохраняет исходный `operationId` для явной reconciliation/replay попытки.
- Сквозная read-only история объекта из #248 и действующие финансовые команды, формулы, limits, ledger, snapshots, XLSX, права, CSRF и append-only replay semantics сохраняются.

## Capabilities

### New Capabilities

- `otiz/settlement-form-recovery`: Строгий ввод рублёвой суммы, восстановление формы после известного отказа, понятный stale state и безопасное поведение при неизвестном исходе финансовой команды.

### Modified Capabilities

<!-- Нет: существующий canonical owner-контракт settlement и snapshot publication не меняется; новый capability задаёт только bounded HTTP/UI behavior. -->

## Impact

Изменяются только `OtizSettlementController`, локальная form model/helper при необходимости, `otiz-snapshot`/локальные partials, `otiz.js`, focused HTTP/browser fixtures и их verification registration. Публичный state-changing seam остаётся `OtizSettlement::recordDiscipline`/`completeSnapshotPayments`; новые writers, schema, migrations, routes, permissions, CSS, navigation, deployment и harness policy не появляются. Параллельные #258/#250/#260 не затрагиваются.
