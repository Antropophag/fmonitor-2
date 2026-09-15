# Test review: FAST-SERVER-RENDERED-PRESENTATION-001

- Reviewer: independently tasked agent `/root/gate3_review`; not an author of the specification, OpenSpec artifacts, test, evidence, or planned implementation.
- Test author: root agent, as required by the current goal and delivery record.
- Reviewed source: base `0ca3b2c937f307978dd8860cdca88f9d3bfad69c` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T231317Z-3763277daa/snapshot/source.patch`, SHA-256 `05fffab25ad838b0d2956e76d3319a99b48187c94a112669e31fb43ef4d9a831`; candidate source `6a293478d3e50ab2c72d5ea9918394baff2a5e05f551d75ef3f3b1c10e12d201`, executable source `c1652af0cfca750af17ddd6187c1194afd2223db4e62f5f8f4e077eee295bf51`.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T231317Z-3763277daa/package.json`; verification-plan SHA-256 `3ab562b06a5e1b69c0fc92a0e8dcede43ee3be91cb37d04ef3fef77d0eefc327`.
- Agreed review scope / prior findings disposition: first complete Gate 3 review of cases A–O, the owner constraints, normative specification, root-authored RED test, and package-referenced evidence. No prior findings.
- Specification: `specs/FAST-SERVER-RENDERED-PRESENTATION-001.md`, SHA-256 `ed163a0e8a30ec8c1da28510a9a79257310a73ca9544e6291db31cb413238dbd`.
- Public seam: `python3 tools/delivery/change-verification.py plan|check|run` and public `python3 tools/delivery/harness.py prepare`.
- Red command and intended failure: `python3 tests/Verification/change_verification_server_rendered_fast_160_test.py`; record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789513970359516000-762914823b6c40eeaef638e70c526310.json`, exit `1`, package outcome `INTENDED_RED`. Cases A–L and O encounter `SETUP_FAILURE: malformed boundary`; M/N report `ok`.
- Verification lane: `CRITICAL`; required reviews `gate3` and `final`.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — Case O does not exercise the required `prepare -> selected verification` chain.** Locations: `tests/Verification/change_verification_server_rendered_fast_160_test.py:133-141`; `specs/FAST-SERVER-RENDERED-PRESENTATION-001.md:44-46`. The test invokes `harness.py prepare`, ignores its emitted package and plan, replans separately into `plan.json`, and directly runs that unrelated plan. A defective implementation could prepare the wrong selected oracle while this test still passes. Resolve the artifact produced by public `prepare`, assert its selected public oracle, and execute focused verification from that prepared artifact through the public execution seam; prove the defective variant is rejected by that exact selected oracle.

2. **HIGH — the closed negative-boundary contract is represented as output metadata rather than behaviorally proven.** Locations: `specs/FAST-SERVER-RENDERED-PRESENTATION-001.md:32-43`; `tests/Verification/change_verification_server_rendered_fast_160_test.py:64-84,101-125`. `NEGATIVE` names twelve required categories, but CSRF/security, external integrations, and jobs/outbox/scheduler have no owned fixture boundary or fail-closed path. Persistence/write is only exercised through a migration path despite an available persistence owner. Echoing the twelve strings would satisfy the current assertion even if the classifier admitted one of these surfaces as FAST. Give every required category an owned representative boundary/path and demonstrate that mixing it with the presentation candidate cannot select FAST. Combined cases are acceptable, but each named category needs an executable witness.

3. **HIGH — “exactly one public oracle” is not tested at its upper bound.** Locations: `specs/FAST-SERVER-RENDERED-PRESENTATION-001.md:21-26`; `tests/Verification/change_verification_server_rendered_fast_160_test.py:39-49,127-131`. The suite covers one oracle and zero oracles, but not multiple eligible registered oracles. An implementation that arbitrarily chooses one of several would pass. Add a deterministic multiple-oracle fixture and require fail-closed behavior, or refine the normative contract if deduplication or selection among multiple registrations is intended.

4. **MEDIUM — Case M's captured result passes for the generic pre-existing `malformed boundary` rejection, so the RED evidence does not establish missing-oracle semantics.** Locations: `tests/Verification/change_verification_server_rendered_fast_160_test.py:127-131` and the retained stderr for record `1789513970359516000-762914823b6c40eeaef638e70c526310`. Cases A–L/O fail because `fast_class` metadata is not recognized, while the combined M/N method reports `ok`; its nonzero assertion does not distinguish missing-oracle rejection from unsupported metadata. Assert the specific missing-oracle rejection after the new metadata is recognized and retain focused exact-source RED evidence identifying the intended missing behavior.

The test uses an isolated temporary Git repository, fixed fixture contents, local subprocesses, and no production systems, so its deterministic setup foundation is sound. Expected presentation literals are fixture-owned. The test cites the stable specification and exercises the real planner module, but the findings above leave traceability and sensitivity incomplete. CI and deployment remain `UNKNOWN`, and enforcement remains unconfigured; none is treated as GREEN or approval.

## Required changes

Return to Gate 2 and correct all four findings as one complete A–O matrix. Preserve the owner-authorized scope: no product code, later T05.x class, new planner/registry/Gate, AST/LLM classifier, dependency graph, CI-performance work, merge, deploy, or settings change. Capture a fresh reconstructible exact-source package and intended-RED evidence, then obtain independent Gate 3 rereview before Gate 4 implementation.

---

## Gate 3 correction rereview — 2026-09-16

- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T231638Z-c2ed136fdc/package.json`.
- Exact corrected source: base `0ca3b2c937f307978dd8860cdca88f9d3bfad69c` plus reconstructible snapshot; candidate source `81f0ff49abe83cbcf07411629c400eb9fb1a668559b8961db989e2d3ec7c3004`, executable source `4074b8a023a8c80e40386822c1eac076813955ad0355c6bdd7e52cdaf2f92388`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T231638Z-c2ed136fdc/snapshot/source.patch`, SHA-256 `50ab2b084fbc2a97fe46c0a2e6743f1df7690e5d4f690939b81d53928685a6df`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T231638Z-c2ed136fdc/verification-plan.json`, SHA-256 `752222e7b88d08208fd385f884bf2de96bee114d24d54e3d8ca00b2e55f002e7`; lane remains `CRITICAL`, with required reviews `gate3` and `final`.
- Corrected test SHA-256: `d732b89a377b8a30f6b230f90552da8b203b80aeba44e4e857ea07587c680e0d`.
- Reviewer independence is unchanged. This rereview is limited to the four prior findings, completeness of the corrected A–O matrix, and regressions introduced by those corrections.

### Prior-finding disposition

1. The Case O chain finding is resolved. The corrected test deliberately damages the presentation before public `harness.py prepare`, parses the package emitted by that invocation, loads its prepared plan, requires that plan to select the registered presentation oracle, and runs the focused phase against that exact prepared plan. The oracle's `INTENDED_RED presentation label missing` result is required, so a lane-only or disconnected-plan implementation cannot satisfy the test.

2. The negative-boundary finding is resolved. The fixture now owns explicit security/CSRF, domain mutation, persistence writer, integration, and jobs/outbox paths in addition to the existing authorization, controller, read-model, migration, current-state, offline, product/OpenSpec, verification policy, and runtime/route witnesses. Every category named by `negative_boundaries_checked` therefore has an executable mixed-boundary case that must return its conservative lane and must not return FAST. The #153A semantic-escalation assertion remains explicit.

3. The exactly-one-oracle finding is resolved. Separate deterministic branches now cover zero and two registered public oracles, require planning failure with no plan, and require the specific `FAST presentation boundary requires exactly one registered public oracle` diagnostic.

4. The Case M evidence finding is resolved. Missing-oracle behavior no longer passes on an arbitrary nonzero result: it requires the exact oracle-cardinality rejection. In the retained pre-implementation RED, this assertion fails because the current planner still reports generic `SETUP_FAILURE: malformed boundary`; the intended new validation behavior is therefore observable and absent.

### Complete correction findings

None.

The corrected A–O matrix remains deterministic and isolated in a temporary Git repository, uses the actual planner and public harness seams, and derives expected lanes and oracle behavior from the normative contract rather than a planned implementation. The additional category commands are local deterministic fixtures registered in the canonical inventory. No owner-excluded product code, later FAST class, alternate planner/registry/Gate, semantic analyzer, dependency graph, merge, deployment, or settings behavior entered the reviewed scope.

The retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789514150547787000-6bb0775c8b3843cbb4b6f30cedc4f505.json` reports exit `1`, `source_drift=false`, and executable source `4074b8a023a8c80e40386822c1eac076813955ad0355c6bdd7e52cdaf2f92388`. Its candidate source `ee4e694f6af9a0a4fe3a4ee060d9cb5b81daa3b15453f6b28cbe879cce822ea1` predates inclusion of the prior review record in the corrected package, while the package binds the same executable source and exact corrected test bytes. This documentary-only source distinction does not alter the reviewed executable RED artifact.

CI and deployment remain `UNKNOWN`, and enforcement remains unconfigured. This Gate 3 decision does not treat them as GREEN or authorize publication, merge, deployment, or settings changes.

### Correction verdict

`APPROVED`

Gate 3 passes for corrected candidate source `81f0ff49abe83cbcf07411629c400eb9fb1a668559b8961db989e2d3ec7c3004`. Gate 4 may proceed against this reviewed specification and test matrix. Any later normative expectation or executable-test change requires renewed Gate 2/3 review.

---

## Post-approval setup and shipped-policy test-delta review — 2026-09-16

- Delta package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T232227Z-26ff5a5520/package.json`.
- Exact reviewed source: reconstructible snapshot over base `0ca3b2c937f307978dd8860cdca88f9d3bfad69c`; candidate source `bd504c8889fceed37f5006f4ace74a6b06eb01f3147ccf397e778e1cb7165839`, executable source `d6310e5d829bd7f3c75bfc13cf4d28e5c493387ae77b5746264e9dc4da233778`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T232227Z-26ff5a5520/snapshot/source.patch`, SHA-256 `411f0f04d3d7c2d40d76f56a857d53faa6eb32b99b62e773f7b09baa6b49944f`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T232227Z-26ff5a5520/verification-plan.json`, SHA-256 `992edd164fff222e8c93fd3c933685cb6bbef3081f1e8af5c7ac4caaf175bf1c`; lane `CRITICAL`, required reviews `gate3` and `final`.
- Corrected test SHA-256: `4353f36d3b5912ad6e2d055ad1f5429307fcb0ace40ea404cb2bba50f66686e5`.
- Scope: only the setup correction, non-accumulating negative cases, retained public-prepare chain, new shipped-policy witness, and regressions introduced by this test delta. Reviewer independence is unchanged.

### Delta assessment

No findings.

The fixture now supplies the instruction and product context files and repository directories required by public `harness.py prepare`. Its synthetic verification inventory is deterministically sorted and contains registered local unit, governance, integration, and e2e commands in the canonical four-column form. The corrected evidence demonstrates that this is no longer a setup failure: all five behavioral groups from the previously approved A–O matrix execute successfully against the executor's partial classifier.

The negative-neighbor loop now removes each synthetic changed file after its subtest. Consequently every expected lane is determined from the presentation owner plus exactly the current neighbor; earlier cases cannot accumulate and cause a later assertion to pass under the wrong stricter boundary. All required negative categories and the explicit #153A assertion remain present.

Case O retains the approved end-to-end sensitivity. It creates the defective presentation before public `harness.py prepare`, parses that invocation's emitted package, asserts the registered selected oracle in the emitted plan, and executes the focused phase using that exact plan path. The selected oracle must reject the defective label with the intended diagnostic.

The new shipped-policy witness is appropriately exact and fail-closed. It requires exactly one boundary carrying `fast_class="bounded-server-rendered-presentation"`, the single reviewed public oracle `tests/Yii2/yii2_main_navigation_001_test.php`, the closed six-file repository-owned presentation pattern set, and the complete ordered negative-boundary declaration. This prevents an implementation that only supports synthetic fixture metadata while omitting or broadening the repository's shipped registration.

The source-bound RED record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789514532700872000-bf5b4d16da3e438885369f945722a402.json` reports exit `1`, `source_drift=false`, candidate source `bd504c8889fceed37f5006f4ace74a6b06eb01f3147ccf397e778e1cb7165839`, and executable source `d6310e5d829bd7f3c75bfc13cf4d28e5c493387ae77b5746264e9dc4da233778`. Its output has exactly five passing behavior groups and one intended failure: `INTENDED_RED shipped presentation boundary absent`. The failure occurs after valid setup and is sensitive to the remaining missing production-policy behavior.

CI and deployment remain `UNKNOWN`, and enforcement remains unconfigured. This test-delta decision does not treat them as GREEN or authorize publication, merge, deployment, or settings changes.

### Delta verdict

`APPROVED`

Gate 3 is approved for exact candidate source `bd504c8889fceed37f5006f4ace74a6b06eb01f3147ccf397e778e1cb7165839`. Gate 4 may continue against this exact corrected specification and test matrix. Any later normative expectation or executable-test change requires renewed independent Gate 2/3 review.

---

## Gate 5 semantic-owner correction review — 2026-09-16

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T233540Z-1346a7c27a/package.json`.
- Exact reviewed source: reconstructible snapshot over base `0ca3b2c937f307978dd8860cdca88f9d3bfad69c`; candidate source `11c2b3a9fc51892441fae964f115dfd187f55865330e372fb60204880656941a`, executable source `2875fbd9e1fa2a4338f7219dd77c8d7ac5307dcb585b57272fe9904949664570`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T233540Z-1346a7c27a/snapshot/source.patch`, SHA-256 `5b442caefce5034ba2343d9d06a2280a8ceaf6d045421b24b69580b45da8b1a3`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T233540Z-1346a7c27a/verification-plan.json`, SHA-256 `cb25537aef967da9465de14ebf9ad78da892a8ba95031035bfa320772a98e51d`; lane `CRITICAL`, required reviews `gate3` and `final`.
- Corrected test SHA-256: `e6f01d0f2a4415d5fcda431b5c8a1c90af3318df079bbcbbb43bda17a76d2b80`.
- Scope: the Gate 5 finding that the prior shipped owner grouped mixed semantic code, the narrowed owner/oracle expectation, and regressions introduced by that correction. Reviewer independence is unchanged.

### Correction assessment

No findings.

The shipped-policy expectation now permits exactly one mechanically distinct read-only presentation owner: `app/YiiRuntime/Views/feedback-confirmation.php`. It requires the already registered public HTTP oracle `tests/Yii2/yii2_feedback_001_test.php`. That oracle reaches the real `POST /pilot/feedback` seam, requires a successful rendered confirmation containing `Обращение сохранено`, and verifies the safe return link. It therefore observes the owned view's behavior rather than merely checking a filename or lane string.

The former mixed owner is explicitly dismantled by the expectation. `app/YiiRuntime/MainNavigation.php`, `app/YiiRuntime/ViewSupport.php`, `app/YiiRuntime/Views/objects.php`, and `app/YiiRuntime/Views/users.php` must each continue matching the conservative `application-code` boundary. The exact singleton FAST pattern assertion also prevents `construction-control.php`, `roles.php`, or any other view/code path from remaining in or entering this class by implication. Unknown or overlapping ownership continues to fail closed through the previously approved classifier matrix.

This is a static closed-policy registration, not semantic inference. The correction adds no AST or LLM analysis, product behavior, route/action, dependency graph, alternate planner/registry/Gate, later T05.x class, or diff-size heuristic. The normative contract remains unchanged: exact repository ownership plus one registered public oracle is the proof, and the existing negative matrix retains precedence for sensitive and #153A surfaces.

The source-bound RED record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789515325806568000-8725c024745c4100b2df20a9e7b90fbb.json` reports exit `1`, `source_drift=false`, candidate source `11c2b3a9fc51892441fae964f115dfd187f55865330e372fb60204880656941a`, and executable source `2875fbd9e1fa2a4338f7219dd77c8d7ac5307dcb585b57272fe9904949664570`. All five established A–O behavior groups pass. The sole intended failure is exact and relevant: the shipped policy still selects `yii2_main_navigation_001_test.php` instead of the narrowed feedback oracle. This demonstrates the correction remains absent from the reviewed implementation source without a setup failure.

CI and deployment remain `UNKNOWN`, and enforcement remains unconfigured. This test-correction decision does not treat them as GREEN or authorize publication, merge, deployment, or settings changes.

### Correction verdict

`APPROVED`

Gate 3 is approved for exact candidate source `11c2b3a9fc51892441fae964f115dfd187f55865330e372fb60204880656941a`. Implementation may correct the shipped policy against this exact expectation. Any later normative expectation or executable-test change requires renewed independent Gate 2/3 review.
