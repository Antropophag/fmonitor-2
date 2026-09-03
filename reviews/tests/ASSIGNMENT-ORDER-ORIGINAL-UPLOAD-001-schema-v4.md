# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema migration v4

- Reviewer: separately tasked agent `/root/original_upload_migration_gate3_v4`
- Test author: separately tasked agent `/root/original_upload_migration_red`
- Reviewed commit: `fb73935506883d1da0de4994b4926b299b1c4867`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v4, owner-approved hash `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`; OpenSpec delta hash `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Corrected test SHA-256: `5c8d0db8e4ddba66460c0e72b4d735b65d0221a90dfbdc7d2a74f41db8d7609f`
- Verdict: `CHANGES_REQUESTED`

## Prior unreachable finding

The v4 correction validly closes the unreachable hostile-capability fixture.
After constructing the exact v1-v11 predecessor it invokes the public successor
owner `AssignmentOrderOriginalSchemaMigration::apply()` directly, so the
expected v12 conflict can be reached without asking the historical v3 owner to
accept an unknown literal. The exact expected result names schema version 12 and
the hostile capability table, and the complete prefix snapshot detects any
table, definition, row or `AUTO_INCREMENT` mutation.

The existing production-runner v3/v4 adversarial cases are unchanged. In
particular, their unknown extra literal still must fail at v3 before the
instrumented v4 seam is invoked. The correction therefore preserves the older
owners' fail-closed contract rather than weakening it to make v12 reachable.

## Blocking finding: contradictory public-runner expectations

The claimed lifecycle separation does not produce an implementable GREEN
repository. `assignment_order_original_upload_001_schema_test.php` requires the
canonical deployment CLI to report `schemaVersion=12`, apply versions `1..12`
on a clean database, create the seven v12 tables, and return no applied versions
on repeat. That behavior necessarily changes the same public CLI observed by
the unchanged `production_migration_runner_001_test.php`.

The existing runner verifier contains multiple exact expectations for
`schemaVersion=11`, clean `appliedVersions=[1..11]`, repeat
`appliedVersions=[]`, v3-recovery `appliedVersions=[3..11]`, and its exact
31-table v11 catalog. Once Gate 4 canonically registers v12, those assertions
must fail even if the v12 implementation is correct. Conversely, leaving the
runner at v11 makes the new clean and repeat CLI assertions fail. Both tests
cannot be GREEN against one production catalogue.

Calling the older test “historical” does not remove it from `make verify`, nor
does Gate 3 permit relying on an unreviewed post-GREEN rewrite of an executable
public contract. The successor RED must include the additive runner expectation
update now, while retaining all v3/v4 conflict, short-circuit, recovery,
configuration, charset and failure assertions. Its catalog/fingerprint oracle
must also account for the seven v12 tables and expanded capability CHECK. This
is a test correction, not permission to delete or weaken predecessor coverage.

## Retained schema sensitivity

Apart from the runner contradiction, the schema oracle remains sensitive. Its
independent seven-table manifest still fixes ordered columns, identity and
single-current indexes, lineage foreign keys, terminal result/audit shapes,
byte bounds, engine and collation. The populated fixture preserves historical
manual-registration and capability facts, then requires exact preservation of
representative root, revision, request, fingerprint, event, audit and
maintenance rows plus definitions and counters. Malformed-root and
hostile-revision partial families still require zero-mutation conflict. The
serializer correction reviewed in v3 remains intact.

The intended missing-class RED is honest for the new verifier, but it occurs
before these database fixtures execute. The independently GREEN current runner
only proves the v11 baseline; it does not resolve the contradictory post-v12
expectations above.

## Independent reproduction

At `2026-09-04T02:04:15+03:00` on exact reviewed commit
`fb73935506883d1da0de4994b4926b299b1c4867`:

```text
$ sha256sum tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
5c8d0db8e4ddba66460c0e72b4d735b65d0221a90dfbdc7d2a74f41db8d7609f  tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_schema_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

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

$ git diff --check
PASS (no output before this review record)
```

The database credential was read from the healthy repository test contour and
was not printed or persisted. No test or production artifact was edited by this
review.

## Required correction

1. Add the v12 successor expectations to the public production-runner verifier:
   exact clean/repeat/recovery results, expanded capability semantics, and exact
   v12 catalog/fingerprint while preserving every v3/v4 adversarial assertion.
2. Reproduce the combined RED: the v11 baseline/setup must remain demonstrably
   healthy, and both successor schema and runner expectations must fail only
   because the production v12 owner/registration is absent.
3. Obtain a fresh independent Gate 3 review of the complete corrected RED batch
   before resuming Gate 4. Preserve reviews v1-v4 append-only.

## Gate decision

Fresh Gate 3 is `CHANGES_REQUESTED`. The direct v12 conflict-routing correction
is approved in isolation, but Gate 4 must not resume until the contradictory
canonical-runner expectations are corrected and independently rereviewed.
