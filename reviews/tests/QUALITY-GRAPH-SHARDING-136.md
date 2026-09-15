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

---

## Gate 3 restart — missing per-test fallback diagnostic

- Reviewer: independent Codex reviewer `/root/issue136_gate3`; authored neither the contract nor the revised test
- Restart reason: final review found that a canonical test missing its individual historical weight could use fallback without emitting the required diagnostic; root added the acceptance assertion and reconstructed a no-implementation RED source
- Reviewed source: commit `f8f20fe7a3d47dab7fa13c621d0d1018f22eeee7`; candidate source `69f68c19da603e5ac575b9d0cd15b90c44bb2b43e35febea02617469791c2363`; executable source `14990ce9f1bd3f3adbe6787143b4bc76f05357cc83d0dd9dc1c64f93797c1edf`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T214016Z-fb6d4c0b1e/snapshot/source.patch`, SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` (empty patch over the reviewed commit)
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T214016Z-fb6d4c0b1e/package.json`; plan SHA-256 `f497c2aa411562626aecda21ee396177e2da0adc4b4e1883e88cd1daeb036886`
- Revised test: `tests/Verification/verification_ci_001_test.py`, SHA-256 `40644bfdd8d8ecc7e5123f864e87a6ae40ebb98c035ae370395d81440d68f4c2`
- Specification: `specs/QUALITY-GRAPH-SHARDING-136.md`, SHA-256 `09ddd232630ba6bc7629c8a1b82d6593f34302021111440722942202195c41ca`; normative contract unchanged, with the required plain-language preface added
- Revised RED evidence: `python3 tests/Verification/verification_ci_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789508360217332000-8897a985060041e7afc07fb4ea909895.json`; exit `1`, `INTENDED_RED`. Seventeen unaffected methods remain GREEN. The four non-green methods are attributable to absent pre-implementation behavior: old round-robin cannot improve the skew (`24.0 == 24.0`), the no-implementation module lacks the LPT seams, invalid/missing artifacts emit no fallback marker, and the newly covered per-test missing weight emits neither `INTEGRATION_TIMING_FALLBACK` nor `missing weight`. No runtime, fixture or environment setup failed.
- Preservation evidence: canonical inventory record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789508378729615000-d5eb729fa60a47d697aa0bc78960beac.json` is GREEN (22 tests); #153A semantic-closure record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789508400787784000-e6cec02de44a456b8e81d18ef8b2fea0.json` is GREEN (11 tests)
- Verdict: `APPROVED`

### Findings and disposition

None. The added assertions at `tests/Verification/verification_ci_001_test.py:261-265` close the exact gap: a hints file may be readable and syntactically valid yet omit every new canonical path, and each shard invocation must now expose both the stable fallback marker and the specific `missing weight` reason while still returning success. The same fixture continues to prove the new tests are scheduled exactly once, stale history does not restore a removed path, and union/intersection invariants hold. All previously approved A-H algorithm, order-independence, invalid-input, workflow-topology and fail-closed aggregate coverage remains unchanged.

### Required changes

None. Gate 3 is re-approved for the minimal implementation correction against revised test SHA-256 `40644bfdd8d8ecc7e5123f864e87a6ae40ebb98c035ae370395d81440d68f4c2`.

---

## Gate 3 continuity after rebase onto current main — APPROVED

- Reviewer: independent Codex reviewer `/root/issue136_gate3`; authored neither the contract, tests nor implementation
- Rebase under review: current main `82b8b2cc6ee1b3eace47e96556a65252f2cbf3cb` to candidate `06bcb7b462c3fc2673cc3533ce4001a5b428f488`
- Reconstructible exact-source package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T222442Z-23a884d124/package.json`; candidate source `dc6df8f8aeba7a18c049e06f3556d35096d4736de9cb2f2667e7fa69dc08c22a`; executable source `6c9305f6235ff3bb6364a361eb2b812d0f84eabc96ccd6e56b23e7d938e692d4`; plan SHA-256 `2d8f89bbf6b7ed6e3f32928a248019ecc07d533b17ec423861bda794447e08d7`; snapshot patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` (empty patch over commit `06bcb7b4`)
- Test continuity: `tests/Verification/verification_ci_001_test.py` SHA-256 `40644bfdd8d8ecc7e5123f864e87a6ae40ebb98c035ae370395d81440d68f4c2`, byte-identical to the test approved in the preceding Gate 3 restart
- Specification continuity: `specs/QUALITY-GRAPH-SHARDING-136.md` SHA-256 `09ddd232630ba6bc7629c8a1b82d6593f34302021111440722942202195c41ca`, byte-identical to the specification approved in the preceding Gate 3 restart
- Exact-source focused evidence: semantic closure `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789511338459527000-0c59b901664b40aeb1a646a1171f6f3b.json` GREEN (11 tests); verification CI `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789511348223388000-fddb2ccd26d041948bebe7a66dbae5a7.json` GREEN (21 tests); canonical inventory `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789511367789267000-60bd8c3c8e2a4378b648c8e06a6ca673.json` GREEN (22 tests). All records bind candidate source `dc6df8f8…` and executable source `6c9305f6…`.
- Canonical integration inventory: 273 members after rebase
- Verdict: `APPROVED`

### Continuity decision

The prior Gate 3 approval remains applicable. Rebase changed commit identities and incorporated the newer main history, but it did not change one byte of the approved executable acceptance test or normative specification. Therefore the retained no-implementation RED evidence and its attribution remain the same reviewed contract lineage; rerunning an artificial post-implementation RED is neither necessary nor compatible with the v1 plan's correct `INTENDED_RED` expectation.

The exact rebased candidate passes all three focused witnesses. The 21-method verification contract still exercises A-H, including exact deterministic LPT allocation, reversed input, new/missing/stale/invalid hints, diagnostics, union/intersection, two workflow jobs and fail-closed aggregation. Canonical inventory and #153A closure remain GREEN, and the integration category remains 273 members. The stated current-main FULL run `35028248716` is contextual baseline evidence, not a substitute for this Gate 3 lineage decision.

### Findings

None.

### Required changes

None. Gate 3 continuity is approved for the rebased source; a new Gate 3 cycle is required only if the specification or acceptance-test bytes change.
