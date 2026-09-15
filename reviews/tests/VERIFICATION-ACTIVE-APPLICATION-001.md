# Test review: VERIFICATION-ACTIVE-APPLICATION-001

- Reviewer: Codex independent reviewer `/root/review_calc` (gpt-5.6-sol / low)
- Test/spec author: root agent
- Reviewed source: base `b1d6b5178126090e6e2ee3d6eb090100547d3c47` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210130Z-967f2a716d/snapshot`; predecessor content was also checked from package `20260914T210043Z-a562058a1b`
- Candidate/executable source: `c0fe14d369fe2360e8a46818fd0adf870efba2c64a9a453169abdf2a4fbc76cd` / `74009ffae2dd9181eeac1e42462866c08a082f202993415f927f32d04f1b8607`
- Evidence: full focused file record `1789419637085297000-e69d9f7cd8da4dd7a33b8ae8d0a51d31` is exact-source `INTENDED_RED`, with the existing e2e entry and nine direct rapid paths as the two expected policy failures after the other 17 checks pass.
- Scope decision: mandatory CI targets the current Yii2 application; nine direct rapid verifiers and the exact 16 listed retired launch/UI entrypoints leave the catalog. Shared application/domain/security/migration/history checks, five inspection characterization wrappers, production negative rapid-package checks, and the V1 calculation oracle remain mandatory.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — the test does not enforce the promised exact preservation of every non-retired catalog entry** (`specs/VERIFICATION-ACTIVE-APPLICATION-001.md:11,34`, `tests/Verification/verification_ci_001_test.py:390-400`). The specification says all entries other than the exact 25 retirements remain, and specifically calls out all five inspection characterization wrappers plus broad Yii auth/access/session/CSP, security, migration and history coverage. The new test bans rapid paths and the 16 named files, then requires only 11 selected paths. Apart from the separately exact e2e list, an implementation could delete arbitrary unit, integration or governance entries—including the five characterization wrappers—and still pass. Gate 5’s planned manual base comparison is useful evidence but does not make the executable contract regression-sensitive. Assert that the complete resulting `(group, runtime, path)` roster equals the base roster minus the exact 25 retired entries plus the one V1 oracle, or pin an independently derived digest/count and explicitly require the five characterization wrappers and other named families.

The bounded retirement list itself matches the owner decision: nine direct `rapid-pilot/verify-*` entries and 16 named retired launch/UI files, with no skiplist, allow-failure, optional lane or aggregator relaxation. The focused RED is attributable to the intended catalog delta. The proposed native V1 oracle must be included in the correction snapshot for its literal assertions and direct `LegacyPremiumCalculation` import to receive final Gate 3 review.

## Required changes

1. Make exact preservation of the full non-retired roster executable, including the five inspection characterization wrappers.
2. Include the proposed V1 oracle source in the next reconstructible review package.

## Correction rereview — 2026-09-15

- Reviewed source: base `b1d6b5178126090e6e2ee3d6eb090100547d3c47` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210444Z-c1e452c45d/snapshot`, restored at `/private/tmp/fmonitor-active-app-g3-r2.pih9Sk/checkout`
- Candidate/executable source: `5e4d407037f0eac6de20303b118c84d19b21f4eadd18b14c76be149ba9445678` / `405adc7d37365f237e81277a390365eff5c513298f3f665d40a4bee2ffafe090`
- Corrected test SHA-256: `9d46a590b84183350db25c2f5bfff2c2645fce304ab187b95de9cde977b86af0`
- Staged V1 oracle SHA-256: `185ce7c0b1828e46322aef704e55982b1790e3621ec1b0319abdb80d997f9df6`
- RED evidence: full focused `python3 tests/Verification/verification_ci_001_test.py` record `1789419800743847000-abff9c719c0f41e297c9d4a0d9967885` exits 1 at the intended current-catalog mismatch, bound to the candidate and executable source above.
- Prior findings disposition: both resolved.
- Verdict: `APPROVED`

### Complete findings

None.

The corrected test hashes the complete sorted `[category,runtime,path]` roster. I independently reconstructed it from commit `b1d6b517`: 443 base entries minus the 16 explicitly retired files and nine disjoint direct rapid verifiers, plus the native V1 oracle, yields exactly 419 entries and SHA-256 `7e325890f8e337a4db1c4801c627b5e58bf1aa22454cb1a7a81d0c74efdf6772`. This makes every retained entry—including all five inspection characterization wrappers and the existing security, migration, history and Yii contracts—sensitive to removal, category drift, runtime drift or duplication.

The staged oracle is now part of the reconstructible snapshot. Its assertions match the retired premium verifier’s literal V1 version, progress, lateness coefficient, integer amounts, payout discrepancy, payout independence and exclusion behavior; only its dependency changes to the current application adapter `app/Otiz/LegacyPremiumCalculation.php`. Moving these unchanged bytes to the asserted final `tests/Otiz/` path will make the relative import valid and register the historical application contract without retaining a rapid entrypoint.

The remaining RED is the intended pre-implementation state: the catalog still contains the retired e2e/direct rapid entries and does not yet contain the final V1 oracle entry. No allow-failure, skip list, optional lane, aggregator change or unrelated coverage retirement is authorized.

### Required changes

None.
