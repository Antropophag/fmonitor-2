# PILOT-OBJECT-CARD-001 — route CSP RED correction v4

Date: 2026-09-04

RED author: `/root/original_upload_red`

Lineage: `b4f1b49` plus independent v3 approval `2b91bed`

Verdict: **CORRECTED INTENDED RED; fresh Gate 3 required**

## Correction

V3 required `type="module"` on both scripts. Neither the owner-approved
PILOT-ROUTE-CSP-001 contract nor its reviewed body evidence specifies that
attribute, and the already approved navigation element is source-only. The v3
module expectation was therefore untraceable even though the v3 test review
approved it.

The exact ordered successful-card script set remains:

1. `/pilot/assets/navigation.js`
2. `/pilot/assets/object-details.js`

Each element now must have exactly one attribute, its exact `src`, and empty
inline content. Attribute cardinality rejects `type`, `defer`, `async`, nonce,
integrity and every other invented attribute. Exactly two total scripts,
ordered sources, no inline event attributes and no `javascript:` URL remain
mandatory before any content assertion. Error responses retain BASE CSP.

## Intended RED

On the exact lineage based on `f5636a9`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
Example A broad reader without capability has exactly two approved external scripts
Expected: 2
Actual: 1
exit 255

$ git diff --check
PASS (no output)
```

The current source-only navigation script satisfies the corrected first-element
shape; the intended failure remains the absent second object-details script,
before the separate known content RED.

Production and all content, authorization, error and zero-mutation assertions
are unchanged. This append-only record invalidates only the untraceable module
claim in v3 evidence; fresh independent Gate 3 is required for v4.
