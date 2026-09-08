# PILOT-OBJECT-CARD-001 — route CSP RED correction

Date: 2026-09-04

RED author: `/root/original_upload_red`

Base: `f5636a9`

Verdict: **CSP ORACLE CORRECTED; next existing card RED reached; fresh Gate 3 required**

## Approved inputs

```text
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
312982a37d6093914ddbd976246710c7ae86ff010635460c63c04cf27b5ef736  reviews/tests/PILOT-ROUTE-CSP-001-v4.md
014bf3f5726ef7913816ebb536a0b57946b1203e96809c2ecb14f49d4d0e3d19  reviews/tests/PILOT-OBJECT-CARD-001.md
```

## Correction

Successful exact object-card GET and equivalent HEAD now require byte-exact
SCRIPT CSP with `script-src 'self'`. Their representation must contain exactly
one script element: empty inline content, exact same-origin source
`/pilot/assets/object-details.js`, `defer` present and `async` absent. The test
also forbids every inline event attribute and `javascript:` href/src. Thus
removing the old blanket `<script` ban does not admit inline, third-party or
additional scripts.

Generic error/rejected responses still call the default security assertion and
therefore require BASE CSP without `script-src`. All card content,
authorization, adversarial escaping, error and zero-mutation assertions are
unchanged.

## Demonstration

Before correction on exact `f5636a9`, the fixture reached the stale assertion:

```text
Example A broad reader without capability content-security-policy
Expected: default-src 'none'; style-src 'self'; img-src 'self'; ...
Actual: default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; ...
exit 255
```

After correction:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
Example A broad reader without capability visible literal/order: 77-000123
Expected: true
Actual: false
exit 255
$ openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid
$ git diff --check
PASS (no output)
```

The run passes the corrected CSP/script checks and reaches the next pre-existing
card presentation/order assertion. That assertion may require a product choice
and is deliberately not changed here. Production code was not modified; the
changed test requires fresh independent Gate 3.
