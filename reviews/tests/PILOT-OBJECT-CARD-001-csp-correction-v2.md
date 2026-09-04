# PILOT-OBJECT-CARD-001 — independent route-CSP correction Gate 3 rereview v2

Date: 2026-09-04
Reviewer: fresh separately tasked agent `/root/object_card_csp_gate3_v2`
Reviewed commit: `d8541703fc60126af5267381c5855a38b45658b9`
Base/review lineage: `f5636a9e44e184cf1c0c9c7021a312c9620c83f0` → `b1f2785ef7d6babad151ec79d391f40102ceea55` → `0d8f93609d202c5434053fe499a4a3a444320fcc`
Verdict: **CHANGES_REQUESTED**

The reviewer did not author or modify the specification, corrected test, RED
evidence, production implementation, or prior reviews. This append-only review
record is the only edit.

## Reviewed inputs

```text
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
312982a37d6093914ddbd976246710c7ae86ff010635460c63c04cf27b5ef736  reviews/tests/PILOT-ROUTE-CSP-001-v4.md
014bf3f5726ef7913816ebb536a0b57946b1203e96809c2ecb14f49d4d0e3d19  reviews/tests/PILOT-OBJECT-CARD-001.md
4f7692a5aacf961b29a958b8a6e640d986ac772cb2e46cb169d742004b563411  reviews/tests/PILOT-OBJECT-CARD-001-csp-correction-v1.md
3267b575be453db741ef5876cc0e50341459d262e770ca002c69158dfa8c5f78  tests/InstallationProcess/pilot_object_card_001_test.php
46cf8a506a9a90a27b306048c711c946292f69af9f51db72774f37eb9d2208bc  docs/operations/pilot-object-card-csp-red-correction-v2-2026-09-04.md
```

The owner-approved route-CSP specification hash and prior route-CSP approval
remain pinned. The reviewed commit changes only the object-card test and new
append-only RED correction evidence; it changes no production file.

## Closed findings from v1

The literal `defer` invention is removed. The new XPath correctly identifies
the existing exact module tag shape used by `rapid-pilot/ObjectDetails.php`:
`type="module"`, same-origin `/pilot/assets/object-details.js`, empty content,
and no extra attributes. The script and executable-content checks now run
before the unrelated visible-content ordering loop. The focused public-seam run
therefore reaches this assertion and fails there, exactly as recorded:

```text
Example A broad reader without capability has exact module object-details script with no invented attributes
Expected: 1
Actual: 0
exit 255
```

Inspection of the current production seam confirms why: `ObjectCardView`
returns `PilotView::document()` directly, and that document currently contains
only `/pilot/assets/navigation.js`. Thus the run is a real missing-object-
details-script RED rather than setup failure or the later known content RED.

The diff leaves `pocError()` and the default `pocSecurity(..., false)` path
unchanged. Existing 401, 403, 404, 405 and 503 assertions therefore continue to
require byte-exact `BASE_CSP`, exact redacted body/status/length, `Allow` and
`Retry-After` behavior. The success path still requires byte-exact
`SCRIPT_HTML_CSP`; inline event and `javascript:` URL prohibitions remain.

## Blocking finding

### The exact-one-script oracle contradicts the approved route inventory

`PILOT-ROUTE-CSP-001` names the successful object-card route's required script
evidence as **“navigation plus `/pilot/assets/object-details.js`”**. The
corrected helper first requires `//script` cardinality to equal one and then
requires that sole tag to be object-details. It therefore rejects the approved
representation containing both navigation and object-details scripts.

This is not merely a currently missing production behavior. The current public
seam's `/pilot/assets/navigation.js` tag is also part of the approved route-CSP
inventory and is emitted by the shared `PilotView`; wiring the already existing
`RapidPilotObjectDetails` decorator would append the exact module object-details
tag and produce two external same-origin tags. The proposed oracle would still
remain RED and would force removal of the approved navigation asset without an
owner-approved contract change.

Required correction: retain the exact object-details tag assertion, empty
content, no-extra-attribute and executable-content safety checks, but permit and
independently pin the approved navigation-plus-object-details script set (for
example, exact cardinality two plus one exact assertion for each approved tag).
Alternatively, changing the representation to object-details only requires an
explicit Gate 1 amendment and owner approval. Recapture the intended RED and
submit the corrected bytes to another fresh independent Gate 3 reviewer.

## Reproduced commands

```text
php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_card_001_test.php
# exits 255 only at the reachable missing exact module object-details assertion

openspec validate define-pilot-route-csp --strict
Change 'define-pilot-route-csp' is valid

git diff --check 0d8f93609d202c5434053fe499a4a3a444320fcc...d8541703fc60126af5267381c5855a38b45658b9
PASS (no output)
```

Gate 3 remains closed. Production implementation is not authorized against
these bytes.
