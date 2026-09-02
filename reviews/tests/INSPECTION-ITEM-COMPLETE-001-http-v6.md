# INSPECTION-ITEM-COMPLETE-001 — independent corrective test rereview v6

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, evidence, specification, fixture or production)  
Mission: `TEST-USER-READY`  
Verdict: `APPROVED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Final corrective HTTP test: SHA-256
  `9f9351c5b90210f43f2f4eda87dc0f669fd421123cd5c9b9b8bb877d9bc63abc`.
- Final schema-drift test: SHA-256
  `535dedbcbc0b67d016cd8b2e7fbc3f69b0f75b3e456ff8c8364bee8b077cbd17`.
- Corrective evidence v6: SHA-256
  `fa00f50a9ae08c9d238a7e2351ade5fad8f2415cdfa3edade3a3fd94b1d04ba1`.
- Fixture-only schema regression: SHA-256
  `14bb42fea624bc1406c894c7e0776c70dd726e09cc274faad3ec04efd11942d2`.
- Prior v5 review: SHA-256
  `248e3868edd416f61b8a91778ce21a9e8b5e7ea9b5cd48a0df1b0c89a5010afa`.

No production or test file was edited by this review.

## Independent lifecycle and results

I started `compose.test.yaml` and waited for the MariaDB health check, then ran:

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php -l tests/InstallationProcess/inspection_item_complete_001_schema_drift_test.php
php -l tests/InstallationProcess/inspection_evidence_schema_001_test.php
php tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php tests/InstallationProcess/inspection_item_complete_001_schema_drift_test.php
php tests/InstallationProcess/inspection_evidence_schema_001_test.php
```

Observed:

```text
No syntax errors detected (all three files)
PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring
PASS: INSPECTION-ITEM-COMPLETE-001 partial v8 schema drift
INSPECTION-EVIDENCE-SCHEMA-001 tests passed.
HTTP_RC=0 DRIFT_RC=0 SCHEMA_RC=0
```

I then ran `docker compose -f compose.test.yaml down -v --remove-orphans`.
Final `docker compose ... ps --all` was empty: container, volume and network
were removed.

## Remaining finding closure

### HTTP4-01 — closed

The malformed contour now starts from the already reviewed valid literal and
changes only `deviceInstallationId` to the independently chosen literal
`not-a-uuid`. It asserts that exact raw malformed value on the captured public
command, together with authenticated actor `7301`, resolved canonical case
`9512`, one resolver call and one recording call. The spy's typed
`INVALID_COMMAND(0)` maps to exact `{status: rejected, revision: 0}`.

No production policy/helper is called as an oracle, no missing-field sentinel
is frozen, and every other command field inherits the independently asserted
valid contour. The test is sensitive to restoring legacy HTTP syntax rejection,
client actor trust, object/case confusion, sanitizing the malformed value into
a valid one, duplicate delegation and incorrect result mapping.

### HTTP4-02 — closed

For each of the four canonical v8 evidence tables the drift test snapshots
exact `SHOW CREATE TABLE` text and all rows in deterministic order before and
after the public application command. `SHOW CREATE TABLE` covers the complete
column types/defaults/charset/collation/generated definitions, indexes,
constraints, engine and table options including a changed `AUTO_INCREMENT`.
The strict before/after equality also requires the injected
`unexpected_v8_drift(actor_user_id)` index to remain present and unchanged.

Because the application receives an admin-capable connection, forbidden repair
is not made impossible by permissions; any DDL repair or rolled-back insert
that advances auto-increment is observable. The exact typed
`INSPECTION_SCHEMA_UNAVAILABLE(0)` result and zero clock calls additionally pin
fail-closed precedence.

### HTTP4-03 — reconfirmed closed

The same snapshot strictly compares every row of revisions, operations,
operation installers and photos. It therefore retains full observation of
slice-owned business mutation while keeping schema metadata and DML in one
before/after scalar projection.

## Preserved coverage

- The full previously approved HTTP status/revision matrix, unequal object/case
  identity, trusted actor handling, exactly-once recording/resolution,
  schema-infrastructure mapping and valid non-item isolation remain unchanged.
- Behavior is observed through public `ChecklistSync::accept` and the public
  production inspection application seam. Metadata is used only for the
  explicit no-DDL invariant, not as a business-outcome substitute.
- The canonical v1-v8 runner establishes the starting schema before the test
  injects one controlled incompatibility.
- The fixture-only schema update remains green with its prior DML-only,
  migration and fail-closed expectations intact.
- Evidence accurately leaves raw endpoint-admission coverage outside this
  corrective increment.

## Gate decision

HTTP4-01 and HTTP4-02 are fully closed, HTTP4-03 remains closed, and all focused
and regression tests reproduce green in a clean lifecycle. Expectations are
spec-derived, public-seam sensitive and not weakened. The post-Gate-5
corrective test increment is `APPROVED` for the exact hashes above. Any further
production correction still requires independent Gate 5 code review.
