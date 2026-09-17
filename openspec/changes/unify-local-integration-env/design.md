## Context

См. `proposal.md` и `specs/local-integration-env/spec.md`. На актуальном `main` Make targets уже передают runtime consumers два приватных файла и проверяют их через `tools/delivery/local-integration-config`, но сами файлы оператор должен создать вручную. Общий `.env` уже разбирается `tools/delivery/local-runtime-env`; секреты не должны попадать в Compose interpolation/output или argv.

## Goals / Non-Goals

**Goals:**

- Расширить существующую delivery boundary так, чтобы она материализовала приватные adapter-файлы из уже проверенного `.env` непосредственно перед import/sync.
- Оставить private-file contracts Yii2 consumers неизменными.
- Обеспечить детерминированный preflight, атомарную замену и безопасные permissions.

**Non-Goals:**

- Не менять application owners, DB schema, migration classification, Bitrix delivery semantics, scheduler или production secret-management.
- Не добавлять secrets в Compose service environment и не менять backup/restore.
- Не использовать `rapid-pilot` как implementation destination.

## Decisions

### 1. Один локальный bootstrap владеет parsing, validation и staging

Расширить `tools/delivery/local-integration-config` режимом подготовки из корневого `.env` через существующий безопасный parser contract, а Make targets вызывать его до Compose. `tools/delivery/local-runtime-env` SHALL распознавать integration keys как допустимые записи единого файла, но не экспортировать их в запускаемое окружение и не подставлять через `@env:*`; integration bootstrap остаётся единственным owner их чтения. Это сохраняет один owner локальной integration-конфигурации и не дублирует правила в Make/PHP. Альтернатива — передать все значения как Compose environment — отвергнута из-за утечки в `docker compose config` и process environment.

### 2. Сохранить file-based adapter boundary

Legacy consumer продолжает получать `FMONITOR_LEGACY_SOURCE_CONFIG`, workforce consumer — `FMONITOR_BITRIX_CONFIG`; Make монтирует автоматически созданные файлы read-only. Альтернатива — менять Yii2 consumers на прямой `.env` — расширила бы security surface и затронула production contracts без необходимости.

### 3. Сначала полная валидация, затем атомарная публикация

Bootstrap разбирает применимый набор в памяти, валидирует весь документ, создаёт `.local` с `0700`, пишет временный файл в том же каталоге с `0600`, синхронизирует/закрывает и заменяет destination атомарным rename. До успешной публикации Compose/DB/network не запускаются. Временные файлы очищаются. При конкуренции последний полностью валидный rename допустим: смешанного содержимого consumer не увидит.

### 4. Безопасная диагностика — только стабильные коды/имена полей

Ошибки указывают интеграцию и имя неверного параметра либо стабильную общую причину, но не его значение. Bootstrap не печатает сформированные документы. Make не включает shell tracing. Focused tests используют canary secrets и проверяют argv/output/config/build-context boundaries.

### 5. Тестируемый публичный seam

Нормативный контракт будет закреплён стабильной спецификацией `LOCAL-INTEGRATION-ENV-001`; RED tests вызывают Make/bootstrap boundary в изолированном временном checkout/state, подменяя downstream command witness, чтобы доказать отсутствие внешних effects при invalid input, contents/modes при valid input, повторное применение и redaction. DB-поведение не переопределяется; существующие import/sync tests остаются regression evidence.

### 6. Архитектурные и операционные границы

Owning module — delivery tooling; allowed dependencies — стандартные Python/shell/filesystem primitives и существующий local env parser contract. Persistence owner отсутствует: staging-файлы являются заменяемой runtime-конфигурацией, не product facts. `rapid-pilot` не меняется. Verification planner должен учесть `.env.example`, Make, delivery tool, docs и новые focused tests; schema frontier, fixtures/table inventories, deployment, backup/restore и migrations неприменимы, потому что DB/schema/runtime image contract не меняются.

## Risks / Trade-offs

- [Сбой между заменой файла и запуском consumer оставляет новую валидную конфигурацию] → Это безопасный desired state; следующий запуск повторно материализует `.env`.
- [Старый валидный файл существует при новом invalid `.env`] → Consumer не запускается; старый файл не считается обходом preflight.
- [Одновременные команды используют разные snapshots `.env`] → Атомарная публикация исключает partial file; каждый запуск валидирует собственный snapshot, а operator-level serialization не требуется для product history.
- [Permissions зависят от umask/platform] → Явно задавать directory/file mode и fail closed, если post-write verification не подтверждает boundary.

## Migration Plan

1. Добавить новые placeholders в `.env.example`; существующие `.env` получают параметры вручную при следующем использовании соответствующей integration-команды.
2. Перевести Make targets на автоматическую подготовку; ранее созданные корректные `.local` файлы будут атомарно заменены данными из `.env`.
3. Rollback возвращает прежние Make/bootstrap версии; product data и volumes не меняются. Старые приватные файлы остаются локальными и исключёнными из Git, но операторский manual-file путь не документируется после поставки.
