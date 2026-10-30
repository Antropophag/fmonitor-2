# Object-scoped OTIZ payment decisions — independent final code review

Reviewer: `/root/otiz_ui_review`, not an author of code or tests.
Verdict: **APPROVED**, 2026-10-30. Base: `70eb4008`.

## Reviewed behavior

- A manual `do_not_pay` decision is keyed by exact calculation, object and employee.
  The selected share becomes reserve with its reason; coworkers and the same employee
  on other objects retain their normal amounts.
- Nullable `object_id` preserves historical/global decisions. Exact scoped decisions
  override a global decision only for their pair. Existing accepted/paid projections
  are not recomputed on read and paid facts are immutable.
- Membership, reason, revision, authorization, idempotency/replay, deduction conflict,
  transaction locking and no-write failures remain owned by the settlement seam.
  Preview is read-only and returns only the selected object with coworker effects.
- Replacement drafts preserve nullable object scope and normalize entitlement baselines
  before projection, preventing duplicate subtraction/recognition.
- HTTP always supplies object scope; missing/foreign scope is rejected rather than
  becoming global. Object and employee groupings require explicit pair selection.
  Stale and missing-reason errors recover only the matching modal with an inline alert.
- Workbook decisions expose object scope while retaining corporate formatting and all
  financial values. Reserve release behavior is unchanged.

## Migration and recovery

Migration v38 performs complete preflight before additive DDL. Existing column/index
shapes are validated as nullable unsigned bigint and ordered non-unique BTREE;
incompatible partial schemas fail without mutation. Predecessor data remains byte/value
equivalent with NULL global scope, canonical replay is idempotent, and migration 38 is
registered at the current frontier.

Runtime backup/restore advances to schema v38 while reusing the unchanged v37 table and
auto-increment inventory. Exact v38 backup/restore and readiness checks pass. A 77 MB
pre-deploy DB backup exists and production preflight records schema 37 without the new
column, the expected predecessor state.

## Evidence and limitations

GREEN evidence includes scoped model/replacement/payment/history, mutation-free
migration conflicts and canonical repeat, HTTP owner path, decision/filter browsers,
stale and server-side missing-reason recovery, concurrency, feedback browser,
workbook/editing compatibility, v38 recovery and runtime readiness. Quality-policy and
suite registration map the new migration/model/recovery files to their focused tests
without an exemption or policy weakening. `git diff --check` and PHP lint pass.

Full local suite/CI and ordinary gate ordering were deferred by the owner's short
production cycle; this review does not claim overall CI GREEN.

## Production operation addendum

The bounded standalone-v38 delivery route is independently approved. It temporarily
blocks OTIZ POST requests in nginx, validates and reloads that exact configuration,
drains in-flight work, then acquires the canonical MariaDB migration lock. Under the
lock it requires no active InnoDB transaction, the exact captured v37 predecessor DDL,
and unchanged financial and identity fingerprints. It applies only the reviewed v38
migration, requires exact result/NULL legacy scope, verifies all facts unchanged and a
second no-op. It does not run past or forge success for the unresolved canonical v14
conflict.

Runtime delivery uses reviewed-source and production-baseline CAS for host and PHP
container bytes, preserves rollback copies, lints PHP, verifies all hashes, and only
then publishes v38 readiness. On any failure it restores every applied file first;
under the restored code it invalidates startup attestation, recomputes deep readiness,
publishes and asserts the restored marker, records `rollback-readiness.json`, and
requires schema version 37. Nginx is restored from exact saved bytes in the outer
`finally`. A file-only rollback after scoped writes remains forbidden; the retained
77 MB database backup is the recovery boundary.

Reviewed ops-script hashes:

- `fc34ae36266ede4f66cf5c056df0e6ef4cd74ada230d2c8453d6b8efefbcc0ad` `apply-v38-direct.php`
- `89b51cfaf65b690ca9b5ba724175b0291e32ee4a561eeca2928e1f20840891e2` `deliver-v38.py`
- `0f82e3eb751f8619c8cb20e049ca5fe5ed1e7f529e0762d0b216aec7716a763a` `deploy-final.py`

## Exact runtime binding

- `cb73fda0a72f29bf6f475564bab5239084e7ed9252625b3a46f5a1251c789126` `app/InstallationProcess/OtizPaymentDecisionObjectScopeSchemaMigration.php`
- `2b6e12e9c4658810d39ca76195fc65c039b237f86efc0656e52602f11cd4cc63` `app/InstallationProcess/ProductionPilotMigrationCatalogue.php`
- `55dafde15659b11458e73fcea65c20acbc2fad42857f79dc66e6f2aed429ba0f` `app/Otiz/MariaDbOtizSettlementV2.php`
- `a984904db43c0fc03d94e53b272712f20828854a692aece8868de396cf41ff9a` `app/Otiz/OtizSettlementV2Workbook.php`
- `bff12d43a24355ffd6c3c13fe5f65f7cd0a598e2d151095635609ce223a6626c` `app/RuntimeRestore/RuntimeRecovery.php`
- `dee9922c2de8238d78858bf603b4accfe1a9d68be05a2cae91a09cf8fea86371` `app/RuntimeRestore/RuntimeRecoverySchemaV38.php`
- `273e19ee0feed9dc4096acdda9d43733ecaa002ca493cd6d3a0b2fe043e91c72` `app/YiiRuntime/Assets/otiz.js`
- `e0b280705e1e5014cd760580f86cd6f2c25942d972d60f536a825a5d358c0620` `app/YiiRuntime/Controllers/OtizSettlementV2Controller.php`
- `d6e5bd51c6848d57bc0ccf2bc0f4fa5b8419dec54247363ca43434ca35056869` `app/YiiRuntime/Views/_otiz-v2-group-table.php`
- `00e60d132a95c6eeae0b5ca473c5ac29a609c0d4b02bed82a0a226a6a9d2ae77` `app/YiiRuntime/Views/otiz-calculation-v2.php`
