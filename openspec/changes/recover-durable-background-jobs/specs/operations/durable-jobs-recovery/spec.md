## Purpose

Определяет безопасное восстановление canonical v23 durable queue/outbox state без
скрытого исполнения, потери append-only history или ложного exactly-once обещания.

## ADDED Requirements

### Requirement: V23 bundle имеет exact versioned inventory
Production recovery SHALL сохранять exact canonical v23 inventory: 69 base tables,
39 AUTO_INCREMENT families, `schemaVersion=23` и technical `deferred=[]`. V22
contract SHALL оставаться отдельным и неизменным; несовпадающий frontier/inventory
SHALL быть отвергнут до target mutation.

#### Scenario: Exact v23 restore
- **WHEN** оператор восстанавливает valid v23 bundle exact v23 image в empty contour
- **THEN** все 69 tables, 39 counters, rows и private state совпадают с source

#### Scenario: Version или inventory не совпадает
- **WHEN** bundle сообщает другой frontier, missing/extra table либо missing/extra AUTO key
- **THEN** restore возвращает `BUNDLE_INVALID` и оставляет zero target DDL/state

### Requirement: Quiesce останавливает каждого writer в безопасном порядке
V23 backup attestation SHALL означать остановку scheduler, затем worker, затем
web/php. Graceful worker SHALL сохранить terminal outcome; forced worker SHALL
оставить current lease и unknown result без guessed success.

#### Scenario: Forced worker stop
- **WHEN** active handler не завершился в grace перед backup
- **THEN** bundle содержит неизменённый leased job без terminal result, а child отсутствует

### Requirement: Restore не исполняет очередь и outbox
Restore SHALL byte/value-exact восстановить ready/leased/completed/dead jobs,
events, slots, role heartbeats и pending/delivered/dead outbox rows. Он SHALL NOT
refresh/release/reclaim lease, sweep intent, invoke handler/transport или менять
append-only history. Stale operational health SHALL NOT делать exact restore
неуспешным.

#### Scenario: Unknown external outcome остаётся unknown
- **WHEN** bundle содержит pending intent, ambiguous attempt и leased либо dead dispatch job
- **THEN** `RESTORE_COMPLETED` не добавляет attempt/event и не вызывает transport

### Requirement: Resume выполняется явно и сохраняет retry semantics
После restore DML-only jobs services SHALL возобновлять работу только по явному
operator start. Pending intent без job получает один dispatch; lease reclaim следует
expiry/attempt policy; ambiguous delivery сохраняет intent-wide provider identity;
dead recovery требует authorized linked retry.

#### Scenario: Lease и outbox resume
- **WHEN** fake-only recovery проходит unexpired/expired/attempt5 leases и pending/ambiguous intents
- **THEN** unexpired work исключён, attempts1–4 reclaim-ятся с новым token, attempt5 становится dead/no6, sweep не дублируется и business fact не повторяется

### Requirement: Обновление идёт только forward
Оператор SHALL восстанавливать v22 bundle exact v22 image и затем применять
additive migration23 с exact preservation прежних rows/state/AUTO values. V22
tooling SHALL отвергать v23 bundle до mutation; schema downgrade отсутствует.

#### Scenario: V22 переходит в v23
- **WHEN** restored v22 contour получает reviewed migration23
- **THEN** 63-table state остаётся exact, шесть Jobs tables создаются empty/exact и последующий v23 backup/restore проходит

#### Scenario: V23 нельзя восстановить v22 tooling
- **WHEN** v22 recovery получает v23 bundle
- **THEN** он возвращает `BUNDLE_INVALID`, не создаёт tables/state и не удаляет Jobs history
