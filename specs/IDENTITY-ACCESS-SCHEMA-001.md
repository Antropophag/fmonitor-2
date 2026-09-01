# IDENTITY-ACCESS-SCHEMA-001 — canonical identity/access schema ownership

- Статус: `APPROVED / GATE 1 COMPLETE`
- Версия: `0.1`
- Дата: `2026-09-01`
- Актор: оператор развёртывания FMonitor 2.0
- Публичный operator seam: отдельный процесс `php bin/fmonitor2-migrate.php`
- Публичный application diagnostic seam: identity/access migration result object
  до redaction CLI output
- Решение Gate 1: `APPROVED` — owner явно утвердил literal canonical version
  `6` и restartable exact-compatible partial recovery; `GRILL-002` остаётся
  blocker только для RBAC/authorization behavior и не блокирует schema ownership.

## 1. Цель и граница

Следующая canonical migration после фактически landed predecessor принимает
production ownership ровно девяти существующих identity/access tables:

1. `fm2_pilot_users`;
2. `fm2_pilot_roles`;
3. `fm2_pilot_role_permissions`;
4. `fm2_pilot_user_roles`;
5. `fm2_pilot_auth_credentials`;
6. `fm2_pilot_invitations`;
7. `fm2_pilot_user_role_events`;
8. `fm2_pilot_auth_attempts`;
9. `fm2_pilot_user_status_events`.

Этот срез переносит только schema ownership. Он не утверждает local RBAC как
authority, не меняет permission catalogue, роли, legacy fallback, login,
session/password/invitation policy, block/unblock authorization или текущую
смешанную audit-retention semantics. Эти вопросы остаются `NEEDS_GRILL
(GRILL-002)`. Наблюдаемое auth/access поведение допускается только как
characterization, а не как нормативное одобрение.

`rapid-pilot/IdentityBootstrap.php` и lazy DDL в
`rapid-pilot/UserAccessView.php` являются evidence текущего состояния, но после
реализации не являются production schema owners. Canonical migration не seed-ит,
не backfill-ит, не repair-ит и не удаляет identity/access rows. Intentional
destructive seed/rebuild остаётся отдельной явно вызванной operator operation
только для disposable/bootstrap environments и никогда не вызывается runner или
HTTP/request path.

## 2. Prefix, version и идентификаторы

`tablePrefix` наследует canonical runner ceiling и обязан соответствовать
`/^[A-Za-z0-9_]{0,25}$/D`. Это ASCII byte contract; пустой prefix допустим.
Невалидное значение runner отклоняет как `CONFIGURATION_INVALID` до DB
connection, а migration object — `InvalidArgumentException` до первого DB call.
Сам input и производные identifiers не попадают в error text.

Этот pending draft наследует текущий composed-runner ceiling 25 bytes.
Для него самый длинный table basename — `fm2_pilot_user_status_events`
(28 bytes), поэтому собственная family-local граница была бы 36 bytes, а target
table при composed prefix имеет максимум 53 bytes. Все определённые ниже
explicit symbols также проверяются при prefix 25. Таким образом, каждый table,
FK и index identifier укладывается в MariaDB limit 64 bytes. Предлагаемое
сужение следует из release-supporting 39-byte classification-provenance basename
и уже действует в landed canonical runner v5. Оно supersede-ит исторический
approved 32-byte runner contract без изменения workforce family-local 37/38.

Landed predecessor — workforce schema v5, поэтому identity/access получает
literal canonical version `6`. Значение `6` используется как key migration map,
`schemaVersion` migration result, conflict version и конечный runner
`schemaVersion`. Если до implementation между v5 и identity будет вставлен иной
landed predecessor, потребуется fresh version reconciliation и повторный Gate 1
review; implementation не может молча перенумеровать approved contract.

### 2.1 Deterministic symbol policy

Canonical CREATE использует explicit names. Index names table-local и одинаковы
для любого prefix. FK constraint symbols должны сосуществовать в одной database
на MariaDB versions, где FK names database-global, поэтому являются функцией
validated raw prefix:

| Structure | Empty-prefix symbol | Non-empty-prefix symbol |
|---|---|---|
| role-permissions role FK | `fk_ia_role_permissions_role` | `fk_{prefix}ia_rp_role` |
| user-roles user FK | `fk_ia_user_roles_user` | `fk_{prefix}ia_ur_user` |
| user-roles role FK | `fk_ia_user_roles_role` | `fk_{prefix}ia_ur_role` |
| credentials user FK | `fk_ia_auth_credentials_user` | `fk_{prefix}ia_ac_user` |
| invitations user FK | `fk_ia_invitations_user` | `fk_{prefix}ia_inv_user` |

`{prefix}` означает literal validated value, включая его trailing underscore.
Например, `blue_` даёт `fk_blue_ia_ur_user`. Два разных non-empty prefixes
дают разные FK symbols.

Observed source использует unnamed keys/FKs, и MariaDB генерирует их names.
Имена index/FK являются presentation metadata и **не участвуют** в compatibility
fingerprint; участвуют category, uniqueness, ordered columns, referenced exact
table/columns и rules. Поэтому существующая semantic-exact populated family с
generated names совместима и не переименовывается. Все новые canonical tables
получают explicit symbols выше и index names ниже. Любой future implementation
не может считать generated name oracle, а изменение имени само по себе не
разрешает DDL. Это позволяет стабилизировать clean creation без destructive
rename существующих security-sensitive tables.

## 3. Нормативная нормализация fingerprint

Для каждой таблицы значимы и сравниваются до mutation:

- exact basename/prefix и отсутствие view вместо base table;
- ordinal columns, exact MariaDB `COLUMN_TYPE`, nullability, semantic default и
  `AUTO_INCREMENT` flag;
- character set `utf8mb4` только у character/enum columns; отсутствие character
  metadata у numeric/binary/datetime columns;
- primary, unique и secondary BTREE index structures с exact ordered columns;
- пять documented FK relationships, `DELETE_RULE` и `UPDATE_RULE`;
- `ENGINE=InnoDB`;
- table/character-column collation, равная database-default utf8mb4 collation,
  разрешённой для target database до первого DDL.

Runner подтверждает, что database default character set — `utf8mb4`, и берёт её
exact database-default collation как environment value этого запуска. До DDL он
проверяет имя collation по `/^[A-Za-z0-9_]+$/D`. MariaDB может сообщать default
как `utf8mb4_uca1400_ai_ci`, но публиковать ту же UCA collation в
`information_schema.COLLATIONS` как alias `uca1400_ai_ci` с nullable charset.
Поэтому membership подтверждается либо exact utf8mb4 row, либо documented UCA
alias без `utf8mb4_` prefix, после чего безопасное пробное применение exact
database-default имени к utf8mb4 обязано успешно пройти до первого target DDL.
Unknown alias, небезопасное имя и non-utf8mb4 database отклоняются как
`DATABASE_UNAVAILABLE` до identity mutation. Canonical
DDL задаёт `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE <validated database
default>` с безопасным identifier quoting; полагаться на server/connection
default через omission `COLLATE` запрещено. Конкретное имя вроде observed
`utf8mb4_uca1400_ai_ci` не является portable literal этого spec. Existing table
с другой collation, даже если обе collations относятся к utf8mb4, конфликтует:
migration не выполняет скрытую conversion.

Presentation metadata незначимы: integer display widths, MariaDB-added nullable
`DEFAULT NULL`, quoting/SQL whitespace/case keywords, index/FK names, implicit
`ON UPDATE RESTRICT`, generated FK-supporting index name и table option
`AUTO_INCREMENT=<next>`. AUTO_INCREMENT counter — data state; он никогда не
сравнивается, не сбрасывается и не включается в compatibility result. Значимы
сам `AUTO_INCREMENT` flag и тип колонки. Никакие extra columns, indexes,
constraints, FKs или differing enum literals не допускаются.

Semantic defaults ниже: `∅` означает отсутствие default у NN field; `NULL` —
nullable default NULL. MariaDB effective nullable NULL и explicit source
`DEFAULT NULL` эквивалентны.

## 4. Exact nine-table manifests

Во всех таблицах engine `InnoDB`; table/character charset/collation следуют
разделу 3; все indexes — `BTREE`. FK update rule везде `RESTRICT`.

### 4.1 `{prefix}fm2_pilot_users`

| # | Column | Type | Null | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `user_id` | `bigint(20) unsigned` | NO | ∅ | `auto_increment` |
| 2 | `full_name` | `varchar(300)` | NO | ∅ | — |
| 3 | `email` | `varchar(254)` | NO | ∅ | — |
| 4 | `phone` | `varchar(100)` | NO | `''` | — |
| 5 | `status` | `tinyint(1)` | NO | `1` | — |
| 6 | `activation_state` | `enum('invited','active','blocked')` | NO | ∅ | — |
| 7 | `session_version` | `int(10) unsigned` | NO | `1` | — |
| 8 | `source_updated_at` | `varchar(40)` | NO | ∅ | — |

Indexes: `PRIMARY(user_id)`; unique `uq_ia_users_email(email)`; secondary
`ix_ia_users_status_name(status,full_name)`. Foreign keys: none.

### 4.2 `{prefix}fm2_pilot_roles`

| # | Column | Type | Null | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `role_id` | `bigint(20) unsigned` | NO | ∅ | `auto_increment` |
| 2 | `code` | `varchar(64)` | NO | ∅ | — |
| 3 | `name` | `varchar(300)` | NO | ∅ | — |
| 4 | `description` | `varchar(500)` | NO | ∅ | — |
| 5 | `status` | `tinyint(1)` | NO | ∅ | — |
| 6 | `source_updated_at` | `varchar(40)` | NO | ∅ | — |

Indexes: `PRIMARY(role_id)`; unique `uq_ia_roles_code(code)`. Foreign keys:
none.

### 4.3 `{prefix}fm2_pilot_role_permissions`

| # | Column | Type | Null | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `role_id` | `bigint(20) unsigned` | NO | ∅ | — |
| 2 | `permission` | `varchar(100)` | NO | ∅ | — |

Indexes: `PRIMARY(role_id,permission)` and no additional semantic index. FK:
`role_id → {prefix}fm2_pilot_roles(role_id)`, DELETE `CASCADE`, symbol section
2.1.

### 4.4 `{prefix}fm2_pilot_user_roles`

| # | Column | Type | Null | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `user_id` | `bigint(20) unsigned` | NO | ∅ | — |
| 2 | `role_id` | `bigint(20) unsigned` | NO | ∅ | — |
| 3 | `origin` | `varchar(40)` | NO | ∅ | — |
| 4 | `assigned_at` | `varchar(40)` | NO | ∅ | — |
| 5 | `assigned_by_user_id` | `bigint(20) unsigned` | YES | `NULL` | — |

Indexes: `PRIMARY(user_id,role_id)`; FK-supporting secondary
`ix_ia_user_roles_role(role_id)`. FKs: `user_id → users(user_id)`, DELETE
`CASCADE`; `role_id → roles(role_id)`, DELETE `RESTRICT`; exact prefixed parent
tables and symbols section 2.1. There is deliberately no FK on
`assigned_by_user_id`; adding one is redesign and conflicts.

### 4.5 `{prefix}fm2_pilot_auth_credentials`

| # | Column | Type | Null | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `user_id` | `bigint(20) unsigned` | NO | ∅ | — |
| 2 | `email_normalized` | `varchar(254)` | NO | ∅ | — |
| 3 | `password_hash` | `varchar(255)` | YES | `NULL` | — |
| 4 | `password_set_at` | `varchar(40)` | YES | `NULL` | — |
| 5 | `updated_at` | `varchar(40)` | NO | ∅ | — |

Indexes: `PRIMARY(user_id)`; unique
`uq_ia_auth_credentials_email(email_normalized)`. FK: `user_id →
users(user_id)`, DELETE `CASCADE`, symbol section 2.1.

### 4.6 `{prefix}fm2_pilot_invitations`

| # | Column | Type | Null | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `id` | `bigint(20) unsigned` | NO | ∅ | `auto_increment` |
| 2 | `user_id` | `bigint(20) unsigned` | NO | ∅ | — |
| 3 | `token_hash` | `binary(32)` | NO | ∅ | — |
| 4 | `expires_at` | `datetime(6)` | NO | ∅ | — |
| 5 | `used_at` | `datetime(6)` | YES | `NULL` | — |
| 6 | `revoked_at` | `datetime(6)` | YES | `NULL` | — |
| 7 | `created_by_user_id` | `bigint(20) unsigned` | YES | `NULL` | — |
| 8 | `created_at` | `datetime(6)` | NO | ∅ | — |

Indexes: `PRIMARY(id)`; unique `uq_ia_invitations_token(token_hash)`;
secondary `ix_ia_invitations_user_expiry(user_id,expires_at)`. FK: `user_id →
users(user_id)`, DELETE `CASCADE`, symbol section 2.1. There is deliberately no
FK on `created_by_user_id`.

### 4.7 `{prefix}fm2_pilot_user_role_events`

| # | Column | Type | Null | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `id` | `bigint(20) unsigned` | NO | ∅ | `auto_increment` |
| 2 | `user_id` | `bigint(20) unsigned` | NO | ∅ | — |
| 3 | `role_id` | `bigint(20) unsigned` | NO | ∅ | — |
| 4 | `action` | `varchar(40)` | NO | ∅ | — |
| 5 | `occurred_at` | `varchar(40)` | NO | ∅ | — |
| 6 | `actor_user_id` | `bigint(20) unsigned` | YES | `NULL` | — |

Indexes: `PRIMARY(id)`; secondary
`ix_ia_user_role_events_user(user_id,id)`. Foreign keys: none.

### 4.8 `{prefix}fm2_pilot_auth_attempts`

| # | Column | Type | Null | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `id` | `bigint(20) unsigned` | NO | ∅ | `auto_increment` |
| 2 | `email_normalized` | `varchar(254)` | NO | ∅ | — |
| 3 | `succeeded` | `tinyint(1)` | NO | ∅ | — |
| 4 | `attempted_at` | `datetime(6)` | NO | ∅ | — |

Indexes: `PRIMARY(id)`; secondary
`ix_ia_auth_attempts_email_time(email_normalized,attempted_at)`. Foreign keys:
none.

### 4.9 `{prefix}fm2_pilot_user_status_events`

| # | Column | Type | Null | Default | Extra |
|---:|---|---|---|---|---|
| 1 | `id` | `bigint(20) unsigned` | NO | ∅ | `auto_increment` |
| 2 | `user_id` | `bigint(20) unsigned` | NO | ∅ | — |
| 3 | `action` | `varchar(40)` | NO | ∅ | — |
| 4 | `occurred_at` | `varchar(40)` | NO | ∅ | — |
| 5 | `actor_user_id` | `bigint(20) unsigned` | NO | ∅ | — |

Indexes: `PRIMARY(id)`; secondary
`ix_ia_user_status_events_user(user_id,id)`. Foreign keys: none. The observed
absence of FKs on event identity columns is preserved; this spec does not
redesign relationships or approve audit semantics.

## 5. Classification, mutation and result

One family preflight inspects all nine exact target names and fully classifies
every present member before first DDL/DML. Result ordering always follows
section 1. `conflictingTables` and `missingTables` contain unique fully prefixed
names in that order. Metadata queries are exact-name scoped and ignore decoy
namespaces.

### Clean

No target member exists. Migration creates all nine in dependency-safe order,
without rows, seed or events:

```text
applied = true
schemaVersion = 6
tablesCreated = [all nine names in section 1 order]
```

### Exact-compatible complete / repeat / populated

All nine exist and conform after section 3 normalization. Migration performs no
DDL/DML and returns:

```text
applied = false
schemaVersion = 6
tablesCreated = []
```

This result is identical for empty and populated families. All row values,
password hashes, tokens, timestamps, role/permission rows, event rows,
AUTO_INCREMENT counters and schema bytes outside ignored presentation metadata
remain unchanged.

### Exact-compatible partial recovery

A non-empty proper subset exists and **every present member is exact-compatible**.
Migration creates only missing members in dependency-safe order and never
alters/recreates present members or data. It returns `applied=true` and
`tablesCreated` containing only actually created names in section 1 order.
After success all nine conform.

This restartable MariaDB DDL policy соответствует owner-approved и strict-valid
OpenSpec artifacts. Любой incompatible existing member по-прежнему блокирует
всю family до первого DDL/DML.

### Incompatible family

If any present member differs in any significant field, the entire preflight
returns before DDL/DML:

```text
applied = false
schemaVersion = 6
reason = SCHEMA_MIGRATION_CONFLICT
conflictingTables = [all incompatible present names in section 1 order]
missingTables = [all absent names in section 1 order]
tablesCreated = []
```

This applies to a single column/index/FK defect, multiple family defects,
wrong engine/charset/collation, view substitution, extra structure or a
cross-prefix FK. No missing table is created, no compatible table is changed,
no row/counter is changed and later migrations are not invoked. Driver errors
outside classified conflict remain runner `MIGRATION_FAILED`; confirmed earlier
MariaDB DDL is not falsely reported rolled back, and a repeat reclassifies the
whole family.

Application diagnostic result сохраняет ordered `conflictingTables`,
`missingTables` и `tablesCreated` для проверки classifier и orchestration.
At CLI boundary migration conflict preserves existing runner contract: exact
single JSON line with `ok=false`, reason `SCHEMA_MIGRATION_CONFLICT`,
`schemaVersion=6`, empty stderr and exit `2`. Success final
`schemaVersion` is `6`; `appliedVersions` contains literal `6` iff this
invocation created at least one identity member, and omits `6` on repeat. Runner output does not disclose prefix/table names,
SQL, catalog diagnostics, connection data or driver details.

## 6. Executable examples

### A. Clean prefix and stable catalog

With valid prefix `ia_`, no target tables and database-default utf8mb4 collation,
one CLI run creates exactly the nine `ia_` tables with section 4 semantics,
explicit `fk_ia_ia_*` non-empty-prefix symbols per section 2.1 and the printed
table-local index names. Every table is empty. Result contains exact
`appliedVersions:[1,2,3,4,5,6]` and final `schemaVersion:6`.

### B. Populated exact-compatible repeat

An independently created compatible family contains sentinel rows in every
table (including nullable credential/invitation values and at least one row in
each event/attempt table), non-default AUTO_INCREMENT next values, and generated
legacy FK/index names. One runner call succeeds with exact
`appliedVersions:[]` and `schemaVersion:6`. Independent before/after row digests, ordered catalog
semantic tuples and AI counters are equal. No name-only rename occurs.

### C. Compatible 8/9 recovery

Eight exact tables exist and `fm2_pilot_user_status_events` is absent. Sentinel
rows and counters exist in the other eight. Runner creates only the missing
empty ninth table, returns exact `appliedVersions:[6]` and `schemaVersion:6`, and preserves all
sentinels/counters. A second run is a no-op. A second fixture omits a
dependency-safe middle member (`roles`) while its dependent members are absent;
recovery creates the exact missing subset in dependency order.

### D. Incompatible partial preflight

`bad_fm2_pilot_users` has an extra column, compatible roles exists, and the
other seven tables are absent. Runner returns conflict naming users and the
seven missing members in normative order. Before/after schema, rows and counters
are equal; zero missing tables are created and identity is not applied.

### E. Complete relationship conflict

All nine names exist, but `user_roles.role_id` references a decoy-prefix roles
table or has DELETE `CASCADE`. Runner reports only `user_roles` conflicting,
does zero mutation and does not register identity. A separate collation fixture
uses a different utf8mb4 collation and is likewise rejected without conversion.

### F. Prefix coexistence and limits

In one database, sequential runner calls for `blue_` and `green_` create two
complete families. FK symbols are prefix-derived and do not collide; every FK
references its own prefix. Decoy rows/tables of the other namespace remain
unchanged. Prefix lengths 25 and 26 are independently exercised: 25 succeeds
and every derived table/FK/index identifier is at most 64 bytes; 26 is rejected
before DB connection/access.

### G. Runtime has no DDL

After migration, login, invitation issue/acceptance, role attach/detach,
block/unblock and access projection characterization traverse their current
public HTTP/application seams. A DB observer sees no `CREATE`, `ALTER`, `DROP`
or schema repair; block/unblock can append to the pre-created status-events
table. With that table missing or incompatible, runtime fails closed through
the existing safe error boundary and performs no DDL. Exact authorization and
audit outcome is not asserted beyond unchanged characterization (`GRILL-002`).

### H. Destructive seed is separate

Canonical CLI migration on clean or compatible schema creates no users, roles,
permissions or grants and never calls bootstrap rebuild. A separately and
explicitly invoked disposable bootstrap may destructively reset/seed only its
validated owned namespace; its output/call path cannot be confused with runner
success, repeat or partial recovery.

## 7. Gate 2 independent matrix

Gate 2 starts only after owner approval and must be authored/reviewed independently
from implementation. Operator-boundary tests run the real CLI process against
isolated MariaDB; classifier-detail tests use the public migration application
result object before CLI redaction. Tests transcribe section 4 manifests into
test-owned literal tuples; production
DDL, production constants, SHOW CREATE output and post-migration catalog cannot
generate expected values.

Required sensitivity matrix:

1. clean creation: exact nine manifests, empty rows, deterministic symbols,
   `schemaVersion:6` and `appliedVersions:[1,2,3,4,5,6]`;
2. safe complete repeat and fully populated compatibility with byte-observable
   row, counter and semantic-catalog preservation;
3. exact-compatible partial recovery, interrupted recovery repeat and a missing
   dependency subset;
4. zero-mutation conflicts independently covering ordinal/name/type,
   nullability/default/AI flag, enum, extra column, PK/unique/secondary index,
   FK target/columns/delete/update, engine, charset and non-default collation;
5. multi-table family conflict returns complete deterministic lists and creates
   no missing member;
6. generated-name compatibility plus canonical explicit-name creation;
7. two complete non-empty prefixes in the same database, cross-prefix decoys,
   exact FK isolation and no database-global symbol collision;
8. prefix 25 accepted with all derived identifier lengths asserted; prefix 26
   and invalid characters rejected before DB access;
9. runtime DDL observer covers login, invitation, role and block/unblock paths,
   migrated success characterization and missing/incompatible fail-closed path;
10. canonical migration never seeds/rebuilds, while destructive bootstrap is a
    separately invoked seam;
11. exact CLI stdout/stderr/exit and redaction for conflict and unexpected
    failure; ordered conflict/missing lists and stop-before-later-migration are
    asserted at the application diagnostic seam. Assertions requiring v6 to
    exist are authored and independently reviewed during RED, then first execute
    after minimal v6 GREEN without weakening expectations.

Gate 2 records a demonstrated RED caused by the absent identity migration and
remaining runtime DDL, not fixture failure. A separately tasked reviewer records
Gate 3 in `reviews/tests/IDENTITY-ACCESS-SCHEMA-001.md`. Tests must not assert
that current local RBAC authority or audit retention is correct.

## 8. Security, preservation and failure classification

The runner is an offline deployment seam authorized by deployment DB
credentials, not an HTTP/session actor. It emits no process/security audit event
and exposes no secrets or schema details in CLI output. Migration never reads
password/token values for decisions beyond opaque preservation snapshots owned
by tests, never logs them, and never normalizes identity data.

Five observed FKs and deliberate omissions in section 4 are preserved exactly.
Adding actor FKs, unifying timestamp types, retaining successful auth attempts,
changing role catalogue refresh or making bootstrap grants append audit events
requires separate product/security Gate 1 after `GRILL-002`; none is smuggled
into schema canonicalization.

Failure classes are mutually observable:

- invalid config/prefix: `CONFIGURATION_INVALID`, exit 64, before DB;
- connection/utf8mb4 confirmation failure: `DATABASE_UNAVAILABLE`, exit 69,
  before schema mutation;
- classified semantic schema drift: `SCHEMA_MIGRATION_CONFLICT`, exit 2,
  `schemaVersion=6`, zero identity-family mutation;
- unexpected metadata/DDL/driver failure: `MIGRATION_FAILED`, exit 70; previously
  confirmed DDL may persist and is handled by exact-compatible partial recovery.

No automatic down/rebuild/rename/collation conversion exists. Application image
rollback is allowed only when it remains compatible with the unchanged family.

## 9. Done

This slice is Done only when all are true:

- owner changes this artifact to `APPROVED` and Gate 1 decision to `APPROVED`;
- OpenSpec partial-family policy остаётся reconciled с approved compatible
  partial recovery без ослабления incompatible preflight;
- Gate 2 literal tests and demonstrated RED have independent Gate 3 `APPROVED`;
- minimal implementation registers literal version `6` after landed v5, owns
  exactly nine tables, uses deterministic MariaDB-safe symbols and passes clean,
  repeat, populated, partial, conflict, prefix and preservation cases;
- runtime/request DDL is absent and destructive seed/rebuild remains separate;
- `GRILL-002` remains explicit and no RBAC/auth/audit behavior is newly approved
  or changed;
- focused verification, built-image/fresh lifecycle, `make architecture-check`
  and `make verify` are green;
- separately tasked Gate 5 reviewer records `APPROVED` in
  `reviews/code/IDENTITY-ACCESS-SCHEMA-001.md`.

## 10. Evidence and approval

Normative fingerprints are derived from
`docs/operations/identity-access-schema-evidence.md`, the exact DDL owners
`rapid-pilot/IdentityBootstrap.php` and `rapid-pilot/UserAccessView.php`, and
their inspected consumers. Evidence SHA-256/SHOW CREATE hashes remain
reproduction evidence, not compatibility inputs, because they include generated
and environment metadata normalized by this spec.

Relevant inherited contracts: `PRODUCT.md`, `CONTEXT.md`,
`docs/fmonitor-2-pilot-spec.md`, `docs/fmonitor-2-pilot-data-model.md`,
`docs/development-process.md`, the historical approved 32-byte contract in
`specs/PRODUCTION-MIGRATION-RUNNER-001.md`, and the superseding current
catalogue inventory in `docs/operations/catalogue-prefix-ceiling-reconciliation.md`.

- Владелец продукта: пользователь проекта
- Дата решения: `2026-09-01`
- Решение: `APPROVED`
- Комментарий: owner явно утвердил `IDENTITY-ACCESS-SCHEMA-001 v0.1` с
  literal canonical version `6` и restartable exact-compatible partial
  recovery. Owner amendment разрешает application diagnostic seam для exact
  internal lists при сохранении redacted CLI и разрешает впервые исполнить
  v6-dependent failure/short-circuit assertions на minimal GREEN. Второй owner
  amendment разрешает MariaDB UCA alias normalization при обязательных safe-name,
  utf8mb4 database и trial-application checks. Gate 2
  разрешён; `GRILL-002` продолжает блокировать только отдельные RBAC/authorization
  behavior changes.
