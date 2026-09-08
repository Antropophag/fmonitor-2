# PILOT-OBJECT-CARD-001 — independent route-CSP correction Gate 3 review

Date: 2026-09-04  
Reviewer: fresh separately tasked agent `/root/object_card_csp_gate3`  
Reviewed commit: `b1f2785ef7d6babad151ec79d391f40102ceea55`  
Base: `f5636a9e44e184cf1c0c9c7021a312c9620c83f0`  
Verdict: **CHANGES_REQUESTED**

The reviewer did not author or modify the specification, corrected test, RED
evidence, production implementation, or prior reviews. This review record is
the only edit.

## Reviewed contract and hashes

```text
47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef  specs/PILOT-ROUTE-CSP-001.md
312982a37d6093914ddbd976246710c7ae86ff010635460c63c04cf27b5ef736  reviews/tests/PILOT-ROUTE-CSP-001-v4.md
014bf3f5726ef7913816ebb536a0b57946b1203e96809c2ecb14f49d4d0e3d19  reviews/tests/PILOT-OBJECT-CARD-001.md
4fe6de250888b82ddce7b249c209bf32bb08fc649bc423e10ce730f9f02e1f6c  tests/InstallationProcess/pilot_object_card_001_test.php
53a00c209bf8cfd167535cf12fee3ecca911da48d083edec0c85143a29315b90  docs/operations/pilot-object-card-csp-red-correction-2026-09-04.md
```

The route-CSP specification and its latest fixture-correction approval retain
their pinned owner-approved hashes. The existing object-card Gate 3 record is
also unchanged. The reviewed commit changes only the object-card test and its
append-only correction evidence; it changes no production file.

## Independent assessment

The CSP split is traceable to the approved successful object-card route in
`PILOT-ROUTE-CSP-001`: exact `GET /pilot/objects/{positive-id}` HTML and its
equivalent HEAD representation require byte-exact `SCRIPT_HTML_CSP`. The
success helper now requests that exact policy, including directive order,
while `pocError()` continues to call `pocSecurity()` with its default and thus
requires byte-exact `BASE_CSP` without `script-src` for every existing 401,
403, 404, 405 and 503 assertion. Existing GET/HEAD header parity continues to
compare CSP and Content-Length and require an empty HEAD body.

The new DOM assertions are structurally capable of rejecting extra/inline or
third-party scripts, inline event attributes and case/whitespace variants of
`javascript:` in `href` or `src`. The exact SCRIPT CSP independently excludes
`unsafe-inline`, `unsafe-eval`, nonce/hash, wildcard and third-party origins.
However, the reviewed RED does not reach those assertions, and one of them is
not derived from the approved contract, as detailed below.

Diff inspection confirms that all object-card fixture data, ordered visible
literals, structure, authorization, escaping, persistence-neutrality, error,
route, method and cleanup assertions are byte-unchanged. The only former
blanket assertion removed was the contradictory ban on every `<script`
element; the replacement is narrower and materially more sensitive to the
approved external-script contract.

## Blocking findings

### 1. The claimed script-check progression is not reproduced

`pocSuccess()` evaluates the complete ordered-visible loop before the new
doctype and DOM checks. The ordinary corrected run therefore stops at
`77-000123` without executing the exact source, `defer`, inline-content,
event-handler, or `javascript:` assertions. The correction evidence states
that the run passes those checks, but its transcript cannot support that claim.

To test the hidden next assertion without changing reviewed expectations, the
reviewer temporarily moved the unchanged visible loop below the new DOM block,
ran the focused test, then restored the exact reviewed file. That diagnostic
failed as follows:

```text
Example A broad reader without capability has exact deferred object-details script
Expected: 1
Actual: 0
exit 255
```

The rendered production tag is
`<script type="module" src="/pilot/assets/object-details.js"></script>`; it has
module-defer semantics but no literal `defer` attribute. Thus the evidence's
claim that the exact deferred-script assertion passed is false.

Required correction: put the new CSP/script safety checks before the unrelated
known presentation RED (or demonstrate them in a separately reachable
response), recapture the real RED sequence, and describe every observed
failure accurately.

### 2. Literal `defer` is not an independently approved expected value

`PILOT-ROUTE-CSP-001` requires the object-card representation to load
`/pilot/assets/object-details.js` externally and same-origin, but it does not
require a literal `defer` attribute or replace the existing module script tag.
A1 also says the existing body is unchanged by this CSP slice. The reviewed
test instead rejects the current `<script type="module" ...>` representation
and accepts a non-module `defer` representation, creating an unapproved body
change beyond the route-CSP correction.

Required correction: derive the exact script-element expectation from an
approved contract. For this CSP-only slice, freeze the existing module-script
representation while retaining the exact-source, empty-content, no-extra-
script, no-event-handler and no-`javascript:` safety assertions. If literal
`defer` is a desired product change, amend and approve Gate 1 first.

## Reproduced visible RED progression

On the exact base test bytes from `f5636a9`, with the same production tree:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_card_001_test.php

Example A broad reader without capability content-security-policy
Expected: default-src 'none'; style-src 'self'; img-src 'self'; ...
Actual:   default-src 'none'; style-src 'self'; script-src 'self'; ...
exit 255
```

After restoring the corrected test from reviewed commit `b1f2785`:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_card_001_test.php

Example A broad reader without capability visible literal/order: 77-000123
Expected: true
Actual: false
exit 255
```

The second transcript proves only that status/media/length and corrected SCRIPT
CSP pass before the pre-existing card presentation RED. It does not prove the
later DOM assertions. All error paths remain byte-exact BASE CSP by unchanged
`pocError()` calls and the default `pocSecurity(..., false)` branch.

Additional verification:

```text
php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php

openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid

git diff --check f5636a9...b1f2785
PASS (no output)
```

Gate 3 is **CHANGES_REQUESTED**. Return only the test/evidence correction to
Gate 2; do not change the unrelated card presentation expectations or
production code. A fresh independent Gate 3 review is required after the
script oracle is traceable and its RED is actually reachable and recorded.
