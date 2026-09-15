# Test review: ARCHITECTURE-FILE-SIZE-ADVISORY-001

- Reviewer: Codex independent reviewer `/root/issue116_gate3` (gpt-5.6-sol / low)
- Test/spec author: root agent; reviewer authored neither artifact
- Reviewed source: base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T185705Z-1d2d5166bb/snapshot`, patch SHA-256 `4b23bb76dc3de13c33d73aba46fb30cf4e151122472904f61264905872488cee`
- Candidate/executable source: `d0a7ad767fb056cb7fdd4f191f668c8c5cf359828a4f0015cbe9c89dd1edfd36` / `cc7e0f031df4f7cfeedb38e18e8d8f89a21eeccf06320e319ddd28c81df30650`
- Specification: `specs/ARCHITECTURE-FILE-SIZE-ADVISORY-001.md`
- Public seam: `tools/architecture/check`, its human and `--json` output, process exit, and the proposed size-only baseline-update CLI
- RED command: `python3 tests/Verification/architecture_file_size_advisory_001_test.py`; retained record `1789498613820204000-c401d843085f43ed8f72351e701ae5cd` is exact-source `INTENDED_RED`. It exits 1 because current size findings are blocking, JSON has no `advisories`, and `--write-size-baseline` does not exist; the DDL and dependency blocking controls already pass.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — baseline safety is not tested against a current unrelated violation** (`specs/ARCHITECTURE-FILE-SIZE-ADVISORY-001.md:15`, `tests/Verification/architecture_file_size_advisory_001_test.py:95-106`). The test seeds meaningful exceptions in the old baseline but does not put matching or new SQL, DDL, dependency, public-seam, session/workforce, or rapid-boundary violations in the scanned fixture. An implementation that writes all current findings into every baseline section would produce the asserted values in this fixture and pass, yet would automatically accept a new unrelated architecture exception in the real scenario the contract forbids. Add at least one current, non-baselined meaningful violation while invoking the size-only update, and assert that its non-size section remains byte/value-equivalent to the prior baseline (and that only `hotspots` changes).

2. **MEDIUM — human-readable advisories are tested only on PASS, not on FAIL** (`specs/ARCHITECTURE-FILE-SIZE-ADVISORY-001.md:13`, `tests/Verification/architecture_file_size_advisory_001_test.py:85-93`). The normative contract explicitly requires advisories to be shown in both outcomes. A checker that suppresses advisory output whenever blocking errors exist would pass the test suite. Add a human-mode mixed fixture and assert non-zero exit, the blocking-rule failure, and visible size advisory.

3. **MEDIUM — the large-file DDL and dependency fixtures do not assert the required size advisory** (`specs/ARCHITECTURE-FILE-SIZE-ADVISORY-001.md:11-14,19`, `tests/Verification/architecture_file_size_advisory_001_test.py:70-83`). Both tests only inspect the blocking error. An implementation could classify size observations as advisories only for otherwise-clean files, or drop them for DDL/dependency failures, and pass. Assert an advisory in each mixed blocking-rule case, as already done for SQL.

The remaining covered paths are sound: fixtures execute a copied production checker through its public CLI in isolated temporary repositories; the 150-line expected value is independently constructed; new, grown, moved and comment-only threshold cases distinguish exit/`ok`/`errors` from `advisories`; SQL mixed JSON exercises the two arrays and errors-driven exit; RED output is attributable to missing requested behavior rather than fixture setup. The existing registered architecture guard remains available to preserve broader negative-rule regression coverage in the canonical CI matrix, but it does not fill the three observable gaps above.

## Required changes

1. Exercise `--write-size-baseline` with a current new meaningful violation and prove that only size metadata changes.
2. Exercise human output for a mixed blocking-error plus size-advisory result.
3. Require size advisories alongside the DDL/runtime-migration and dependency blocking errors.

## Correction rereview — 2026-09-15

- Reviewed source: base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T185922Z-908745e923/snapshot`, patch SHA-256 `0a4470ee3f5c15f4340428c3452ab6242a542d6306452ded7fff13fd098a43d5`
- Candidate/executable source: `edd8c156cdf59143613ddba484fb553d95bcfbb10307bec51807df671f5330e1` / `300c6435171d2981e3e7698d0fd9988d76c5a498d90d0fb12ea2e09d693f4342`
- RED evidence: `python3 tests/Verification/architecture_file_size_advisory_001_test.py`, retained record `1789498749998832000-f0254632e519465c8f4f18e210caf928`, exact-source `INTENDED_RED`
- Prior findings disposition: findings 2 and 3 are resolved. Finding 1's missing current violation is resolved, but its corrected assertion has an independently incorrect expected size and remains blocking.
- Verdict: `CHANGES_REQUESTED`

### Complete findings

1. **HIGH — the corrected baseline-safety test expects the wrong physical line count** (`tests/Verification/architecture_file_size_advisory_001_test.py:109-122`). `large(extra)` constructs one `<?php` line, 149 comment lines, and one extra SQL line: 151 lines total. The test nevertheless requires `updated["hotspots"] == {"app/Large.php": 150}`. A correct size-only updater that records the checker's actual metadata will therefore fail after the intended CLI is implemented. Change the independently expected hotspot count to 151 (or construct an exactly 150-line fixture while retaining a live, non-baselined meaningful violation).

The new mixed human-mode test now checks non-zero exit, the SQL ownership failure, and visible advisory output. The DDL/runtime-migration and dependency JSON cases now require advisories alongside their blocking errors. The baseline fixture now includes a live new SQL violation and still requires every non-size section to equal the preexisting baseline, which is sensitive to unsafe whole-baseline replacement once the line-count expectation is corrected. The new RED remains attributable to absent advisory behavior and absent size-only CLI; however, the hidden 150/151 mismatch would prevent valid GREEN.

### Required changes

1. Correct the baseline fixture's hotspot line-count expectation (or its construction) so expected metadata equals the physical file size.

## Final correction rereview — 2026-09-15

- Reviewed source: base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T190038Z-c9ccee0246/snapshot`, patch SHA-256 `908798e44d1eb45a992adeecfc38e604b8d3ffbd0d6b2e6daf2491c5da713e65`
- Candidate/executable source: `d3120f4a14504d4c4f6bf16b390797e0e4e49ed2fc75cc4d2537d5f2cd0a2c55` / `3d328a0eb88f895facfa73eb7da0a452f2bba920432e40a230554e0fd7d84c53`
- RED evidence: `python3 tests/Verification/architecture_file_size_advisory_001_test.py`, retained record `1789498826925545000-3cfde736f8984dfe909f6fefa0630985`, exact-source `INTENDED_RED`
- Prior findings disposition: all resolved
- Verdict: `APPROVED`

### Complete findings

None.

The baseline-safety fixture now independently expects 151 physical lines, matching one PHP opener, 149 comment lines and one live forbidden-SQL line. It proves that the proposed size-only update changes `hotspots` while preserving every non-size baseline section despite a current non-baselined meaningful violation. Mixed human output proves advisories remain visible on blocking failure, and the SQL, DDL/runtime-migration and dependency JSON fixtures each require blocking errors together with size advisories. Clean size cases cover threshold crossing, hotspot growth, new files and rename/move with errors-driven exit semantics. The fixture invokes the delivered public checker, is isolated and deterministic, and the retained failures are attributable to the absent advisory/result/update behavior rather than setup or an incorrect expected value.

### Required changes

None.

## Post-Gate-5 test-delta rereview — 2026-09-15

- Reviewed source: base `3c4dd015269d2ca1c20df3285077b136b6492b08` plus retained root snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T191651Z-4911816500/snapshot`, patch SHA-256 `0ec281c09af51aa742eb8c966ba3d99b80ded3d02afac1db9a74e395697b36eb`
- Package candidate/executable source: `7a638e3ec1f76463ce41f73cd934b1a57713ce754e1d420147f8af1a57340068` / `faa2dd98294a28fd486b1260cca15b8607de8c2d7f319b772256650aebfd7079`
- Corrected test SHA-256: `c48090d7ae0817c23bd312264ef9b7a03b9f26e76be9021437a79323f7c1e1cc`
- GREEN evidence: focused advisory contract record `1789499719658184000-7fe1427e207344b4849e40ed53e5c0cf` (10 tests) and existing architecture fixture record `1789499733580776000-26a34261da8140d0867b6e73f50db42b` (59 tests), both GREEN on candidate/executable source `5abc9c191a90735436390e0a55fe4b84880438933b803e52ef2ed426bb82f989` / `2f74986a9e863fee5a111c628c24ed2b8454e183d6690ea033357827c222d364`. The focused record's command blob is the corrected test SHA-256 above; the later root package includes the resulting review metadata.
- Delta under rereview: the human mixed-result assertion now requires the preexisting lowercase `sql_ownership:` prefix; implementation output retains that prefix while adding advisories.
- Prior findings disposition: all remain resolved
- Verdict: `APPROVED`

### Complete findings

None.

The one-line assertion is independently derived from the established human-readable checker output and is more regression-sensitive than the prior uppercase expectation: it prevents an advisory implementation from accidentally changing the meaningful error prefix consumed by humans or text consumers. It still executes the public CLI and jointly requires exit 1, the blocking SQL reason and visible advisory output. The focused suite passes all ten advisory/baseline scenarios, and the unchanged architecture fixture suite passes all 59 meaningful-rule cases, including prior negative fixtures. No expected behavior was weakened and no implementation or test changes were made by this reviewer.

### Required changes

None.
