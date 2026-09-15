# Gate 5 review — TASK-CONTEXT-MANIFEST-001

- Reviewer: independent agent `/root/final_review` (gpt-5.6-sol/low)
- Scope: issue #157, T02 of #145 only
- Base: `0ff3210565efb086712b2d72ff77525f37c0cddf`
- Reviewed commit: `82e479aae21a95e30b3e1462cba6b4ef04729f50`
- Candidate source: `a5e6340f0d8d68b504e5500615505719e8bf143f41c523b11d581016b9df771e`
- Executable source: `c32144dbb96e8cc8acb87292d1030624be1f80b5044a77fd1534bdd157c194ce`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T214439Z-bcbc824ce3/package.json`
- Package manifest SHA-256: `226f40790bf4e66c304a08fee01d6a68bb53cde68842bdfa1bc1d6ce4f87e38f`
- Required-context SHA-256: `33fc47c376535cf1ab34a2863b28a1dc7e89b53317d39fa2dd5b8dde75e65434`
- Verdict: `CHANGES_REQUIRED`

## Complete findings inventory

1. **BLOCKING — the section-index freshness digest is wrong for real repository-sized packages.** In `build_task_context`, `index_bytes` initially contains the exact bytes of `tools/delivery/context-sections.json`, but the compact-history branch reassigns that same variable to each serialized historical collection (`tools/delivery/harness_context.py`, in the `len(historical) > 20` loop). The later `task.policy_digests.instruction_index` and `section_index.digest` therefore hash the final non-empty history group rather than the declared section-index source. The reviewed package demonstrates the failure: both advertised fields contain `7456ae32e0df305d09f58e41c69dd9a0e8739aa9c0408102fa1d7bda66e07401`, while SHA-256 of candidate `tools/delivery/context-sections.json` is `bd5cfc0a9232ca01a13b317a563c0e40d44f8405ff531b909904d55e5674a7d7`. This violates R1/R3 and cases E/K: a consumer cannot validate the locator/version binding against the named canonical index, and a history-tree change can falsely present as an instruction-index change. Preserve the original index bytes/digest under a distinct immutable name and use a separate variable for historical collection serialization.

2. **BLOCKING TEST GAP — the executable freshness matrix accepts the incorrect digest.** `delivery_harness_context_manifest_001_test.py` checks only that the section-index digest is 64 hexadecimal characters and that its version is 1. It never recomputes the digest from the source named by `section_index.source`; consequently all four A–L tests pass against the defective package. Add an exact digest/source assertion in the ordinary repository-sized (>20 historical entries) route, and assert `task.policy_digests.instruction_index` has the same current canonical binding. This is a test correction discovered at Gate 5, so the repository process requires plan recomputation and the applicable independent Gate 3 test-delta review before implementation resumes.

No other blocking or major finding was found in the reviewed delta. In particular, applicability remains a bounded deterministic path table rather than an LLM/NLP or second planner; unknown applicability falls back to the seven full safe canonical sources; invalid/non-unique section locators fall back source-specifically; prepare/package/state expose the real manifest and required-context route while approval remains `NOT_REVIEWED`; reviewer packages retain exact candidate/spec/evidence/review navigation; historical material is retained through reconstructible on-demand references; and the change does not modify product code, FAST classification, verification coverage, Gates, CI performance, or another #145 task.

## Evidence inspected

- Full committed delta from the stated base through the candidate commit, including specification, OpenSpec artifacts, Gate 3 history, tests, implementation, baseline and delivery record.
- Prepared `package.json`, `task-context-manifest.json`, and `required-context.json`; direct recomputation exposed finding 1.
- `python3 tests/Verification/delivery_harness_context_manifest_001_test.py`: 4 tests GREEN, but insensitive to findings 1–2.
- `python3 tests/Verification/delivery_harness_001_test.py`: observed passing through the reviewed harness cases; the prepared package also retains exact-source GREEN evidence for the selected context-manifest command.
- `python3 tests/Verification/change_verification_001_test.py`: 18 tests GREEN.
- `openspec validate compact-task-context-manifest --strict`: valid.
- `git diff --check 0ff3210565efb086712b2d72ff77525f37c0cddf..82e479aae21a95e30b3e1462cba6b4ef04729f50`: GREEN.
- Baseline and replay reporting preserve token usage as `UNKNOWN` and distinguish mandatory content from serialized manifest/artifact overhead; claims are limited to reduced mandatory bytes and expected token-pressure reduction.
- Live GitHub/CI state in the exact package was `UNKNOWN`; it was not treated as GREEN or approval.

Gate 5 does not approve this candidate. Correct both findings, refresh exact-source evidence/package, obtain the required independent test-delta decision, and return the corrected complete candidate for independent final review.

---

## Gate 5 rereview v2 — section-index freshness correction

- Reviewer: independent agent `/root/final_review` (gpt-5.6-sol/low)
- Corrected candidate commit: `7ee72d2e950a4c885629273e0b564828849d63bf`
- Candidate source: `8999fa7f31e5ce1c7f3e035a1acd21a3f69fcbbe19593b28772a7c043e9c1ba7`
- Executable source: `4685c073c909539da5b2f1e93cff946bb9cbb1211120c3cfad199468a75e68e3`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T215255Z-1f79c3387f/package.json`
- Package manifest SHA-256: `6870d8527b992985d033554e3964456eba2a6bb019f1eff1c18c0ddf7e33945e`
- Required-context SHA-256: `33fc47c376535cf1ab34a2863b28a1dc7e89b53317d39fa2dd5b8dde75e65434`
- Gate 3 correction-test approval: commit `fb278712` for exact test delta `ca4322a2`
- Verdict: `APPROVED`

### Prior findings disposition

1. **Resolved.** `build_task_context` now keeps canonical section-index bytes in `index_bytes` and serializes historical groups through a separate `collection_index_bytes` variable. Direct reconstruction against the fresh real package proves that `section_index.digest`, `task.policy_digests.instruction_index`, and SHA-256 of the declared `tools/delivery/context-sections.json` source all equal `bd5cfc0a9232ca01a13b317a563c0e40d44f8405ff531b909904d55e5674a7d7`. Compact historical collection digests remain independently bound to their collection indexes.

2. **Resolved.** The corrected executable test derives the expected digest from exact canonical index bytes, checks both manifest bindings, and forces the previously missed compact branch with 25 committed deterministic historical files. Gate 3 v6 independently approved that exact RED test delta before the three-line implementation correction.

### Complete rereview findings inventory

No new blocking, major, or minor findings.

The correction is limited to the faulty local variable and its directly sensitive regression fixture. The original full-candidate assessment remains valid: A–L applicability and fail-safe behavior are preserved; compact historical collections stay reconstructible and on demand; package/state integration remains real and admission remains `NOT_REVIEWED`; measurement continues to report content and delivered artifact overhead honestly with token usage `UNKNOWN`; and there is no product, planner, FAST, verification-coverage, Gate, CI-performance, or other #145 scope expansion.

### Rereview evidence

- `python3 tests/Verification/delivery_harness_context_manifest_001_test.py`: 4 tests GREEN on exact candidate source, including the >20-history regression; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789509154101252000-399e25b2e528452b9ffbf91e5c3093b0.json` reports exit 0 and matching start/end source.
- Direct SHA-256 reconstruction of the fresh package's declared section-index source: all three canonical/manifest/policy values match as recorded above.
- `openspec validate compact-task-context-manifest --strict`: valid.
- `git diff --check 82e479aae21a95e30b3e1462cba6b4ef04729f50..7ee72d2e950a4c885629273e0b564828849d63bf`: GREEN.
- Exact package state still reports GitHub/CI as `UNKNOWN`; this review does not claim CI GREEN, publication readiness, merge, or deployment.

Gate 5 approves candidate `7ee72d2e950a4c885629273e0b564828849d63bf` for TASK-CONTEXT-MANIFEST-001. Exact-source CI and PR preparation remain separate required delivery steps.
