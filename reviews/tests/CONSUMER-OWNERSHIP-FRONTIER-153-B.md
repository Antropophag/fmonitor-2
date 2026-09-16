# Test review: CONSUMER-OWNERSHIP-FRONTIER-153-B

- Specification/test author: root delivery agent
- Implementation author: pending separate executor
- Reviewer: pending independent Gate 3 agent
- Base: `b9dfcb4d9d1cdd934f16fa4a9f4910464f1ddfc6`
- Public seam: disposable repository invoking public `change-verification.py plan/check`
- Planned lane/reviews: `CRITICAL`; `gate3`, `final`
- Root package before RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T195646Z-2ae5de824c/package.json`
- Valid RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789588652836118000-67f4ac96c5954ccfb8eb2869ba3a911c.json`; exit `1`; outcome `INTENDED_RED`
- Discarded setup-only record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789588613261817000-91bf56c04c3b486eb0b741a834780ca9.json`; it failed before behavior because fixture directories were incomplete and is not Gate 2 evidence.

## BEFORE

Unmodified Slice A planner emitted no `consumer_expansions`. For synthetic canonical migration/schema change, `migration_verifier_001_test.py`, `current_schema_recovery_001_test.py` and indirect `runtime_inventory_001_test.py` were absent from the focused selected paths; only the broad Slice A integration representative was selected. The current-assignment direct/runtime consumers were likewise absent as ownership selections. Presentation-only and Slice A preservation controls remained GREEN.

## Gate 3 decision

### Review 2026-09-16 — independent Gate 3

- Reviewer: `/root/gate3_consumer_frontier` (`gpt-5.6-sol`, low), independent of specification/test authorship and with no production implementation performed.
- Exact candidate source: `d751ba3e105ffd29d39a53a7f81d5c36ac224fdf626642f100ed9aa5f269fe10`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T195911Z-6166a835e6/package.json`; SHA-256 `7eb38a549bb2c2ebd8b888fa4732961108bd938801cfb8db89856b4d4df63d64`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T195911Z-6166a835e6/verification-plan.json`; SHA-256 `98dd703fcdcb1904352ec21f7287f150094690a02be4c7141d71a80bfbed65f0`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Retained snapshot: base `b9dfcb4d9d1cdd934f16fa4a9f4910464f1ddfc6`; patch SHA-256 `9a3d91b0263ba9b7ce3b5033d3708803e905f618e9bf03f0480c8c1b2beb4336`.
- RED evidence reviewed: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789588733762056000-2404dfd50290433d82122b48544a3e48.json`; command `python3 tests/Verification/change_verification_consumer_frontier_153_test.py`; exit `1`; `INTENDED_RED`; source `d751ba3e105ffd29d39a53a7f81d5c36ac224fdf626642f100ed9aa5f269fe10`; executable source `da57e21386174d40d088d637fd18efc55a1dd9bc01ea38b8fe7a6d7e6c98afbf`. The output shows 11 tests run, with the pre-existing Slice A, presentation-only, and plan/check controls green and 16 failures attributable to the absent Slice B behavior.

#### Findings

1. **HIGH — The successful graph fixture contradicts the normative policy shape.** The contract requires bounded capabilities to declare non-empty owner patterns (`specs/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:13`), and the malformed-policy test independently says an empty `patterns` list is invalid (`tests/Verification/change_verification_consumer_frontier_153_test.py:235-257`). However, the normal success graph gives `current-schema-recovery`, `runtime-schema-inventory`, and `assignment-runtime` empty owner patterns (`tests/Verification/change_verification_consumer_frontier_153_test.py:52-59`) and expects plans using them to succeed (`:159-182`, `:224-233`, `:259-266`). No implementation can both reject these declarations under contract item 1 and pass the success cases. **Required correction:** return to Gate 1 to state explicitly whether non-root consumer-only capabilities may have no owner patterns. Then make all success fixtures and malformed-policy cases enforce that one rule consistently and recapture RED.

2. **HIGH — Case M does not protect the promised full Slice A closure or unchanged lane/reviews.** Contract item 6 and matrix M require the integration category, the *full canonical integration closure*, lane, and review requirements to remain unchanged (`specs/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:18,37`). The test observes one synthetic integration representative and one semantic escalation, then runs `check` (`tests/Verification/change_verification_consumer_frontier_153_test.py:274-283`). An implementation could drop other canonical integration commands or alter `verification_lane` / `required_reviews` and still pass. **Required correction:** add independent expected assertions for the complete synthetic canonical integration closure and for unchanged lane/review outputs (or narrow the normative promise if those fields are not part of this seam), then recapture RED.

3. **MEDIUM — Case L only covers absence of expansion/escalation, not preserved FAST behavior.** Matrix L requires existing FAST/presentation behavior to be preserved (`specs/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:36`), but the test only asserts empty `consumer_expansions` and `semantic_escalations` (`tests/Verification/change_verification_consumer_frontier_153_test.py:268-272`). It would not catch loss of the existing presentation command selection, FAST lane, or review selection. **Required correction:** assert the independently expected presentation command(s), `verification_lane`, and `required_reviews` for this fixture, then recapture RED.

4. **MEDIUM — The required multiple-path traversal behavior is untested.** Contract item 3 explicitly requires cycles **and multiple paths** to terminate with a canonical deterministic result (`specs/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:15`). Case J exercises a cycle and declaration reordering only (`tests/Verification/change_verification_consumer_frontier_153_test.py:224-233`). It does not constrain whether a diamond graph preserves each distinct causal chain, selects one canonical chain, or accidentally duplicates execution/evidence. **Required correction:** specify the observable canonical result for a verifier reachable by multiple paths and add a diamond/multiple-path assertion covering causal evidence and command deduplication.

#### Verdict

`CHANGES_REQUESTED`

The RED is genuinely sensitive to the currently missing Slice B mechanism, uses the public planner seam, is deterministic and isolated, and covers most A–M rejection/expansion behavior. Gate 3 nevertheless cannot approve an internally contradictory policy contract or the missing preservation/multiple-path observations above. Return to Gates 1–2, correct the complete candidate, capture fresh exact-source intended RED evidence, and obtain a new independent Gate 3 review before implementation.

### Rereview 2026-09-16 — corrected Gate 1/2 candidate

- Reviewer: `/root/gate3_consumer_frontier` (`gpt-5.6-sol`, low), still independent of specification/test authorship; no production implementation or specification/test modification performed by the reviewer.
- Exact corrected candidate source: `124d0dc092819047d234a66a8537b4fac60fce43c4902ee9b790c2e85f0a873c`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T200232Z-b86d0b077b/package.json`; SHA-256 `426b5ab02761b0f5cce28f14a619b9b744390290d096ed3471850ec34fae8fc5`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T200232Z-b86d0b077b/verification-plan.json`; SHA-256 `697a3886f9b823cdc6e3786205096352aa4f3b37a3e44aaa50adb4ee8cb0fa8a`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Retained snapshot: base `b9dfcb4d9d1cdd934f16fa4a9f4910464f1ddfc6`; patch SHA-256 `60c1ec4b8a9882256db319372fa324b6882d741ce440922822dadff708e24524`.
- Corrected RED evidence: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789588942253405000-1c88eb22f23d44be8b0c8660776146bf.json`; command `python3 tests/Verification/change_verification_consumer_frontier_153_test.py`; exit `1`; `INTENDED_RED`; candidate source `124d0dc092819047d234a66a8537b4fac60fce43c4902ee9b790c2e85f0a873c`; executable source `62782451ff06a8e11a3046e03ff7ef5779e197bed272898c35ec98265145b75e`. It ran 12 tests with 17 expected failures attributable to absent Slice B behavior; BEFORE, presentation-only FAST, and Slice A closure/plan-check controls remained green.

#### Prior-finding disposition

1. **Resolved — owner-pattern contract and fixtures.** Contract item 1 now distinguishes root-capable entries, which require non-empty unique patterns, from consumer-only entries, which may use an empty list (`specs/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:13`). The successful downstream fixtures consistently use the permitted consumer-only form, while malformed metadata now tests duplicate owner patterns rather than rejecting the permitted empty form (`tests/Verification/change_verification_consumer_frontier_153_test.py:54-62,256-279`).
2. **Resolved — complete Slice A closure and lane/review preservation.** Case M now installs two canonical integration inventory entries and asserts the full sorted closure, exact `added_checks`, `STANDARD`, `gate3` plus `final`, and successful public `check` (`tests/Verification/change_verification_consumer_frontier_153_test.py:39-52,299-314`).
3. **Resolved — presentation FAST preservation.** Case L now asserts `FAST`, final-only review selection, and the independently expected presentation/unit command in addition to absence of consumer and semantic expansions (`tests/Verification/change_verification_consumer_frontier_153_test.py:290-297`).
4. **Resolved — multiple-path semantics and sensitivity.** Contract item 3 now defines one evidence item per unique simple causal chain in canonical order with one execution (`specs/CONSUMER-OWNERSHIP-FRONTIER-153-B.md:15`). The new diamond case declares branches in non-canonical order, asserts both canonically ordered chains, and asserts one runtime-verifier execution (`tests/Verification/change_verification_consumer_frontier_153_test.py:237-254`).

#### Remaining findings

None. The full corrected candidate is traceable to matrix A–M, exercises the public planner seam rather than internals, derives exact outputs and stable diagnostics from the contract, covers affirmative traversal and fail-closed cases, preserves conservative/FAST behavior, and remains deterministic and isolated from production systems. The refreshed failure output demonstrates sensitivity to the missing mechanism rather than fixture/setup failure.

#### Rereview verdict

`APPROVED`

Gate 3 passes for exact corrected source `124d0dc092819047d234a66a8537b4fac60fce43c4902ee9b790c2e85f0a873c`. Gate 4 may proceed using this independently reviewed specification/test snapshot. Any subsequent specification or test change requires recomputed planning and renewed Gate 3 review when required by that plan.

### Gate 3 restart 2026-09-16 — BEFORE harness correction

- Reviewer: `/root/gate3_consumer_frontier` (`gpt-5.6-sol`, low), independent of the root-authored correction and production implementation.
- Reviewed detached pre-implementation source: `0a3bf1bb1b410a10850747e6805c705830995489bb72e8edb1e2f02edeaa1d77` in `/private/tmp/fmonitor-153b-g3-correction`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T200914Z-ca017bc6fa/package.json`; SHA-256 `509632a387a6f6c34b96d002b6c88b183b1afd1437da2b1d25e7e1d31c593f11`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T200914Z-ca017bc6fa/verification-plan.json`; SHA-256 `370030cba5d1ba3bbf2c5ccf19e14cac240256c3ff933f3607e909baecd0f118`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Retained snapshot: base `e83e6f749363a3b019923eb1a40ddee30e863bbf`; patch SHA-256 `31cd21ba2570dce9f62fa0950af0f3931c43eede3affea9d8d6ff9402dde3584`.
- Corrected RED evidence: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789589342823823000-71bbefb1654b483bb8b243f214a3f9a1.json`; command `python3 tests/Verification/change_verification_consumer_frontier_153_test.py`; exit `1`; `INTENDED_RED`; source `0a3bf1bb1b410a10850747e6805c705830995489bb72e8edb1e2f02edeaa1d77`; executable source `eb2c899185490ac9573e7a7e3198ead4c3d22fee616b17c54dc889ac15c064f7`. The main candidate test is byte-identical to this reviewed test. Output remains 12 tests / 17 intended behavior failures with BEFORE and both preservation controls green.

#### Restart finding

1. **HIGH — The BEFORE oracle is mutable and depends on an outer-worktree remote-tracking ref.** `use_slice_a_planner()` shells out in `ROOT` to `git show origin/main:tools/delivery/change-verification.py` (`tests/Verification/change_verification_consumer_frontier_153_test.py:120-128`). This fixes the immediate contradiction while `origin/main` happens to be Slice A, and the conditional commit correctly avoids a no-op failure. It does not provide a stable test oracle: `origin/main` may be absent in an exported/shallow test environment, may differ according to fetch state, and after Slice B merges it can resolve to the Slice B planner itself. In that last ordinary lifecycle state, BEFORE would no longer exercise unmodified Slice A and could fail because it emits the very expansions the test requires to be absent. **Required correction:** materialize the frozen Slice A planner without a mutable remote ref—prefer a candidate-contained fixture or other repository-owned stable oracle; if project policy deliberately accepts historical Git-object coupling, use and validate the exact Slice A base identity rather than `origin/main`. Preserve the conditional no-op commit, verify the test remains byte-identical on the implementation candidate, and capture fresh exact-source RED.

#### Sensitivity assessment

Apart from that environment/lifecycle dependency, the delta does not weaken the approved assertions. Only the BEFORE case swaps the planner executable; all A–M behavior tests still exercise the candidate planner, and the retained output demonstrates the same missing-feature failures and green preservation controls. The no-op commit guard is safe: it updates the fixture base only when materialization changes the staged planner.

#### Restart verdict

`CHANGES_REQUESTED`

Gate 3 restart does not approve a long-lived regression test whose BEFORE oracle changes with local fetch state and eventually with the feature under test. Correct the oracle source, recapture intended RED, and request another independent restart review before continuing implementation evidence.

### Gate 3 restart rereview 2026-09-16 — immutable BEFORE oracle

- Reviewer: `/root/gate3_consumer_frontier` (`gpt-5.6-sol`, low), independent of test/specification and production authorship.
- Reviewed detached source: `344ed17e78ce72dac20895d377518c0cfdd398af139222446359832c15417c33` in `/private/tmp/fmonitor-153b-g3-correction`; the main candidate contains a byte-identical test.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T201109Z-433a64f703/package.json`; SHA-256 `7ce7806f481b70bc1fc85828089f5efc433e17514f13b1b85b8bf9d8c181f66d`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T201109Z-433a64f703/verification-plan.json`; SHA-256 `e3980c945dbf2aa6588210cd1bf0777580e8182ce7aea991316f1ef0bdeeff75`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Retained snapshot: base `e83e6f749363a3b019923eb1a40ddee30e863bbf`; patch SHA-256 `8acb1639f8b8485ddfb5202bd6d57787062c095be6e22782e02f25e872aa9ef1`.
- Fresh RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789589458957136000-681d72bf153a4ac18c216999b6b4d57e.json`; command `python3 tests/Verification/change_verification_consumer_frontier_153_test.py`; exit `1`; `INTENDED_RED`; source `344ed17e78ce72dac20895d377518c0cfdd398af139222446359832c15417c33`; executable source `784ba581a8ddc43c216b477040dfe590966413a24e7a02026565a33bf4216cf7`.

#### Prior-finding disposition and sensitivity

**Resolved.** `use_slice_a_planner()` now reads `tools/delivery/change-verification.py` from immutable Slice A commit `b9dfcb4d9d1cdd934f16fa4a9f4910464f1ddfc6` and verifies the materialized bytes against SHA-256 `788eb80bf11afbd79ed38129555227deaacda7aa1b160cb539c29324bce1395c` before using them (`tests/Verification/change_verification_consumer_frontier_153_test.py:12-13,123-133`). Direct inspection confirmed that exact Git object exists and hashes to the asserted value. Unlike `origin/main`, neither identity can move after merge. The Quality Graph workflow checks out full history with `fetch-depth: '0'` in its jobs (`.github/workflows/quality-graph.yml`), so the selected CI consumer retains the pinned ancestor. Absence or corruption fails explicitly during setup rather than silently substituting another planner.

The conditional commit remains correct and avoids a no-op commit while updating the disposable fixture base whenever the pinned planner changes its tracked bytes. Only BEFORE uses the pinned Slice A planner; all affirmative/rejection A–M cases continue to exercise the candidate planner. Fresh output preserves the complete sensitivity profile: 12 tests, 17 failures for missing Slice B behavior, with BEFORE, presentation FAST, and Slice A closure/plan-check controls green. No assertion or rejection case was weakened, and no network or mutable remote-tracking ref is consulted.

#### Restart rereview verdict

`APPROVED`

Gate 3 is restored for the byte-identical corrected test on the main implementation candidate. The immutable historical object is an intentional test fixture dependency supported by the selected full-history CI checkout; any later change to the pinned identity/digest or test bytes requires renewed review.
