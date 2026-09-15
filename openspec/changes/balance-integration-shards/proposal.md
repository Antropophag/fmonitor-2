## Why

Полный Quality Graph ждёт более медленный из двух существующих integration shards, хотя canonical inventory уже позволяет распределять один и тот же обязательный набор тестов детерминированно. Свежие FULL-прогоны после №135 показывают меняющийся перекос; issue №136 требует уменьшить critical path без изменения coverage, semantics или числа runners.

## What Changes

- Распределять canonical integration category из `tools/verification/suites.tsv` между ровно двумя существующими jobs deterministic longest-processing-time greedy по repository-owned historical planning weights.
- Обеспечить положительный fallback для новых tests и безопасный deterministic fallback при missing/corrupt/invalid hints; stale hints не влияют на membership.
- Сохранить два существующих имени jobs, fail-closed aggregation, semantic integration closure №153A и неизменную category membership.
- Измерить один setup candidate и реализовать не более одной безопасной оптимизации только при подтверждённом повторе и минимум трёх сопоставимых before/after повторах; иначе зафиксировать `NO_SAFE_SETUP_OPTIMIZATION_FOUND`.
- Зафиксировать fresh baseline, estimated old/new loads и exact-source FULL CI result, не выдавая prediction за measured improvement; token/cost telemetry остаётся `UNKNOWN`.

## Capabilities

### New Capabilities

- `deterministic-integration-sharding`: планирование двух integration shards из canonical inventory по необязательным historical weights с fail-safe invariants.

### Modified Capabilities

- Нет.

## Impact

Затрагиваются только verification tooling/tests, один небольшой performance-hints artifact при необходимости, delivery evidence и Quality Graph documentation. Product code, test semantics, inventory membership, FAST policy, №132/T02 contracts, №153 closure, число shards и required results не меняются. Actor — CI/developer; source oracle — issue №136, fresh successful FULL runs и `suites.tsv`; target seam — существующий `tools/verification/ci.py`; release value — меньший ожидаемый wall-clock самого медленного integration shard без ручной регистрации timing для новых tests.
