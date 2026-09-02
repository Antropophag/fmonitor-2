## Context

См. `proposal.md` и delta spec. Mutation route существует внутри rapid-pilot и вызывается после local-auth, но до общего production request/origin policy. Он одновременно выполняет HTTP coordination, проверки, SQL и runtime schema creation. Текущий verifier напрямую рендерит проекции с заранее вставленными facts, поэтому не чувствителен к поломке самой команды.

Этот change создаёт characterization harness, а не целевой completion-модуль. Владельцем будущей state-changing capability рассматривается bounded application module `InstallationCompletion` с seam-кандидатом `recordPtoAct`; кандидат не становится утверждённым API до GRILL-001.

## Goals / Non-Goals

**Goals:**

- проверить реальный public HTTP seam от local-auth/session и CSRF до ответа и сохранённого факта;
- получить минимальный accepted RED, затем расширить матрицу рисков отдельными RED/GREEN gates;
- обеспечить изоляцию DB и детерминированные проверки при живом Moscow clock;
- сделать verifier видимым canonical characterization-команде и architecture verification;
- сохранить rapid-pilot только oracle/adapter и не добавлять туда новую domain logic.

**Non-Goals:**

- проектирование или реализация target `InstallationCompletion`;
- canonical completion migration либо исправление runtime DDL;
- изменение текущего route, авторизации, 85% threshold, replay semantics или таблицы;
- декларация, correction/supersession, OTIZ и legacy reconciliation.

## Decisions

### 1. Boundary — HTTP process, не private methods

Verifier поднимает штатный pilot router на loopback и проходит local-auth/session flow, затем выполняет POST формы и проверяет HTTP status/headers/body вместе с DB outcome. Это ловит wiring, session, CSRF и transaction regressions, которые прямой вызов helpers пропускает.

Альтернатива — рефлексией/прямым вызовом `handle()` — отвергнута: это не публичный seam и не воспроизводит router ordering.

### 2. Persistence остаётся pilot-owned только на время characterization

Fixture создаёт необходимые upstream tables под уникальным prefix; completion table в DDL-ordering scenario намеренно не создаётся заранее. Verifier только наблюдает существующий pilot SQL и удаляет собственные prefixed tables. Новый business persistence SQL не добавляется в HTTP/UI.

Целевой persistence owner — будущий adapter модуля `InstallationCompletion`, а production DDL — canonical migrations. Этот change ownership не переносит, потому что durable fact/correction model заблокирована GRILL.

### 3. Live clock проверяется диапазоном

Production pilot не получает новый clock seam. Harness снимает `Europe/Moscow` timestamps непосредственно до и после HTTP request, округляет границы наружу до целых секунд (pilot пишет `DATE_ATOM` без долей), допускает переход даты внутри диапазона, проверяет offset/parseability и нормализует concrete `recorded_at`. Today/future fixtures выводятся из того же диапазона; при фактическом переходе полуночи сценарий безопасно переснимает границы вместо ложного RED.

Альтернатива — полагаться на `FMONITOR_NOW` — отвергнута, потому что oracle его игнорирует. Hard-coded дата также отвергнута.

### 4. Конкуренция проверяется двумя независимыми HTTP clients

Harness запускает штатный PHP pilot server с `PHP_CLI_SERVER_WORKERS` не меньше 2, подтверждает по process/runtime preflight наличие нескольких workers, а два клиента получают отдельные аутентифицированные sessions/CSRF и синхронно освобождаются к одному case. Все workers разделяют один prefixed DB. Проверка наблюдает multiset статусов `{303,409}` и один persisted fact, не навязывая победителя или конкретный timestamp. Один single-worker loopback server для этого сценария недопустим.

### 5. Architecture impact — ratchet, не исключение

Verifier регистрируется в characterization layer и не добавляет application dependency на rapid-pilot. Известный runtime DDL остаётся в baseline/plan как release risk; change не расширяет allowlist и должен проходить `make architecture-check`. Future target application module не сможет зависеть от HTTP/UI, rapid-pilot или concrete MariaDB adapter.

## Risks / Trade-offs

- [HTTP harness может падать из-за setup, а не behavior] → отдельная preflight-фаза проверяет PHP, DB, loopback readiness и маркирует environment failure до assertions.
- [Живые часы создают midnight race] → date и timestamp assertions используют до/после bounds и повторяют fixture setup при пересечении даты.
- [Concurrency orchestration может быть flaky] → readiness barrier, ограниченный timeout и проверка результата как unordered multiset.
- [Characterization выглядит как одобрение broad authorization/85%] → `PILOT_ONLY` присутствует в spec, test names и target-contrast backlog; target slices остаются `NEEDS_GRILL`.
- [Runtime DDL scenario нормализует нарушение] → architecture check продолжает запрещать новый DDL; достижимый HTTP test фиксирует creation-before-missing-case, а недостижимый через local-auth DDL-before-actor остаётся только source observation и не создаёт allowlist precedent.

## Migration Plan

1. Gate 1 фиксирует точный executable spec `CHARACTERIZE-COMPLETION-PTO-RECORDING-001` и получает owner approval.
2. Minimal RED покрывает один успешный HTTP mutation; независимый reviewer подтверждает seam и expected values.
3. Minimal GREEN добавляет только characterization harness.
4. Следующий RED расширяет rejection/replay/concurrency/DDL-ordering матрицу; новый независимый review предшествует GREEN.
5. Verifier регистрируется в canonical characterization suite; выполняются focused regression и architecture-check.
6. Target contrast обновляет backlog/GRILL без реализации target behavior.
7. Отдельный независимый code review закрывает change. Rollback — удалить только verifier registration/harness и planning records; production pilot/schema не меняются.
