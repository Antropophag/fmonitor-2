## Context

См. `proposal.md` и
`docs/operations/active-baseline-provenance-schema-evidence.md`. Один runtime
owner создаёт release-supporting table после начала import output flow. Таблица
используется native и historical paths, тогда как ранее сгруппированные baseline
tables optional. Её 39-byte basename задаёт catalogue prefix ceiling 25 bytes.

## Goals / Non-Goals

**Goals:**

- Зарегистрировать exact one-table canonical owner до native import.
- Сохранить populated rows/counter и дать deterministic conflict/repeat.
- Удалить runtime DDL и ратчетить prefix/ownership boundaries.

**Non-Goals:**

- Canonicalize legacy-active baseline/case tables.
- Менять taxonomy, hashes, replay/conflict или import output semantics.
- Выполнять backfill либо объединять case/history output с provenance в новую
  массовую transaction redesign.

## Decisions

1. **Отдельный migration owner в `app/InstallationProcess`.** Public seam —
   `bin/fmonitor2-migrate.php`; новый class следует существующему canonical
   migration contract и зависит только от mysqli/schema fingerprint primitives.
   Rapid-pilot target остаётся behavioral adapter и только требует schema.
   Альтернатива — оставить таблицу в optional active-baseline migration —
   блокирует native-only bootstrap и смешивает разные dependencies.
2. **Одна таблица, две compatibility states.** Preflight отличает absent и exact
   present; incompatible present всегда zero-mutation conflict. Для одной DDL
   нет family partial matrix. Bounded two-runner case фиксирует один v11 winner
   и одного safe `MIGRATION_FAILED` loser на exact v1–v10 predecessor. Его
   verifier-only composition инъецирует coordinator barrier, который сообщает
   arrival после absent-v11 preflight и блокирует каждый отдельный subprocess
   непосредственно перед plain `CREATE TABLE`, пока verifier не отпустит оба.
   Production catalogue/factory всегда передаёт no-op implementation; CLI не
   имеет argv/env/config switch к coordinator. Поэтому production остаётся
   plain preflight→`CREATE`: `GET_LOCK`, `SLEEP`, durable/ephemeral ledger и
   любая скрытая serialization запрещены. `CREATE TABLE IF NOT EXISTS` без
   fingerprint отвергнут.
3. **Semantic fingerprint.** Проверяются ordered columns/indexes, defaults,
   extra, engine, resolved explicit-utf8mb4 collation и отсутствие FK/CHECK.
   Index names не сравниваются. Plain TEXT reason JSON сохраняется, чтобы schema
   move не усилил неутверждённую taxonomy.
4. **Ordering symbolic до predecessors.** Migration должна идти после canonical
   process tables, но до import/bootstrap, которые создают operational cases.
   Literal version выбирается после фактического landing предыдущих changes.
5. **25-byte prefix ratchet применяется catalogue-wide.** Existing earlier
   drafts обновляются до Gate 1. Runner валидирует byte length до создания DB
   connection/access. Это
   предпочтительнее локального исключения, при котором ранняя migration проходит,
   а provenance падает позже.
6. **Runtime precondition раньше source/output work.** Три batch adapters
   проверяют exact schema до source connection/fetch и output mutation и
   возвращают свои exact safe `*_BATCH_UNAVAILABLE` JSON outcomes. Gate 2
   использует independent source sentinels. Change не исправляет
   бизнес-атомарность output+provenance; mandatory native-case contrast лишь
   характеризует существующее окно и гарантирует, что schema absence/DDL больше
   его не вызывает.
7. **Architecture ratchet уменьшается.** Runtime DDL owner удаляется без baseline
   exception; architecture check запрещает новый DDL вне canonical owner и рост
   rapid-pilot mutation debt.

## Risks / Trade-offs

- [Predecessor versions изменятся] → оставить version symbolic и обновить exact
  catalogue непосредственно перед Gate 1/RED.
- [25-byte ceiling ломает старые drafts] → централизованно reconcile все
  migration specs/verifiers до их owner approval; не grandfather longer prefixes.
- [Environment выбирает другую utf8mb4 collation] → Gate 1 фиксирует approved
  target environment, migration эмитирует явно проверенное resolved значение.
- [Output без provenance уже возможен по другим ошибкам] → не заявлять его
  исправленным; вести отдельный behavior/transaction slice после characterization.
- [Historical и active-baseline import тоже consumers] → DDL-denied regression
  покрывает все три observed output kinds, но не продвигает historical/active
  evidence в TEST-USER release.

## Migration Plan

1. После predecessor landing закрепить literal version/order и обновить все
   earlier prefix contracts до composed 25/26 pre-DB-access boundary.
2. Повторно утвердить amended executable Gate 1 с exact verifier-only barrier,
   затем заново доказать RED и получить свежий независимый test review; прежние
   Gate 1 hash и Gate 3 approval не переносятся на amended contract.
3. Добавить canonical migration/preflight, runner registration и focused
   clean/existing/conflict/repeat/bounded winner-loser verifier.
4. Перенести schema precondition перед import work и удалить runtime DDL owner;
   прогнать exact native/historical/active CLI outcomes с DDL-denied principal,
   source sentinels и mandatory native output-without-provenance contrast.
5. Выполнить architecture/full verification и независимый code review. Rollback
   кода не удаляет additive table/data; несовместимость исправляется следующей
   migration, не destructive cleanup.
