# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 lease-race received-bytes correction — independent Gate 3 review v2

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/lease_fixture_gate3_v2`
- Correction/test author: `/root/lease_fixture_red_correction`
- Reviewed commit: `d2668d04a99979cfb70bee24e930469a93972ef8`
- Prior Gate 3 finding: `a05fe27e822cf19d2160e86122466bfc58b20af1`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54, inheriting approved v52 lease-race behavior
- Verdict: **APPROVED**

The reviewer authored none of the specification, OpenSpec artifacts, test,
production implementation, correction evidence or prior reviews. This
append-only review record is the only authored artifact.

## Independent oracle review

The correction restores the exact canonical base64 fixture from the executable
specification and shared remaining-matrix support. Strict decoding produces
exactly `327` bytes, round-trips byte-for-byte, contains `/Type /Catalog`, and
has SHA-256
`4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`.
The test pins byte count and digest immediately after strict decode and before
transport, so an unaccompanied payload edit fails before the worker observes it.

All changed downstream expectations are the approved canonical identities:
ready/final blob, accepted result, immutable revision, terminal request and
private content identity use 327 bytes and `4028…8784`. Independently packing
the ten normative initial-fingerprint members as unsigned four-byte big-endian
length plus raw bytes creates a `208`-byte preimage and SHA-256
`dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d`,
matching the restored fingerprint oracle. No old 318-byte digest, identity,
size or fingerprint remains in the active lease test.

## Factual RED reproduction

The corrected executable test reaches the post-`RELEASE` retained-reference
assertion. Current partial production returns exactly
`PARTIAL/LOCKED/true/1/0/1/0/null`, while the approved expectation is
`COMPLETED/null/false/1/0/1/0/null`. Exit is `255`. This is the intended missing
lease-release behavior, not fixture or setup failure.

```text
$ php -l tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_lease_race_001_test.php

$ php -r '<strict decode, round-trip and Catalog inspection of exact test literal>'
bytes=327 sha256=4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784 catalog=present roundtrip=yes
support_literal_match=yes

$ php -r '<independent pack("N", strlen(member)) tuple computation>'
preimage=208 fingerprint=dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
Expected: COMPLETED / null / false / 1 / 0 / 1 / 0 / null
Actual:   PARTIAL / LOCKED / true / 1 / 0 / 1 / 0 / null
exit 255

$ git diff --check d2668d04^ d2668d04
PASS (no output)
```

Post-run checks found no `assignment_order_original_worker_entry` process, no
`aoou-lease-*` temporary root, and no `t_aoou_lease_*` MariaDB schema. The test's
bounded cleanup therefore leaves no observed worker, filesystem or database
leak on the RED path.

## Scope and exact reviewed hashes

Commit `d2668d04a99979cfb70bee24e930469a93972ef8` changes only the lease test and
its append-only correction-v2 evidence. It changes no production file,
OpenSpec artifact, product specification, task or unrelated test. Pre-existing
untracked partial production files were preserved and excluded from review.

```text
83b584d3f4dd4048059d22a893e634cae0b49008b1e47c9f254e271a69212dcc  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
5ab63662023e827f3be98ab1c9f953c5f991895e4c2503d5828bf9903bba6aeb  docs/operations/assignment-order-original-lease-race-received-bytes-red-correction-v2-2026-09-05.md
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
3fe18be313bf66286ea9f7669bfac2828925754665fe1437637a799f78bf82c3  tests/Support/AssignmentOrderOriginalRemainingMatrix.php
```

This review record intentionally omits its own circular hash. Gate 3 for the
superseding lease-fixture correction is **APPROVED**.
