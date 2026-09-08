# PILOT-OBJECT-CARD-001 — route CSP RED correction v2

Date: 2026-09-04

RED author: `/root/original_upload_red`

Base/review lineage: `b1f2785` plus independent Gate 3 verdict `0d8f936`

Verdict: **CORRECTED INTENDED RED; fresh Gate 3 required**

## Correction of v1 evidence

The v1 test invented a `defer` attribute and placed its script-shape assertions
after the first visible-content ordering loop. The known content failure stopped
execution before those assertions, so the v1 evidence incorrectly stated that
the external-script checks had passed.

The approved exact body contract is now asserted before any visible-content
check: successful object-card GET/HEAD has exactly one script element with only
`type="module"` and `src="/pilot/assets/object-details.js"`, empty element
content, and no other attributes. `count(@*)=2` rejects invented `defer`,
`async`, nonce, integrity or any additional attribute. Existing inline event
and `javascript:` URL prohibitions remain. Successful CSP is still byte-exact
SCRIPT CSP; error/rejected responses still use BASE CSP.

## Current intended RED

On exact navigation base `f5636a9` with review commit `0d8f936` preserved:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
Example A broad reader without capability has exact module object-details script with no invented attributes
Expected: 1
Actual: 0
exit 255

$ git diff --check
PASS (no output)
```

A diagnostic DOM observation showed the sole current element is
`<script src="/pilot/assets/navigation.js"></script>`. That observation is not
accepted as the expected oracle: it lacks the approved module object-details
script. The corrected test therefore exposes the intended body-contract RED
before the separate known content-order RED.

No production or content/auth/error/zero-mutation assertion changed. The v1
evidence remains historical and is corrected only by this append-only record.
