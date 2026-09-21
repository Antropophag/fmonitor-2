## Why

Merged delivery issue #12 создаёт ERP projection и hourly job, но pilot-контур на `main` не способен автоматически выполнить эту job: worker не принимает её versioned claim, ERP adapter не соответствует реальной legacy-схеме и пытается материализовать глобальный каталог с лимитом 10 000. Одновременно штатный запуск и readiness допускают ложный GREEN без scheduler/worker, поэтому перед pilot deployment требуется operational slice от реального read-only источника до карточки объекта.

## What Changes

- Исправить versioned claim/handler contract `erp.equipment-facts.sync` и доказать executable regression через публичный jobs seam: успешный run завершает job, `SOURCE_UNAVAILABLE` создаёт безопасный failed run и остаётся retryable, повторный tick текущего часа не создаёт дубль.
- Заменить глобальный ERP snapshot на параметризованные chunked-запросы только по уникальным ненулевым локальным `fm_maintable.zavnumber`; использовать реальные fully-qualified `[1c-erp].[...]` таблицы и подтверждённые join/filter semantics legacy `Integration.php::shlz_prodorders`.
- Сохранить authoritative semantics: nullable значения присутствующей записи очищают факт, отсутствие заказа не очищает, неоднозначное local mapping не пишет projection, failure любого chunk не меняет projection, raw `zavnumber` не попадает в history/diagnostics.
- **BREAKING** для pilot runtime configuration: заменить обязательные password/HMAC files прямыми `.env`-значениями `FMONITOR_ERP_PASSWORD` и `FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY`; провести все ERP параметры через allowlist/validation и canonical/generated Compose без реальных секретов в Git или evidence.
- Сделать обычный owner-approved pilot startup полным operational contour с web/db/php, jobs-worker и jobs-scheduler; readiness SHALL быть неуспешным при отсутствующем или неработоспособном jobs-контуре и configuration SHALL fail-fast до restart-loop.
- Квалифицировать state-preserving локальный стенд `127.0.0.1:8093`: применить локальный `.env` с ограниченными правами, не удаляя volumes/БД/sessions/artifacts, дождаться successful hourly ERP run, проверить projections/card для 1226, 1427, 2238, 2239 и same-slot/history idempotency.
- Не выполнять merge или внешний deployment без отдельного подтверждения владельца; не переносить legacy controller/domain logic, не печатать credentials/DSN/SQL/source rows и не расширять sync на нерелевантные ERP orders.

## Capabilities

### New Capabilities

- `erp-equipment-facts-operations`: Production/pilot operational contract для bounded ERP source query, versioned durable-job execution, runtime configuration, complete startup/readiness и state-preserving live qualification.

### Modified Capabilities

Нет: исходная capability `erp-equipment-facts` ещё не архивирована в `openspec/specs`; этот follow-up уточняет отдельную operational capability и нормативно связывается с `specs/ERP-EQUIPMENT-FACTS-001.md`.

## Impact

Затрагиваются ERP adapter и composition, `EquipmentFactsApplication`, jobs claim/handler/worker и hourly scheduler, runtime env validation, `.env.example`, canonical и generated Compose/Dockerfile parity, Make/startup targets, health/readiness, migration/recovery inventories, object-card projection/view и focused tests. Source oracle — read-only `../fmonitor/application/controllers/Integration.php::shlz_prodorders`; target public seams — canonical jobs scheduler/worker, application sync seam, pilot readiness и `/pilot/objects/<id>`. Release value — реально автоматическая hourly ERP-синхронизация пилотного стенда без потери существующих данных.
