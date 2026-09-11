## Why

Первое применение delivery harness в PR #89 и PR #91 показало, что отдельно корректные локальные проверки не гарантируют согласованность публичных CLI, retained evidence, worktree bindings и verification registries. Срез `DELIVERY-HARNESS-HARDENING-001` нужен, чтобы подтверждённые классы orchestration-дефектов воспроизводились детерминированно до публикации кандидата, не добавляя новых Gate-стадий или LLM-review циклов.

## What Changes

- Добавляется table-driven executable contract публичного `harness.py run`, одновременно проверяющий потоки, outcome, shell exit, child return codes, retained record и побочные эффекты.
- Добавляются сквозные проверки цепочек prepare/check/refresh/run, hook/binding/context/plan, worktree isolation, Gate 3 package и runner/wrapper/CI aggregate.
- Product verification roster получает детерминированную проверку согласованности существующих registries, включая regression PR #91, но agent-harness tests не регистрируются как product suites.
- Для agent delivery harness вводится отдельный bounded tooling/governance check, не запускающий product unit/DB/E2E и не требующий полного product CI.
- Добавляется ограниченный deterministic fault-injection набор для критического delivery orchestration.
- Существующий Gate 3 verification input для публичных CLI/infrastructure seams расширяется явными observable dimensions без нового approval Gate.
- Обычный product GREEN и строгость product CI не меняются; #90 доказывается отдельным bounded harness-контуром и не использует product full suite как acceptance evidence.

## Capabilities

### New Capabilities

- `delivery/delivery-harness-hardening`: Полные публичные контракты delivery harness, межseamная совместимость, worktree isolation, синхронизация verification registries и mutation sensitivity.

### Modified Capabilities

Нет.

## Impact

- Затрагиваются `tools/delivery/`, bounded harness verification entry point и Python tests в `tests/Verification/`; membership product suites не расширяется.
- Публичные seams: `python3 tools/delivery/harness.py`, `change-verification.py`, repository hooks и canonical verification commands.
- Источник требований — issue #90 и подтверждённые дефекты PR #89/#91; продуктовые domain seams и `rapid-pilot/` не меняются.
- Новые runtime dependencies, Gate-стадии, управляющие агенты и внешние хранилища не вводятся.
