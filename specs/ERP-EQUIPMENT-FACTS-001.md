# ERP-EQUIPMENT-FACTS-001 — факты готовности и отгрузки оборудования

## Простыми словами

FMonitor 2 раз в час читает из 1С ERP три независимые даты оборудования и безопасно прикрепляет их к объекту по точному номеру заказа. Исправления и явные снятия сохраняют историю; ошибка источника или неоднозначный объект ничего не портят. Этот срез не меняет ход монтажного дела, не создаёт новую integration platform и не затрагивает `rapid-pilot`.

## Scope и actor

Actor записи — только системный `erp-equipment-facts-hourly-v1`. Единственный public state-changing seam — `EquipmentFactsApplication::execute(array $command): array`; adapter, CLI, scheduler, worker, HTTP и screen MUST NOT писать owned tables напрямую. Read seam карточки использует существующую authorization `objects.read`.

Не входят issues #13/#16/#30/#11/#135, email, redesign, общий integration framework, новый scheduler/outbox, изменение object identity или процесса монтажного дела. Production ERP access/deployment не выполняются в delivery.

## Legacy integration evidence

Read-only evidence `../fmonitor/application/controllers/Integration.php::shlz_prodorders` подтверждает прямой PDO/dblib transport к MS SQL BI schema `1c-erp`:

- order number: `BI_DЗаказКлиентаID.Номер`;
- readiness: `BI_Sпроф_СрокиХраненияГотовойПродукцииID.ДатаКомплектности`;
- full shipment: `BI_Sпроф_СрокиХраненияГотовойПродукцииID.ДатаПолнойОтгрузки`;
- first shipment: minimum valid `BI_DЭтапПроизводства2_2ID.ДатаОтгрузки` across non-deleted stages for order type `ЛифтовоеОборудование`; `NULL` and `0001-01-01` do not form a first-shipment date;
- stable mapping key: trimmed ERP order number equals `fm_maintable.zavnumber` exactly; no `regnumber`, numeric coercion or other fallback;
- legacy invocation endpoint exists, but its truncate/reload persistence is not a target. Owner decision 2026-09-15 sets native invocation hourly through the existing scheduler/worker without cron.

## Public command and receipt

Complete command keys SHALL be exactly:

```text
actor = {type: "system", id: "erp-equipment-facts-hourly-v1"}
kind = "complete"
runId = lowercase UUID
observedAtUtc = canonical UTC timestamp
records = list of {
  sourceOrderNumber: non-empty trimmed string, max 120 bytes,
  readinessDate: YYYY-MM-DD|null,
  firstShipmentDate: YYYY-MM-DD|null,
  fullShipmentDate: YYYY-MM-DD|null
}
```

Failed command keys SHALL be exactly `actor`, `kind="failed"`, `runId`, `observedAtUtc`, `reason`, where reason is `SOURCE_UNAVAILABLE` or `SOURCE_INVALID`. Both command kinds pass through the same authorization, validation, replay and transaction owner.

Record keys and order SHALL be exact. Duplicate `sourceOrderNumber` invalidates the entire batch. Dates SHALL be real Gregorian dates other than `0001-01-01`. A valid complete empty `records` list is a successful no-op and does not clear absent orders.

Success receipt keys SHALL be exactly `status`, `runId`, `matched`, `changed`, `unchanged`, `unmatched`, `ambiguous`, with status `completed`. Failed receipt keys SHALL be exactly `status="failed"`, `runId`, `reason`. Repeating any terminal runId with byte-equivalent canonical command returns its original immutable receipt, including a failed receipt. Same runId with any different kind, records, reason, actor or observed time returns `conflict/RUN_ID_CONFLICT` without mutation. A retry after a failed run MUST use a new runId; it may then complete normally. Rejected calls return `rejected` and one allowlisted reason: `UNAUTHORIZED`, `BATCH_INVALID`, or `RUN_ID_CONFLICT`. Persistence/connection failures throw only a safe integration exception to the composition; raw SQL/source/credentials MUST NOT appear in receipt, history or diagnostics.

## A — Initial import всех трёх дат

For a uniquely mapped order, a valid record with all three dates SHALL atomically create current projection values and exactly three append-only fact transitions from `NULL`.

## B — Только readiness

A record with readiness and two `NULL` shipment fields SHALL store readiness only. It MUST NOT infer either shipment fact.

## C — Readiness и первая отгрузка

A record with readiness and first shipment SHALL store both and leave full shipment `NULL`. Readiness MUST NOT be treated as first shipment.

## D — Полная отгрузка без первой

A record with full shipment and `NULL` first shipment SHALL store full shipment without synthesizing first shipment.

## E — Correction одной даты

When one value changes, current projection SHALL update only that fact and append exactly one transition containing old/new values. Other facts and their history SHALL remain unchanged.

## F — Идемпотентный повтор

Applying an identical material state SHALL create no fact transition. A new successful run may update projection provenance/freshness but MUST NOT duplicate fact history. Replay of the same run/input SHALL return the original immutable receipt and MUST NOT update timestamps.

## G — Technical failure и incomplete batch

Adapter SHALL produce a `complete` command only after both bounded ERP queries complete and every row validates. Query/transport/auth/schema/date/duplicate failure SHALL submit a `failed` command through the same `execute` owner, record a failed run with allowlisted `SOURCE_UNAVAILABLE` or `SOURCE_INVALID`, and preserve current projection, fact history and last-success metadata. It MUST NOT submit partial records. Any persistence failure while applying a complete batch SHALL roll back all projection, history, diagnostic, run and metadata writes from that batch.

## H — Missing/ambiguous mapping

Mapping SHALL use binary-exact equality after trimming the source value only. Zero or more than one matching `fm_maintable.zavnumber` rows creates no projection/history for that record. The completed receipt increments `unmatched` or `ambiguous`; a bounded diagnostic stores runId, HMAC-SHA-256 of source order number using server configuration, and reason `OBJECT_NOT_FOUND` or `OBJECT_AMBIGUOUS`, never the raw number or payload.

## I — Explicit clear и отсутствующий order

Within a present valid record, `NULL` is authoritative explicit clear. Clearing a non-null fact appends exactly one old-to-`NULL` transition. An order absent from the complete batch is outside its authoritative scope and its saved facts SHALL remain unchanged.

## J — Process isolation

Sync SHALL mutate only equipment projection, equipment fact history, equipment sync runs/metadata and safe diagnostics. `fm2_process_state`, process events, opening, progress, completion, checklist, assignment and original-document facts MUST remain byte-equivalent before and after initial/correction/clear/failure sync.

## K — Provenance, history и freshness

Current projection SHALL expose object id, the three nullable dates, source `1c_erp`, source-order HMAC, last successful runId and observed time. Each immutable transition SHALL expose object id, fact type `readiness|first_shipment|full_shipment`, old/new nullable values, source, source-order HMAC, runId and observed time. Global read status SHALL distinguish `never_synced`, `failed_before_success`, `fresh`, and `failed_after_success`, returning last successful time plus latest failed safe reason when applicable.

## L — Safe diagnostics

Unmatched/ambiguous diagnostics and job/CLI results SHALL contain only stable counters, run identity, allowlisted reason and order HMAC. They MUST NOT contain DB credentials, DSN, SQL, exception text, raw source rows or raw order number.

## ERP adapter contract

The bounded read-only adapter SHALL issue only the confirmed order aggregate and stage-shipment queries. It SHALL preserve independent values, compute first shipment only from valid stage dates, and produce one record per order aggregate. The order aggregate defines authoritative record presence: a stage row for a locally requested number absent from the successful order aggregate SHALL be ignored without creating a record, clear, diagnostic or failure; order rows without stages remain valid with `firstShipmentDate=null`.

## Hourly native invocation

The existing scheduler process SHALL enqueue one version-1 `erp.equipment-facts.sync` job per Europe/Moscow hourly slot with actor `erp-equipment-facts-hourly-v1`. Repeat ticks in a slot return the existing job; after downtime only the current slot is enqueued and skipped slots are counted. Existing worker dispatches the canonical sync composition. Adapter/owner failure is retryable. No cron or new scheduler/worker framework is introduced.

The public claim contract SHALL recognize exactly the registered type/version before any ERP access. A completed application receipt completes the job. `SOURCE_UNAVAILABLE` or `SOURCE_INVALID` SHALL first create the corresponding safe failed run through `EquipmentFactsApplication` and then return a retryable worker outcome. Unknown type/version is a non-retryable `CONFIGURATION_INVALID` rejection before source access. The first scheduler tick after process startup SHALL enqueue and execute the current slot when worker readiness is healthy. A documented manual command SHALL invoke the same canonical composition and return only the safe receipt.

## Operational bounded source contract

Before ERP access the canonical composition SHALL read the trim-normalized unique non-empty, non-`0` local `fm_maintable.zavnumber` candidate set. It SHALL send only those values to the ERP source in safely parameterized bounded chunks. Global ERP catalogue size, including more than 10 000 orders, MUST NOT determine completeness and the implementation MUST NOT materialize the global catalogue. Failure or invalid output from any chunk invalidates the whole batch before projection changes.

Every ERP object SHALL be fully qualified with `[1c-erp].[...]`; the configured default database SHALL be the actual legacy database and MUST NOT be assumed to be `1c-erp`. The order aggregate contract is `prod.Номер = sale.Номер`, `sroki.ЗаказКлиента = sale.Ссылка`, grouped by `sale.Номер`, with `MAX(sroki.ДатаКомплектности)` and `MAX(sroki.ДатаПолнойОтгрузки)` projected as canonical dates; an aggregate sentinel `0001-01-01` SHALL normalize to `NULL`. The shipment-stage contract is `prod.Ссылка = etap.Распоряжение`, `sale.Ссылка = prod.ДокументОснование`, `type.Ссылка = sale.shlz_ТипЗаказа`, with `etap.ПометкаУдаления = 0` and `type.Наименование = N'ЛифтовоеОборудование'`. `NULL` and `0001-01-01` SHALL be excluded before `MIN(etap.ДатаОтгрузки)`.

Owner decision 2026-09-21: для текущего изолированного pilot-контура legacy SQL Server transport SHALL использовать `Encrypt=yes;TrustServerCertificate=yes`. Владелец явно принимает отсутствие peer authentication как ограниченный pilot security exception после того, как обязательный pinned-certificate вариант сделал рабочую ERP-интеграцию недоступной без отсутствующего внешнего сертификата. Это решение не разрешает `Encrypt=no`, не расширяет доступ за пределы jobs services и MUST быть пересмотрено до более широкого production rollout при появлении корпоративного CA/server certificate.

Local ambiguity continues to produce zero arbitrary writes and a safe diagnostic. A present source record with `NULL` remains an explicit clear; a locally requested order absent from successful source output remains unchanged. Raw `zavnumber`, parameter values, SQL, DSN, credentials and source rows MUST NOT enter history, diagnostics, receipts, readiness, logs or review records.

## Pilot runtime configuration and readiness

Pilot ERP configuration SHALL come directly from ignored, restrictive-permission `.env` values `FMONITOR_ERP_HOST`, `FMONITOR_ERP_DATABASE`, `FMONITOR_ERP_USER`, `FMONITOR_ERP_PASSWORD` and `FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY`, plus explicitly allowlisted bounded timeout/row/chunk controls. `FMONITOR_ERP_PASSWORD_FILE` and `FMONITOR_ERP_EQUIPMENT_FACTS_HMAC_KEY_FILE` are no longer mandatory inputs. Missing/empty required values or out-of-range controls SHALL fail startup with `CONFIGURATION_INVALID` before a background restart-loop or ERP query. Structurally valid but rejected access is a safe failed run and retryable job.

Canonical templates and generated Dockerfile/Compose SHALL carry the same allowlisted values to every required jobs consumer. `.env.example` contains only safe placeholders; real values remain ignored and untracked.

One documented ordinary pilot startup SHALL state-preserving start db/php/web plus jobs-worker and jobs-scheduler. Process readiness SHALL be GREEN only while both jobs processes are present, correctly configured and fresh according to their observable heartbeat contract. Missing, crashed or stale worker/scheduler makes process readiness non-GREEN even if UI is healthy. Append-only dead-job and overdue-queue diagnostics remain visible through the separate operator health seam but MUST NOT make a repaired process permanently unable to become ready solely because historical dead rows are preserved. Ordinary update/recovery MUST preserve database volumes, sessions and artifacts; no reset or volume deletion belongs to the deployment path.

## Local pilot qualification

The authorized qualification target is `http://127.0.0.1:8093`. It SHALL prove healthy scheduler/worker, a safe completed ERP run receipt, populated projections and protected cards for objects 1226, 1427, 2238 and 2239, no ERP mapping for object 1318 with `zavnumber=0`, and no second job or duplicate fact history from a repeated current-hour slot or identical state. Existing stand data, sessions, artifacts and volumes SHALL remain present. Any missing live observation is `UNKNOWN` and blocks production/pilot readiness.

## Card/read behavior

The existing object card SHALL add one compact `equipmentFacts` block with exact keys `readinessDate`, `firstShipmentDate`, `fullShipmentDate`, `source`, `status`, and `lastSuccessfulSyncAt`. Before a projection exists the dates and successful time are `null`, source is `1c_erp`, and status is the global `never_synced` or `failed_before_success`. Missing individual facts display as unknown, not derived values. A `failed_after_success` status keeps stored dates visible. If owned projection reading itself fails, the exact block has three null dates, source `1c_erp`, status `unavailable`, and null successful time; only this block degrades and the rest of the authorized card remains readable and unmodified.

## Persistence, recovery и verification

Forward additive migration SHALL create current projection, append-only history, sync runs, singleton metadata and bounded diagnostics with exact constraints and indexes. Migration is idempotent, fails closed on incompatible same-name schema and joins the existing production migration catalogue/backup surface without changing harness policy. Focused disposable-DB tests exercise the public application/integration/read/job seams for A–L, schema recovery and concurrency. Full suite runs only in exact-source GitHub CI.
