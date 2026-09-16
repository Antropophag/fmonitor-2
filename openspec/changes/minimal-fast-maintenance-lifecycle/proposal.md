## Why

После merged T05.1 planner умеет доказуемо выбирать FAST для узкого класса bounded presentation changes, но repository routing всё ещё механически предполагает новый OpenSpec change и повторяет acceptance между proposal, delta spec, design и tasks. Для maintenance-исправления уже существующего канонического требования это создаёт churn и freshness invalidations без усиления RED, independent final review или exact-source CI.

## What Changes

- Добавить в реальный `harness.py prepare/state/package` детерминированный lifecycle route `FAST_MAINTENANCE | OPENSPEC_REQUIRED` с reason; planner и T05.1 остаются единственными владельцами FAST classification.
- Разрешить `FAST_MAINTENANCE` только при planner-selected FAST, существующем непротиворечивом canonical requirement и явном `semantic_change=false`; отсутствие доказательства fail-closed отправляет в обычный OpenSpec lifecycle или `NEEDS_OWNER` при противоречии.
- Расширить существующий delivery/change record минимальными machine-readable ссылками на issue/task, exact source/base, FAST class/reason, requirement digests, executable regression, verification plan, final review, exact-source CI и disposition.
- Делать prepared package stale при изменении referenced requirement digest.
- Сохранить обязательные executable RED, один independent final review и exact-source CI; не менять STANDARD/CRITICAL lifecycle и explicit `openspec-propose` workflow.
- Добавить deterministic cases A–N и исторический replay с измерением mandatory artifact count/bytes, review dispatches и explicit phase stops; token savings не заявлять без telemetry.

## Capabilities

### New Capabilities

- `delivery/fast-maintenance-lifecycle`: детерминированный выбор минимального lifecycle и compact durable evidence для planner-selected FAST maintenance.

### Modified Capabilities

- Нет.

## Impact

- Затрагиваются только delivery harness, verification input/package schema, repository process documentation, executable tooling regressions и delivery records.
- Product runtime, FAST classifier coverage, #132/#153A, STANDARD/CRITICAL gates, historical OpenSpec changes и existing OpenSpec CLI/workflow не меняются.
- Actor: root delivery session; source oracle: planner-selected FAST plan плюс существующие canonical requirements; public seam: repository `harness.py prepare/state/package`; release value: FAST maintenance проходит RED → implementation → focused verification → final review → CI без proposal boundary, если semantics неизменны.
- Non-goals: LLM semantic classifier, второй registry/documentation framework, автоматическое создание OpenSpec, migration старых artifacts, T02/#107/#141, merge/deploy/settings.
