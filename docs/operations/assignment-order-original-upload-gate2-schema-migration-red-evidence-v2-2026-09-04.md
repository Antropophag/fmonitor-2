# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — corrected schema migration RED v2

Date: 2026-09-04 01:45:59 MSK (`2026-09-03T22:45:59Z`)
Gate: 2 correction after `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-schema-v1.md`
RED author: separately tasked agent `/root/original_upload_migration_red`
Review/base commit: `187bf83444df0d8a49a99d2ea0546aee78033932`

## Finding disposition

The prior Gate 3 verdict remains append-only `CHANGES_REQUESTED`. The corrected
verifier no longer permits a capability-only v12 to pass. It defines and checks
the minimum canonical storage required by the owner-approved v4 DTOs and
evidence shapes:

- immutable root and revision lineage;
- an enforceable unique current marker per root plus root/revision and
  previous-revision identities needed by CAS;
- terminal request results and exact accepted-versus-rejected/conflict evidence
  shape;
- globally unique accepted-operation fingerprints;
- one domain event per accepted revision;
- one safe attempt audit per terminal rejected/conflict request;
- terminal maintenance result/audit rows with exact counter/outcome constraints.

The seven technical table names use the repository's canonical
`fm2_<aggregate>_<fact>` convention. Their columns are transcribed only from the
approved typed commit DTOs and canonical evidence JSON. No new user-visible
behavior, HTTP surface, composition application, opening rule, registration
rewrite, or runtime DDL was introduced.

For each table the verifier reads `information_schema` through the migration
acceptance boundary and compares exact ordered columns/types/nullability,
InnoDB/collation, primary/unique/secondary index semantics, FK target/delete
semantics and CHECK expressions. A migration that creates no original storage,
omits any lineage/request/fingerprint/event/audit family, permits two current
leaves, loses correction ancestry, or weakens exact result/count bounds cannot
pass.

## Corrected fixtures

- Clean CLI apply must reach contiguous v12 and match the full canonical
  fingerprint.
- Repeat must preserve every definition, row and `AUTO_INCREMENT`.
- Populated v11 upgrade still preserves all old grants and literal historical
  `registered` facts. It then seeds a compatible root/revision, accepted and
  rejected terminal requests, fingerprint, event, safe audit and maintenance
  result. A public-application repeat must preserve all bytes and counters.
- Existing hostile capability semantics still conflict at v12 with zero
  mutation.
- A malformed populated root table and a hostile populated revision partial
  table independently conflict at v12 before any repair, sibling creation,
  capability alteration, row/counter or decoy mutation.

The test neither calls a private repository method nor uses runtime DDL. Direct
DDL exists only inside isolated migration-state fixtures to represent hostile
pre-existing schema, matching established canonical migration tests.

## Intended RED

Corrected test SHA-256:

```text
dadf18a2376751c52a73a8a03dec247f782830f7feebb52925eba96039dd6b41  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
```

Commands and relevant output:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

$ git diff --check
PASS (no output)

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical additive
assignment-order-original schema migration v12 is missing.
```

The corrected verifier first confirms the unchanged owner-approved executable
and OpenSpec hashes, then stops at the exact missing public v12 migration class.
The current canonical v11 runner was reproduced GREEN immediately before the
corrected RED, so the failure is not database or predecessor setup.

## Gate status

This is a fresh intended **RED** after Gate 3 findings. Production is untouched,
OpenSpec task 3.1 remains open, and a fresh separately tasked Gate 3 review is
required before implementation.
