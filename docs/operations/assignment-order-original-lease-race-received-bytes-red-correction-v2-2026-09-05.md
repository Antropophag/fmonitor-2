# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — lease-race received-bytes RED correction v2

Date: `2026-09-05`

Correction author: separately tasked Gate 2 correction agent
`/root/lease_fixture_red_correction`. This author remains ineligible to review
the corrected test at Gate 3.

Outcome: **INTENDED RED — approved canonical fixture and oracles restored**.

This record supersedes the conclusion of
`docs/operations/assignment-order-original-lease-race-received-bytes-red-correction-2026-09-05.md`
without modifying that append-only history. Fresh independent Gate 3 review
`a05fe27e822cf19d2160e86122466bfc58b20af1` correctly found that the first
correction changed approved v52/v54 oracles to follow a malformed 318-byte
test-local payload. The approved contract instead fixes the canonical positive
PDF, including `/Type /Catalog`, at 327 bytes and SHA-256
`4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`.

The lease-race input now uses the exact base64 literal already pinned by
`specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` and
`tests/Support/AssignmentOrderOriginalRemainingMatrix.php`. All ready/final
blob, result, domain revision, terminal request, content identity and
fingerprint expectations are restored to their approved values. A strict
pre-transport assertion independently pins the decoded byte count and digest.
Race scheduling and READY/RELEASE behavior are unchanged.

## Corrected factual transcript

```text
$ php -l tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_lease_race_001_test.php

$ php -r '<strictly decode the exact corrected lease-race payload>'
327
4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784
/Type /Catalog: present

$ php tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
PHP Fatal error: Uncaught TestFailure: Referenced accepted content remains.
Expected: COMPLETED / null / false / 1 / 0 / 1 / 0 / null
Actual:   PARTIAL / LOCKED / true / 1 / 0 / 1 / 0 / null
exit 255

$ git diff --check -- tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
PASS (no output)
```

With the canonical received bytes restored, the executable spec reaches the
same post-RELEASE assertion factually: current partial production still reports
the accepted referenced content as `PARTIAL/LOCKED`, rather than
`COMPLETED`/retained after the upload has resolved. This is the remaining RED
behavior; it is not a fixture-oracle mismatch.

No production file, OpenSpec artifact, product spec, task or other test was
changed.

## Exact corrected hash before this evidence file

```text
83b584d3f4dd4048059d22a893e634cae0b49008b1e47c9f254e271a69212dcc  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
```

Fresh independent Gate 3 is required. This record intentionally omits its own
circular hash.
