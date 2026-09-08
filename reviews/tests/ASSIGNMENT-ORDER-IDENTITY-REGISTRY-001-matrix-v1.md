# Test review: ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001 full matrix v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commits authored by Timofey Grishin
- Reviewed commit: `ce6198afdd6dff04b50424e3634e1f3560db0ae8`
- Specification: `specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md` v0.1, SHA256 `31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f`
- Public seam: production migration facade, typed verification apply/snapshot and phase observer, plus standard MariaDB catalog
- Red command and intended failure: each of the four standalone registry tests exits `1` at its explicit missing-public-engine assertion after real owned-database setup and cleanup
- Verdict: `CHANGES_REQUESTED`

## Exact reviewed inputs

```text
31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f  specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md
e5cbce078a73019a1e04a2735c927f19abcbaefd444efeb79fd59a2caff2db2b  docs/operations/assignment-order-identity-registry-gate1-review-v01-2026-09-05.md
e4841961e89b7c7e39c11449579be71a38766c8b20be0023cb52db69b4c74073  tests/InstallationProcess/assignment_order_identity_registry_001_test.php
847936333f4b6700cb7ab7c83c246d331c445dc98df243522263500a5da1bf89  tests/InstallationProcess/assignment_order_identity_registry_schema_001_test.php
23e1453486ad26ac124edbdb921ae9a702ae5196ae895732d957cb8fc713392b  tests/InstallationProcess/assignment_order_identity_registry_recovery_001_test.php
42e885421aa0f73bce6080e25e9ae41470f4a9cc8c4b2bf644607c5e9c810ca1  tests/InstallationProcess/assignment_order_identity_registry_concurrency_001_test.php
b409eddac3fa05caf1283c7c2dbf96f36f7611f4bd1a86cb8fd4bbf06b89d02c  tests/Support/IdentityRegistryTestDatabase.php
d3c9a7b9584ade1c1d9a18f26f9f891fb08a85194e25aff5501dabce14aac655  tests/Support/assignment_order_identity_registry_worker.php
62cce31d814d482924d8f6fb0e5450c0e8f02227a98a081dff0506d8084daba3  docs/operations/assignment-order-identity-registry-matrix-red-2026-09-05.md
8ee4777de3525be2bcb2008e249452310972f59b9ee3d0427f72283c4ee4c169  /Users/antropophag/.local/state/fmonitor2-verification/admission-20260905-w7wmv_ye/registry-red-matrix-v2.json
```

The previously approved populated/repeat tracer remains byte-identical. Its scoped approval is unaffected. This verdict reviews whether the combined package is sensitive to the specification's complete mandatory schema, negative, recovery and concurrency matrix; it does not review implementation or authorize Gate 4 while the findings below remain.

## Findings

### Blocking G3-1: prefix rejection is not sensitive to the required before-access boundary

The specification requires an invalid prefix, including length 26, to throw `InvalidArgumentException('Invalid registry migration configuration.')` before database access. The schema test invokes invalid prefixes only on a healthy selected connection and compares database state before and after. That proves no mutation, but an implementation may issue arbitrary read/catalog/lock queries and then reject the prefix while still passing every assertion.

Add a deterministic public-seam case whose outcome distinguishes pre-access validation from any attempted connection use, such as invoking the invalid-prefix path with a caller-owned connection that has been deliberately closed after setup. The expected fixed `InvalidArgumentException` must remain observable without a database operation. Keep valid-prefix database behavior in the existing real fixtures.

### Blocking G3-2: the mandatory out-of-range historical-source cases are absent

The approved mandatory matrix requires out-of-range source rejection, and section 5 independently constrains both legacy order IDs and installation-case IDs to `1..9223372036854775807`. Current source negatives cover version `0`, an orphan case, invalid calendar/offset/trailing time forms, and an oversized row inserted into the target registry after completion. They do not place an otherwise structurally valid historical source order ID above `PHP_INT_MAX`, nor an otherwise linked historical source case ID above that bound.

The target-registry corruption case cannot detect an implementation that validates persisted registry IDs but accepts or truncates an oversized source decimal during preflight/hash/frontier calculation. Add independent literal source fixtures for both bounded fields using lossless SQL decimal literals and assert the exact zero-mutation conflict outcome.

### Blocking G3-3: fixture cleanup does not prove preservation of foreign decoys

The mandatory matrix requires exact owned cleanup with foreign-decoy preservation. `IdentityRegistryTestDatabase::close()` attempts rollback, closes the fixture connection, drops the exact owned database, drops recorded owned users, and verifies the owned schema is absent. Those are useful assertions, and all four RED runs completed them successfully. However, the only decoy is `other_prefix_marker` inside the owned database, which is necessarily removed when that database is cleaned up. It tests migration preservation, not cleanup target scoping.

No separately owned similarly named schema or user survives cleanup as an external sentinel. Therefore a broadened cleanup implementation or helper regression that removes neighboring resources is not detected. Add a bounded foreign schema/user sentinel outside the exact fixture ownership set, verify it survives both normal and failure cleanup, and remove that sentinel explicitly with attempt-all test-harness cleanup.

## Non-blocking assessment of covered behavior

Apart from the three gaps, the expected values are derived from the approved specification rather than production DDL or engine output. The schema test fixes the empty receipt hashes, ordinal columns, normalized numeric types, nullability/defaults, ASCII fields, exact indexes, FK actions, named CHECK predicates, InnoDB/default collation, prefix 0/25 behavior, caller transaction rejection, incompatible siblings/source shape, representative source/receipt corruption, nonempty unreceipted state, committed-receipt missing sibling, frontier regression, maximum/overflow frontiers, exhaustion sentinel, and distinct real DDL/DML denial followed by privileged recovery.

The CHECK oracle removes only presentation differences the specification declares insignificant while retaining literals, operands, operators and logical structure. Expected schema manifests are literal test data. Before/after `allState()` snapshots serve only as mutation/preservation observations and do not manufacture expected target schema.

Recovery covers all six phase interruption points and asserts exact observed prefixes, truthful DDL visibility, pre-commit rollback versus post-commit persistence, transaction and named-lock release, retry semantics, repeat-only lock phase, compatible receipts-only recovery, a current frontier gap of `101`, source recapture after controlled change, and non-1 release after committed backfill. These assertions would detect phase emission on attempted rather than completed work and false success across acknowledgement loss.

Concurrency uses argument-array `proc_open`, validates the worker's database/prefix scope before connection, and has bounded phase, completion, TERM and KILL/reap deadlines. It holds the actual named lock at `LOCK_ACQUIRED`, proves a second same-prefix worker times out around five seconds without DDL, proves an independent prefix completes during that barrier, then proves first completion and repeat. A separate worker reaches `BEFORE_BACKFILL_COMMIT`; a fresh connection sees no staged rows, actual TERM is observed and reaped, recovery backfills once, and source/marker snapshots remain unchanged. The worker is a test-only process seam and adds no runtime fault selector.

The helper creates random task-owned databases, applies the public predecessor migration, tracks restricted users, aggregates cleanup failures, and confirms owned-schema absence with the still-open admin connection. Setup construction succeeded before each missing-engine assertion in all reproduced runs. The worker cannot yet traverse its observer path because the approved engine types are intentionally absent; its syntax and static scope validation are constructible, while its dynamic paths properly remain post-GREEN obligations.

Independent reproduction at reviewed commit:

```text
$ php tests/InstallationProcess/assignment_order_identity_registry_001_test.php
RED_ASSERTION: public assignment-order identity registry migration is missing
Expected: true
Actual: false

$ php tests/InstallationProcess/assignment_order_identity_registry_schema_001_test.php
RED_ASSERTION: registry schema engine is missing
Expected: true
Actual: false

$ php tests/InstallationProcess/assignment_order_identity_registry_recovery_001_test.php
RED_ASSERTION: registry recovery engine is missing
Expected: true
Actual: false

$ php tests/InstallationProcess/assignment_order_identity_registry_concurrency_001_test.php
RED_ASSERTION: registry concurrency engine is missing
Expected: true
Actual: false
```

Every command exited `1`; no additional setup or cleanup failure appeared. These are valid missing-engine REDs, but they do not compensate for untested mandatory acceptance boundaries.

## Required changes

1. Make invalid-prefix testing distinguish rejection before database access from rejection after harmless reads or lock/catalog queries.
2. Add lossless out-of-range historical source order-ID and installation-case-ID conflict cases.
3. Add an external similarly named schema/user decoy and prove it survives attempt-all fixture cleanup on normal and failure paths.

After these Gate 2 corrections, preserve fresh RED evidence and obtain a new independent Gate 3 review before starting engine implementation. No full-engine Gate 5, canonical registration, writer cutover, parent selection completion, protected E2E, safe-log or remote-work conclusion follows from this review.
