# IDENTITY-ACCESS-SCHEMA-001 RED evidence v7 — corrected HTTP status observation

- Date: `2026-09-01`
- Role: fresh Gate 2 test author `identity_access_red_statusfix_20260901h`
- Supersedes: `identity-access-schema-red-evidence-v6.md` for the isolated
  runtime observer defect and Gate 2 task accounting
- Outcome: `QUALIFYING RED`; independent Gate 3 review remains required
- Production code changed: no

## Corrected observer

`RapidPilotUserAccessView::handleStatus()` terminates its HTTP seam with PHP
`exit`. A safe application response such as HTTP 400 therefore has child
process exit 0. The v6 observer incorrectly treated non-zero process exit as
the fail-closed oracle and reported the incompatible status-event fixture as a
failure even though the public application boundary returned its existing safe
HTTP 400.

The child now emits a test-only shutdown marker containing
`http_response_code()`. The parent strips that marker from the response body
and records `httpStatus` independently from `exit`, `out` and `err`. Missing
and incompatible fixtures require exact HTTP 400, unchanged user state, and no
observed `CREATE`, `ALTER` or `DROP`. The incompatible branch now passes with
HTTP 400 and process exit 0. The missing branch remains intentionally RED with
HTTP 303, mutated state and observed lazy CREATE. Migrated block/unblock also
remain intentionally RED because both emit the known lazy CREATE.

## Executed RED contours

Canonical public runner:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/identity_access_schema_001_test.php
Expected: schemaVersion=6, appliedVersions=[1,2,3,4,5,6]
Actual:   schemaVersion=5, appliedVersions=[1,2,3,4,5]
exit: 255
```

Exclusive runtime observer:

```text
$ tools/verification/run-identity-access-isolated-red.sh
Runtime observer failures:
- migrated paths emitted DDL: [two CREATE TABLE IF NOT EXISTS ...status_events]
- missing family did not fail closed with safe HTTP 400: httpStatus=303, exit=0
- missing family mutated state
- missing family emitted DDL: [CREATE TABLE IF NOT EXISTS ...status_events]
exit: 255
```

No incompatible-family failure is present in the aggregated output. Its HTTP
400, zero state mutation and zero runtime DDL assertions all passed.

## Gate 2 self-audit

The current `identity_access_schema_001_test.php` SHA-256 is
`94b40292d05a3694ece90d5b270485066862968157d33bb08c717166cd0466e6`.
Together with the corrected runtime observer it closes the prior Gate 3
`CHANGES_REQUESTED` findings: test-owned literal manifests and populated
preservation, dependency-safe partial recovery, category and family conflict
zero-mutation matrices, deterministic prefix/symbol isolation, limits and
redaction, separate bootstrap characterization, and public runtime SQL
observation for login, invitation, role, block/unblock, missing and incompatible
status-event states.

Unexpected failure specifically inside future v6 and observation of a later
migration after v6 remain non-selectable until production v6 exists: the public
runner has neither v6 nor a test injection seam. Gate 2 did not add production
hooks. The literal post-GREEN assertions remain required before completion;
this does not invalidate the two present qualifying RED causes.

OpenSpec authored-scope tasks 2.1–2.3 are complete. Task 2.4 remains unchecked
until a fresh separately tasked independent reviewer returns `APPROVED` in an
append-only Gate 3 record.

## Hygiene

`php -l` for both focused PHP tests, `bash -n` for the isolated runner, and
`git diff --check` passed. The isolated runner trap removed its random
`fm2-ia-red-*` container; the post-run container query returned no names.
