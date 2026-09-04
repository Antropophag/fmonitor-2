# PILOT-SESSION-STORAGE-001 — anonymous repeated commit RED v2

Date: 2026-09-04

RED author: `/root/original_upload_red`

Lineage: `a12326c` plus independent review `74913e3`

Verdict: **COLLISION ORACLE CORRECTED; primary intended RED preserved; fresh Gate 3 required**

## Correction

V1 incorrectly expected `start(null)` to inspect committed candidates and skip
a preexisting file. Anonymous start owns only an in-memory candidate identity;
collision exclusion belongs to publication.

V2 calls `start(null)` first and independently expects deterministic ID1. It
then creates a mode-0600 externally-owned committed ID1 file. The subsequent
`writeCommit(ID1,payload)` must preserve the external bytes/digest, select the
next deterministic ID2, return `OK/ID2`, and create exactly one owner file whose
bytes equal the submitted payload. The collision instance therefore contains
exactly external ID1 plus owner ID2 and no overwritten/extra committed file.

The collision scenario executes and passes before the unchanged primary
repeated-write assertion.

## Intended RED

```text
$ php -l tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_anonymous_repeat_001_test.php
Repeated anonymous commit retains the caller current ID1.
Expected: [OK, 1111111111111111111111111111111111111111111111111111111111111111]
Actual:   [OK, 2222222222222222222222222222222222222222222222222222222222222222]
exit 255

$ git diff --check
PASS (no output)
```

Reaching that primary assertion proves the corrected collision scenario already
passed. The primary contract remains unchanged: ordinary repeated commit must
retain ID1, one committed file and latest bytes. Production and the checklist
integration test were not changed. Fresh independent Gate 3 is required.
