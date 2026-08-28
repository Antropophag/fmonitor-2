# BITRIX-WORKFORCE-HISTORY-001 — синхронизировать текущий кадровый снимок из Bitrix с честной историей наблюдений

- Статус: `EPIC — NOT EXECUTABLE — SUPERSEDED FOR GATE 2`
- Версия: `0.1`
- Дата: `2026-08-28`
- Актор: системный оператор планового задания FMonitor 2.0
- Публичный production seam: `BitrixWorkforceSync.run(runId)`
- Публичный seam чтения: `WorkforceCatalog.findInstallerSnapshot(employeeNumber)` и `WorkforceCatalog.getSyncFreshness()`
- Deployment seam (decomposed): `BITRIX-WORKFORCE-SCHEMA-001`

> Этот документ сохраняет согласованные продуктовые решения и границы будущей интеграции, но объединяет transport, normalization, publication, history, catalog reading и deployment migration. Он не является единичным исполняемым срезом и не разрешает Gate 2. Для Gate 2 schema migration его заменяет `BITRIX-WORKFORCE-SCHEMA-001 v0.1`.

## 1. Цель и граница среза

Один запуск получает **полный** текущий кадровый снимок монтажников непосредственно из Bitrix24, проверяет все страницы и только затем одной транзакцией публикует новую current projection, append-only observations и audit запуска. Кадровая система учёта — `1c_zup`; прямой канал доставки в FMonitor — `bitrix24`. Legacy FMonitor и `fm_installators` не читаются и не изменяются.

Монтажники не являются пользователями FMonitor. Инженеры строительного контроля остаются пользователями FMonitor и проверяются существующим role/capability-контрактом; этот sync не создаёт им workforce-записи, роли или права.

Срез включает production polling, хранение истории и production-чтение, необходимое последующим UI/domain-проверкам. Расписание, экран справочника, уведомления об исключениях и изменение business-кодов `prepare/open` — отдельные срезы.

## 2. Конфигурация и безопасный transport

Production sync получает только из environment/secret store:

| Имя | Контракт |
|---|---|
| `FMONITOR_BITRIX_BASE_URL` | абсолютный `https` URL портала без query, fragment и credential |
| `FMONITOR_BITRIX_WEBHOOK_TOKEN` | непустой secret; никогда не возвращается и не логируется |
| `FMONITOR_BITRIX_TARGET_DEPARTMENT_IDS` | непустой, отсортированный список уникальных положительных decimal ID |
| `FMONITOR_BITRIX_CONNECT_TIMEOUT_SECONDS` | integer `1..10`; пример использует `3` |
| `FMONITOR_BITRIX_REQUEST_TIMEOUT_SECONDS` | integer `1..30`; пример использует `10` |

Credential имеет read-only `user_basic`; `user.userfield` не требуется. TLS peer и hostname verification обязательны, redirects запрещены. Запрос допускает максимум 3 попытки только для connect timeout, HTTP `429` и HTTP `502/503/504`; задержки детерминированно ограничены `1s`, `2s` плюс injected jitter `0..250ms`. Schema/JSON/authorization errors не retry-ятся. Ни URL с token, ни request/response body, ни ФИО/email/табельные номера не попадают в exception, stdout, application/security/process audit.

## 3. Точный Bitrix request и allow-list

Каждая страница — `POST` метода `user.get` с:

```text
order[ID] = ASC
filter[UF_DEPARTMENT] = <каждый configured target department ID>
start = 0, 50, 100, ...
select = [ID, ACTIVE, LAST_NAME, NAME, SECOND_NAME, WORK_POSITION,
          EMAIL, UF_XING, UF_DEPARTMENT, UF_EMPLOYMENT_DATE]
```

Фильтр `ACTIVE` намеренно отсутствует. Page size — Bitrix maximum `50`. Response envelope допускает только `result` (array), `total` (non-negative integer), optional `next` (non-negative integer) и optional `time` (object, игнорируется). Employee object допускает ровно перечисленные `select` keys; отсутствие key, неизвестный key, неверный JSON/type или Bitrix `error` проваливает весь run как `DELIVERY_SCHEMA_INVALID`. Порядок keys не значим.

Страницы читаются, пока количество уникальных `ID` не равно first-page `total`. До этого `result=[]`, отсутствие ожидаемого `next`, изменение `total`, `next <= start`, `next` не кратный 50 либо повтор start — ошибка `DELIVERY_PAGINATION_INVALID`. После достижения `total` `next` должен отсутствовать. Каждая запись обязана содержать хотя бы один configured department ID; иначе `DELIVERY_SCOPE_INVALID`.

## 4. Точное нормализованное отображение личности

Для каждого employee:

| Нормализованное поле | Правило без fallback, кроме указанного |
|---|---|
| `delivery_person_id` | decimal string из `ID`, canonical без ведущих нулей, значение `>=1` |
| `employee_number` | сначала regex `/^tab([1-9][0-9]*)@/Di` над `EMAIL`; если нет match — exact decimal `UF_XING`; canonical positive decimal без ведущих нулей |
| `full_name` | непустые `LAST_NAME`, `NAME`, `SECOND_NAME` соединить одним ASCII space в этом порядке; каждую часть trim; пустые middle parts пропустить |
| `position` | trim `WORK_POSITION`, результат непустой |
| `employment_status` | boolean `ACTIVE=true` → `employed`; `false` → `dismissed`; строки `Y/N`, `1/0` запрещены |
| `employed_from` | непустой `UF_EMPLOYMENT_DATE` обязан быть strict `YYYY-MM-DD`; пустой/null → `null` |
| `dismissal_effective_at` | всегда `null` в v0.1: payload не содержит утверждённой ZUP-origin даты |
| `authority_system` | literal `1c_zup` |
| `delivery_system` | literal `bitrix24` |
| `source_modified_at` | `null` |

Email хранить запрещено. Конфликт email-derived number и непустого `UF_XING` — `IDENTITY_CONFLICT`, даже если оба canonical values отличаются только ведущими нулями после исходного parse. Отсутствующая/некорректная identity — `IDENTITY_INVALID`.

После нормализации весь набор сортируется numeric `delivery_person_id`. Дубли `(delivery_system, delivery_person_id)` или `employee_number` проваливают весь run с `IDENTITY_CONFLICT`; выбрать одну запись нельзя. Checksum — lowercase SHA-256 UTF-8 JSON array в указанном порядке; objects имеют keys ровно в порядке таблицы выше от `delivery_person_id` до `source_modified_at`, JSON flags `JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES`, без whitespace/newline.

## 5. Материальное изменение и честная история

Первое успешное появление создаёт observation. Далее observation создаётся только при изменении одного из: `employee_number`, `full_name`, `position`, `employment_status`, `employed_from`, `dismissal_effective_at`, `authority_system`, `delivery_system`, `source_modified_at`, `reconciliation_state`.

`observed_at` — один server timestamp, зафиксированный **после полной проверки всех страниц и до DB transaction**; он одинаков для всех фактов запуска. Повтор идентичного normalized set не создаёт observations, но создаёт completed sync-run audit и обновляет freshness current projection.

`first_observed_dismissed_at` — minimum `observed_at` сохранённого observation со status `dismissed` для стабильного `(delivery_system, delivery_person_id)`. Оно не очищается при `dismissed → employed` и никогда не заполняет `dismissal_effective_at`. В v0.1 `dismissal_effective_at=null` и `dismissal_time_quality=observed_only`; `effective_from_source` запрещено.

Работник из прежней projection, отсутствующий в полностью проверенном новом наборе, получает observation/current `reconciliation_state=missing_from_delivery`, сохраняя последнее доставленное имя/status. Это не `dismissed`; `first_observed_dismissed_at` не меняется. Повтор отсутствия material change не создаёт.

## 6. Atomic publication, concurrency и неизвестный commit

`runId` — caller-generated lowercase UUIDv4. До HTTP создаётся audit row `started`; повтор того же ID возвращает сохранённый terminal result, а `started` моложе 30 минут даёт `SYNC_ALREADY_RUNNING`. MariaDB named lock на `{prefix}bitrix_workforce_sync` допускает один run; lock contention даёт `SYNC_ALREADY_RUNNING` без Bitrix call.

После fetch/validation sync начинает одну InnoDB transaction и в порядке:

1. lock-ит единственную metadata row;
2. повторно убеждается, что base successful run не изменился;
3. вставляет только material observations;
4. upsert-ит все delivered current rows и missing projections;
5. устанавливает каждому current row `last_successful_sync_run_id`, `last_successful_sync_at=observed_at`;
6. переводит audit row в `completed` с counts/checksum/completed_at;
7. commit.

Любая transport/API/pagination/schema/identity/DB ошибка оставляет прежние observations/current/freshness без изменений; audit получает `failed`, redacted reason и `completed_at`. Частичная projection и `TRUNCATE` запрещены.

Если commit вернул ошибку/connection loss и результат commit неизвестен, seam возвращает `SYNC_COMMIT_UNKNOWN`, не выполняет автоматический повтор и не помечает run failed. Новый connection обязан разрешить исход по `runId`: terminal `completed` возвращается как успех; всё ещё `started` переводится в `failed` с reason `COMMIT_NOT_APPLIED` только после доказательства отсутствия observations/current links этого run. Недоказанное состояние остаётся `started` и блокирует следующий run.

## 7. Schema migration v5

Normative executable migration behavior, exact v5 catalog and table shape, compatible partial recovery, FK-safe creation order and exact results are owned only by `BITRIX-WORKFORCE-SCHEMA-001 v0.1`. The decisions retained here are: v5 is additive from compatible v1–v4, preserves existing catalog rows, creates run/observation/metadata storage, does not invent delivery identity or sync success, and has no destructive down migration.

## 8. Production catalog and freshness behavior

`findInstallerSnapshot(employeeNumber)` remains a parameterized exact positive-number lookup and returns existing snapshot keys plus:

```text
deliveryPersonId, authoritySystem, deliverySystem,
dismissalEffectiveAt, firstObservedDismissedAt, dismissalTimeQuality,
reconciliationState, lastSuccessfulSyncAt
```

It returns `null` only for absent identity. It does not disguise `dismissed`, `missing_from_delivery`, unknown employment start or stale delivery as absence; domain/UI callers can distinguish them. Existing `employedTo` maps exact `dismissal_effective_at`, never first observation.

`getSyncFreshness()` returns exact metadata `{lastSuccessfulRunId,lastSuccessfulAt}` or both `null` before first success. This spec does not invent a freshness threshold: a later business slice must define when age blocks prepare/open. Until that slice, inherited `ORDER-PREPARE-003` still governs status and known employment period; synced rows with unknown `employed_from` remain honestly unknown rather than receiving a fabricated date.

## 9. Исполняемые примеры

### A. First full success

`runId=018f47ba-2f6d-4f80-8f42-0b37657fd111`, started `2026-08-28T09:00:00+03:00`, validated at `observed_at=2026-08-28T09:00:04+03:00`. Page 0 says `total=2,next` absent only after page collection and contains:

```json
[{"ID":"501","ACTIVE":true,"LAST_NAME":"Иванов","NAME":"Иван","SECOND_NAME":"Иванович","WORK_POSITION":"Электромеханик по лифтам","EMAIL":"tab1042@example.invalid","UF_XING":"1042","UF_DEPARTMENT":[71],"UF_EMPLOYMENT_DATE":null},{"ID":"502","ACTIVE":false,"LAST_NAME":"Сидоров","NAME":"Семён","SECOND_NAME":"","WORK_POSITION":"Монтажник","EMAIL":"other@example.invalid","UF_XING":"1043","UF_DEPARTMENT":[71],"UF_EMPLOYMENT_DATE":null}]
```

Exact result:

```text
accepted=true; runId=...d111; status=completed; pages=1; delivered=2;
materialChanges=2; missing=0; observedAt=2026-08-28T09:00:04+03:00
normalizedChecksum=d9169e985d40f14381f905c337f24ff37c909f17d68d1e579d66bc079ebb3061
```

There are exactly two observations. `1042` is `employed/delivered`, dismissal fields null, quality `observed_only`. `1043` is `dismissed/delivered`, `first_observed_dismissed_at=2026-08-28T09:00:04+03:00`, `dismissal_effective_at=null`, `employedTo=null`. Both current rows and freshness refer to run `...d111`.

### B. Idempotent repeat and missing delivery

Run `...d112` at `2026-08-28T10:00:04+03:00` receives the same two records: `materialChanges=0`, observation count remains 2, both current freshness values and metadata advance to `...d112`.

Run `...d113` at `2026-08-28T11:00:04+03:00` receives only unchanged `501` with `total=1`: exact result has `delivered=1,materialChanges=1,missing=1`. One new observation is for person `502` with `reconciliation_state=missing_from_delivery`, status still `dismissed`, and first observation remains `09:00:04`. It cannot be selected as employed and is not reported as newly dismissed.

### C. Fail closed

Run `...d114` page 0 says `total=51,next=50`; page 50 times out on all three allowed attempts. Exact result: `accepted=false,status=failed,reason=DELIVERY_TRANSPORT_FAILED,pages=1`. Current rows, observations, metadata and last-success timestamps remain byte-for-byte equal to post-run `...d113`; only redacted run audit is added. A duplicate employee number, unknown response key, TLS verification failure, or `total` drift has the same no-publication guarantee with its exact reason from sections 2–4.

## 10. Audit, authorization and observability

Sync is machine-to-machine and cannot be invoked through an FMonitor user/process command. DB principal for runtime sync may insert observations/runs, update current/metadata and may not update/delete observations. Read-only UI/process principal cannot write sync tables.

Operational metrics/logs contain `runId`, status, timings, counts, checksum and enumerated reason only. Process event table receives no per-worker event. Secrets, URL, payload, name, email, employee number, department ID and raw exception are redacted.

## 11. Rejected cases and exact reasons

`SYNC_ALREADY_RUNNING`, `DELIVERY_TRANSPORT_FAILED`, `DELIVERY_AUTHORIZATION_FAILED`, `DELIVERY_API_FAILED`, `DELIVERY_SCHEMA_INVALID`, `DELIVERY_PAGINATION_INVALID`, `DELIVERY_SCOPE_INVALID`, `IDENTITY_INVALID`, `IDENTITY_CONFLICT`, `DATABASE_PUBLICATION_FAILED`, `SYNC_COMMIT_UNKNOWN`, `COMMIT_NOT_APPLIED`, `SCHEMA_MIGRATION_CONFLICT` are the complete new reason set. Every failure is terminal and fail closed except unresolved `SYNC_COMMIT_UNKNOWN`.

## 12. Follow-up slices

- `BITRIX-WORKFORCE-SCHEMA-001`: **approved Gate 1 schema-only slice**; the only slice currently permitted to enter Gate 2.
- `BITRIX-WORKFORCE-DELIVERY-001`: HTTPS Bitrix polling, pagination, allow-list validation and redacted transport failures.
- `BITRIX-WORKFORCE-NORMALIZATION-001`: identity/name/status normalization, duplicate rejection and deterministic checksum.
- `BITRIX-WORKFORCE-PUBLICATION-001`: run audit, locking, atomic current/history publication, missing reconciliation and unknown-commit recovery.
- `BITRIX-WORKFORCE-CATALOG-READ-001`: expanded catalog snapshot and freshness read seams.
- `WORKFORCE-FRESHNESS-POLICY-001`: threshold, prepare/open business codes and user copy.
- `WORKFORCE-DIRECTORY-UI-001`: list/detail, honest dismissal wording and exception display.
- `WORKFORCE-EMPLOYMENT-PERIOD-001`: product decision for unknown `employed_from`; no date may be inferred from first delivery.
- `BITRIX-WORKFORCE-SCHEDULER-001`: cadence, health alert and recovery tooling.
- `BITRIX-ZUP-DISMISSAL-DATE-001`: only after an approved, documented ZUP-origin field exists.
- Production migration runner v5 composition and least-privilege credential provisioning/legacy secret rotation.

## 13. Решения и доказательства

- `PRODUCT.md`, `CONTEXT.md`: installers are external workers; control engineers are FMonitor users; append-only facts and integrated workforce provenance.
- `docs/bitrix-workforce-integration-research.md`: verified current payload, lack of dismissal date/update event, polling/security constraints.
- `WORKFORCE-CATALOG-001`: existing production table/delegate and immutable order snapshot boundary.
- `ORDER-PREPARE-003`, `OPEN-INSTALLATION-001`: inherited domain checks; this sync does not silently weaken them.

## 14. Историческое решение и текущий Gate status

- Владелец продукта: пользователь проекта
- Дата: `2026-08-28`
- Историческое решение: продуктовые решения о `1С ЗУП` как authority, Bitrix24 как direct delivery, исключении legacy FMonitor и разграничении effective date/first observation сохранены.
- Текущий verdict: `EPIC — NOT EXECUTABLE — SUPERSEDED FOR GATE 2`.
- Причина: документ объединяет несколько публичных seams и red-green циклов; прежняя отметка `APPROVED` не является действительным одобрением исполняемого Gate 1 slice.

Gate 2 по этому epic запрещён. Gate 2 разрешён только отдельным спецификациям из decomposition list после их собственного `APPROVED`; сейчас это `BITRIX-WORKFORCE-SCHEMA-001 v0.1`.
