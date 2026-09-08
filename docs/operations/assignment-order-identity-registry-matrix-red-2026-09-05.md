# Registry engine matrix — Gate2 RED before implementation

Дата2026-09-05. Test author `/root`.
Gate1 approved spec hash31ffe9a; review
`assignment-order-identity-registry-gate1-review-v01-2026-09-05.md`.
First populated/repeat tracer has scoped Gate3 approval at06f0576. Он не менялся.

Добавлены schema, recovery и concurrency tests плюс task-owned DB fixture и
process worker. Production engine/facade/observer/snapshot classes отсутствуют;
production code не написан. Все четыре scripts запущены до implementation,
каждый завершился exit1 с explicit missing-public-engine assertion, после
успешного real DB/predecessor setup и с успешным exact owned schema cleanup.

```text
assignment_order_identity_registry_001_test.php:
RED_ASSERTION: public assignment-order identity registry migration is missing
assignment_order_identity_registry_schema_001_test.php:
RED_ASSERTION: registry schema engine is missing
assignment_order_identity_registry_recovery_001_test.php:
RED_ASSERTION: registry recovery engine is missing
assignment_order_identity_registry_concurrency_001_test.php:
RED_ASSERTION: registry concurrency engine is missing
Expected: true / Actual: false — для каждого invocation.
```

Commands — `php tests/InstallationProcess/<file>` без flags/skips. Private exact
stdout/stderr/exits и input hashes:
`/Users/antropophag/.local/state/fmonitor2-verification/admission-20260905-w7wmv_ye/registry-red-matrix-v2.json`.
Previous v1 archive сохранён отдельно; он предшествует последним matrix cases.

## Coverage, ещё требующая independent Gate3

Schema: empty/populated, prefix0/25 и invalid26, exact ordinal fields/types/
nullability/default/ASCII collation, indexes/FK/check predicates; empty hash,
repeat/catalog/counters, caller transaction, wrong sibling/source shape,
orphan/invalid date/status-version/range, receipt/hash/time corruption,
nonempty-unreceipted family, committed-receipt missing sibling, regressed
frontier, initial max/max+1, late exhaustion and corrupt oversized persisted ID,
separate real DDL denial и post-DDL DML denial plus privileged recovery.

Recovery: every specified phase throw, exact phase-prefix/success timing,
pre-commit atomic rollback, post-commit acknowledgement loss, caller connection/
transaction/lock release, empty receipts-only partial, preserved current registry
gap101 поверх legacy81, deterministic changed-source capture и non1 release.

Concurrency: phase pipe barriers, same-prefix actual5s lock timeout and no DDL,
independent prefix success while first lock retained, first success/subsequent
repeat, separate real TERM at BEFORE_BACKFILL_COMMIT with fresh external visibility,
subsequent recovery, owned child reap and DB/user cleanup. Foreign-prefix marker
и all source snapshots сохраняются при migration. Worker не является runtime hook.

Все negative/recovery branches пока не исполнялись за missing-seam assertion;
этот record не выдумывает их observed failures. Fixed expected values/phase
sequence/schema manifest происходят из spec, а before/after snapshots доказывают
preservation, не формируют expected schema. При GREEN должны пройти все branches.

## Setup correction history

Первый schema-test attempt имел дополнительный cleanup failure: PHP не разрешил
bind_param by-reference к readonly fixture name. Это не qualifying clean RED.
До independent review helper исправлен локальной копией name; повторные runs
в archive v1/v2 показывают только intended missing engine assertion и clean
catalog absence. Также numeric PHP array key frontier явно приведён к строке
для independently specified lossless decimal comparison. Эти test-authoring
исправления сделаны до какого-либо matrix Gate3/production edit.

PHP lint всех новых scripts PASS; git diff --check PASS. Full matrix Gate3
ещё требуется. Нет full engine GREEN/Gate5, canonical registration, writer
cutover или parent Done. Protected E2E, safe-log mechanisms и remote не менялись.
