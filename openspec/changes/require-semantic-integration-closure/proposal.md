## Why

PR #148 показал, что GREEN выбранных direct/focused checks не доказывает полноту проверки при изменении сквозной semantic invariant: downstream migration/frontier, recovery, current-state consumers, fixtures и UI assumptions проявились только в full integration CI после публикации. Slice A issue #153 должен до публикации консервативно требовать существующую integration closure для repository-owned high-risk semantic surfaces, не строя transitive consumer graph и не создавая новый verification framework.

## What Changes

- Добавить в существующий change-verification planner детерминированную closed-set классификацию persistence, schema/migration/frontier, authoritative current-state/domain-contract и recovery representation surfaces.
- При таком изменении требовать category-level integration closure из canonical `tools/verification/suites.tsv`; отсутствие зарегистрированных integration verifiers останавливает prepare fail-closed.
- Выдавать machine-readable surface, причину escalation и добавленные integration checks/category; plan без обязательной closure не может быть publication-ready.
- Сохранить текущую FAST semantics для genuinely local presentation и lifecycle-docs changes.
- Не реализовывать capability-to-consumer graph, Gate 3 completeness audit, CI feedback expansion, Gate 6 либо изменения product code.

## Capabilities

### New Capabilities

- `verification/semantic-integration-closure`: pre-PR deterministic escalation и canonical integration closure для high-risk semantic surfaces.

### Modified Capabilities

Нет.

## Impact

Меняются только repository verification contract, planner policy/seam, executable planner regression, canonical test registration и delivery evidence. Runtime LLM judgement, второй inventory/registry, product runtime, FAST safety #132 и CI topology не затрагиваются. Issue #153 остаётся открытой для Slice B/C/D.
