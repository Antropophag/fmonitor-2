# Test review: PILOT-E2E-ADMISSION-CLASS-TOKENS-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root` (recorded in the Gate 2 RED evidence); reviewed commit authored by Timofey Grishin
- Reviewed commit: `a9ba89a773391a5a8a305931328919945a3ba31c`
- Specification: inherited owner-approved `docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md`, revision 3, SHA256 `c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`
- Public seam: `FMonitor2\Tests\Support\ProtectedE2eAdmissionOracle::matches(string $html): bool`
- Red command and intended failure: `php tests/Verification/protected_e2e_admission_class_tokens_001_test.php`; exit `1`, at `RED_ASSERTION: forbidden class token separated by HTML FF`, expected `false`, actual `true`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e  docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md
915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c  docs/operations/owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md
0b05d0fa48e256a4584f0937285c892598a4a23110d7e60f9bb921560cf44049  tests/Verification/protected_e2e_admission_class_tokens_001_test.php
e6f4ecfd716b949905415162bf333266e95c59d5eec7eca0360b71c0eb50f621  docs/operations/protected-e2e-admission-class-tokens-red-2026-09-05.md
7859809e0286d25cb20aa27a0b1537048b4777b3e367a17b4d3c63bc1ab9d5fd  tests/Support/ProtectedE2eAdmissionOracle.php
e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9  tests/Verification/protected_e2e_admission_oracle_001_test.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

The original approved 28-case test, existing oracle implementation, and protected E2E retain their previously reviewed hashes. This review covers only the separately restarted class-token regression test required by the prior Gate 5 `CHANGES_REQUESTED` finding.

## Findings

Traceability is complete. The inherited revision-3 contract forbids an element with class token `shlz-table-wrap` inside the sole `main#main-content`. The new test fixes the exact HTML token boundary: ASCII TAB, LF, FF, CR, and SPACE are separators. Its positive control is the specification's independent literal HTML rather than renderer output.

Sensitivity is complete for this bounded correction. Five one-condition negatives insert the same forbidden token between `x` and `y`, varying only the five HTML ASCII-whitespace separators. Three positives delimit the rejection boundary: `x-shlz-table-wrap` and `shlz-table-wrapper` are near tokens, while `x` plus NBSP plus `shlz-table-wrap` plus NBSP plus `y` remains one class value because NBSP is not an HTML class-token separator. These cases would catch a correction that handles only form feed, uses a broad Unicode-whitespace split, or rejects substrings instead of complete tokens.

The expected values are independently determined from the inherited class-token requirement. The test neither reads application output nor derives expectations from the existing implementation. It exercises the same public test-support seam, uses only fixed in-memory HTML, and has no database, network, filesystem mutation, production renderer, private-method, or protected-E2E dependency. Execution order and inputs are deterministic.

The existing implementation is present and the positive fixture succeeds, proving setup and the public seam are functional. The first matrix entry is deliberately FF, so the observed failure isolates the exact Gate 5 defect before later cases. After minimal GREEN, the unchanged loop must execute all five separator negatives and all three boundary positives before printing `PROTECTED_E2E_ADMISSION_CLASS_TOKENS_OK cases=9`.

Independent reproduction at reviewed commit:

```text
$ php tests/Verification/protected_e2e_admission_class_tokens_001_test.php
RED_ASSERTION: forbidden class token separated by HTML FF
Expected: false
Actual: true
```

Exit status: `1`. Classification: intended public-oracle behavior RED, not setup failure and not missing production behavior. `php -l tests/Verification/protected_e2e_admission_class_tokens_001_test.php` also passes.

No blocking traceability, sensitivity, expected-value independence, boundary, determinism, isolation, or seam-choice finding remains. Gate 3 is `APPROVED` for test hash `0b05d0fa48e256a4584f0937285c892598a4a23110d7e60f9bb921560cf44049`. Minimal correction of the test-support oracle may proceed through Gate 4. This verdict does not approve a protected E2E patch, production/UI change, or full E2E status.

## Required changes

None.
