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

## Validation extraction delta review — 2026-09-14

- Reviewer: Codex independent reviewer `/root/review_calc` (gpt-5.6-sol / low)
- Implementation author: executor assigned by root; reviewer authored neither production file
- Reviewed source: base commit `79b3a4cecae558d74d781f353e967f03c036b921` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T193035Z-53029fe020/snapshot`, patch SHA-256 `572b06d32e81c69d9e16aa36c4b934aa67b2b30d7b402526510e87b0f83d1d38`; restored and checked at `/private/tmp/fmonitor-issue66-calc-refactor.0LuU3V/checkout`
- Agreed review scope: only the refactor delta in `app/Otiz/PremiumCalculationV2.php` and new internal collaborator `app/Otiz/PremiumCalculationV2Evidence.php`. Unrelated certificate/publication candidate files present in the frozen snapshot are excluded from this verdict.
- Unchanged approved inputs: `specs/OTIZ-EXCEL-CALCULATION-001.md` SHA-256 `58b21ab2feb9dcf6ff26f26057f0c1833497d920fbaf4b2a5231d48aab7d106a`; `tests/Otiz/excel_calculation_001_test.php` SHA-256 `fef9233fdccbc853cfa688e1a9055457f1d69f0a911a10786ad9bb2ec67910f1`
- Reviewed implementation hashes: calculator `66c699deae54d0103dd687a885000f78c47882238db9ae329b4b5ae030c12fcd`; evidence collaborator `fd37b29dd67135a4189174e53bdee8e99e08fbe50dab234c2eace1053d0efcd7`
- Exact-source evidence: retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789414228414116000-8b585c04991c4dfcbb41533c9ecce74c.json` reports `php tests/Otiz/excel_calculation_001_test.php` GREEN at candidate source `93765de116823fec7cb3bf203304acb8e958d4b06e23a5df063c31144ee4d4f2` / executable source `3b2ddd1ce9a9cafdd3ee2d8a234c2e03b68fc2f2005f3fe42cbc53f295a496f9`.
- Reviewer verification: `php tests/Otiz/excel_calculation_001_test.php` GREEN, all seven groups plus terminal marker; `php -l app/Otiz/PremiumCalculationV2.php` GREEN; `php -l app/Otiz/PremiumCalculationV2Evidence.php` GREEN; `python3 tools/architecture/check.py` GREEN, 7 rules.
- Verdict: `APPROVED`

### Findings

None for the agreed refactor delta.

The calculator retains its documented public methods, constants, argument types, result grammar and arithmetic/allocation implementation. The extracted collaborator contains the prior operand, payment, exclusion, provenance and date validation with the same call order, bounds, accepted values and `InvalidArgumentException` behavior. It has no state or external dependency and is referenced only by `PremiumCalculationV2`. The extraction removes the calculator file-size hotspot while keeping evidence validation cohesive and leaves the approved specification and tests byte-identical.

### Required changes

None.
