# Code review: PILOT-E2E-ADMISSION-ORACLE-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate5`
- Implementation author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `b45c3e7ee0f7dd514b679be8fcaee41957aa6dbf`
- Implementation base: `fac9f5979cf430a17cd208d2dfbb6646b5d1fe1c`
- Specification: owner-approved `docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md`, revision 3, SHA256 `c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`
- Owner approval: `docs/operations/owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md`, SHA256 `915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c`
- Approved test review: `reviews/tests/PILOT-E2E-ADMISSION-ORACLE-001-v1.md`, `APPROVED`, SHA256 `0014692cf93fd687eb377f3f5e9bb38c09f16c8a88ea5d75fc3febd884d392b6`
- Verification commands: `php tests/Verification/protected_e2e_admission_oracle_001_test.php`; `php -l tests/Support/ProtectedE2eAdmissionOracle.php`; `make architecture-check`; `git diff --check b45c3e7ee0f7dd514b679be8fcaee41957aa6dbf^ b45c3e7ee0f7dd514b679be8fcaee41957aa6dbf`
- Verdict: `CHANGES_REQUESTED`

## Exact reviewed inputs

```text
c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e  docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md
915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c  docs/operations/owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md
e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9  tests/Verification/protected_e2e_admission_oracle_001_test.php
0014692cf93fd687eb377f3f5e9bb38c09f16c8a88ea5d75fc3febd884d392b6  reviews/tests/PILOT-E2E-ADMISSION-ORACLE-001-v1.md
bd4e150b5bffc7bbd5b3f986f0a93c4cc34bfe25943c968f39c22b3eec52f141  docs/operations/protected-e2e-admission-oracle-red-2026-09-05.md
83c703324955d542bba11b831f2ab4500b0c3901f626dd02bc2feed31af1c931  docs/operations/protected-e2e-admission-oracle-green-2026-09-05.md
7859809e0286d25cb20aa27a0b1537048b4777b3e367a17b4d3c63bc1ab9d5fd  tests/Support/ProtectedE2eAdmissionOracle.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

The approved 28-case test is byte-identical to its Gate 3 artifact. The protected E2E is also byte-identical to the RED/GREEN evidence and was not edited by the reviewed commit.

## Findings

### Blocking: forbidden HTML class token can bypass the wrapper check

The wrapper query uses XPath `normalize-space(@class)`, whose whitespace set is
the XML set (space, tab, carriage return and line feed). HTML class tokens also
use form-feed as ASCII whitespace. Consequently a forbidden token separated by
form-feed is accepted. An independent public-seam probe at the reviewed commit,
using the fixed positive document plus
`<div class="x\fshlz-table-wrap\fy"></div>` inside `main`, returned `true`.
The approved contract forbids a `shlz-table-wrap` class token inside the sole
main, so this is a specification-conformance and regression-sensitivity gap.

The existing 28-case matrix checks ordinary-space token separation and the
allowed `x-shlz-table-wrap` substring, but it does not exercise form-feed token
separation. Under Gate 5, adding that sensitivity case restarts at Gate 2 and
requires a new independent Gate 3 approval before implementation changes.

Apart from the blocking class-token case above, the oracle conforms to the revision-3 public test-support seam. It rejects empty and invalid UTF-8 input, parses the supplied HTML with `LIBXML_NONET`, contains libxml diagnostics, requires exactly one `main#main-content`, rejects a native table and ordinary-whitespace-separated `shlz-table-wrap`, and requires one canonical `/pilot/objects/4512` anchor whose normalized text is `4512`.

Membership and fact locality match the contract: the anchor must resolve to exactly one `li` with a direct `ul` or `ol` parent, and every required registration, address, entrance, start-date and finish-date literal must occur in that same item's descendant-text projection. Text nodes are visited in document order, Unicode whitespace is collapsed, empty runs are dropped, and Unicode letter/number lookarounds prevent partial matches such as `Подъезд 20`, `Подъезд 22`, or `д. 100`. `preg_quote` keeps literal punctuation literal. Navigation outside the sole main cannot supply membership or facts.

The implementation is deterministic and side-effect free at the approved seam: it has no database, HTTP, filesystem-write, application-renderer, or production API dependency and emits no output. The reviewed commit adds only the test-support oracle and GREEN evidence and advances the already completed Gate 3 task checkbox. It does not mutate production code, routes, application seams, or the protected E2E.

The unchanged test would catch plausible regressions in main cardinality, canonical anchor cardinality/text/href, semantic list membership, same-item locality, Unicode normalization and boundaries, punctuation treatment, invalid input, and forbidden table/wrapper markup. Independent focused execution produced:

```text
PROTECTED_E2E_ADMISSION_ORACLE_OK cases=28
No syntax errors detected in tests/Support/ProtectedE2eAdmissionOracle.php
ARCHITECTURE CHECK PASSED (7 rules)
```

`git diff --check` also passed. No heavy full suite was run because this Gate 5 is scoped to the test-support oracle and the later protected integration retains separate gates.

The read-only private archive named by the GREEN record was independently checked. Its three recorded SHA256 values match, `check-response.php` reports `REAL_HTTP_ADMISSION_SEMANTIC_LIST_OK`, and the saved unchanged protected run still shows the stale line-153 exact-link failure. This corroborates the fixture/oracle mismatch only; this review does not approve a protected E2E patch, downstream behavior, full-E2E success, or launch readiness.

## Required changes

1. Add a regression proving that a `shlz-table-wrap` token separated by HTML
   form-feed whitespace inside `main#main-content` is rejected, preserve its
   RED result, and obtain independent Gate 3 approval.
2. After that approval, change the oracle minimally so class-token recognition
   follows HTML ASCII-whitespace tokenization, then rerun the focused test,
   lint, architecture check, and independent Gate 5 review.
