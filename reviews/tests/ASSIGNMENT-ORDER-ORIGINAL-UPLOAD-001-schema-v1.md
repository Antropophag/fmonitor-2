# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema migration

- Reviewer: separately tasked agent `/root/original_upload_migration_gate3`
- Test author: separately tasked agent `/root/original_upload_migration_red`
- Reviewed commit: `6970e9ec291b9e0a63597c5a491d375dc0cf4aad`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v4, owner-approved hash `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`; OpenSpec delta hash `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Public seam: `CanonicalMigrationApplication::run(...)` and `bin/fmonitor2-migrate.php`
- Red command and intended failure: `FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 FMONITOR_TEST_DB_ADMIN_USER=root FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php` -> `INTENDED_RED: canonical additive assignment-order-original schema migration v12 is missing.`
- Verdict: `CHANGES_REQUESTED`

## Findings

### Blocking: the verifier is insensitive to the required original-evidence schema

OpenSpec task 3.1 requires an additive capability **and schema** migration. The
approved design requires persistence for immutable root/revision identities,
one-current-leaf CAS, terminal request results, accepted fingerprints, domain
events, safe attempt audit, composition identity/hash, document/upload facts,
storage identity and correction lineage.

The reviewed test deliberately treats all new private original tables as opaque.
After v12 it asserts only the seven-value capability CHECK, the migration ledger
result, repeat equality, preservation of pre-v12 capability/registration rows,
and rejection of one hostile capability CHECK. It never proves that v12 created
any storage capable of representing the required original facts or constraints.

Consequently, a plausible broken implementation can pass: register v12, replace
only `ck_fm2_process_user_capability` with the expected seven literals, and create
no original-evidence schema at all. Clean, repeat, populated and conflict paths
would satisfy every current assertion. The opaque whole-schema snapshot cannot
repair this sensitivity gap because it has no independently specified expected
post-v12 delta.

This also leaves compatible/incompatible partial-state handling of the new
schema untested. The sole conflict fixture mutates the predecessor capability
constraint; it cannot detect a malformed pre-existing original table, missing
lineage uniqueness/CAS constraint, or migration that silently accepts an
incompatible original-evidence family.

### Traceability, seam and independent values

The executable and delta hashes exactly match the recorded owner approval. The
v11 -> v12 catalogue order, exact CLI JSON, exact seven capability literals and
historical registration values are independently transcribed and do not call
future production helpers. Both migration application and deployment CLI are
valid public seams. The random database/prefix ownership and `finally` cleanup
are isolated; complete before/after snapshots make repeat and rejected conflict
checks deterministic for the seeded rows.

The populated fixture correctly retains `assignment_order.confirm_registration`
and proves the historical manual-registration columns and existing grants remain
byte-identical. It does not mutate those facts to manufacture target pilot truth.

### Runtime DDL boundary

The reviewed artifact adds no production or runtime code, and the intended RED
occurs before database access. Repository architecture policy independently
forbids schema-on-demand DDL outside canonical migration owners. Gate 4/5 must
run that policy against the implementation; this Gate 3 record does not infer
runtime-DDL safety from a missing production class.

### Reproduction

At `2026-09-04 01:41:51 MSK` on the exact reviewed commit:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=<redacted-local-test-secret> \
  php tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
PHP Fatal error: Uncaught TestFailure: INTENDED_RED: canonical additive
assignment-order-original schema migration v12 is missing.

$ git diff --check
PASS (no output before this review record)
```

The RED is the intended missing-class failure and matches the captured Gate 2
record. A separate attempt to rerun the predecessor MariaDB suite with the
repository's fallback demo password was rejected by the current database
credential boundary; therefore this review relies on the exact earlier healthy
predecessor transcript for that DB-only setup proof and does not claim a fresh
predecessor GREEN.

## Required changes

1. Add independently derived assertions for the minimum v12 original-evidence
   schema semantics required by the approved contract. Internal class layout may
   remain free, but the verifier must fail if no lineage/request/fingerprint/
   event/audit persistence or enforceable single-current/CAS identities exist.
2. Add a populated compatible original-schema fixture and an incompatible
   partial original-schema fixture. Prove compatible facts are preserved and an
   incompatible family fails before any schema, row, counter or decoy mutation.
3. Re-capture the corrected intended RED and obtain a fresh independent Gate 3
   review before production implementation. Preserve this record append-only.
