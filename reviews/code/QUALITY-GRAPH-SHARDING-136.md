# Code review: QUALITY-GRAPH-SHARDING-136

- Reviewer: independent Codex reviewer `/root/issue136_final_review`; authored neither the contract, tests nor implementation
- Reviewed source: commit `e7487c295b278b582b968ebbbbe0da763ba962ef`; candidate source `b7c8f409038dfc38a76a8a07fc72b643d92e2d808eb3d5397c11f99f3bbabbc4`; executable source `b5cdc8d56979d2755ff763be7f5a5784ae49d65aaa5fbbcc1ab1fd91963aad78`
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T213207Z-0218d3b57d/snapshot`; empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855` over the reviewed commit
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T213207Z-0218d3b57d/package.json`; plan SHA-256 `6201a7e2d708c5bbebd5f676f44e8f92b044468496fbb4ac17c44868086d9f6e`
- Fixed point: `0ff3210565efb086712b2d72ff77525f37c0cddf`
- Specification: `specs/QUALITY-GRAPH-SHARDING-136.md`, SHA-256 `a27835a01b37ad1c3b6161ed305a9b84de95cb319418fa6eb3e554c81f9aa546`
- Prior required review: corrected-source Gate 3 in `reviews/tests/QUALITY-GRAPH-SHARDING-136.md`, verdict `APPROVED`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — unrelated #157 scope is committed in the #136 candidate.** The fixed-point diff replaces `docs/operations/current-delivery-goal.md:1-11` with the compact task-context-manifest goal and even states that CI performance #136 is out of that goal. This conflicts with the owner's “Не расширять задачу” boundary and the #136 proposal's bounded impact. Remove the unrelated pointer change from the #136 candidate, or rebase/cherry-pick #136 onto a base where that independent change is already present and therefore absent from this PR diff. Rebuild the exact-source plan/package afterward.

2. **Medium — the executable specification omits the mandatory opening summary.** `specs/QUALITY-GRAPH-SHARDING-136.md:1-7` starts directly with `Scope`, while `docs/development-process.md` requires every executable specification to start with a short non-normative `Простыми словами` section describing what changes, why, and what is excluded. Add that section without changing the normative contract; because the contract file changes, refresh the bound plan/package and obtain the review required by the recomputed plan.

3. **Medium — a missing per-test timing hint falls back silently.** `tools/verification/ci.py:141` gives an absent canonical path weight `1.0`, but emits `INTEGRATION_TIMING_FALLBACK` only for an unreadable artifact or malformed/duplicate/invalid rows. The OpenSpec requirement at `openspec/changes/balance-integration-shards/specs/deterministic-integration-sharding/spec.md:27-31` requires a missing hint to produce documented fallback **and diagnostics**. Emit deterministic diagnostics for canonical paths absent from an otherwise valid hints file, and extend the new-test-without-history executable case to assert them. This is a test change and therefore requires Gate 2/3 recomputation and rereview.

4. **Medium — required delivery authorship is not recorded.** `AGENTS.md` and `docs/development-process.md` require actual authors and authorization to be recorded. `docs/operations/issue-136-quality-graph-performance.md` records measurements and outcome but not the root spec/test author, named implementation executor, or whether autonomous authorship was authorized. Add the actual authorship/authorization record.

5. **Completion note — exact-source after-CI evidence is still pending.** `docs/operations/issue-136-quality-graph-performance.md:32-34` is explicitly a placeholder and OpenSpec tasks 4.3/4.4 remain unchecked. This is consistent with running exact-source FULL CI after Gate 5, but the current snapshot is not yet PR-ready and must not claim measured CI improvement. After corrections and approval, run the single authorized exact-source FULL CI and record actual job timings with the stated runner-variance caveat.

## Conforming behavior reviewed

- `suites.tsv` remains the only membership source. The timing artifact has exactly 273 unique, finite, positive rows for the 273 canonical integration paths; stale rows cannot add membership.
- The implementation is the specified two-bin LPT: sort by `(-weight, path)`, choose the lower accumulated load, and choose shard index 1 on a load tie. Output is stable and input order cannot change allocation.
- The published estimates reproduce from the reviewed source: old round-robin `696.077s / 541.920s`, LPT `619.005s / 618.992s`, predicted critical-shard reduction `77.072s` (11.1%). The report correctly labels this as an estimate.
- Tests cover skew, exact LPT/ties, shuffled input, new/stale/invalid timing behavior, union/intersection/exactly-once invariants, two existing workflow job names, and fail-closed shard failure/cancellation/absence. No product, workflow topology, FAST policy, category membership, MariaDB/browser/runtime acceptance, rapid-pilot, or #153 closure code was changed.
- The setup investigation remains limited to `quality_graph_ci_setup_001_test.php`, reports three same-profile before runs, changes no setup code, and honestly records `NO_SAFE_SETUP_OPTIMIZATION_FOUND`. Token/cost remains `UNKNOWN`.

## Verification evidence inspected

- `python3 tests/Verification/verification_ci_001_test.py` — GREEN, retained record `1789507869813750000-685c09c35c034cb1ae92853099e28100.json`
- `python3 tests/Verification/verification_inventory_001_test.py` — GREEN, retained record `1789507890355336000-dc2ac224b2d54d3788c4b5909ef73a0b.json`
- `python3 tests/Verification/change_verification_semantic_closure_153_test.py` — GREEN, retained record `1789507912504789000-68258bd9aea64cc2b44c26a102134672.json`

No local full suite was run. After findings 1-4 are corrected, prepare a new exact-source reviewer package and request independent Gate 5 rereview before CI.
