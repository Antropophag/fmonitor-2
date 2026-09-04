# Prepare mixed-provenance parser Gate 4 correction GREEN v1

Date: `2026-09-04`  
Author: implementation agent `/root/prepare_v15_green`  
Base / Gate 3 v17 approval: `f7a46e9eb4ad68614f6de2b4213018eefdb9bc03`  
Reviewed test commit: `932b2ae`  
Verdict: **CORRECTION GREEN / fresh independent Gate 5 required**

The production picker now consumes provenance association `childNodes`
atomically. Interstitial text is accepted only when it consists exclusively of
U+0009, U+000A, U+000D and U+0020; any other text returns before activation.
Only after this grammar pass are element rows checked for exact cardinality,
order, tag, attributes, descendants, ID/source/update association and visible
text. A rejected association therefore keeps opener hidden, fallback and source
list visible, and creates zero hidden installer IDs.

No test, support, specification or review file changed. OpenSpec tasks 3.1 and
3.2 remain checked; no Gate 5 claim is made.

## Verification

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`, started
`2026-09-04T11:07:40+03:00`, completed `2026-09-04T11:08:27+03:00`:

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

Exact hashes before this evidence commit:

```text
9994edc1e66cc2c8b8991a486399cefd100bef1854759dceb80a7e0951b64aa8  app/PilotHttp/picker.js
fae262571db508b02175a6c2f52cd67e8867b15b9ad7a572da05e2888f3c7ec8  tests/InstallationProcess/support/pilot_prepare_picker_client.js
59552423291008f1fa9b42a33a5523a988522c8c8b1841c05d2496a410be7611  tests/InstallationProcess/pilot_prepare_form_001_test.php
```
