# Code review: VERIFICATION-ACTIVE-APPLICATION-001

- Reviewer: Codex independent reviewer `/root/review_calc` (gpt-5.6-sol / low)
- Specification/test author: root agent; registry implementation author: separate executor
- Reviewed source: base `b1d6b5178126090e6e2ee3d6eb090100547d3c47` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T211009Z-54790151ac/snapshot`
- Candidate source: `108a7e6adece188a73f83c718358482526d90aaf5b81cfd88f6a8dfdb3777aaf`; executable source: `f350067a062e51bde59546ce1a53068acee4230d8f3853c77a64c9a05d31f7d7`
- Contract: `specs/VERIFICATION-ACTIVE-APPLICATION-001.md`
- Verdict: `APPROVED`

## Findings

None.

The implementation changes only the two verification registries for catalog behavior, deletes the exact 16 retired launch/UI entrypoints, and moves the approved historical V1 oracle into `tests/Otiz/`. The runner, planner, aggregation behavior, CI workflow, allow-failure policy and skip behavior are unchanged.

I independently reconstructed the complete registry delta from base `b1d6b517`: 443 entries minus the 16 named retired files and nine disjoint direct `rapid-pilot/verify-*` entries, plus one `unit/php` native V1 oracle, yields 419 unique entries. The final sorted `[category,runtime,path]` digest is `7e325890f8e337a4db1c4801c627b5e58bf1aa22454cb1a7a81d0c74efdf6772`. Both registries contain exactly those 419 paths with matching categories; every survivor retains its prior category and runtime. The five inspection characterization wrappers, shared security/migration/history contracts, current Yii2 application tests and negative production-package boundaries remain registered.

The new oracle has SHA-256 `185ce7c0b1828e46322aef704e55982b1790e3621ec1b0319abdb80d997f9df6`. It preserves the retired verifier's literal V1 assertions and replaces only the rapid dependency with the current application adapter `app/Otiz/LegacyPremiumCalculation.php`, retaining historical replay value without a rapid entrypoint.

## Verification evidence

- `php tests/Otiz/legacy_premium_calculation_001_test.php`: exact-source `GREEN`, record `1789420089286457000-03f44b625b694a6eab908595196ffa82`.
- `python3 tests/Verification/verification_inventory_001_test.py`: exact-source `GREEN`, record `1789420089286496000-0c21c3fa58dc477da083747f6ae10079`.
- `python3 tools/verification/ci.py verify-roster`: exact-source `GREEN`, record `1789420138040312000-105abc514a014010b357b597b7c5e2e8`.
- `python3 tests/Verification/verification_ci_001_test.py`: exact-source `GREEN`, record `1789420148859182000-08b2ff18111845faa6d30832db7516c7`.

This approval covers the bounded active-application CI policy implementation. Overall merge admission and the subsequent exact-source full CI remain separate decisions.

## Required changes

None.
