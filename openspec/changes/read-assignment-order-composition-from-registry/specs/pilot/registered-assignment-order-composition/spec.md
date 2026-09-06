## Purpose

Читать состав для подписанного оригинала из единственного зарегистрированного
источника, сохраняя историю, nondisclosure и совместимость старых распоряжений.

## ADDED Requirements

### Requirement: Registry owns source selection

Reader SHALL разрешать identity через registry в одном consistent read-only
snapshot. Other-case identity MUST возвращать not_found без source probes.
Orphan, dual source, mismatch и query failure MUST возвращать unavailable без
fallback/repair. Exact contract: ASSIGNMENT-ORDER-REGISTERED-COMPOSITION-READER-001.

#### Scenario: Selected composition without template
- **WHEN** original application читает registry selection identity с matching dateless header/members/hash
- **THEN** получает immutable composition обоих поддержанных modes без создания PDF или physical order

#### Scenario: Ambiguous ownership
- **WHEN** registry и источники противоречат друг другу либо недоступны
- **THEN** reader возвращает unavailable без выбора приоритетного источника и без mutation

### Requirement: Preserve legacy semantics and history

Legacy branch SHALL сохранять прежние temporal/member rules. Selection branch
SHALL читать выбранные snapshot members без legacy temporal filter. Reader MUST
NOT менять authorization, original revisions, composition applicability, opening,
audit, tables или counters; application сохраняет собственную authorization.

#### Scenario: Registered historical order
- **WHEN** old order содержит assign/retain/release history
- **THEN** состав определяется прежними правилами на дату распоряжения, а новые выборы не меняют старую запись

### Requirement: Unwired release boundary

Adapter SHALL оставаться standalone до combined readiness/wiring gate. Ошибки
observer/owned release MUST закрывать чтение; caller connection/transaction не
передаются reader во владение. Factories MUST NOT выполнять lazy schema setup.

#### Scenario: Standalone verification succeeds
- **WHEN** новый reader проходит independent Gate5
- **THEN** существующие production factories, locked writer checks и canonical migrations сохраняют прежнее wiring до отдельного integration gate
