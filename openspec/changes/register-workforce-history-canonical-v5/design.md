## Context

`BitrixWorkforceHistorySchemaMigration` уже реализует и независимо проверяет строгий v5 contract, но canonical runner заканчивается на v4. Bootstrap/importer компенсируют это прямым invocation и runtime collation repair.

## Goals / Non-Goals

**Goals:** зарегистрировать существующий v5 как последний canonical runner step; сделать runtime callers schema consumers; сохранить точную классификацию setup/conflict/regression.

**Non-Goals:** менять manifest v5, синхронизацию Bitrix, normalization/publication, кадровые product rules или номера уже утверждённых migrations.

## Decisions

1. Runner catalogue добавляет existing v5 class после v4 и отражает её `applied` в ordered `appliedVersions`. Альтернатива — отдельная workforce CLI — отвергнута как второй schema owner.
2. Runtime callers выполняют read-only schema compatibility/precondition check либо полагаются на startup gate, но не repair. Альтернатива сохранить idempotent direct apply нарушает DDL ownership и скрывает deployment drift.
3. Existing v5 conflict result переводится в canonical runner conflict JSON; unexpected DB exception остаётся `MIGRATION_FAILED`. Это сохраняет различие incompatible schema и environment/runtime failure.
4. Importer-owned charset conversion не переносится автоматически: approved v5 exact preflight либо принимает исходную v2 schema, либо возвращает conflict. Любой необходимый conversion требует отдельного reviewed migration delta.

## Risks / Trade-offs

- [Старый bootstrap рассчитывает на self-healing] → deployment harness всегда запускает canonical migration до bootstrap/import.
- [Partial v5 already exists] → inherited v5 recovery contract остаётся единственным механизмом восстановления.
- [Удаление runtime repair выявит drift] → классифицировать как deployment conflict, не обходить проверку.

## Migration Plan

1. Зафиксировать runner-level executable spec/RED/review.
2. Зарегистрировать v5 и проверить clean/repeat/conflict/partial paths.
3. Удалить direct apply/ALTER из bootstrap/importer, добавить precondition failure.
4. Запустить focused workforce/runner tests, architecture check и full regression; deploy canonical migration перед обновлёнными consumers.
