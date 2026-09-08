# Prepare SVG workaround reversion — 2026-09-04

Date: `2026-09-04`  
Author: implementation agent `/root/prepare_v15_green`  
Base: `b755fd800f3d703a70defc58b7b24d874387369e`  
Verdict: **workaround removed / canonical verifier RED**

The generic SVG path digit entity encoding introduced in production correction
`ef59395182497344338bd9e8916ee748b4f78666` was rejected as a test workaround
and is removed. `PilotView` again emits the ordinary repository-owned SVG path
bytes. The workforce/provenance and Unicode code-point corrections from that
commit remain unchanged. Tests, specs and reviews are unchanged.

Canonical reproduction from
`/home/antropophag/code/fmonitor-2-prepare-rbac`, started
`2026-09-04T10:25:49+03:00` and completed `2026-09-04T10:25:51+03:00`:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

TestFailure: excluded 75
Expected: false
Actual: true
tests/InstallationProcess/pilot_prepare_form_001_test.php:131
# exit 255
```

The assertion scans the complete raw HTML for the two-character substring
`75`; ordinary shared SVG path geometry contains that substring. This record
therefore does not claim GREEN or Gate 5. Correcting verifier scope requires a
test change and return to Gate 2/independent Gate 3.

Unaffected checks after the reversion:

```text
node tests/InstallationProcess/support/pilot_prepare_picker_client.js app/PilotHttp/picker.js
prepare picker client contract: PASS

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
# exit 0, no output

openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

git diff --check
# exit 0, no output
```

Reverted production hash:

```text
567cd92982259c8197458f1f6ca575a916fe4355fb26fddea2ce25b430f6baf8  app/PilotHttp/PilotView.php
```
