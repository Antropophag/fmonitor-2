# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 lease-race received-bytes correction — independent Gate 3 review v1

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/lease_fixture_gate3`
- Correction/test author: `/root/lease_fixture_red_correction`
- Reviewed commit: `a6c3445be9ff3630005ad2b0e7760c307921ed40`
- Prior command-matrix Gate 3: `2907b4e7436b260c92c0c9aa4ef5e415728494cf`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54, inheriting the v52 lease-race behavior
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, OpenSpec artifacts, test,
production implementation, correction evidence or prior reviews. This
append-only review record is the only authored artifact.

## Independent oracle reproduction

Strict RFC4648 decoding of the exact base64 transmitted by the corrected test
does produce `318` bytes, round-trips byte-for-byte, and has SHA-256
`22a425371f4824de8f896f14d3cb002b80f8349d5a18570168618e9c82a133e6`.
Independently packing the ten initial-fingerprint members in normative order as
unsigned four-byte big-endian length plus raw bytes produces a `208`-byte
preimage and SHA-256
`fd76190fa72cd5084a815adf8e33a0b128b18f1d39ae4dd0a2ff59e32fa8ba4a`.
The corrected content identity, ready/final blob, accepted Result, revision,
request and fingerprint expectations are internally consistent with those
received bytes. The pre-transport size/digest assertion is sensitive to an
unaccompanied fixture edit.

These calculations do not make the new fixture a specification-derived oracle.

## Blocking finding: the correction changes the approved lease fixture

V54 is the shared-content schema-v2 amendment. Its approved Gate 1 review
explicitly limits that amendment to the revisions index/schema lifecycle; it
does not amend the inherited v52 worker-barrier behavior. The stable executable
specification still defines the canonical positive fixture as the exact
327-byte base64 value with SHA-256
`4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`.
Its lease-race section requires request0400 to transmit that Example PDF and
requires the 327-byte digest, private identity, accepted evidence and blob
inventory. The independent v52 Gate 1 review records the same exact literals.

The corrected test instead transmits a different 318-byte base64 value. It is
not merely observing received bytes without transformation: compared with the
canonical fixture it omits `/Type /Catalog` from object 1 while leaving the
trailer root at object 1. The test selects `injected_passive`, so this difference
can bypass the real inspector while redefining every downstream exact oracle.
An implementation that accepts the wrong lease fixture and publishes the wrong
content identity would now satisfy the test. That violates traceability,
expected-value independence and sensitivity at Gate 3.

No production or unrelated file is present in the reviewed commit; its diff is
limited to the lease test and its correction evidence. This scope property does
not cure the normative contradiction.

## RED reproduction

At the exact reviewed HEAD, with the existing untracked partial production
files preserved, syntax validation passed and the test reached only the
post-`RELEASE` maintenance assertion. It exited `255` for the intended retained
content behavior family, but the actual current observation differed from the
correction record: maintenance returned `COMPLETED/null/false/1/1/0/0/null`
instead of expected `COMPLETED/null/false/1/0/1/0/null`. Thus the test is red
after release, but the evidence record's quoted `PARTIAL/LOCKED` actual is not
reproducible from the factual current worktree. Cleanup left no lease worker or
`aoou-lease-*` temporary root.

```text
$ php -r '<strict decode and canonical round-trip of the exact test fixture>'
318 22a425371f4824de8f896f14d3cb002b80f8349d5a18570168618e9c82a133e6 true

$ php -r '<independent pack("N", strlen(member)) tuple computation>'
208 fd76190fa72cd5084a815adf8e33a0b128b18f1d39ae4dd0a2ff59e32fa8ba4a

$ php -l tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_lease_race_001_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
Expected: COMPLETED / null / false / 1 / 0 / 1 / 0 / null
Actual:   COMPLETED / null / false / 1 / 1 / 0 / 0 / null
exit 255

$ git diff --check a6c3445^ a6c3445
PASS (no output)
```

## Required correction

Restore the exact approved canonical 327-byte base64 fixture and its existing
327-byte/SHA/content/fingerprint expectations in the lease test. The received-
bytes-before-transformation invariant should remain observable by pinning the
decoded canonical bytes before transport. Then capture a fresh factual RED
transcript and request another independent Gate 3 review. If a 318-byte fixture
is desired instead, that is a product-contract change and requires an approved
Gate 1 amendment before Gate 2/3.

## Exact reviewed hashes

```text
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
b1c6a1009996f7b796bd627dd1e9ad1a0b0d491cd6cc643320352e53ebfdc46f  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
f7b08d6aa8f0a67e48e38646777fc151d1aa531951ec8637b952867ba870664b  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
0cd213068a1dd44513f317ca841fc93b454795a2e730395a8883b155e7fdcb23  docs/operations/assignment-order-original-lease-race-received-bytes-red-correction-2026-09-05.md
```

Gate 3 is **CHANGES_REQUESTED**. Minimal production correction against this
amended lease expectation is not authorized.
