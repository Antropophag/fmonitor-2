# PILOT-OBJECT-CARD-001 — route CSP RED correction v3

Date: 2026-09-04

RED author: `/root/original_upload_red`

Lineage: `d854170` plus independent review `31c74f5`

Verdict: **CORRECTED INTENDED RED; fresh Gate 3 required**

## Correction

V2 incorrectly required `object-details.js` to be the only successful card
script. The approved shared-shell/body contract retains navigation and adds
object details. The exact ordered set is now:

1. `<script type="module" src="/pilot/assets/navigation.js"></script>`
2. `<script type="module" src="/pilot/assets/object-details.js"></script>`

The verifier requires exactly two script nodes. For each node it independently
checks DOM order, `type="module"`, exact same-origin `src`, exactly two
attributes total, and empty inline content. Existing event-handler and
`javascript:` URL prohibitions remain. These CSP/script assertions execute
before every visible-content/order assertion. Error responses continue to
require BASE CSP.

## Intended RED

On the exact reviewed lineage based on `f5636a9`:

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

The sole current script is the first approved navigation module; the intended
RED is the missing second object-details module. The known later content-order
RED remains untouched and is not reachable until this predecessor is GREEN.

No production, content, authorization, error or zero-mutation assertion was
changed. Prior evidence remains historical and is corrected append-only here.
