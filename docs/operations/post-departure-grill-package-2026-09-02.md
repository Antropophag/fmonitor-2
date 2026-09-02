# GRILL-009 — Gate 1 testability and snapshot amendments

## Topic

Четыре independently discovered Gate 1 gaps в session storage, classification
v11 concurrency, E2E RBAC snapshot boundary и prepare renderer observability.

## Why this decision is needed

Во всех трёх slices RED/review дошли до места, где буквальный approved contract
либо позволяет self-attesting test harness, либо требует ненаблюдаемую race, либо
противоречит обязательной business mutation. Production нельзя продолжать,
ослабляя test или добавляя скрытую архитектуру.

## What repository/pilot currently does

- Session storage жёстко использует runtime filesystem/session behavior, но
  approved spec не задаёт injectable primitive/event/fault seam. Два fresh test
  reviews отклонили test-owned dispatcher, который мог бы сам объявить GREEN.
- Classification v11 plain preflight→CREATE корректен, но два полных public
  runners проходят v1–v10 с разной скоростью: поздний runner часто видит уже
  exact v11 и возвращает ordinary repeat `0/[]`, не проигрывая CREATE. Попытка
  сделать race детерминированной через `GET_LOCK`/`SLEEP` удалена как
  неутверждённая.
- E2E RBAC main branch до artifact boundary обязательно выполняет prepare,
  создающий order, event, artifact metadata и bytes. Approved spec одновременно
  требует full-equal DB/storage/process snapshot от pre-list до этой boundary.
- Prepare GET/HEAD canonical production factory жёстко создаёт concrete
  `ProductionPrepareFormRenderer`; entrypoint не имеет factory-owned decorator
  или instrumentation seam. Manual test graph может скрыть production wiring
  regression и уже отклонён fresh reviewer.

## What existing specs/docs imply

- `docs/development-process.md` запрещает production до sensitive RED и fresh
  independent test approval.
- Session spec требует crash/fault/Compose matrix, но не определяет наблюдаемый
  test seam, через который production alone может сделать её GREEN.
- `CLASSIFICATION-PROVENANCE-SCHEMA-001` требует и ordinary repeat `0/[]`, и
  exact concurrent loser `70`; без post-preflight barrier поздний runner
  неотличим от ordinary repeat.
- `PILOT-E2E-RBAC-FIXTURES-001` требует неизменность RBAC authority перед PDF,
  но literal full-state equality конфликтует с approved prepare journey.

## Questions, recommendations and consequences

1. **Session storage Gate 1 должен определить exact injectable filesystem
   primitives/events, deterministic crash/pause hooks, clock/entropy, public
   factory/result DTO и read-only Compose volume inspection seam?**
   - Recommendation: **да**; test seam является application infrastructure
     contract, production implementation остаётся owner behavior, а tests
     независимо наблюдают каждый phase и не исполняют его вместо production.
   - Alternative consequence: black-box-only matrix не может детерминированно
     доказать fsync/rename/crash boundaries; test-owned dispatcher даёт ложный
     GREEN.

2. **Classification race добавить test-only barrier после absent v11 preflight
   и до plain CREATE или разрешить late runner ordinary repeat `0/[]`?**
   - Recommendation: **test-only barrier**, недоступный production config и
     активируемый только verifier-controlled process; production остаётся plain
     preflight→CREATE, а approved loser-after-CREATE contract становится
     наблюдаемым. Barrier contract должен пройти fresh Gate 1 review.
   - Alternative consequence: разрешение `0/[]` реалистичнее для arbitrary
     scheduling, но ослабляет exact concurrency oracle; advisory lock/timeout
     меняет production semantics и не рекомендуется.

3. **E2E RBAC snapshot boundary трактовать как full equality только вокруг
   authorization reads, а у artifact boundary требовать неизменность exact RBAC
   facts плюс строго approved prepare delta?**
   - Recommendation: **да**; full DB/storage snapshot сравнивается до/после list
     authorization, затем prepare может создать только уже утверждённые
     order/event/artifact facts; RBAC rows/counters остаются byte-equal до PDF.
   - Alternative consequence: literal full equality делает journey
     невыполнимым; удаление snapshot полностью потеряет sensitivity к скрытым
   authorization writes.

4. **Prepare RBAC Gate 1 разрешает узкий factory-owned renderer decorator/
   observation seam для tests?**
   - Recommendation: **да**; canonical factory принимает optional decorator в
     explicit test composition path, production entrypoint всегда использует
     identity decorator. Spy оборачивает реальный renderer, считает invocation
     и делегирует bytes без изменения route/authorization behavior.
   - Alternative consequence: manual graph не проверяет canonical wiring;
     reflection/shadowing хрупки; black-box body absence не доказывает, что
     renderer не вызван и результат просто не отброшен.

## Exactly which slices are blocked

- `define-pilot-session-storage-contract` Gate 1 amendment, Gate 2 approval и
  весь GREEN.
- `canonicalize-classification-provenance-schema` tasks 3.1, 3.3–5.2 и зависимый
  canonical frontier (object detail, generation metadata, fixture seed).
- `pilot-e2e-rbac-fixtures` Gate 3/4 и зависимый `pilot-e2e-combined-pdf` RED;
  local-RBAC/route-CSP integration Done остаётся открытым.
- `pilot-prepare-rbac-fixtures` Gate 3/4 и зависимое полное закрытие local-RBAC
  route fixtures.

Work-navigation planning correction, unrelated characterization/discovery и
already approved harness work не заблокированы.
