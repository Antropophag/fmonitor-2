# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — lease-race received-bytes RED correction

Date: `2026-09-05`

Correction author: separately tasked Gate 2 correction agent
`/root/lease_fixture_red_correction`. This author must not review the corrected
test at Gate 3.

Outcome: **INTENDED RED — fixture oracle contradiction removed**.

The command-matrix Gate 3 approved commit
`842edc13144e5534389bedd9fc7009196f994555` after the earlier corrections, but
the lease-race test still coupled the canonical Example A PDF expectations to
different bytes than its transmitted base64 fixture. The test file hash before
this correction was
`5889edc881ee0585e6d5ca987c02b7a50f9968ef200850c028fe9a72e5329c6b`.

Strict base64 decoding of the exact transmitted fixture produces `318`
received bytes with SHA-256
`22a425371f4824de8f896f14d3cb002b80f8349d5a18570168618e9c82a133e6`,
not the former expected `327` bytes and
`4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`.
Under the approved received-bytes-before-transformation contract, the exact
ready/final blob, accepted result, revision, terminal request and private
content identity now use those received values. The independently encoded
208-byte initial fingerprint preimage consequently has SHA-256
`fd76190fa72cd5084a815adf8e33a0b128b18f1d39ae4dd0a2ff59e32fa8ba4a`.
The test also pins strict decoded size and digest before worker transport, so a
future fixture edit cannot silently recreate the contradiction.

No race scheduling, READY/RELEASE protocol, maintenance expectation, product
behavior or production file changed.

## Contradiction and corrected RED transcript

```text
$ php -r '<strictly decode the exact lease-race base64 fixture>'
array (
  0 => 318,
  1 => '22a425371f4824de8f896f14d3cb002b80f8349d5a18570168618e9c82a133e6',
)

$ php -r '<independent pack("N", strlen($member)) initial tuple computation>'
208 fd76190fa72cd5084a815adf8e33a0b128b18f1d39ae4dd0a2ff59e32fa8ba4a

$ php -l tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_lease_race_001_test.php

$ php tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
PHP Fatal error: Uncaught TestFailure: Referenced accepted content remains.
Expected: COMPLETED / null / false / 1 / 0 / 1 / 0 / null
Actual:   PARTIAL / LOCKED / true / 1 / 0 / 1 / 0 / null
exit 255

$ git diff --check -- tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
PASS (no output)
```

The corrected test reaches the post-RELEASE maintenance assertion. Its RED is
therefore no longer caused by fixture metadata: current partial production
keeps the content lease locked after the upload commit instead of making the
referenced content observable as retained without a held upload lease.

## Exact corrected hash before this evidence file

```text
f7b08d6aa8f0a67e48e38646777fc151d1aa531951ec8637b952867ba870664b  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
```

Fresh independent Gate 3 review is required before production is corrected.
This record intentionally omits its own circular hash.
