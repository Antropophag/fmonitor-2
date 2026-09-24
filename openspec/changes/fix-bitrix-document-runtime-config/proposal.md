## Why

После перехода `bitrix.order-document-links.sync` на раздельный runtime contract production worker не получает origin и webhook user ID, а отсутствующий host token file молча подменяется `/dev/null`. Планировщик продолжает ставить задания, но доставка не доходит до Bitrix API и завершается retry/dead; исправление нужно сейчас, чтобы восстановить публикацию ссылок на техническую документацию без раскрытия webhook token.

## What Changes

- Ввести единый upgrade/staging seam: оператор хранит существующий `FMONITOR_BITRIX_WEBHOOK_URL` только в `.env`, staging атомарно публикует один private config, а worker извлекает из него origin/user ID и создаёт временный private token file непосредственно перед запуском.
- Согласовать `.env` template, `local-integration-config`, `stage-runtime-secrets` и `deploy/runtime/compose.yaml` с `BitrixOrderDocumentDelivery`.
- Удалить silent fallback активной document-links интеграции на `/dev/null` и отдельный host token contract; при включённой интеграции отсутствующий, небезопасный или невалидный canonical config должен давать понятный fail-fast результат до запуска worker.
- Не дублировать token в Compose mounts или metadata; worker создаёт короткоживущий private token file из одного staged config и исключает token из environment, Compose output, argv, логов и tracked files.
- Добавить bounded regression coverage upgrade/config staging path, runtime secret mount и redaction, а также операторскую документацию проверки и ротации секрета.
- Подтвердить read-only delivery seam синтетическим Bitrix transport test; реальный production fetch, публикация, deployment и изменение данных остаются внешней ручной проверкой и до неё имеют статус `UNKNOWN`.
- Не изменять workforce sync contract, scheduler semantics, document-link persistence/schema, карточку объекта или Bitrix API traversal.

## Capabilities

### New Capabilities

- `bitrix-document-runtime-config`: Каноническая безопасная подготовка одного private Bitrix config из `.env` и его consumption worker для синхронизации ссылок на техническую документацию, включая fail-fast validation.

### Modified Capabilities

Нет.

## Impact

Затрагиваются `.env.example`, `tools/delivery/local-integration-config`, production secret staging, `deploy/runtime/compose.yaml`, focused installation/runtime tests и операторская документация. Актор — оператор deployment; source oracle — существующий приватный webhook config; target public seam — staging/Compose contract, потребляемый `BitrixOrderDocumentDelivery`; release value — worker получает валидные несекретные параметры и private token file без дублирования или утечки секрета.

Не входят реальные production secrets, изменение workforce sync, доменная логика публикации, схема БД, scheduler/retry policy, UI, merge и deployment.
