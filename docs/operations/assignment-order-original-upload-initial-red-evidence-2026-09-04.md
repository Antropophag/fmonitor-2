# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — initial public-seam RED evidence

Date: 2026-09-04 01:09:53 MSK

Author: separately tasked RED agent `/root/original_upload_red`

Gate: 2, OpenSpec task 2.1 only

Classification: **INTENDED RED**

## Scope

Added the smallest direct `INITIAL` upload test through the normative
`AssignmentOrderOriginalVerificationFactory`, which composes the same canonical
application implementation as production. The test calls only
`submitAssignmentOrderOriginal` and fixes expected values independently from
future production code:

- request `00000000-0000-4000-8000-000000000001`;
- case `4512`, order `81`, actor `18`;
- immutable composition identity `composition-81-v1` and literal composition
  SHA-256 of 64 `1` characters;
- document date `2026-09-01`, fixed clock/upload time
  `2026-09-02T09:15:30Z`;
- production-owned IDs `original-0001` and `revision-0001`;
- the approved literal PDF oracle, exact `327` bytes and SHA-256
  `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`;
- exact accepted result evidence, byte-identical private content, and an
  unchanged composition/opening/process snapshot.

No production file was added or changed. Tasks 2.2 and 2.3 remain open; this
record is not Gate 3 approval and does not claim GREEN.

## Gate 1 integrity

Immediately before the RED run, the owner-approved normative hashes remained:

```text
97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a99946c8662b8cf6dbc21ff8e513bf0813cc6d6604a92087a03c019e2922c482  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
b81f11b5aabd69645404b624d5301cd65a209b870d06ef587dcb34eebbcfc9b2  openspec/changes/replace-pilot-registration-with-original-upload/design.md
127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
```

These equal the v4 owner approval record. The task checkbox update is
operational progress metadata and is not part of that owner-approved normative
batch.

## Commands and transcript

Run from `/home/antropophag/code/fmonitor-2-original-upload` at
`2026-09-04T01:09:53+03:00` (`2026-09-03T22:09:53+00:00`) on pre-RED HEAD
`e5cc1603f4c49a755476bd769bebbed4eaafca76`:

```text
$ sed -n '36p' tests/InstallationProcess/assignment_order_original_upload_001_test.php | cut -d "'" -f2 | base64 -d | wc -c
327

$ sed -n '36p' tests/InstallationProcess/assignment_order_original_upload_001_test.php | cut -d "'" -f2 | base64 -d | sha256sum
4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784  -

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_test.php

$ php -l tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php
No syntax errors detected in tests/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_test.php
PHP Fatal error:  Uncaught TestFailure: INTENDED_RED: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 canonical production application seam is missing: FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication in tests/InstallationProcess/assignment_order_original_upload_001_test.php:26
Stack trace:
#0 {main}
  thrown in tests/InstallationProcess/assignment_order_original_upload_001_test.php on line 26
exit 255

$ git diff --check
PASS (no output)
```

## Classification

This is the intended Gate 2 failure. Test bootstrap and both RED files parse;
the independent byte oracle matches the approved contract. Execution reaches
the explicit public-seam guard and fails solely because the canonical
`FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplication`
production seam does not exist yet. It does not fail because of DB, storage,
network, fixture, parser, credentials, or another predecessor/setup condition.
