# Gate 5 code review — VERIFICATION-DELIVERY-DEDUPLICATION-001

- Reviewer: independently tasked agent `/root/issue198_gate3`; authored neither implementation nor reviewed tests/specification.
- Review date: 2026-09-19.
- Base: `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Reviewed candidate source: `65d2ab4c60bc2f782cf85a170eebd574434f99bb50862be17a03dc19fb647b4d`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T111812Z-2598d59f79/package.json`, SHA-256 `d0c0353944bf98b926c0eba008ef83b38c4a0ca211476b9f3dbdd02e484039da`.
- Reconstructible snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T111812Z-2598d59f79/snapshot/source.patch`, SHA-256 `542c817d2887cdc6d8ce2114658563c13d5d1ef6e8f04a0f60eecba258122f00`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T111812Z-2598d59f79/verification-plan.json`, SHA-256 `b4ccaaccf8e7448c0cf4abd1ec38d693671b206c63e29fca13e8c5d91e227218`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Stable contract: `specs/VERIFICATION-DELIVERY-DEDUPLICATION-001.md`.
- Gate 3: final append-only verdict `APPROVED` for test candidate source `006e7c9972b4fe491b6f92b746f0ae6c70b916a83a61f691be254c6cb3f64132` in `reviews/tests/VERIFICATION-DELIVERY-DEDUPLICATION-001.md`.
- Focused contract evidence: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789816610825631000-5a5f23f40b0a4e0b8e1e24046836a072.json`; `python3 tests/Verification/verification_delivery_deduplication_001_test.py`; exact-source GREEN.
- Canonical browser attempt: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789816622302193000-7433c3c0f2aa44f7a6988de49c5fb342.json`; exit `255`; missing `vendor/autoload.php`; environment/setup failure and explicitly **not GREEN**.
- Exact-source GitHub CI: `UNKNOWN`; not considered GREEN or approval.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **CRITICAL — a completed PR-triggered run discovered by the real transport can never be admitted or reused.** `GithubTransport.list_runs()` converts list results with `_run()` but does not attach jobs (`tools/delivery/ci-launch.py:31-34`). `select_or_dispatch()` immediately sends a completed applicable run to `_decide_existing()` (`tools/delivery/ci_launch.py:64`), which requires a non-empty same-run `jobs` list and therefore returns `UNKNOWN` (`:32-36`). The only code that fetches jobs is `observe()` (`ci-launch.py:45-49`), and discovery never calls it. This violates A2/A3's completed-run reuse path and the issue's main goal. Hydrate the selected completed PR run through the observer before admission, keep its exact run identity, and add adapter-level CLI transport tests proving list response → exact run/jobs response → admission without dispatch.

2. **CRITICAL — the fallback dispatch path performs the external dispatch and then reports `UNKNOWN` because a successful GitHub dispatch normally has no JSON response body.** `_gh()` unconditionally calls `json.loads(result.stdout)` on every successful command (`tools/delivery/ci-launch.py:16-20`). The workflow-dispatch endpoint normally returns HTTP 204 with an empty body, so the POST at `:36-39` raises `JSONDecodeError` after the irreversible external action. `select_or_dispatch()` catches it and returns `UNKNOWN`; an operator cannot know from the result that a run was already created and may retry. This is exactly the duplicate/uncertain-dispatch hazard the slice must remove. Support no-content success explicitly, then identify the created run without assuming uniqueness. Test the actual subprocess adapter with an empty successful POST response and prove one dispatch plus captured identity.

3. **CRITICAL — dispatch identity capture is ambiguous and fails when any earlier manual run exists for the same head.** After dispatch, the transport lists all `workflow_dispatch` runs for the head and accepts an identity only when `len(runs) == 1` (`tools/delivery/ci-launch.py:40-43`). A prior manual run for the same SHA makes the response ambiguous after the new dispatch, returning `{}` and `UNKNOWN` even though a second run was just created. Conversely ordering is not validated, so relying on `runs[0]` would also be unsafe. Capture a pre-dispatch run-ID set (or a supported API identity/correlation), then accept exactly one new matching run with bounded discovery; zero/multiple new identities must remain explicit UNKNOWN without another dispatch.

4. **CRITICAL — real applicability does not verify mode and real admission does not verify required Quality Graph results.** `_run()` sets `"mode": self.args.mode` (`tools/delivery/ci-launch.py:28`) rather than deriving it from run/workflow inputs or an authoritative trigger contract. `_applicable()` therefore compares the requested mode with a value copied from the same request, so mode can never mismatch. Separately, `Admission.evaluate()` marks success when every job returned happens to be completed/successful (`:52-58`); it has no authoritative required-job set and can declare `SUCCESS` when a required job is absent. This contradicts A2/A3 and the design's requirement to reuse the existing admission/observer truth rather than create a weaker local engine. Use the repository's existing exact-source admission/observer mechanism (or an equivalent explicit required-results contract), derive/validate mode from authoritative run data, and add negative adapter tests for missing required jobs and mismatched mode.

5. **HIGH — terminal conclusions other than `failure` and `cancelled` can be converted to successful reuse.** `_decide_existing()` special-cases only those two conclusions (`tools/delivery/ci_launch.py:30-31`). A completed `timed_out`, `action_required`, `stale`, `skipped`, or other non-success conclusion proceeds to job admission and may return `REUSE` if the attached job list is successful. Fail closed on every completed conclusion other than `success`; preserve failure/cancelled/other terminal outcomes for triage without dispatch or GREEN.

6. **HIGH — a dispatched run still queued after two immediate observations is returned as successful `DISPATCHED` and the CLI exits zero.** After two observations, `select_or_dispatch()` returns `{"decision": "DISPATCHED"}` regardless of whether the run is still queued/in-progress (`tools/delivery/ci_launch.py:70-80`), and `main()` maps `DISPATCHED` to exit zero (`tools/delivery/ci-launch.py:75`). The default CLI observer performs no sleep between these two calls, making this likely. The contract allows a dispatch decision but requires tracking the captured run through the existing observer and does not permit an incomplete result to masquerade as completed evidence. Return a distinct pending/UNKNOWN outcome or hand the identity to a real waiting observer; only report a successful terminal CI result after required results are admitted.

## Verification and scope disposition

- A1 implementation is coherent: the wrapper is deleted, its inventory row is removed, the active SHLZ mapping points directly to the unchanged canonical browser test, and the focused governance test is GREEN.
- A4/A5 helpers and documentation conform to the reviewed contract at the tested seam; no finding was found in their bounded behavior.
- The focused browser attempt is not evidence of preserved browser GREEN because it failed before the browser seam on missing `vendor/autoload.php`. It remains an unresolved environment verification item, not an implementation regression finding by itself.
- The package contains no GREEN evidence for the planner-selected `change_verification_001_test.py` obligation, applicable architecture check, canonical browser flow, or exact-source GitHub CI. Those remain `UNKNOWN` and must not be reported as complete.

## Required correction

Correct findings 1–6 and add tests at the actual `GithubTransport`/CLI boundary, not only the injected selector seam. Preserve the approved Gate 3 expectations. Re-run bounded focused obligations; obtain a real canonical-browser GREEN in a prepared environment; regenerate the exact-source package and independent final review. The eventual PR must then use the corrected launcher and one exact-source Quality Graph run; CI remains `UNKNOWN` until that external evidence exists.

---

## Correction rereview — 2026-09-19

- Corrected candidate source: `291faa1153a168980fcb44ec5f2e6d11765c26e6dcc4a0f0a476b3742ff29790` over base `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T112606Z-0f00c199e4/package.json`, SHA-256 `ec775d1d4808cb1b01a357cbc1a47e7d8c8d1a1acbe2781e3777f311b0bede91`.
- Delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T112606Z-0f00c199e4/delta.patch`, SHA-256 `dfbe2e1fbf47ee1284b3a1afc9e96b29e8fafbf4dce73f4f7d95bf8fa986b25`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T112606Z-0f00c199e4/snapshot/source.patch`, SHA-256 `b213500912d1af3173ab3f33802cd7beaf581c393d18d7cf8fbb83767f261f44`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T112606Z-0f00c199e4/verification-plan.json`, SHA-256 `1553d6da74c56ab9de7706cbeadb804d24448751d7e5148748dc7e4c0afdca24`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Root contract evidence: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789817150429684000-1a3663cf4f3046ea93cb987ced5c903b.json`; exact-source GREEN.
- Executor transport test: `python3 tools/delivery/ci_launch_transport_198_test.py`; four cases GREEN in reviewer reproduction, but the command is not a selected planner obligation or registered inventory consumer.
- Canonical browser: vendor-enabled attempt reached the explicit expected object-card `INTENDED_RED`; it is **not GREEN**.
- Exact-source GitHub CI: `UNKNOWN`.
- Correction verdict: `CHANGES_REQUESTED`.

### Prior findings disposition

1. **Resolved.** A discovered completed PR run is hydrated through `observe(run_id)`, its ID and applicability are revalidated, same-run jobs are attached, and authoritative required-job admission runs without dispatch. The adapter regression covers this path.

2. **Resolved.** `_gh(..., allow_empty=True)` accepts the normal empty successful dispatch response, and the adapter test proves a 204-like empty result does not become a JSON parsing failure.

3. **Resolved.** Dispatch now snapshots old manual run IDs and boundedly accepts exactly one new ID; zero or multiple new runs fail closed. The test includes old run `55` and new run `900` and proves one POST.

4. **Resolved.** PR mode is computed through repository policy (`tools/verification/ci.py plan`) rather than copied from the CLI request; manual dispatch is authoritatively full-only. Admission consumes `tools/delivery/admission.py::expected_jobs`, rejects missing required jobs and duplicate job names, and compares required conclusions. The adapter test covers missing `verify` and authoritative mode mismatch.

5. **Partially resolved; one blocking incomplete-response exception remains.** `_decide_existing()` now treats every completed conclusion other than `success` as `OBSERVE_FAILURE`, and the adapter test enumerates failure, cancelled, timed_out, action_required, stale, skipped and `None`. However the dispatched-run path contains a separate exception at `tools/delivery/ci_launch.py:83-96`: when `status == completed` and `conclusion is None`, it bypasses `_decide_existing()` and returns `DISPATCHED` if jobs admit successfully. A missing conclusion is an incomplete GitHub response and A2 requires `UNKNOWN`, not success. The transport test checks `None` only by calling `_decide_existing()` directly, so it does not catch this contradictory production branch. Remove the exception or require exact `conclusion == success`; update the root contract fixture to include a successful conclusion rather than weakening fail-closed production behavior.

6. **Resolved.** A dispatched run remaining queued/in-progress after the bounded observations now returns `PENDING`, and CLI exit zero requires admitted `SUCCESS`. The adapter test proves queued run `900` returns pending after one dispatch.

### New finding

1. **HIGH — the new real-transport regression is orphaned from mandatory verification.** `tools/delivery/ci_launch_transport_198_test.py` is added to planned paths but not to `tools/verification/suites.tsv`, the acceptance mapping, or the verification plan's selected commands. The package evidence contains only the root contract command. Therefore `make test`/Quality Graph will not discover this test through the repository inventory, and the exact transport regressions that caused the first Gate 5 return can recur while all mandatory checks remain GREEN. Move/register the test under the canonical verification inventory and include it in the acceptance/plan. Because this is a test mapping change after Gate 3, regenerate the plan and obtain the applicable independent test rereview before final approval.

### Code conformance and verification blockers

Aside from the incomplete-conclusion exception, the corrected implementation conforms to the reviewed A1–A5 behavior and the six original findings are otherwise addressed. This code verdict is separate from delivery readiness:

- canonical browser verification is `INTENDED_RED`, not GREEN;
- exact-source GitHub CI is `UNKNOWN`;
- the planner-selected `change_verification_001_test.py` evidence is not present in the package;
- the new transport regression is locally GREEN but not a mandatory registered check.

Correct the incomplete-conclusion path and register/map the transport regression, then refresh Gate 3 as required by the changed test mapping and resubmit final review. Browser GREEN and exact-source CI GREEN remain required before PR-ready; neither may be inferred from this review.

---

## Final correction rereview — 2026-09-19

- Exact source: `7a767014d2294416c7b9491662f208199eeac85b93177db0fec0dc47e3dffb05` over base `62d027d54af7a01a1da300eda2901ba68b2bab20`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T113210Z-83bd3af49c/package.json`, SHA-256 `7a701ca3e20af64a34a40c19969d3c54bef4bf001a6c426bdc05d14ad5c64861`.
- Delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T113210Z-83bd3af49c/delta.patch`, SHA-256 `c10055f5e8823679baaae9ecacf6c7ae2708c507c07fe3d8c7e57b33253087b6`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T113210Z-83bd3af49c/snapshot/source.patch`, SHA-256 `3e48e7b4c4865fba569e06b62dd8d5313707307f345782124d3f3a9cef8cb947`.
- Verification plan: SHA-256 `510c6371a19475e039cf83ab624ce0ef6a60091d6d7c65fac0d63d0ffdc01d2b`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Supplemental test-delta review: `APPROVED` above for the same exact source.
- Root contract and registered transport tests: exact-source GREEN records `1789817471740393000-18ff2679233941a69f0ab56da1a4d377` and `1789817520222370000-4ef7ebdf75a6442fa3c4786fd5de7c9f`.
- Findings: none.
- Code verdict: `APPROVED`.
- Delivery readiness: `BLOCKED` by verification evidence below; this is not PR-ready approval.

### Final dispositions

- The last partial finding is resolved: a dispatched completed observation with missing conclusion now flows through `_decide_existing()` and returns `OBSERVE_FAILURE`; it cannot be admitted as `DISPATCHED`. The root positive fixture supplies exact `success`, while the real transport test independently proves missing conclusion is rejected even with permissive admission.
- The orphan-test finding is resolved: the transport regression is registered in the canonical inventory, permitted by the exact narrow inventory boundary, selected by the refreshed verification plan and backed by exact-source GREEN evidence.
- The earlier six implementation findings remain resolved. Completed PR runs are hydrated with same-run jobs, workflow dispatch accepts empty 204 responses, created identity is selected by pre/post ID difference, mode is policy-derived, required jobs come from the repository admission contract, all non-success terminal conclusions fail closed, and nonterminal dispatch returns `PENDING` with nonzero CLI exit.
- A1, A4 and A5 remain conformant; no new scope expansion, authorization change, history mutation or weakening was found.

### Remaining delivery blockers

Code conformance approval does not establish PR readiness:

- the direct canonical browser run reached an explicit object-card `INTENDED_RED`; it is **not GREEN**;
- exact-source GitHub CI remains `UNKNOWN`;
- no GREEN evidence for any other still-applicable planner obligation may be inferred from the two focused GREEN records.

The candidate may proceed only to resolving/characterizing the browser result as required and running the single exact-source CI through the reviewed launcher. Until browser and CI evidence satisfy the delivery contract, do not report PR-ready, GREEN delivery, merge readiness or approval beyond this code verdict.

---

## Exact committed candidate rereview — 2026-09-19

- Commit: `a84890fb1c51a8ac88d2907a64c34b8842051b08` (`Deduplicate verification delivery for issue 198`).
- Exact candidate source: `8663ac889491a1fea894e4e981ab2e2db49f3aa009803d082b2871d4efc1aacc`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T125400Z-8d009fdcc7/package.json`, SHA-256 `42cbcd876f3dde06a3e91f8b32cac2c360a76a0232474478d80eb4227a1b9849`.
- Snapshot manifest: base commit `a84890fb1c51a8ac88d2907a64c34b8842051b08`; empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`; committed bytes are the reviewed bytes and the worktree was clean at review.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T125400Z-8d009fdcc7/verification-plan.json`, SHA-256 `2e2a2711bdf26861aa598c7858806d6fd7d5359bc1f1dcd7e12262586c8676b9`; lane `CRITICAL`; required reviews `gate3`, `final`.
- Root contract GREEN: record `1789822336452620000-1e3e2e0dd625457aad9dcf4705a474ff`.
- Registered transport GREEN: record `1789822337649415000-cc12eedeb4dc4f8b9bfda1e5b2399c8f`.
- Planner governance GREEN: record `1789822368777866000-ee27e516201741ae8dbc5d873f7d4cc6`, command `python3 tests/Verification/change_verification_001_test.py`.
- Canonical direct browser GREEN: record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789822402853176000-fe357efaf7c746218a38860eccf28d13.json`; command `php tests/Yii2/yii2_preopening_browser_001_test.php`; exit `0`; `PASS: YII2-PREOPENING-JOURNEY-001 browser`; source drift false.
- Findings: none.
- Exact-source code verdict: `APPROVED`.
- Delivery readiness: exact-source GitHub CI remains `UNKNOWN`; this review alone is not PR-ready or merge-ready approval.

### Final assessment

The committed implementation, stable specification, root acceptance contract, registered real-transport regression, supplemental Gate 3 approval and prior Gate 5 dispositions are coherent with issue #198. The canonical browser bytes remain unchanged from base while the redundant executable wrapper and every active direct mapping are removed. The launcher reuses only an exact applicable PR run, hydrates completed runs before authoritative required-job admission, performs at most one fallback dispatch, correlates the new run by pre/post identities, preserves one-run job ownership, and fails closed for mode, binding, incomplete response, non-success terminal conclusion and transport uncertainty. Correction-package and cosmetic-delta behavior remain bounded to A4/A5.

All planner-selected local obligations and the canonical browser flow are GREEN on the exact committed candidate. No implementation, test, specification, review-history or issue-acceptance defect remains in the reviewed bytes.

The remaining step is external verification: run or reuse one exact-source Quality Graph through the reviewed launcher and record its admitted result. Until that CI is confirmed GREEN, report the state as code-review approved with local verification GREEN and CI `UNKNOWN`, not PR-ready, merge-ready or fully delivered.
