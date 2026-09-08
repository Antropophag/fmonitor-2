# PILOT-OBJECT-CARD-001 — independent route-CSP correction Gate 3 rereview v4

Date: 2026-09-04
Reviewer: fresh separately tasked agent `/root/object_card_csp_gate3_v4`
Reviewed commit: `357b925cc57599e8cd6484e494ffae6d0270a19a`
Base/review lineage: `2b91bed691421b1313ab915f6ba95ac7cfc0b4ae` → `357b925cc57599e8cd6484e494ffae6d0270a19a`
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
7b91bd37caee1c6df2a3c07ed29adcbc12f219d649b2e3544db87260bbc83448  reviews/tests/PILOT-OBJECT-CARD-001-csp-correction-v3.md
3362de98a97b9098e2e6cf388c84a99bcd217823bf8663fb377dfe6146eceae1  tests/InstallationProcess/pilot_object_card_001_test.php
4fc933708ffea4a059d4a98b8351f4eda42c3cefdf783aaab132fe9572c01ae8  docs/operations/pilot-object-card-csp-red-correction-v4-2026-09-04.md
```

The owner-approved route-CSP specification and its prior independent review
remain byte-pinned. The reviewed commit changes only the object-card test and
adds the append-only v4 RED correction evidence; it changes no production file.

## Correction of the mistaken v3 approval

The v3 review approved `type="module"` on both script elements, but neither the
owner-approved route inventory nor its reviewed body evidence requires that
attribute. The current approved navigation element is source-only. The v4
evidence explicitly records this traceability error and invalidates only that
claim, append-only; the v3 record remains available as historical evidence.

V4 removes the two untraceable module assertions. It does not replace them with
`defer` or any other invented transport attribute.

## Standards

**APPROVED; zero findings.** The correction is minimal, deterministic and at
the full HTTP response seam. Expected script sources come directly from the
approved route inventory rather than current production output. No production,
authorization, content, error or persistence expectation changed.

## Spec

**APPROVED; zero findings.** For every successful card response the oracle
requires exactly two script nodes in DOM order:

1. `/pilot/assets/navigation.js`
2. `/pilot/assets/object-details.js`

Each node must have exactly one attribute, the corresponding exact `src`, and
empty inline content. Exact total cardinality and ordered lookup reject missing,
additional, reordered or duplicated scripts. Attribute cardinality rejects
`type`, `defer`, `async`, nonce, integrity and every other extra attribute.
The following global assertions reject inline event attributes and normalized
case variants of `javascript:` in `href` or `src`.

All of those script/executable-content assertions occur before `pocVisible()`
and every visible-content/order assertion. The reproduced focused run therefore
fails at the current missing second script with the exact intended result:

```text
Example A broad reader without capability has exactly two approved external scripts
Expected: 2
Actual: 1
exit 255
```

The existing source-only navigation element accounts for the actual one. The
separate known content RED remains unreachable until this script predecessor
becomes GREEN.

`pocError()` and the default `pocSecurity(..., false)` path are unchanged.
Existing 401, 403, 404, 405 and 503 cases therefore continue to require
byte-exact `BASE_CSP`, alongside their existing status, body, length, `Allow`
and `Retry-After` assertions. Success still requires byte-exact
`SCRIPT_HTML_CSP`. Zero-mutation and deterministic-read assertions are also
unchanged.

## Reproduced commands

```text
php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_card_001_test.php
# Expected: 2 / Actual: 1 at the exact two-script assertion; exits 255

openspec validate define-pilot-route-csp --strict
Change 'define-pilot-route-csp' is valid

git diff --check 2b91bed691421b1313ab915f6ba95ac7cfc0b4ae..357b925cc57599e8cd6484e494ffae6d0270a19a
PASS (no output)
```

Gate 3 is approved for the exact corrected test bytes at `357b925`. Gate 4 may
implement the missing source-only object-details script while preserving the
ordered navigation-plus-object-details contract and every unchanged boundary.

Summary: Standards 0 findings; Spec 0 findings.
