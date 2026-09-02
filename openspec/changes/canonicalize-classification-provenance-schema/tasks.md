## 1. Preconditions и Gate 1

- [x] 1.1 После predecessor landing закрепить literal migration version/order и reconcile catalogue-wide 25-byte success / 26-byte pre-DB-access rejection во всех ещё не утверждённых migration drafts; проверить strict OpenSpec validation
- [x] 1.2 Amend exact executable spec: bounded v11 winner/loser использует injected verifier-only coordinator barrier после absent-v11 preflight и непосредственно перед plain `CREATE`, production composition всегда no-op и не имеет argv/env/config activation, а `GET_LOCK`/`SLEEP`/ledger/иная serialization запрещены; сохранить clean/existing/conflict/repeat, populated rows/counter, ambient decoys, exact native/historical/active CLI outcomes, source sentinels и mandatory native `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` contrast; получить fresh independent Gate 1 rereview и explicit owner approval новых exact hashes

## 2. RED и независимый test review

- [x] 2.1 После approval amended exact hash обновить deterministic verifier для двух verifier-composed real subprocesses, доказать arrival обоих после absent-v11 preflight до simultaneous release/plain `CREATE` и сохранить fresh RED; прежнее RED evidence не переносится автоматически
- [x] 2.2 Передать amended verifier свежему независимому test-review агенту, сохранить новый verdict в `reviews/tests/` и исправлять замечания с новым reviewer до approval; прежний Gate 3 approval не действует для amended contract

## 3. Minimal GREEN

- [x] 3.1 Добавить exact one-table canonical migration/preflight, production runner registration с обязательным no-op barrier и verifier-only injected coordinator seam; проверить clean, compatible populated, conflict zero-mutation, repeat и bounded real-subprocess winner/loser race без `GET_LOCK`, `SLEEP`, ledger или иных serialization claims
- [x] 3.2 Применить composed 25-byte catalogue prefix validation и 26-byte/invalid rejection до DB connection/access; обновить затронутые canonical migration tests без grandfather exceptions
- [x] 3.3 Перенести exact schema precondition перед native/historical/active-baseline import work и удалить runtime DDL из provenance target; проверить все три consumer paths под DDL-denied principal

## 4. Regression и architecture

- [x] 4.1 Прогнать native-only generation, object queue/PilotHttp origin, template linking, native OTIZ и historical-import focused regressions, доказав отсутствие optional legacy-active table creation
- [x] 4.2 Прогнать `git diff --check`, `make architecture-check`, strict OpenSpec validation и `make verify`; устранить regressions без taxonomy/import-transaction expansion

## 5. Независимый code review и Done

- [x] 5.1 Передать GREEN diff свежему независимому code-review агенту, сохранить verdict в `reviews/code/` и исправлять замечания с новым reviewer до approval
- [x] 5.2 Обновить operations status/runtime plan и отметить Done только при наличии approved spec, RED evidence, test review, minimal GREEN, full regression/architecture checks и code review
