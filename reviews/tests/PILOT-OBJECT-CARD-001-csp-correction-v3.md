# PILOT-OBJECT-CARD-001 — independent route-CSP correction Gate 3 rereview v3

Date: 2026-09-04
Reviewer: fresh separately tasked agent `/root/object_card_csp_gate3_v3`
Reviewed commit: `b4f1b497055775ec3a4532fbc7cb5be295735555`
Base/review lineage: `31c74f5ca8cae863bfed7081cafc151140aa4775` → `b4f1b497055775ec3a4532fbc7cb5be295735555`
Verdict: **APPROVED**

The reviewer did not author or modify the specification, corrected test, RED
evidence, production implementation, or prior reviews. This append-only review
record is the only edit.

## Reviewed inputs

```text
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
312982a37d6093914ddbd976246710c7ae86ff010635460c63c04cf27b5ef736  reviews/tests/PILOT-ROUTE-CSP-001-v4.md
014bf3f5726ef7913816ebb536a0b57946b1203e96809c2ecb14f49d4d0e3d19  reviews/tests/PILOT-OBJECT-CARD-001.md
4f7692a5aacf961b29a958b8a6e640d986ac772cb2e46cb169d742004b563411  reviews/tests/PILOT-OBJECT-CARD-001-csp-correction-v1.md
37b68ae26623541909088ab9ea934657ca8a6fb989112742a522d53b101043b2  reviews/tests/PILOT-OBJECT-CARD-001-csp-correction-v2.md
d1b82353d8f5047b0a9a68dbd385ba7837b0e97c99e90ec40595e7021b846ea5  tests/InstallationProcess/pilot_object_card_001_test.php
95230aa8b7a18956a4f2046841329ea8b214e9a232a653df3f9fd705f503cdf5  docs/operations/pilot-object-card-csp-red-correction-v3-2026-09-04.md
```

The owner-approved route-CSP specification hash and prior route-CSP approval
remain pinned. The reviewed commit changes only the object-card test and new
append-only RED correction evidence; it changes no production file.

## Standards

**APPROVED; zero findings.** The exact correction has no documented-standard
violation and no applicable maintainability smell. The test uses the existing
helper vocabulary and changes only the contradictory script oracle. The v3
evidence accurately describes the changed expectation, actual failure and
unchanged scope.

## Spec

**APPROVED; zero findings.** The corrected success oracle now requires exactly
two script nodes in DOM order:

1. `/pilot/assets/navigation.js`
2. `/pilot/assets/object-details.js`

Each node is independently required to have exactly two attributes,
`type="module"`, its exact same-origin `src`, and empty inline content. Exact
cardinality excludes extra scripts; attribute cardinality plus the exact values
excludes invented attributes. Existing global checks still reject inline event
handlers and case/whitespace variants of `javascript:` in `href` or `src`.
This closes the v2 finding without inventing a literal `defer` requirement.

The byte-exact `SCRIPT_HTML_CSP`, script cardinality, node-shape and executable-
content assertions all execute before `pocVisible()` and the known later
visible-content/order loop. The focused run therefore reaches the intended
missing-script predecessor and reports exactly:

```text
Example A broad reader without capability has exactly two approved external scripts
Expected: 2
Actual: 1
exit 255
```

Inspection confirms the existing representation has the approved navigation
module as its sole script. The missing second object-details module is therefore
the current intended RED; the unrelated content-order RED remains unreachable
until this predecessor becomes GREEN.

`pocError()` and `pocSecurity()` are byte-unchanged. Every existing 401, 403,
404, 405 and 503 assertion still selects the default non-scripted branch and
requires byte-exact `BASE_CSP`, along with its existing exact status, body,
length, `Allow` and `Retry-After` behavior. No production, authorization,
content, persistence-neutrality or error expectation changed.

## Reproduced commands

```text
php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_card_001_test.php
# Expected: 2 / Actual: 1 at the exact two-script assertion; exits 255

openspec validate define-pilot-route-csp --strict
Change 'define-pilot-route-csp' is valid

git diff --check 31c74f5ca8cae863bfed7081cafc151140aa4775...b4f1b497055775ec3a4532fbc7cb5be295735555
PASS (no output)
```

Gate 3 is approved for these exact corrected test bytes. Gate 4 may implement
the missing object-details module while preserving the ordered navigation-plus-
object-details contract and all unchanged expectations.

Summary: Standards 0 findings; Spec 0 findings.
