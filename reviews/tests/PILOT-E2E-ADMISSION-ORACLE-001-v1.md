# Test review: PILOT-E2E-ADMISSION-ORACLE-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root` (recorded in the Gate 2 RED evidence); reviewed commit author is Timofey Grishin
- Reviewed commit: `41247c5a5911f34f632df89c3af0d027bd5f2c36`
- Specification: owner-approved `docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md`, revision 3, SHA256 `c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`
- Public seam: `FMonitor2\Tests\Support\ProtectedE2eAdmissionOracle::matches(string $html): bool`
- Red command and intended failure: `php tests/Verification/protected_e2e_admission_oracle_001_test.php`; exit `1`, with explicit `RED_ASSERTION: public ProtectedE2eAdmissionOracle::matches is missing`, expected `true`, actual `false`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e  docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md
915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c  docs/operations/owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md
e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9  tests/Verification/protected_e2e_admission_oracle_001_test.php
bd4e150b5bffc7bbd5b3f986f0a93c4cc34bfe25943c968f39c22b3eec52f141  docs/operations/protected-e2e-admission-oracle-red-2026-09-05.md
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

The owner record explicitly approves the exact revision-3 candidate hash and authorizes its gated oracle work. It does not authorize an immediate protected-test edit, production/UI behavior, a manual-registration target, or a gate waiver.

## Findings

Traceability is complete. The test cites the approved amendment identifier and exact SHA. Its two fixed positive documents and every mandatory revision-3 negative are literal specification data: `ul`, `ol`, combined descendant text with external navigation, missing and duplicated object membership, wrong href and link text, non-`li` membership, each wrong inherited fact, forbidden native table, forbidden `shlz-table-wrap` class token, navigation-only placement, duplicate `main#main-content`, `Подъезд 22`, `Подъезд 20`, and `д. 100`.

The additional cases increase sensitivity without adding product requirements. They prove a second canonical anchor anywhere in main is rejected, facts cannot be borrowed from a sibling item, the matching `li` needs a direct `ul`/`ol` parent, invalid UTF-8 is false, Unicode whitespace is normalized, Unicode alphanumeric neighbors block substring matches, punctuation is literal, and a mere `x-shlz-table-wrap` substring is not treated as a class token. Each negative changes one relevant condition from an independently fixed positive, so missing, duplicate, misplaced, wrong-object, wrong-fact, forbidden-table, and boundary regressions are observable.

The chosen seam is the exact owner-approved test-support API. The test uses no application renderer, response DTO, database, network, production filesystem, private method, or protected E2E modification to obtain expected values. Expected booleans are stated independently in the test matrix. Output capture additionally enforces the no-output part of the seam for every executable matrix row.

The test is deterministic and isolated: inputs are in-memory literals, iteration order is fixed, and no external state or production system participates. Bootstrap succeeds. Because the oracle class is deliberately absent, the initial `class_exists`/`is_callable` predicate reaches `assertSameValue` and reports an explicit assertion instead of an include/autoload/environment error. Short-circuiting before the matrix is appropriate for this first missing-public-seam RED; after minimal implementation, the same unchanged test necessarily executes all 28 cases.

Independent reproduction at reviewed commit:

```text
$ php tests/Verification/protected_e2e_admission_oracle_001_test.php
RED_ASSERTION: public ProtectedE2eAdmissionOracle::matches is missing
Expected: true
Actual: false
```

Exit status: `1`. Classification: intended missing-test-support-oracle RED. This is not evidence of missing production behavior, does not approve a protected assertion patch, and does not establish full E2E GREEN.

No blocking traceability, sensitivity, expected-value independence, rejected-case, determinism, setup-isolation, or seam-choice finding remains. Gate 3 is `APPROVED` for test hash `e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9`. Minimal implementation of the oracle may proceed through Gate 4; later protected E2E assertion changes retain their distinct gates.

## Required changes

None.
