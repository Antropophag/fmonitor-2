# Test review: QUALITY-GRAPH-SHARDING-136

- Reviewer: independent Codex reviewer `/root/issue136_gate3`; authored neither the contract nor the tests
- Test author: root delivery agent
- Reviewed source: commit `f27ab4e3dd912f7d8e79a6cd2959bf99794d9440`; candidate source `31eaedf19b479bf2efb8dbced46b1e429425f27fd7a0a3321942bfc62c4e6b9b`; executable source `1a21deda190be6503764517983e3ff6ace618daaee44696e4dfc982499b24d22`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T212501Z-f67b79f842/snapshot/source.patch`, SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` (empty patch over the reviewed commit)
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T212501Z-f67b79f842/package.json`; plan SHA-256 `56eb1aa6e9581aeb75010e6645123466d95ada043f82592663cfc3f981a934b1`
- Agreed review scope / prior findings disposition: initial Gate 3 review of issue #136 RED source, acceptance cases A-H, canonical inventory and #153A semantic-closure preservation; no prior findings
- Specification: `specs/QUALITY-GRAPH-SHARDING-136.md`, SHA-256 `a27835a01b37ad1c3b6161ed305a9b84de95cb319418fa6eb3e554c81f9aa546`
- Public seam: `python3 tools/verification/ci.py list integration --shard 1/2|2/2`, plus existing workflow/aggregate compatibility seams
- Red command and intended failure: `python3 tests/Verification/verification_ci_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789507447960992000-4012b38610114e3b927d178b60df62f7.json`; exit `1`, `INTENDED_RED`. Nineteen methods pass and only `test_integration_shards_use_deterministic_lpt_and_beat_round_robin_skew` fails because the current round-robin produces `new_max == old_max == 24.0`, so this is missing LPT behavior rather than fixture/setup failure. Canonical inventory (`1789507466357319000-8077ff3a293149b490fa39380e17e2c6`) and #153A semantic closure (`1789507487773691000-9805544fe96944b395c5429abe15a964`) are GREEN.
- Verdict: `APPROVED` after the corrected-source rereview below

## Findings

1. **High — the test does not fix the required LPT algorithm and tie-breaks as executable expected values.** `tests/Verification/verification_ci_001_test.py:188-215` accepts any partition that is complete, disjoint and happens to beat old round-robin on one fixture. It never asserts the allocation independently obtained by sorting `(-weight, path)`, assigning the lower accumulated load and choosing the lower shard index on a load tie. A different heuristic or optimizer can satisfy the current test while violating contract clauses 2 and the explicit owner algorithm. Add an independently calculated exact shard-1/shard-2 expectation, including a weight/path tie and a shard-load tie, while retaining the strict skew improvement assertion.

2. **High — mandatory case B (input shuffled gives the same allocation) is not exercised.** `tests/Verification/verification_ci_001_test.py:217-230` deliberately makes `suites.tsv` non-canonical and asserts rejection, then restores the original file and merely repeats the same ordered input. Rejection preserves the #135 canonical inventory invariant, but it does not demonstrate order-independent allocation. Exercise an order-bearing input that may validly vary without changing membership—most directly, write the same timing rows in different orders and assert exact identical shard outputs—or exercise a pure allocation seam if one is intentionally public. Keep the separate assertion that a shuffled canonical inventory is rejected.

3. **Medium — fail-safe diagnostics and the full invalid-value contract are not asserted.** `tests/Verification/verification_ci_001_test.py:248-269` checks only success, determinism and membership. The specification requires diagnostics for missing/unreadable/malformed/duplicate/non-finite/non-positive hints, yet the test never asserts stderr and omits explicit negative, positive-infinity and unreadable cases. An implementation that silently falls back, mishandles `inf` or negative values, or ignores an unreadable artifact would pass. Add observable diagnostic assertions and fixtures covering every named invalid class (with a deterministic unreadable simulation suitable for the test environment).

The remaining acceptance coverage is sound: new-without-history and stale rows preserve exact membership; the real canonical inventory is partitioned once with empty intersection; the two existing workflow shard names/topology remain asserted; aggregate failure, cancellation and absence remain fail-closed; and focused evidence confirms #135 inventory plus #153A closure remain GREEN. The test is isolated in temporary repositories/runtimes and the retained RED is intended rather than setup-related.

## Required changes

- Add exact, independently derived LPT allocation assertions covering descending weight, path tie-break and lower-index load tie.
- Add a genuine shuffled-input allocation equivalence case without weakening canonical `suites.tsv` validation.
- Assert stderr diagnostics and cover missing, unreadable, malformed, duplicate, NaN/infinity, zero and negative weights while preserving union/intersection invariants.
- Capture a new intended RED and refresh the prepared Gate 3 package after the test/source changes.

---

## Corrected-source rereview — APPROVED

- Reviewer: independent Codex reviewer `/root/issue136_gate3`; authored neither the contract nor the corrected tests
- Reviewed source: commit `346f59e37b1b79354d2b7e6d8d6c0383949bba27`; candidate source `cdc63c52bbc83452bfe60c616a414e8662816ce5f37c2166707154a249a7d9ce`; executable source `fea839c1552ef2fde4edb6fac88b057d86816adc1ebba018816f169da05e9954`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T212917Z-58348563cd/snapshot/source.patch`, SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` (empty patch over the reviewed commit)
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T212917Z-58348563cd/package.json`; plan SHA-256 `acbb87daf2364f22ac7270862610ae74cc54c44bef3b364c02537bbe682b97c1`
- Corrected test: `tests/Verification/verification_ci_001_test.py`, SHA-256 `a563197f822cb47df833a42a9c1b3068f4e1ee934a4555dbcf878a77e94ac0b2`
- Specification: unchanged `specs/QUALITY-GRAPH-SHARDING-136.md`, SHA-256 `a27835a01b37ad1c3b6161ed305a9b84de95cb319418fa6eb3e554c81f9aa546`
- Corrected RED evidence: `python3 tests/Verification/verification_ci_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789507702417707000-70c308f11b8144e38918fe8fa0989ada.json`; exit `1`, `INTENDED_RED`. Eighteen unchanged methods remain GREEN. The three failures are attributable to the missing behavior: current round-robin does not beat the skew (`24.0 == 24.0`), the pre-implementation module lacks `integration_weights`/`integration_shards`, and current listing emits no `INTEGRATION_TIMING_FALLBACK`. There is no fixture/setup failure.
- Preservation evidence: canonical inventory record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789507720611265000-ea943a4c1a9b4ca7b8256f111e53355f.json` is GREEN (22 tests); #153A semantic-closure record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789507742688800000-f7046ca72e324595b742ff9da2b5530b.json` is GREEN (11 tests).
- Verdict: `APPROVED`

### Prior findings disposition

1. **Resolved.** The skew fixture now asserts independently calculated exact shard membership in addition to strict critical-load improvement. The isolated allocation case fixes equal-weight path ordering and lower-index tie behavior with explicit first/second-shard observations.
2. **Resolved.** `test_lpt_allocation_is_independent_of_input_order` passes the same items in forward and reversed orders to the allocation seam and requires identical allocations. The existing negative assertion for non-canonical `suites.tsv` remains intact, so #135 validation is not weakened.
3. **Resolved.** The fallback matrix now requires the stable stderr marker for missing, malformed, zero, negative, positive infinity, NaN, duplicate and unreadable/path-open-error hints. Every case still asserts deterministic output, full union and no duplicates; the separate stale/new-history test remains intact.

### Final findings

None. Cases A-H are executable and traceable: skew improvement and exact LPT allocation (A), order independence (B), new canonical test fallback (C), stale-row exclusion (D), complete safe scheduling and diagnostics for invalid/missing inputs (E), union/intersection/exactly-once invariants on fixture and real canonical inventory (F), preserved two-job workflow topology/name (G), and fail-closed shard execution plus aggregate failure/cancellation/absence (H). The tests use isolated temporary repositories/runtimes, avoid production/network state, and retain canonical inventory and semantic closure unchanged.

### Required changes

None. Gate 3 may advance to minimal implementation against corrected test SHA-256 `a563197f822cb47df833a42a9c1b3068f4e1ee934a4555dbcf878a77e94ac0b2`.
