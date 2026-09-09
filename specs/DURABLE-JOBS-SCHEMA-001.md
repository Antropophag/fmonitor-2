# DURABLE-JOBS-SCHEMA-001 — deployment-owned queue schema

## Contract

The bounded foundation defines exactly `fm2_jobs` and `fm2_job_events`. Final v23
is the union of this manifest and the independently approved four-table
DURABLE-JOBS-EXTENSIONS-SCHEMA-001 manifest; the complete installation has six
owned tables and performs all-six preflight before any DDL.
Canonical migration 23 SHALL add this durable Jobs schema after the established
version 22 catalogue. `FMonitor2\InstallationProcess\JobsSchemaMigration::apply(mysqli, prefix)`
is the explicit deployment owner; `isReady(mysqli, prefix)` is read-only.
Ordinary enqueue/claim/completion SHALL NOT call either DDL or the migration owner.

Before any DDL, the migration SHALL reject an incompatible existing owned table.
It SHALL preserve every existing table and row on that rejected preflight. A clean
installation SHALL become ready; a compatible populated repeat SHALL report
`applied=false` and preserve exact job and append-only event rows. Prefixes isolate
owned families. The canonical runner SHALL reach 23 and repeat without applying
any version again.

A principal with only SELECT/INSERT/UPDATE/DELETE on the target database SHALL
enqueue, claim and complete through `MariaDbJobQueue` after deployment migration.
An actual CREATE statement from that principal SHALL be denied by MariaDB. This
contract does not grant migration rights to workers or scheduler.

## Executable evidence

`tests/Jobs/jobs_schema_001_test.php` uses task-owned real MariaDB databases and an
isolated DML account. It compares literal table inventories and exact job/event
rows, exercises canonical 23/replay and fails at the absent public schema owner
before implementation. It never resets the shared test database or a running stand.

## Exact physical manifest

The normative literal manifest is `tests/Support/jobs_schema_manifest.json` (not a
production input). It defines every column in order, type, nullability, default,
extra, charset/collation, ordered index, foreign key and check constraint for the
two foundation tables. `@prefix` is replaced by the validated table prefix; foreign
key targets receive the same prefix. Check-constraint names are not semantic; their
expressions are compared after whitespace/backtick normalization and removal of redundant whole-expression
parentheses only outside quoted string literals. Internal grouping remains semantic. Literal bytes and case are semantic and
SHALL be preserved; uppercase READY is incompatible with binary lowercase ready.
Integer display widths are ignored because MariaDB versions may omit them;
all other listed physical properties are exact. Column null defaults from MariaDB
are normalized from SQL `NULL` to JSON null. No additional columns/indexes/FKs or
checks count as compatible. InnoDB and table collation utf8mb4_unicode_ci are required.

Status projection uses ready/leased/completed/dead. JSON payload/result/actor/event
details are validated structurally by the database and semantically by Jobs. All
UTC strings use the single fixed 27-character format from the public contract, so
lexical lease/schedule comparisons preserve instant order. The nullable self-link
retains manual-retry provenance; neither FK permits delete/update cascading.

Two independent prefixes in one database SHALL install and replay without changing
the other prefix's job/event rows. An existing malformed owned table in either ordering position SHALL
prevent creation of every missing owned table. Canonical catalogue integration and
readiness do not broaden the migration's ownership to ambient tables.
