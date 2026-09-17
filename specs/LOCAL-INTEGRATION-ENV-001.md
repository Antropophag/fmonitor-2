# LOCAL-INTEGRATION-ENV-001 — единый локальный `.env` для интеграций

Версия 0.1, 2026-09-17.

## Простыми словами

Оператор один раз копирует `.env.example` в `.env`, заполняет доступ к legacy DB и Bitrix и запускает обычные Make-команды. Внутренние приватные файлы система создаёт сама, проверяя всё до сети и БД и не раскрывая секреты. Этот срез не меняет правила импорта, синхронизации, product data или production secret-management.

## Actor, owner и public seams

- Actor: оператор локального или корпоративного Yii2-стенда.
- Owner decision: GitHub issue #149 и подтверждение переключения активной цели, 2026-09-17.
- Configuration input: корневой `.env`, созданный из `.env.example`.
- Public seams: `make import-legacy`, `make sync-workforce`, `make up-with-data`.
- Existing effect owners: `php bin/yii legacy-import/run --interactive=0` и `php bin/yii workforce-sync/run --interactive=0`.

Bootstrap является только configuration adapter. Он MUST NOT читать или менять product DB, legacy rows либо workforce/import history.

## Preconditions и общий вход

- Checkout содержит `.env.example`; `.env`, `.env.*` кроме шаблона и `.local/` исключены из Git и Docker build context.
- Для выбранного local Compose project уже применим контракт `YII2-LOCAL-QUICKSTART-001`.
- Каждое целевое присваивание находится в `.env` ровно один раз. Допустимы значения без кавычек либо целиком в одинарных/двойных кавычках; любой `$`/backtick shell expansion, command substitution и escape evaluation запрещены. Значение SHALL быть представимо существующему private-file consumer без изменения bytes; неоднозначный legacy password, который после снятия внешней пары содержит оба вида кавычек и не может быть lossless записан существующей grammar, отклоняется до публикации.
- `import-legacy` требует только legacy-набор, `sync-workforce` — только Bitrix-набор, `up-with-data` — оба набора.

## Acceptance contract

### A1 — Полный единый шаблон и независимые команды

`.env.example` SHALL содержать безопасные placeholders без реальных секретов для:

```text
FMONITOR_SOURCE_HOST
FMONITOR_SOURCE_PORT
FMONITOR_SOURCE_NAME
FMONITOR_SOURCE_USER
FMONITOR_SOURCE_PASSWORD
FMONITOR_MIGRATION_CUTOFF
FMONITOR_BITRIX_WEBHOOK_URL
FMONITOR_BITRIX_DEPARTMENT_IDS_JSON
```

`make import-legacy` и `make sync-workforce` MUST получать применимые значения только из `.env` и MUST NOT требовать от оператора ручного создания `.local/legacy-source.env` или `.local/bitrix-workforce.json`. Отдельная команда MUST NOT требовать набор другой интеграции. `make up-with-data` SHALL после успешного `make up` выполнить legacy import, затем workforce sync и объявить ready только после успеха всех этапов.

### A2 — Legacy preflight до effects

Legacy-набор SHALL принимать непустые host, database, user и password; port SHALL быть каноническим десятичным числом `1..65535`; cutoff SHALL быть пуст либо иметь формат `YYYY-MM-DD HH:MM:SS`. Полный применимый набор MUST быть разобран и проверен до Docker Compose, network либо source/target DB effects.

При успехе bootstrap SHALL сформировать exact private env document для существующего `FMONITOR_LEGACY_SOURCE_CONFIG`; import owner и его read-only source access остаются неизменными. Отсутствующее, повторное, пустое обязательное или неверное значение SHALL завершить seam ненулевым status с `LOCAL_INTEGRATION_CONFIG_INVALID`, не запуская downstream command.

### A3 — Bitrix preflight до effects

`FMONITOR_BITRIX_WEBHOOK_URL` SHALL быть абсолютным owner-compatible Bitrix REST webhook с exact lowercase scheme вида `https://<authority>/rest/<positive-user-id>/<non-empty-token>/` с допустимым эквивалентом завершающего slash. `FMONITOR_BITRIX_DEPARTMENT_IDS_JSON` SHALL быть непустым JSON list уникальных положительных integer ID; boolean, string, zero, negative, duplicate и non-list values запрещены.

При успехе bootstrap SHALL сформировать JSON только с `baseUrl` и `departments`, принимаемый существующим `WorkerConfiguration`; workforce sync owner остаётся неизменным. Ошибка parsing/validation SHALL вернуть `LOCAL_INTEGRATION_CONFIG_INVALID` до Docker Compose, network или DB effects.

### A4 — Атомарный приватный staging и replay

Bootstrap SHALL автоматически создать `.local` с mode `0700`. Каждый destination SHALL публиковаться через уникальный temporary regular file в том же каталоге, иметь mode `0600` до публикации и заменяться атомарным rename. После публикации bootstrap MUST проверить тип и exact permissions; symlink и unsafe existing path отклоняются fail closed.

Invalid input или write failure MUST не запускать consumer и MUST не выдавать partial file за актуальный. Параллельные подготовки MAY завершаться last-writer-wins, но каждый наблюдаемый destination MUST быть одним полным валидным документом. Temporary files SHALL очищаться после успеха и ожидаемой ошибки.

Повтор с тем же `.env` SHALL быть безопасным replay. Повтор после корректного изменения `.env` SHALL передать consumer новый полный snapshot без `make reset`, удаления volumes или изменения ранее сохранённых product facts.

### A5 — Secret boundary

Legacy password, полный Bitrix webhook/token и содержимое private documents MUST NOT попадать в argv downstream processes, stdout/stderr, tracked files, `docker compose config` либо image layers. Make/bootstrap SHALL не включать shell tracing и SHALL передавать consumers только абсолютные private-file paths через существующие file-based contracts и read-only mounts.

На успехе и ошибке диагностика MAY содержать стабильный reason code и имя параметра, но MUST NOT содержать введённое значение, stack trace или private document. Старый валидный destination при новом invalid `.env` MUST NOT служить обходом preflight: consumer не запускается.

### A6 — Existing owners, history и documentation

Bootstrap MUST не создавать альтернативный owner доменных фактов. Read-only legacy source access, import/sync idempotency, workforce append-only history, DB schema, migrations, backup/restore и production secret-management остаются прежними.

Операторская документация SHALL описывать `.env.example` → `.env` → `make up-with-data` как один актуальный путь, отдельные `make import-legacy`/`make sync-workforce`, безопасное повторное применение изменённого `.env` и внутренний характер автоматически создаваемых private files. Она MUST NOT инструктировать оператора создавать эти files вручную.

## Rejected cases и observable reasons

- Нет `.env`, целевой ключ отсутствует/повторён, assignment синтаксически неверен, кавычка не закрыта, присутствует `$`/backtick shell expression либо значение невозможно lossless представить existing consumer → `LOCAL_INTEGRATION_CONFIG_INVALID`, zero downstream effects.
- Legacy host/database/user/password пуст, port неканоничен либо вне диапазона, cutoff имеет иной формат → тот же безопасный reason, zero downstream effects.
- Bitrix URL не HTTPS/не соответствует REST webhook либо departments невалиден → тот же безопасный reason, zero downstream effects.
- `.local`/destination является symlink, не regular/private, permissions нельзя установить/подтвердить или atomic publication невозможна → ненулевой безопасный failure, consumer не запускается.
- `make up`, import owner или sync owner завершился ошибкой → `up-with-data` прекращается, не объявляет ready и не выполняет последующие этапы после failed prerequisite.

## Authorization, audit и concurrency

Оператор, запускающий Make target, авторизует effects только выбранного local project; production authorization не подразумевается. Git history и delivery evidence являются аудитом изменения bootstrap; private values и полные logs в repository не сохраняются. Runtime staging является заменяемой конфигурацией, а не append-only product fact. Конкурентные staging attempts обязаны сохранять atomic visibility по A4; сериализация product owners этим контрактом не меняется.

## Independently determined examples

1. Legacy input `host=legacy-db.internal`, port `3306`, database `fmonitor`, user `reader`, непустой password и cutoff `2026-09-17 00:00:00` создаёт private env file `0600`; downstream witness видит только его path, не password в argv/output.
2. Port `03306`, `0`, `65536` или `abc` отклоняется до downstream witness; прежний destination не используется.
3. Webhook `https://portal.example/rest/7/SECRET_TOKEN/` и departments `[72,71]` создают private JSON с теми же IDs; ни `SECRET_TOKEN`, ни полный URL не появляются в process/output/Compose witness.
4. Departments `[]`, `[71,71]`, `[0]`, `[true]`, `["71"]` и объект `{}` отклоняются до downstream witness.
5. Два concurrent valid writers с различными canary snapshots оставляют destination, равный целиком одному из snapshots; JSON/env document остаётся parseable, mode `0600`, temporary files отсутствуют.
6. После успешного запуска оператор меняет webhook и повторяет `make sync-workforce`: новый snapshot передаётся existing owner, volumes и прежняя workforce history не удаляются.

## Explicit non-goals

- Изменение отбора объектов/сотрудников, import/sync algorithms, scheduler или application owners.
- Хранение secret values в repository, Compose environment или image.
- Production deployment/secrets redesign, DB/schema migration, backup/restore либо автоматический reset.
- Merge, deploy или изменение GitHub settings в рамках delivery №149.
