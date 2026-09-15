## Context

См. `proposal.md` и capability spec. `ci.py` сейчас сортирует canonical category и делит её `[offset::2]`. `suites.tsv` после №135 — единственный membership source; существующий `VERIFY_TIMING` даёт historical evidence. Работа относится только к verification tooling и CI performance, persistence/product seams отсутствуют; `rapid-pilot` не затрагивается.

## Goals / Non-Goals

**Goals:**

- Ввести малый repository-owned hints artifact `path<TAB>positive-seconds`, если fresh logs дают достаточно weights.
- Сделать pure deterministic two-bin LPT allocation и покрыть его executable cases A–H.
- Измерить ровно один setup path; менять его только при доказанной безопасной экономии.

**Non-Goals:**

- Adaptive scheduling, timing service, третий runner, cache test verdict/DB/volumes, inventory redesign или product changes.
- Изменение policy/context contracts №132/T02, semantic closure №153 или test category membership.

## Decisions

1. Membership сначала читается и валидируется существующим inventory seam; hints lookup выполняется только для уже выбранных integration paths. Это исключает возврат stale tests. Альтернатива — объединять inventory с timing rows — отвергнута как второй inventory.
2. Effective weight — valid finite positive hint либо единый положительный fallback. LPT сортирует `(-weight, path)` и на load tie выбирает shard 1. Результаты внутри shard выводятся в стабильном path order, сохраняя удобный diff. Альтернатива — optimizer — отвергнута постановкой.
3. Missing/corrupt artifact целиком использует fallback и пишет диагностику в stderr; invalid отдельная строка не влияет на membership. Duplicate hint считается invalid для данного пути. Это fail-safe для coverage, не GREEN cache.
4. Historical weights берутся из последних сопоставимых successful FULL logs после №135 и фиксируются как planning snapshot. Их обновление не является обязательным при добавлении test.
5. Единственный setup candidate — prerequisites `quality_graph_ci_setup_001_test`. Если три одинаковых bounded прогона не докажут безопасный repeat-removal/reuse, код setup не меняется и фиксируется допустимый отрицательный результат.

## Risks / Trade-offs

- [Historical durations drift] → hints остаются advisory planning weights; exact-source CI измеряется отдельно, deterministic fallback сохраняет coverage.
- [Malformed hints hide intended weighting] → явная диагностика плюс invariant tests; scheduling остаётся complete.
- [Runner variance masks effect] → сравниваются estimated loads на одном snapshot, а CI timings публикуются без causal overclaim.
- [Setup test measures the preparation itself] → не удалять prerequisite без contract-preserving evidence; default — `NO_SAFE_SETUP_OPTIMIZATION_FOUND`.

## Migration Plan

Добавить spec/tests, получить RED, передать minimal implementation executor; focused checks и independent Gates 3/5; затем один exact-source FULL Quality Graph. Rollback удаляет hints/LPT seam и возвращает прежний round-robin без миграции данных.
