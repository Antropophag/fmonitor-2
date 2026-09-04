# Prepare full-catalog and dynamic provenance correction GREEN v1

Date: `2026-09-04`  
Author: implementation agent `/root/prepare_v15_green`  
Base: `02e16bbcbe0c667e62634801a73f5fed88171dce`  
Verdict: **CORRECTION GREEN / fresh independent Gate 5 required**

`MariaDbPrepareWorkforceDirectory` now consumes every catalog row in stable
`installer_tab_id ASC` order without an arbitrary whole-catalog `LIMIT`.
Every delivered/current row therefore reaches integrity validation, all eligible
rows reach the exact `>500` ceiling, and no eligible tail can be silently
omitted.

Mixed provenance remains outside the exact six-field inert template. Its
visible no-JS list now carries a server-escaped ID/source/update association.
The client atomically validates cardinality, order, exact attributes, ID and
visible text against the already validated picker records. On success it adds
the corresponding source/update text after name/details inside every dynamic
result button and hides the redundant no-JS list. Any mismatch leaves the
fallback visible and creates no hidden IDs. Homogeneous provenance remains one
group-level pair.

Tests, specs and reviews were not changed.

## Verification

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac` on 2026-09-04:

```text
node tests/InstallationProcess/support/pilot_prepare_picker_client.js app/PilotHttp/picker.js
prepare picker client contract: PASS

php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
# exit 0, no output

openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

git diff --check
# exit 0, no output
```

Production hashes before this evidence commit:

```text
d8534e2c5904e716fe62d73bd6b873131dbd1ee22b1fb5843e3750b6c60dbb20  app/PilotHttp/MariaDbPrepareWorkforceDirectory.php
8cbcb9b66bfd48dfbbd45885784ec62f42fa275c480af783daec61d1adc2114d  app/PilotHttp/PrepareFormView.php
4b342ce6cf305f1be24713103cd46b22cd8231b32e9a37d71d9a51e3dd11e1c7  app/PilotHttp/picker.js
```

## Coverage limitation

Gate 3 v15 directly covers homogeneous/mixed server provenance association,
supplementary-plane ordering, invalid rows and the 501-eligible ceiling. It does
not execute a mixed-provenance browser/client fixture and does not place more
than 502 ineligible rows before a valid eligible tail. The dynamic association
parser and removal of the SQL limit are consequently verified by spec-to-code
inspection plus the current approved regressions, not by new test cases. Adding
those cases would require a new Gate 2 RED and independent Gate 3 review.

This record does not claim Gate 5 or integration readiness.

## Exact correction commit confirmation

The production/evidence commit is
`2935710fa7f771e4f76b847d64f9b1cb5ca821b1`. From a clean worktree at that
exact SHA, `2026-09-04T10:53:52+03:00` through
`2026-09-04T10:54:31+03:00`, the direct picker harness and canonical prepare
verifier returned their PASS literals, architecture-check passed all 7 rules,
strict OpenSpec validation succeeded, and lint plus diff-check exited zero.
