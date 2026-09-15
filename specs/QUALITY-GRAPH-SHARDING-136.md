# QUALITY-GRAPH-SHARDING-136

## Простыми словами

Два существующих integration shards получают тот же обязательный набор tests, но распределяют его по исторической стоимости, чтобы уменьшить ожидаемое время ожидания полного Quality Graph. Срез не меняет product code, test semantics, FAST, category membership, число runners или №153 closure; setup остаётся без изменения, потому что безопасная оптимизация не подтверждена.

## Scope

Actor — CI/developer. Oracle — owner issue №136, свежие successful FULL Quality Graph runs после №135 и canonical `tools/verification/suites.tsv`. Public seam — `python3 tools/verification/ci.py list integration --shard 1/2|2/2`; workflow seam сохраняет две jobs `Integration (1/2)` и `Integration (2/2)` и fail-closed aggregate.

## Contract

1. Membership MUST читаться только из canonical `suites.tsv`; timing rows являются planning weights, не inventory и не GREEN evidence.
2. Для ровно двух shards MUST применяться deterministic LPT: descending positive weight, stable path tie-break, назначение меньшей accumulated load, load tie в пользу меньшего shard index.
3. Новый canonical integration test без history MUST получить положительный fallback и исполниться ровно один раз. Stale timing MUST быть проигнорирован.
4. Missing/corrupt/duplicate/non-finite/non-positive timing MUST использовать deterministic safe fallback с диагностикой и MUST NOT удалять tests.
5. Union MUST равняться full integration category, intersection MUST быть пустым, каждый test MUST встречаться ровно один раз; input order не влияет на allocation.
6. Workflow MUST сохранить две существующие job names/required results. Failure/cancellation/absence одного shard MUST не давать overall GREEN.
7. Test semantics, category membership, MariaDB/browser/runtime acceptance, FAST и semantic closure №153A MUST не меняться.
8. Setup investigation MUST ограничиться одним кандидатом. Оптимизация допустима только при подтверждённом повторе, заметной стоимости, сохранённом contract и минимум трёх before/after measurements одинакового profile. Иначе MUST быть зафиксировано `NO_SAFE_SETUP_OPTIMIZATION_FOUND` без setup code change.
9. Report MUST разделять fresh measured baseline, estimated balancing effect и exact-source after CI; runner variance MUST быть обозначена. Token/cost MUST оставаться `UNKNOWN` без supported telemetry.

## Executable acceptance

- Skewed fixture доказывает меньший estimated maximum относительно прежнего round-robin.
- Shuffled/equivalent input даёт ту же allocation.
- New-without-history, stale-history и invalid/missing-history fixtures доказывают полноту и отсутствие дублей.
- Static workflow и aggregate tests доказывают две job names и failure propagation.
- Existing №135 inventory и №153A closure tests остаются GREEN.
