# Test review: ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001 populated/repeat tracer v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root` (recorded in Gate 2 RED evidence); reviewed commit authored by Timofey Grishin
- Reviewed commit: `6fc4d7d72b966fbeb4726f35bb83d83e5afa8910`
- Specification: `specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md` v0.1, SHA256 `31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f`, independently approved at Gate 1
- Public seam: `AssignmentOrderIdentityRegistryMigration::apply/isBackfillComplete`, typed `AssignmentOrderIdentityRegistryMigrationVerification::snapshot`, and standard MariaDB catalog observations
- Red command and intended failure: `php tests/InstallationProcess/assignment_order_identity_registry_001_test.php`; exit `1`, explicit missing-class assertion after successful real-database setup
- Verdict: `APPROVED` for the first populated/repeat tracer only

## Exact reviewed inputs

```text
31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f  specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md
e5cbce078a73019a1e04a2735c927f19abcbaefd444efeb79fd59a2caff2db2b  docs/operations/assignment-order-identity-registry-gate1-review-v01-2026-09-05.md
e4841961e89b7c7e39c11449579be71a38766c8b20be0023cb52db69b4c74073  tests/InstallationProcess/assignment_order_identity_registry_001_test.php
0831d569a1a39c25ba1e42c3c433b77503f78575fa8b93b14ca04dfb84b02ccb  docs/operations/assignment-order-identity-registry-red-v1-2026-09-05.md
```

The Gate 1 review is `APPROVED` for the exact specification hash. This Gate 3 verdict is deliberately narrower than the specification's full mandatory matrix and does not approve whole-engine test coverage.

## Findings

Traceability is complete for the first populated/repeat migration tracer. The test cites the exact approved specification hash and invokes the normative production migration facade. It observes target values through the approved typed verification snapshot, completion through the public read-only method, and source preservation through ordinary MariaDB catalog/data queries. It does not use private methods, reflection, production constants, HTTP, runtime registration, a renderer, or a side channel into planned engine internals.

The fixture matches the independently fixed populated example. It creates synthetic cases `4512/4513`, inserts historical orders in source order `7` then `2`, preserves the source `AUTO_INCREMENT` frontier `81`, and expects registry rows sorted numerically as `2` then `7`. It fixes exact case/version/source-kind values and UTC instants `2026-08-27T09:30:00.000000Z` and `2026-08-28T10:15:00.000000Z` through the typed snapshot.

The immutable receipt assertion fixes format `1`, maximum `7`, legacy and preserved next ID `81`, row count `2`, tuple hash `8159e7f3e55b317c01056ec6c7172e2c9bca8a798c0bc38408b6b6df1d71be86`, and prepared hash `a5506e2a71f414d4667cc95d1446155f69d9156ecf87e51bf9d7ec485997be53`. I independently recomputed both hashes from the specification's literal byte streams and obtained those exact values; the specified empty hash also recomputed as `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.

Sensitivity is adequate for this bounded tracer. A missing facade fails explicitly. An implementation that omits or reorders historical identities, uses migration time instead of normalized source instants, lowers the frontier, changes a receipt field/hash, mutates source rows/schema/counters, reports incomplete after backfill, reapplies on exact repeat, rewrites the receipt for a later row, drops later metadata/source state, leaves a caller transaction, or alters an unrelated table cannot satisfy the post-GREEN assertions. Exact serialized snapshots make both complete repeat and later-row repeat sensitive to unexpected target mutations.

The late-row fixture is expressly a simulated compatible future writer state and not an application writer implementation. It adds source order/identity `81` above the frozen historical maximum `7`, then proves completion remains true, repeat remains `applied=false`, the original receipt is byte-stable at the typed seam, the complete later target snapshot stays unchanged, and the corresponding source/catalog/counter snapshot stays unchanged.

Setup and isolation are sound. Before the missing-class assertion, the test opens a caller-owned connection, creates a random task-owned database, runs the existing public predecessor migration, inserts only literal synthetic source fixtures, sets the source frontier, creates an unrelated preservation marker, and captures the full six-table source snapshot. The reproduced failure therefore is not an include, database, predecessor-migration, fixture, or environment failure. No shared database, production data, real document, PII, external system, protected E2E, safe-log, or remote resource participates.

Cleanup is attempted from `finally` for the exact random database on the RED path. The test drops that database, uses the still-open caller connection to query the catalog and assert its absence, then closes the connection. That assertion executed successfully in the focused run; no cleanup error obscured the intended RED.

Independent reproduction at reviewed commit:

```text
$ php tests/InstallationProcess/assignment_order_identity_registry_001_test.php
RED_ASSERTION: public assignment-order identity registry migration is missing
Expected: true
Actual: false
```

Exit status: `1`. Classification: intended missing public migration seam RED after successful real setup. `php -l tests/InstallationProcess/assignment_order_identity_registry_001_test.php` and `git diff --check` also pass.

This approval covers only the populated example, completion/repeat, later compatible row, source preservation, transaction cleanliness, and owned fixture cleanup in this first tracer. Fresh-empty behavior, exact target schema/catalog matrix, prefix/configuration failures, partial/incompatible families, receipt/source conflicts, bounds and malformed data, frontier extremes, frozen-history violations, lock/concurrency behavior, all interruption and commit-response-loss phases, denied principals, and complete cleanup/foreign-decoy failure paths remain mandatory. Their tests require separate demonstrated RED and independent Gate 3 approval before the corresponding implementation can receive whole-engine Gate 5 review.

Within that explicit boundary, no blocking traceability, seam-choice, expected-value independence, sensitivity, determinism, setup-isolation, or captured-RED finding remains.

## Required changes

None for this first populated/repeat tracer. The separately gated mandatory matrix remains required before whole-engine Gate 5.
