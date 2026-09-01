## Purpose

Гарантировать, что canonical production runner подготавливает и строго проверяет полную identity/access table family до runtime traffic, сохраняя существующие данные и не выполняя скрытый seed, rebuild или request-path DDL.

## ADDED Requirements

### Requirement: Canonical runner owns the complete identity/access family
Canonical migration contract SHALL владеть ровно существующей family из `fm2_pilot_users`, `fm2_pilot_roles`, `fm2_pilot_role_permissions`, `fm2_pilot_user_roles`, `fm2_pilot_auth_credentials`, `fm2_pilot_invitations`, `fm2_pilot_user_role_events`, `fm2_pilot_auth_attempts` и `fm2_pilot_user_status_events`. Exact compatible fingerprints SHALL происходить из одобренной Gate 1 executable schema specification, а не из непроверенного OpenSpec предположения.

#### Scenario: Clean schema
- **WHEN** deployment operator запускает canonical runner на чистом namespace с валидным table prefix
- **THEN** runner создаёт все девять таблиц с одобренными exact fingerprints и регистрирует migration version
- **THEN** runner не создаёт пользователей, роли, permissions, credentials, invitations или audit events

#### Scenario: Literal v6 registration
- **WHEN** identity/access migration добавляется после landed workforce v5
- **THEN** runner регистрирует её как literal version `6`, возвращает `schemaVersion=6` и включает `6` в `appliedVersions` только при создании хотя бы одного member

### Requirement: Compatible existing identity/access data is preserved
Canonical runner MUST принимать полностью существующую compatible family без destructive rebuild, seed или изменения identity/access строк и MUST быть безопасно повторяемым.

#### Scenario: Populated compatible family
- **WHEN** все девять таблиц уже имеют одобренные fingerprints и содержат users, role assignments, credentials, invitations, auth attempts и audit history
- **THEN** runner успешно регистрирует либо подтверждает migration, сохраняя все строки и связи byte-for-byte на уровне наблюдаемых данных

#### Scenario: Safe repeat
- **WHEN** operator повторно запускает canonical runner после успешного применения identity/access migration
- **THEN** runner сообщает успех без schema repair, повторного seed, удаления или изменения существующих строк

### Requirement: Exact-compatible partial family восстанавливается restartably
Canonical runner SHALL до первого DDL классифицировать все existing members.
Если каждый existing member exact-compatible, runner SHALL создать только
missing members в dependency-safe order и сохранить все existing definitions,
rows, counters и audit history. Если хотя бы один member incompatible, runner
SHALL fail closed до mutation и не записывать migration version.

#### Scenario: Exact-compatible partial family
- **WHEN** присутствует неполное подмножество exact-compatible identity/access таблиц
- **THEN** runner сохраняет их byte-equivalent, создаёт только missing members в dependency order и завершает migration success

#### Scenario: Interrupted recovery repeat
- **WHEN** предыдущий recovery остановился после MariaDB implicit DDL commit и все existing members exact-compatible
- **THEN** следующий canonical invocation повторяет full-family preflight и продолжает только с remaining missing members

#### Scenario: Single-table fingerprint conflict
- **WHEN** все девять имён присутствуют, но хотя бы одна таблица отличается от одобренного fingerprint колонкой, типом, nullability, default, enum, index, foreign key, engine, charset или collation
- **THEN** runner возвращает deterministic schema conflict и не изменяет ни одну таблицу или строку

#### Scenario: Family-level relationship conflict
- **WHEN** individual table shapes выглядят допустимо, но их foreign-key/index relationships не соответствуют одобренному fingerprint family
- **THEN** runner отклоняет всю family до version registration без частичного repair

### Requirement: Table prefix isolates the migration target
Canonical identity/access migration MUST применять один валидированный prefix ко всем девяти таблицам и MUST не читать, создавать или изменять одноимённые таблицы другого prefix.

#### Scenario: Two prefixed families coexist
- **WHEN** в одной database существуют compatible либо decoy identity/access tables с другим prefix, а runner вызывается для target prefix
- **THEN** validation, creation и version result учитывают только target family, а другой namespace остаётся неизменным

#### Scenario: Invalid prefix
- **WHEN** operator передаёт prefix вне одобренного безопасного формата
- **THEN** runner отклоняет запуск до schema access и не исполняет DDL

### Requirement: Runtime paths require pre-migrated schema
После canonicalization login, invitation, role grant, block/unblock и access projection paths MUST работать только с заранее мигрированной family и MUST не исполнять `CREATE`, `ALTER`, `DROP`, destructive rebuild или schema repair. Explicit destructive bootstrap MAY существовать только как отдельная operator-invoked seed/rebuild operation вне canonical migration и request traffic.

#### Scenario: Block and unblock on migrated populated schema
- **WHEN** существующий разрешённый public access seam блокирует или разблокирует пользователя после canonical migration
- **THEN** текущие user/status audit facts сохраняются согласно существующему oracle без выполнения runtime DDL

#### Scenario: Runtime schema is missing or incompatible
- **WHEN** auth/access request достигает среды без полной compatible identity/access family
- **THEN** путь fail-closed возвращает существующую безопасную ошибку и не пытается создать или исправить schema

#### Scenario: Explicit rebuild remains outside migration
- **WHEN** operator намеренно вызывает документированную destructive seed/rebuild operation для disposable/bootstrap среды
- **THEN** destructive действие не маскируется под canonical migration, normal application request или idempotent deployment upgrade

### Requirement: Ownership migration does not decide RBAC semantics
Этот deployment capability MUST сохранять наблюдаемое local auth/RBAC поведение, требуемое для schema characterization, но MUST NOT утверждать authority model, permission meanings, role catalogue, legacy fallback или точную authorization policy. Любая implementation, меняющая либо нормативно утверждающая эти behavior contracts, SHALL оставаться blocked как `NEEDS_GRILL (GRILL-002)` до отдельного Gate 1 решения.

#### Scenario: Ownership-only implementation
- **WHEN** implementation переносит DDL ownership и удаляет runtime schema creation без изменения запросов, permission catalogue или HTTP outcomes
- **THEN** slice может пройти schema gates при сохранённой characterization текущего поведения

#### Scenario: Behavior-contract change is proposed
- **WHEN** implementation требует выбрать local RBAC как нормативный authority, вернуть legacy-role authority или изменить authorization/audit outcome
- **THEN** соответствующая behavior часть не реализуется в этом slice и ожидает решения GRILL-002 и отдельной approved executable specification
