# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 domain correction review v1

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/command_gate5_domain_gate3`
- RED author: separately tasked agent `/root/command_gate5_red_domain`
- Reviewed commit: `2fc0646e70235bdd267521209da63a94b3fe2734`
- Reviewed production: `6c4fb5b70065cabb19adab23f6004714c1f0699a`
- Gate 5 finding record: `fa97cfd5f5ca900424bcf9863eff66ed6b701a2e`
- Contract: approved `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Verdict: **CHANGES_REQUESTED**

This reviewer did not author the executable tests, their evidence, the
production implementation, or the planning artifacts. This append-only review
record is the only artifact authored by the reviewer.

## Scope and independence

The reviewed commit adds exactly two executable test files and one append-only
RED evidence record. It changes no file under `app/`, no public contract, and
no product behavior. Its parent contains the separate storage/parser RED
slice; that parent is not approved or reviewed by this record.

The domain test uses the public application and dependency contracts with
test-owned authorizer, composition, repository, stream, storage, and lifecycle
adapters. Expected result tuples and call counts are literal test values rather
than values read back from production. The MariaDB test creates one
random-token, regex-bounded database and drops that exact database in `finally`;
its source tables deliberately omit constraints which could hide malformed
legacy rows. No production data, secret, or broad cleanup target is used.

## Reviewed coverage

The executable assertions are sensitive to Gate 5 findings 3, 4, and 5:

- two different assignment orders share one repository fixture, so the second
  INITIAL fails under the reviewed global `findLineage('')` behavior;
- authorization, terminal-request, and fingerprint `UNAVAILABLE` require the
  exact `FAILED/PERSISTENCE_FAILURE/retryable=true` tuple before stream reads;
- rolled-back or throwing attempt persistence requires the same technical
  tuple instead of returning an unaudited rejection;
- the real MariaDB adapter must return `ROLLED_BACK`, not `CONFLICT`, for a
  missing-schema technical error;
- duplicate releases, a missing release end, reversed release dates, and a
  release after the order date must invalidate the complete composition row
  set instead of producing a filtered partial snapshot;
- correction composition drift requires the exact semantic-collision tuple
  before any stream read;
- the in-process lifecycle adapter observes the application callback after
  `stage.finalize()` and asserts that the returned lease is still held.

The all-row MariaDB cases are negative oracles and therefore could also pass an
adapter which rejects every composition. Existing positive composition suites
remain necessary alongside this correction; the new assertions correctly
expose the reviewed filtering defect but are not a standalone completeness
proof.

## Blocking finding: worker finding 6 is not forced RED

The in-process lifecycle assertion proves application ordering, but neither new
test invokes `AssignmentOrderOriginalVerificationWorkerBootstrap` nor observes
its READY/RELEASE protocol. The reviewed production worker can therefore keep
fabricating finalized metadata and a manual lock before application
construction, bind a no-op lifecycle observer, and still satisfy every
assertion in this commit after the unrelated domain defects are corrected.

Consequently the executable is not sensitive to Gate 5 finding 6. Before
production correction, add the smallest worker-level negative test which uses
the existing public worker protocol and makes READY depend on the actual
`AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT` callback and its live finalized-content
lease. It must fail while bootstrap fabricates metadata/lock or uses a no-op
observer, without adding a second test-owned mutation path for the observed
fact. The corrected exact bytes require a fresh independent Gate 3 review.

## Independent RED reproduction

Both files pass PHP lint. The exact commands against reviewed/current
production fail for the intended contract mismatches:

```text
$ php tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
Authorization UNAVAILABLE has the only contract-valid technical tuple.
Expected: FAILED / PERSISTENCE_FAILURE / true
Actual:   REJECTED / PERSISTENCE_FAILURE / true
exit 255

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php
Malformed all-row composition case 0 is invalid, not filtered into a valid snapshot.
Expected identity: NULL
Actual identity:   composition-81-v1
exit 255
```

`git diff --check 2fc0646^ 2fc0646` exits zero. The failures occur after test
bootstrap and database connection, not because of fixture setup or cleanup.

## Exact reviewed hashes

```text
f788e80143c25cda53fb02d79a4089248ce6079fcf1586b6aeb65b53d5ba6486  tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
40545c57c70239975062e0e677d3f4f82e89e5b0ef7944ffa270949468a8c916  tests/InstallationProcess/assignment_order_original_gate5_mariadb_red_001_test.php
20cdd033a4615e20b3c4597d9cc932ae2fc3faa06b2ce96dd08f42931fb46f5e  docs/operations/assignment-order-original-command-gate5-domain-red-evidence-2026-09-05.md
```

Gate 3 is **CHANGES_REQUESTED** for the exact reviewed commit. This record
authorizes no production implementation.
