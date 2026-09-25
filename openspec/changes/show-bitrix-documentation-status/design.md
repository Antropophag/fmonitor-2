## Context

См. `proposal.md`. Очередь сохраняет текущую строку в `fm2_jobs` и append-only lifecycle в `fm2_job_events`. Claim очищает прежний `failure_code/result_json`, а terminal settlement очищает lease-поля, поэтому одна текущая строка не является историей попыток. Нормативное поведение задано delta spec и `specs/BITRIX-DOCUMENTATION-INTEGRATION-STATUS-001.md`.

## Goals / Non-Goals

**Goals:**

- Локальный Yii read adapter возвращает небольшой typed/allowlisted view model и владеет всеми queries документного блока.
- Controller сохраняет существующую canonical authorization и соединяет четыре независимых page parameters.
- View получает ясную Operate-композицию: документный статус как приоритетный summary, далее существующие источники и диагностические списки; локальный partial инкапсулирует документный блок.
- COUNT/LIMIT происходят после фильтра по job type; outcomes выбираются только для одной bounded страницы.

**Non-Goals:**

- Любая запись, retry, producer/handler detail, network/config probe, schema/index, route/navigation, общий asset или трактовка configured/disabled.
- Полный #251, изменение соседних интеграций и доказательство работы реального Bitrix.

## Decisions

1. Persistence owner — существующий `Jobs`: отдельный `MariaDbBitrixDocumentationStatusRead` использует переданный application-owned `yii\db\Connection` и `yii\db\Query`, не создаёт connection lifecycle и не зависит от command handlers/registry/scheduler. Yii controller только компонует этот read adapter. Размещение SQL adapter в `YiiRuntime/Controllers` отвергнуто после exact-source CI: canonical architecture ratchet разрешает новый SQL над jobs-таблицами только именованному `MariaDb*` owner в `app/Jobs`.
2. Attempt identity — `(job_id, attempt)` из `claimed`; начало — `claimed.occurred_at_utc`. Outcome коррелируется только с последующими `completed`, `retry_scheduled`, `dead` или `expired` events той же attempt. Failure code извлекается только из allowlisted shape `details_json.failureCode`, иначе показывается общее неизвестное состояние. Текущая `failure_code` используется лишь для текущего terminal queue status, не для прошлой attempt.
3. Подтверждённый успех требует одновременно `completed` event и JSON-valid `fm2_jobs.result_json.published` как non-negative integer. Query выбирает newest такой receipt; corrupted newer rows не превращаются в ноль. Это строже, чем доверять status=completed, и соответствует producer contract.
4. Queue summary агрегирует counts по статусам нужного job type и классифицирует newest current job: ready/attempt=0 — queued; ready/attempt>0 с retry event — retry wait; leased с будущим lease expiry — running; leased с просроченным/невалидным lease — unknown; dead — terminal failure; completed — completed. Время сравнения приходит от БД/current UTC только для актуальности lease и не называется durable attempt time.
5. History query сначала COUNT + claimed page с фильтром по joined job type, затем одним bounded query читает events только для выбранных job/attempt pairs. Стабильная сортировка — timestamp/event id; все остальные events и JSON остаются в БД.
6. UI не вводит новый visual world и общий CSS. Локальная семантическая разметка и существующие shlz-ui contracts формируют generous section rhythm, summary grid и contained-scroll tables. На narrow summary складывается в одну колонку; DOM/focus order совпадает с reading order. Декоративные эффекты и новые действия отсутствуют.
7. Persistence owner — существующий Jobs module; migration/backup/restore/readiness не меняются. `rapid-pilot` adapter не нужен. Architecture check должен подтвердить `MariaDb*` ownership, отсутствие foreign-table/global-call violations и command dependencies; HTTP qualification обязателен из-за изменения controller.

## Risks / Trade-offs

- [OFFSET может дорожать на очень большой истории] → fixed page size и фильтрация до LIMIT; смена на cursor требует отдельного public contract.
- [Одновременный writer может завершить attempt между summary и history reads] → GET показывает только committed facts каждого bounded query и не держит writer lock; следующий refresh сойдётся.
- [Старые/ручные rows нарушают event/result contract] → fail-closed unknown/unavailable вместо синтезированного успеха.
- [Локальная view-разметка ограничена без общего CSS] → использовать существующие layout/table/status contracts и минимальные локально scoped styles только в partial, без asset changes.

## Migration Plan

Схема не меняется. После focused checks, planner-required reviews и exact-source CI PR можно объединить обычным порядком. Rollback удаляет adapter/partial и возвращает controller/view; durable очередь не меняется.
