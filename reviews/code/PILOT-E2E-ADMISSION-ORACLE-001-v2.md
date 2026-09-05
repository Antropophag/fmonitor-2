# Code review: PILOT-E2E-ADMISSION-ORACLE-001 v2

- Reviewer: separately tasked agent `/root/admission_oracle_gate5`
- Implementation author: `/root`; reviewed commit authored by Timofey Grishin
- Reviewed commit: `6d327950df6c0f6466e7e1ae37f76e4456dbf950`
- Specification: owner-approved admission amendment revision 3, SHA256 `c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`
- Original approved test review: `reviews/tests/PILOT-E2E-ADMISSION-ORACLE-001-v1.md`, `APPROVED`
- Corrective approved test review: `reviews/tests/PILOT-E2E-ADMISSION-CLASS-TOKENS-001-v1.md`, `APPROVED`, committed as `d59aacf`
- Prior code review: `reviews/code/PILOT-E2E-ADMISSION-ORACLE-001-v1.md`, `CHANGES_REQUESTED`
- Verification commands: both focused verification scripts; PHP lint; saved actual-response oracle check; correction-commit `git diff --check`
- Verdict: `APPROVED`

## Exact reviewed inputs

```text
c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e  docs/operations/proposed-protected-e2e-admission-amendment-2026-09-05.md
915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c  docs/operations/owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md
0014692cf93fd687eb377f3f5e9bb38c09f16c8a88ea5d75fc3febd884d392b6  reviews/tests/PILOT-E2E-ADMISSION-ORACLE-001-v1.md
c1b5b0c492efcdd9122b9dd2071ed12a6f4e6a11af40f3cf48588d6041b14127  reviews/code/PILOT-E2E-ADMISSION-ORACLE-001-v1.md
267688832d2fd56881c26ff19157406817f4fd268e83e8f74bdfb0032f32515d  reviews/tests/PILOT-E2E-ADMISSION-CLASS-TOKENS-001-v1.md
bd4e150b5bffc7bbd5b3f986f0a93c4cc34bfe25943c968f39c22b3eec52f141  docs/operations/protected-e2e-admission-oracle-red-2026-09-05.md
83c703324955d542bba11b831f2ab4500b0c3901f626dd02bc2feed31af1c931  docs/operations/protected-e2e-admission-oracle-green-2026-09-05.md
e6f4ecfd716b949905415162bf333266e95c59d5eec7eca0360b71c0eb50f621  docs/operations/protected-e2e-admission-class-tokens-red-2026-09-05.md
b4de9e6764ac3eac951266ee394521fd856434cb0caa4b502e1317260461e154  docs/operations/protected-e2e-admission-class-tokens-green-2026-09-05.md
496b11c87227abec92c56a977c95dc7935dd81b38e7de28512af10b3be6c1ef2  tests/Support/ProtectedE2eAdmissionOracle.php
e098a846f8d03eb79c7c77c7dbd8b4c422b7bda757d6a7c20ff56bf66d68b9b9  tests/Verification/protected_e2e_admission_oracle_001_test.php
0b05d0fa48e256a4584f0937285c892598a4a23110d7e60f9bb921560cf44049  tests/Verification/protected_e2e_admission_class_tokens_001_test.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

The original 28-case test and protected E2E retain their approved and recorded hashes. The new nine-case regression was committed before its independent Gate 3 review, and the implementation correction follows the committed approval in history.

## Findings

No blocking findings remain.

The v1 form-feed finding is resolved. The oracle now enumerates descendants with a class attribute and tokenizes each value on exactly HTML ASCII TAB, LF, FF, CR and SPACE. It rejects the complete `shlz-table-wrap` token for every valid separator while preserving near tokens and NBSP-containing single values. The implementation does not use broad Unicode whitespace or substring matching. The separate nine-case test covers all five separators and the three acceptance boundaries, so it would catch the original defect and plausible overcorrections.

The rest of the combined oracle remains conformant: UTF-8 validation, `LIBXML_NONET`, contained parser diagnostics, sole `main#main-content`, table rejection, exact canonical link cardinality/text, semantic `li` membership, same-item fact locality, document-order descendant-text projection, Unicode whitespace normalization for visible facts, and Unicode letter/number boundaries are unchanged and remain covered by the original 28-case test.

The change remains test-support only. It introduces no database, network, filesystem-write, production API, route, authorization, audit/history, or application-renderer mutation. The protected E2E remains byte unchanged. The saved real HTTP response continues to satisfy the corrected oracle; that evidence establishes only admission-fixture compatibility and does not approve or apply the separate protected patch.

Independent verification at the reviewed commit produced:

```text
PROTECTED_E2E_ADMISSION_CLASS_TOKENS_OK cases=9
PROTECTED_E2E_ADMISSION_ORACLE_OK cases=28
No syntax errors detected in tests/Support/ProtectedE2eAdmissionOracle.php
REAL_HTTP_ADMISSION_SEMANTIC_LIST_OK
```

`git diff --check 6d327950df6c0f6466e7e1ae37f76e4456dbf950^..6d327950df6c0f6466e7e1ae37f76e4456dbf950` passed. A broader historical diff includes deliberate patch-text whitespace in the separately stored, unapplied protected-patch artifact; it is outside this oracle correction and does not alter the protected test or this verdict. No heavy full suite was run, as requested. Full E2E, the protected assertion integration, downstream failures and launch readiness retain their separate gates.

## Required changes

None.
