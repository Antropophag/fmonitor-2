# Code review: OTIZ-EXCEL-CALCULATION-001

- Reviewer: Codex independent reviewer `/root/review_calc` (gpt-5.6-sol / low)
- Implementation author: executor `/root/implement_calc`
- Reviewed source: base `fda41a50605146cba8c34a7011e33325dde52dbd` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T175224Z-7766d090d2/snapshot`, patch SHA-256 `4f0558e8e168384d7a9f34c757a70167e7d165498066728bb31d2b7caf369036`; restored and checked at `/private/tmp/fmonitor-issue66-gate5.rWIXjK/checkout`
- Agreed review scope / prior findings disposition (for rereview): first Gate 5 review of the pure calculator implementation `app/Otiz/PremiumCalculationV2.php` only. Concurrent certificate, persistence, HTTP and parent-change artifacts in the snapshot are outside this verdict and require separate gates. No prior Gate 5 findings.
- Specification: `specs/OTIZ-EXCEL-CALCULATION-001.md`, SHA-256 `58b21ab2feb9dcf6ff26f26057f0c1833497d920fbaf4b2a5231d48aab7d106a`
- Approved test review: `reviews/tests/OTIZ-EXCEL-CALCULATION-001.md`, final Gate 3 verdict `APPROVED`; approved test SHA-256 `fef9233fdccbc853cfa688e1a9055457f1d69f0a911a10786ad9bb2ec67910f1`
- Implementation SHA-256: `f7d0b1878570dd8058fd156a25f20e333503e284499d4efa540a5d04b6381eb1`
- Verification commands: prepared exact-source records report GREEN for `php tests/Otiz/excel_calculation_001_test.php`, `php tests/Otiz/snapshot_publication_001_test.php`, `python3 tests/Verification/change_verification_001_test.py`, and `php tests/Runtime/runtime_storage_001_test.php`, all at candidate source `863674f8a026c621448216aeb73b9a88eeb257879dae51bbcb7f996107b6c0b3` / executable source `86966a4e2e794d73c00c94a64961b6304faccfc62b8e1c09c5e3f1c6751b6f03`. The reviewer reran all four bounded commands from the restored snapshot: calculation 7 groups plus terminal marker GREEN; snapshot publication GREEN; verification 16/16 GREEN; runtime storage GREEN.
- Verdict: `APPROVED`

## Findings

None for the agreed pure-calculator scope.

The implementation validates every operand, payment and exclusion envelope before calculation, retains the accepted evidence unchanged, rejects invalid nested values without external facts, and treats actual payouts as validated informational evidence. Date comparison, Kss flooring, ordered integer HALF-UP boundaries, recurrence after signed confirmed closures, exclusion visibility and the exact formula trace conform to the contract. Allocation validates list shape, UTF-8 character length, uniqueness and integer bounds; uses safe integer arithmetic within the specified maxima; distributes largest remainders deterministically with binary tab ordering; conserves the pool; and returns integer cents sorted by tab. The class has no database, clock, filesystem, actor, permission or mutable-state dependency and leaves v1 callers untouched.

The use of `mb_strlen` is consistent with the contract's UTF-8 character limit and the repository's existing locked Yii2 platform requirement on `ext-mbstring`; it does not introduce a new package requirement in this slice. Deployment-image qualification remains part of the parent integration candidate rather than this pure Gate 5 verdict.

## Required changes

None.
