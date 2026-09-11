## Why

Первое применение delivery harness в PR #89 и PR #91 показало, что отдельно корректные локальные проверки не гарантируют согласованность публичных CLI, retained evidence, worktree bindings и verification registries. Срез `DELIVERY-HARNESS-HARDENING-001` нужен, чтобы подтверждённые классы orchestration-дефектов воспроизводились детерминированно до публикации кандидата, не добавляя новых Gate-стадий или LLM-review циклов.

## What Changes

- Добавляется table-driven executable contract публичного `harness.py run`, одновременно проверяющий потоки, outcome, shell exit, child return codes, retained record и побочные эффекты.
- Добавляются сквозные проверки цепочек prepare/check/refresh/run, hook/binding/context/plan, worktree isolation, Gate 3 package и runner/wrapper/CI aggregate.
- Состав verification suites получает один канонический источник либо детерминированную проверку согласованности inventory/categories/suites и CI composition, включая regression PR #91.
- Добавляется ограниченный deterministic fault-injection набор для критического delivery orchestration.
- Существующий Gate 3 verification input для публичных CLI/infrastructure seams расширяется явными observable dimensions без нового approval Gate.
- Обычный GREEN и строгость полного CI сохраняются; повторный запуск неизменившегося продуктового E2E не требуется только ради metadata correction, когда действующий evidence protocol допускает reuse.

## Capabilities

### New Capabilities

- `delivery/delivery-harness-hardening`: Полные публичные контракты delivery harness, межseamная совместимость, worktree isolation, синхронизация verification registries и mutation sensitivity.

### Modified Capabilities

Нет.

## Impact

- Затрагиваются `tools/delivery/`, verification policy/registries и Python tests в `tests/Verification/`.
- Публичные seams: `python3 tools/delivery/harness.py`, `change-verification.py`, repository hooks и canonical verification commands.
- Источник требований — issue #90 и подтверждённые дефекты PR #89/#91; продуктовые domain seams и `rapid-pilot/` не меняются.
- Новые runtime dependencies, Gate-стадии, управляющие агенты и внешние хранилища не вводятся.
