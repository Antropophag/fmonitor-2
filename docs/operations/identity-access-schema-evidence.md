# Identity/access schema evidence

Evidence captured 2026-09-01 from the current working tree and a disposable
MariaDB 11.4.7 test database. This document is derived evidence, not an
approved executable specification, migration contract, or promotion of the
observed rapid-pilot behavior.

## Scope and authority

The exact family is nine tables:

1. `fm2_pilot_users`
2. `fm2_pilot_roles`
3. `fm2_pilot_role_permissions`
4. `fm2_pilot_user_roles`
5. `fm2_pilot_auth_credentials`
6. `fm2_pilot_invitations`
7. `fm2_pilot_user_role_events`
8. `fm2_pilot_auth_attempts`
9. `fm2_pilot_user_status_events`

The first eight `CREATE TABLE IF NOT EXISTS` literals are owned today by
`rapid-pilot/IdentityBootstrap.php::createSchema()`. Its `apply()` operation
also seeds roles, permissions, configured bootstrap accounts and grants; its
`replaceLegacySchema()` may destructively drop the first eight tables when an
old roles shape is detected. The ninth table is lazily created inside the
block/unblock request transaction in `rapid-pilot/UserAccessView.php`.
`rapid-pilot/docker-bootstrap.php` calls `IdentityBootstrap::apply()`.

The intended future owner is the canonical migration layer invoked by
`bin/fmonitor2-migrate.php`, but no identity/access migration exists yet. The
OpenSpec artifacts under
`openspec/changes/canonicalize-identity-access-schema/` are unchecked planning
artifacts and explicitly defer exact fingerprints to an approved Gate 1
executable schema specification. No such identity schema spec or independent
test/code review record was found.

Primary source consumers inspected were `rapid-pilot/LocalAuth.php`,
`rapid-pilot/UserAccessView.php`, `app/RapidPilot/LocalRoleCatalog.php`,
`app/PilotHttp/AccessPolicy.php`, `app/PilotHttp/UserDirectoryView.php`,
`app/InstallationProcess/MariaDbProcessUserDirectory.php` and
`rapid-pilot/issue-invitation.php`. Reduced identity fixtures in unrelated
verifiers are not treated as schema authorities.

## Exact observed shape

Notation: `NN` = NOT NULL, `NULL` = nullable, `AI` = AUTO_INCREMENT. Ordinals
are the order shown. A missing default means MariaDB reported no default;
`NULL` means an explicit/effective nullable default of NULL.

| Table | Columns in ordinal order |
|---|---|
| `fm2_pilot_users` | `user_id bigint(20) unsigned NN AI`; `full_name varchar(300) NN`; `email varchar(254) NN`; `phone varchar(100) NN DEFAULT ''`; `status tinyint(1) NN DEFAULT 1`; `activation_state enum('invited','active','blocked') NN`; `session_version int(10) unsigned NN DEFAULT 1`; `source_updated_at varchar(40) NN` |
| `fm2_pilot_roles` | `role_id bigint(20) unsigned NN AI`; `code varchar(64) NN`; `name varchar(300) NN`; `description varchar(500) NN`; `status tinyint(1) NN`; `source_updated_at varchar(40) NN` |
| `fm2_pilot_role_permissions` | `role_id bigint(20) unsigned NN`; `permission varchar(100) NN` |
| `fm2_pilot_user_roles` | `user_id bigint(20) unsigned NN`; `role_id bigint(20) unsigned NN`; `origin varchar(40) NN`; `assigned_at varchar(40) NN`; `assigned_by_user_id bigint(20) unsigned NULL DEFAULT NULL` |
| `fm2_pilot_auth_credentials` | `user_id bigint(20) unsigned NN`; `email_normalized varchar(254) NN`; `password_hash varchar(255) NULL DEFAULT NULL`; `password_set_at varchar(40) NULL DEFAULT NULL`; `updated_at varchar(40) NN` |
| `fm2_pilot_invitations` | `id bigint(20) unsigned NN AI`; `user_id bigint(20) unsigned NN`; `token_hash binary(32) NN`; `expires_at datetime(6) NN`; `used_at datetime(6) NULL DEFAULT NULL`; `revoked_at datetime(6) NULL DEFAULT NULL`; `created_by_user_id bigint(20) unsigned NULL DEFAULT NULL`; `created_at datetime(6) NN` |
| `fm2_pilot_user_role_events` | `id bigint(20) unsigned NN AI`; `user_id bigint(20) unsigned NN`; `role_id bigint(20) unsigned NN`; `action varchar(40) NN`; `occurred_at varchar(40) NN`; `actor_user_id bigint(20) unsigned NULL DEFAULT NULL` |
| `fm2_pilot_auth_attempts` | `id bigint(20) unsigned NN AI`; `email_normalized varchar(254) NN`; `succeeded tinyint(1) NN`; `attempted_at datetime(6) NN` |
| `fm2_pilot_user_status_events` | `id bigint(20) unsigned NN AI`; `user_id bigint(20) unsigned NN`; `action varchar(40) NN`; `occurred_at varchar(40) NN`; `actor_user_id bigint(20) unsigned NN` |

### Keys and indexes

| Table | Primary / unique / secondary indexes |
|---|---|
| `fm2_pilot_users` | PK `(user_id)`; UNIQUE `email (email)`; secondary `status (status, full_name)` |
| `fm2_pilot_roles` | PK `(role_id)`; UNIQUE `code (code)` |
| `fm2_pilot_role_permissions` | PK `(role_id, permission)` |
| `fm2_pilot_user_roles` | PK `(user_id, role_id)`; MariaDB-created secondary `role_id (role_id)` |
| `fm2_pilot_auth_credentials` | PK `(user_id)`; UNIQUE `email_normalized (email_normalized)` |
| `fm2_pilot_invitations` | PK `(id)`; UNIQUE `token_hash (token_hash)`; secondary `user_id (user_id, expires_at)` |
| `fm2_pilot_user_role_events` | PK `(id)`; secondary `user_id (user_id, id)` |
| `fm2_pilot_auth_attempts` | PK `(id)`; secondary `email_normalized (email_normalized, attempted_at)` |
| `fm2_pilot_user_status_events` | PK `(id)`; secondary `user_id (user_id, id)` |

All indexes observed as BTREE. Literal `UNIQUE`/`KEY` clauses are unnamed, so
the displayed names above are MariaDB-generated names, not portable contract
names.

### Foreign keys

MariaDB reported `ON UPDATE RESTRICT` for every FK (implicit in the source):

| Child column | Observed generated constraint name | Parent | DELETE |
|---|---|---|---|
| `role_permissions.role_id` | `{prefix}fm2_pilot_role_permissions_ibfk_1` | `roles.role_id` | CASCADE |
| `user_roles.user_id` | `{prefix}fm2_pilot_user_roles_ibfk_1` | `users.user_id` | CASCADE |
| `user_roles.role_id` | `{prefix}fm2_pilot_user_roles_ibfk_2` | `roles.role_id` | RESTRICT |
| `auth_credentials.user_id` | `{prefix}fm2_pilot_auth_credentials_ibfk_1` | `users.user_id` | CASCADE |
| `invitations.user_id` | `{prefix}fm2_pilot_invitations_ibfk_1` | `users.user_id` | CASCADE |

There are deliberately/observably no FKs on `assigned_by_user_id`,
`created_by_user_id`, either event table's identity columns, or auth attempts.
Whether those omissions are desired canonical behavior is UNKNOWN.

### Engine, charset and collation

Every source literal specifies `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` and no
explicit `COLLATE`. In the disposable MariaDB 11.4.7 database every table had
`InnoDB` and table collation `utf8mb4_uca1400_ai_ci`; every character/enum
column had charset `utf8mb4` and the same collation. Numeric, binary and datetime
columns had no column charset/collation. The collation is environment-derived,
not source-defined, and therefore cannot yet be a portable canonical claim.

## Populated bootstrap observation and audit semantics

The isolated prefix was populated through `RapidPilotIdentityBootstrap::apply`
with one disposable configured superadministrator. The ninth source literal was
then executed separately because normal bootstrap does not create it. Counts
were: users 1, roles 8, role permissions 30, user roles 2, credentials 1,
invitations 0, role events 0, auth attempts 0, status events 0.

Observed AUTO_INCREMENT next values were users 2, roles 9, invitations 1,
user-role events 1, auth attempts 1 and user-status events 1. Tables without an
AI column reported NULL. Role ids happened to be `user=1`, `fkr_operator=2`,
`construction_control_engineer=3`, `construction_control_coordinator=4`,
`otiz_specialist=5`, `manager=6`, `access_administrator=7`, and
`superadministrator=8`; these ids and next values are seed effects, not stable
schema contracts.

Current behavior is mixed rather than uniformly append-only:

- role attach/detach mutates `fm2_pilot_user_roles` and appends
  `role_attached`/`role_detached` to `fm2_pilot_user_role_events`;
- block/unblock mutates user status, activation state and session version, then
  appends `user_blocked`/`user_unblocked` to `fm2_pilot_user_status_events`;
- invitations retain used/revoked timestamps; a replacement revokes an unused
  active invitation;
- auth attempts append failures, but a successful login deletes all attempts
  for that normalized email;
- bootstrap grants use `INSERT IGNORE` and do not append role events; role
  catalogue refresh deletes and recreates permission rows; legacy-shape repair
  can drop eight whole tables.

These are observations of current code, not approval that every record is an
append-only domain audit fact. Event tables also have no referential constraints
and use RFC3339-like `varchar(40)` timestamps, while invitation/auth-attempt
timestamps use `datetime(6)`.

## Reproduction and fingerprints

No secrets, password hashes, invitation tokens, or source records were captured.
The disposable namespace was `iaev1_` in the tmpfs database from
`compose.test.yaml`. Reproduction sequence (credential values omitted here):

```text
docker compose -f compose.test.yaml up -d --wait test-db
php -r 'require "rapid-pilot/IdentityBootstrap.php"; ... RapidPilotIdentityBootstrap::apply($db,"iaev1_",...); ... create the exact UserAccessView status-event literal ...;'
php rapid-pilot/verify-auth-hot-path.php
SHOW CREATE TABLE iaev1_<each table>
SELECT ... FROM information_schema.{COLUMNS,STATISTICS,KEY_COLUMN_USAGE,REFERENTIAL_CONSTRAINTS,TABLES}
DROP TABLE IF EXISTS <the exact nine iaev1_ tables>
docker compose -f compose.test.yaml down
```

The verifier passed with `PASS auth hot path is schema-mutation free`. It is a
static characterization of `LocalAuth` plus selected bootstrap strings; it does
not fingerprint nine tables and does not inspect the lazy DDL in
`UserAccessView`. Its aggregate route is `make characterization-test`.

Literal SHA-256 hashes below normalize only the runtime prefix token to
`{prefix}` and otherwise hash the exact source `CREATE TABLE IF NOT EXISTS ...
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` string:

| Table | Source-literal SHA-256 | Normalized SHOW CREATE SHA-256 |
|---|---|---|
| users | `0af367692b78ba1adc92eab795ead9d118ba2d16464b8fc1efdd1e750d48fcc0` | `5d012e0f4d695191606dfa0c1448981e3b5185f6939c397a6b4e55cf3510bb60` |
| roles | `4ff818d5b522f520589ca4a9efae2598dfe600bc5d4cf2006ebeed69e74c7777` | `ecac210a5b5d483937a537abc81a7506922d0ff3666e43ffbc0dc2fe179d417d` |
| role_permissions | `6277d95575b5013d4c0f1b4ab159f6aa28bafd33e5ca8df5bed899b221a051fe` | `d020e9a80df1321dcb3cc5626f3a5971507fadd2711b3dc0c5ede8b28029acc6` |
| user_roles | `9001e3a1828620e8dd0233a02501bece0fab9cf54f34dcfba7afadcc21949f44` | `e55c4ff4e0dfde9d631d67f2a8221229cabc1edaf8d2d7b528b43e9e668e4b42` |
| auth_credentials | `f6e1fd9007e8d9329118adc582c355ca6a16154b81e8c65ec1817f3676881a3a` | `8db71ee6822bfc769d0340654faaaf91f0625e423ca8f2c221f87d250b663fb4` |
| invitations | `300848fcac16aa42c78a88f80bae2bd4be8fccbe0ef07a2090fc956c48fb8982` | `f144efee9cab321be84298b066f968aaaacfc47b068fd687501c6df0cf6d28e4` |
| user_role_events | `0de6c4a325b89014bad6496ef4abb8315292595645b7f4d61b7517db88be700b` | `4eb78954cee31e724cf2af45b37f177f5b3b135fcb53bfe620d74dc0e51619a5` |
| auth_attempts | `6910e7253da58db6d74dd234ace379a0c1df6c966232668467b21ff2a69e99a6` | `1de75eecd7edbb9dbf16f92eda056ec28ca5bad7d9b54d89170d5e7486e62e08` |
| user_status_events | `7c40baf8ba5245b57c2406e3fed481f50b6663654938283adba215743aea680b` | `787949c21b09f6c7d16d14611c759ce294d3cbcb1428efa96945198e8d6b5eed` |

For SHOW CREATE hashes, `iaev1_` was replaced with `{prefix}` and table-option
`AUTO_INCREMENT=<number>` was replaced with `AUTO_INCREMENT={seed}`; all other
MariaDB 11.4.7 output bytes were retained. These hashes are reproducibility
evidence only, not approved compatibility fingerprints.

Cleanup was verified by an `information_schema.TABLES` count of zero for
`iaev1_%`; the test container and network brought up for this capture were then
removed.

## Differences and UNKNOWNs

### Source vs observed normalization

- MariaDB expanded integer display widths, nullable defaults, generated index
  names, generated FK names, implicit `ON UPDATE RESTRICT`, table collation and
  table options in SHOW CREATE. These are normalization/environment differences,
  not demonstrated semantic differences.
- MariaDB created the supporting `user_roles.role_id` index for its FK.
- Populated AI seeds differ from clean values for users and roles. Seeds are
  operational state and must not make an otherwise compatible schema conflict.

### Material ownership/contract gaps

- Normal bootstrap produces only eight tables; the ninth appears only after a
  block/unblock request. Thus current clean bootstrap can leave a partial 8/9
  family and request-path DDL remains security-sensitive.
- The OpenSpec design's all-or-none partial-family conflict text is reported in
  `docs/operations/status.md` as needing reconciliation with exact-compatible
  partial recovery for restartable MariaDB DDL. This document does not decide
  that policy.
- `CREATE TABLE IF NOT EXISTS` does not validate an already-present table, so
  current code accepts silently incompatible shapes.
- Generated names and the environment-derived collation cannot be adopted as
  canonical without an approved normalization policy.
- It is UNKNOWN whether actor columns should gain FKs, whether audit timestamps
  should be unified, whether successful-login deletion is acceptable audit
  retention, and whether bootstrap role/permission rewrites require events.
- It is UNKNOWN whether any deployed populated database has a different
  collation, seed, generated name, legacy shape, or drift. No production
  database was queried.
- The OpenSpec prose calls the destructive operation `rebuild()`, while the
  current class exposes `apply()` plus private replacement/creation methods.

Before canonical implementation, Gate 1 still needs an approved executable
tuple/fingerprint specification defining ignored vs significant metadata, clean,
repeat, exact-compatible-present, compatible-partial and incompatible-present
outcomes. Independent test review, demonstrated RED, minimal GREEN and
independent code review remain outstanding.
