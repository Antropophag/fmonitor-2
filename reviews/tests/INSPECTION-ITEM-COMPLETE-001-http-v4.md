# INSPECTION-ITEM-COMPLETE-001 — independent post-Gate-5 corrective test review v4

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, specification, fixture or production)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Corrected HTTP wiring test: SHA-256
  `6059eb374f2b058d85a2bc7152eb97f949a7ba9a0a7fa89908600a2f228364d1`.
- New schema-drift test: SHA-256
  `2d8a94aa0f1d435afca9f86048a0f80f852f1201fcb15382b9db0e34dfb9c9a2`.
- Updated schema fixture/test: SHA-256
  `14bb42fea624bc1406c894c7e0776c70dd726e09cc274faad3ec04efd11942d2`.
- Current `ChecklistSync`: SHA-256
  `1e68fad44e18b6eb569830d7ae7c6d394dcf7826dd7f3692009eeaa7c0eddeeb`.
- Previously approved HTTP v3 review: SHA-256
  `1949ecb88166feee127e2c79d1a8defc0b58a1bfe43e730011d02ac23a74de9f`.
- Delivery process: SHA-256
  `a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504`.

No production or test file was edited by this review.

## Independent execution

I started the isolated test Compose database, waited for health, ran:

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
```

All three processes exited `0`. I then ran
`docker compose -f compose.test.yaml down -v --remove-orphans`; the final
`docker compose ... ps --all` was empty.

## Checks that pass

- The new malformed `item_completed` contour is reached through public
  `ChecklistSync::accept`, resolves the route object once, invokes the public
  recording seam once, and maps its `INVALID_COMMAND` typed result to exact
  `{status: rejected, revision: 0}`. It would catch restoration of the old
  common-envelope early return for this operation type.
- The previously approved result matrix, unequal object/case resolution,
  recording counts, trusted actor assertion for the valid contour, schema
  exception mapping and non-item isolation remain intact.
- The schema-drift test uses the public production factory/application command
  seam after a canonical v1-v8 migration. Its added unexpected index is a real
  incompatible-v8 mutation; the expected typed status and zero clock calls
  sensitize schema validation before receipt-time/domain work.
- The schema fixture update is additive support for the landed production
  dependencies: it adds canonical identity/capability facts, missing assignment
  evidence fields, template payload and initial revision. It does not remove or
  relax the existing G2-14/G2-15 assertions. The complete schema test remains
  independently green under its DML-only and fail-closed cases.

## Blocking findings

### HTTP4-01 — malformed command sentinel values are not approved expected values

The corrective test requires a missing client envelope to become exact command
fields `['', '', '', -1, 0, 0, []]`. The approved spec requires canonical UUID,
explicit-offset time, non-negative revision and positive ids, and requires
syntax validation inside the application precedence; it does not define how a
raw missing HTTP field is encoded into the strictly typed
`CompleteInspectionItem` DTO. In particular `-1` versus `0` for a missing
revision is an adapter implementation choice, not an independently determined
Gate 1 expectation.

The contour also does not assert `actorUserId=7301` and resolved
`installationCaseId=9512` on the malformed command. A conditional malformed
path could therefore delegate once yet use the untrusted client/object identity
and still pass, undermining the authorization-before-syntax reason for this
correction.

Required change: amend/approve the adapter contract with the exact lossless
raw-to-command invalid sentinel convention, or redesign the public command
input so malformed syntax is representable without invented values. Then cite
that rule and assert every malformed command field, including trusted actor and
resolved canonical case. If exact sentinel encoding is deliberately not a
product contract, remove the internal projection assertion and use a public
test that still proves application authorization/syntax precedence without
pinning implementation-defined values.

### HTTP4-02 — schema-drift test does not detect runtime DDL repair

The test adds `unexpected_v8_drift`, but its before/after observation contains
only row counts from `fm2_checklist_operations` and
`fm2_checklist_revisions`. Production could drop the unexpected index (or
otherwise repair/alter schema), return `INSPECTION_SCHEMA_UNAVAILABLE`, and
leave those counts unchanged; this test would pass despite the explicit
no-runtime-DDL/no-schema-repair contract.

Required change: snapshot the exact relevant table/index/column/constraint
metadata before the public command and assert strict equality afterward,
including continued presence and definition of the injected drift. Run through
a principal capable of DDL, as the current test does, so the assertion is
mutation-sensitive rather than protected only by permissions.

### HTTP4-03 — “zero business mutation” observes only two tables

The same before/after pair omits at least
`fm2_checklist_operation_installers` and the remaining slice-owned audit/data
tables. An erroneous rejection path could write elsewhere while operations and
revision counts remain equal. The assertion text therefore overclaims zero
business mutation.

Required change: capture a deterministic scalar projection of every
inspection-item-completion-owned business table/fact before and after, or use
the approved public evidence query plus complete revision/evidence projections
that make every possible slice-owned write observable. Keep schema metadata
observation from HTTP4-02 separate from business DML observation.

## Gate decision

The corrective tests are green and target genuine post-review risks, and the
fixture-only compatibility update preserves its prior expectations. However,
the malformed expected values are not independently grounded in the approved
spec, while the new drift test can pass after forbidden schema repair or
partial business mutation. Because post-Gate-5 test changes restart at Gate 2,
this exact corrective increment is `CHANGES_REQUESTED`; it must not be treated
as approved merely because current production passes it.
