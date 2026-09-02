# INSPECTION-ITEM-COMPLETE-001 — independent corrective test rereview v5

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, evidence, specification, fixture or production)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Corrected HTTP wiring test: SHA-256
  `029936a0e06ee8b2dd4351e47b654be1fc771b13adde39fb3a409d99a01ee7df`.
- Corrected schema-drift test: SHA-256
  `924358aa7468fc76d41de9f7217d7283704e2bd6b03390b1701576a11e02be69`.
- Corrective evidence v4: SHA-256
  `41006209c9ed41b8a52f9bac01907079c40c784c5622baa2820831e5de6a64f7`.
- Fixture-only schema test: SHA-256
  `14bb42fea624bc1406c894c7e0776c70dd726e09cc274faad3ec04efd11942d2`.
- Prior v4 review: SHA-256
  `084cea87932309a109e43ff05bb016e426371a0c18d827c4b0e776a81bbc67fb`.

No production or test file was edited by this review.

## Independent lifecycle and results

I started `compose.test.yaml` and waited for MariaDB health, then ran syntax and
all three focused/regression tests:

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
Final `docker compose ... ps --all` was empty.

## Prior finding status

### HTTP4-01 — partially addressed, not closed

The malformed contour now independently asserts trusted authenticated actor
`7301`, resolved canonical case `9512`, exact one resolver/recording call and
the public `{status: rejected, revision: 0}` result. It no longer freezes the
exact array of implementation-selected sentinel values.

However, it establishes that the constructed DTO is invalid by directly
instantiating production-internal `InspectionItemCommandPolicy` and calling its
`valid` helper. That class is not either of the approved public application
interfaces. The adapter and policy can share the same wrong sentinel/validity
assumption and the test will pass; the expected invalidity is therefore derived
from the implementation under test rather than independently from the spec.
The v4 evidence's phrase “approved `InspectionItemCommandPolicy`” is not
supported by the approved public-seam section.

Required change: avoid the internal policy oracle. The smallest independent
contour is a complete otherwise-valid item envelope with one explicitly
malformed field whose raw value is specified by the request itself (for
example a literal non-canonical UUID). Assert that literal malformed value,
trusted actor and canonical case in the captured command and assert the public
typed-result mapping. This proves delegation to application syntax precedence
without inventing missing-field sentinels or duplicating/calling production
validation logic.

### HTTP4-02 — partially addressed, not closed

The snapshot now detects removal/change of the injected unexpected index and
captures table engine/collation, basic ordered columns, ordered indexes and all
rows. This is materially stronger than v4.

It still does not capture column character set/collation, generated-column
state/expression, table `AUTO_INCREMENT`, or table constraints. Runtime code
could repair/change any of those—or consume an auto-increment value through a
rolled-back insert—while returning the expected status and preserving every
currently captured value. The test and evidence would still claim exact schema
preservation/no DDL repair. The repository's schema test already demonstrates
the fuller metadata projection needed here.

Required change: extend the before/after scalar metadata projection with the
full relevant `information_schema` fields, including table `AUTO_INCREMENT`,
column character set/collation and generated metadata, and ordered FK/CHECK
constraints. Keep the strict index comparison that proves the injected drift
survives unchanged.

### HTTP4-03 — closed

Rows from all four slice-owned evidence tables—revisions, operations,
operation installers and photos—are now strictly snapshotted before and after
the public command. Together with the exact typed result and zero clock calls,
this closes the previous partial business-mutation observation. Expected rows
are test fixture state, not values derived from production behavior.

## Preserved coverage

- The prior public recording/resolver spies, full result mapping, unequal
  identity, trusted valid actor and non-item isolation expectations are not
  weakened.
- Canonical v1-v8 migration is used before drift injection; the test invokes
  only the public production factory/application command for the behavior under
  review.
- The fixture-only schema update remains green and retains its prior DML-only,
  fail-closed and migration assertions.
- Evidence correctly declines to claim raw endpoint admission coverage; this
  review does not turn that explicit exclusion into an approval claim.

## Gate decision

All tests reproduce green and HTTP4-03 is closed, but HTTP4-01 still relies on
a production-internal oracle and HTTP4-02 still permits unobserved runtime DDL
or auto-increment mutation. The post-Gate-5 corrective increment remains
`CHANGES_REQUESTED` until those two focused sensitivity gaps are closed without
weakening the approved public expectations.
