# Test review: TASK-CONTEXT-MANIFEST-001

- Reviewer: independent Gate 3 agent `/root/gate3_review` (gpt-5.6-sol/low)
- Test author: root
- Reviewed source: commit `44ccaa3843a360db731d786879067391725abf9e`; candidate source `b27d6c029fb5f7819939674bca8754c201db507cc4ae81e9b054128653d84981`; retained package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T211616Z-cd112ee0b9/package.json`; snapshot manifest SHA-256 `cf3e7e000ec7f35c82c80a1a2a65e4fca51c6409540a576cf1d24fd7fed375a7` (empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`)
- Agreed review scope / prior findings disposition: first review; issue #157, T02 of parent #145 only; canonical spec, OpenSpec lifecycle, three-case baseline, executable A-L matrix and intended RED. No implementation review.
- Specification: `specs/TASK-CONTEXT-MANIFEST-001.md`
- Public seam: `python3 tools/delivery/harness.py prepare` and the generated role `package.json`
- Red command and intended failure: `python3 tests/Verification/delivery_harness_context_manifest_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789506958077134000-4d3dba2f0ba34c4f81c5f8044560139a.json`; exit 1 with four deterministic failures caused by absent `context_manifest` package behavior and absent `tools/delivery/measure-task-context.py`
- Verdict: `CHANGES_REQUIRED`

## Findings

1. **BLOCKING — cases G and I do not exercise the promised role packages.** `prepared()` defaults to `role="root"`, every profile/freshness invocation uses root, and the test named for reviewer reconstruction also calls the root default (`tests/Verification/delivery_harness_context_manifest_001_test.py:35-67, 106-123`). No test prepares `executor` or `reviewer`; no assertion proves that a fresh package for each role rematerializes current required context. The purported reviewer case also does not require candidate/spec/evidence/review references. An implementation that supports root only, or emits an incomplete reviewer package, can pass.

2. **BLOCKING — the public-route test permits a decorative manifest alongside the old mandatory context route.** Assertions establish additive `context_manifest` and `required_context_path` fields, but never assert which package fields/artifact are the effective delivered startup/reviewer context or that unrelated legacy `rules`/`sources` are no longer mandatory/materialized (`:46-67`). The measurement is a separate CLI and may report smaller computed bytes even if actual `prepare/package` consumers still receive/read the previous whole documents. This leaves the core T02 outcome and case L's “real public route” requirement insensitive.

3. **BLOCKING — reconstructibility and digest/range freshness are under-tested.** The test verifies only the digest of the whole current source (`:108-111`). It does not reconstruct each `content_reference`, compare extracted bytes/characters and item digest with the canonical source, verify the required-context artifact digest/content, or assert a section-index version/digest binding. The index corruption assertion merely looks for *any* `FULL_DOCUMENT` (`:114-122`), which can already exist for another source, so an invalid indexed range may still be silently omitted. Case K must identify the corrupted section's source and prove that source specifically falls back in full (or prepare fails closed).

4. **BLOCKING — case L does not test existing admission/evidence semantics.** `approval == "NOT_REVIEWED"` (`:112`) is only a package default. There is no invocation/assertion of an existing consumer/state/admission path, no missing-evidence result, and no check that manifest presence cannot yield GREEN. The required “harness without manifest consumer must not get false GREEN” regression therefore remains uncovered.

5. **BLOCKING — applicability assertions are too weak to prove complete sensitive/general rule delivery.** A checks only one UI rule ID and absence of two sensitive IDs; C checks only one security ID; D checks one governance ID and three product-source exclusions (`:72-88`). They do not assert the complete expected rule/source set derived independently from the canonical index, and the spec does not enumerate that expected set. An implementation can satisfy these checks while omitting applicable general delivery, auth/session/CSRF/secrets, persistence/domain or verification instructions. For fail-safe F, `any(FULL_DOCUMENT)` (`:102-104`) likewise does not prove the conservative safe source set is complete. Gate 3 needs explicit expected source/rule inventories for A-D/F, particularly the security/auth and persistence fixtures.

6. **BLOCKING — freshness/determinism can pass for the wrong reason and does not cover stable identity inputs.** Mutating `AGENTS.md` also changes the fixture candidate source digest, so the changed manifest hash at `:97-100` does not isolate canonical-source freshness/rebuild from ordinary candidate-source binding. The repeat assertion compares only manifest file hashes (`:91-100`) and does not verify task issue identity, policy/instruction digest bindings, section-index digest, required artifact identity, or semantic equality after excluding package-local timestamps/paths. Add an assertion that the changed canonical digest is present/current and the old binding is rejected/not reused, plus stable replay coverage for the complete semantic contract.

7. **BLOCKING — J covers only a synthetic historical goal, not the specified history/evidence behavior.** `:29-30, 77` proves a fake old goal appears somewhere in `load_on_demand`, but there are no historical review and evidence fixtures, no digest/reconstructible-link assertions for load-on-demand items, and no explicit current evidence fixture proving it remains required in a reviewer package. Thus history could be dropped, unreconstructible, or current evidence could be demoted while the test passes.

8. **BLOCKING — measurement acceptance is incomplete and the baseline is insufficiently self-verifying.** The measurement test checks the three IDs, UI byte reduction, token `UNKNOWN`, and three sensitive rule IDs (`:124-133`). It does not verify before/after Unicode characters, whole canonical-document counts, load-on-demand counts, the documented whole-source/unrelated inventories, deterministic identical rerun, source/input digests, or that reported `before` exactly matches the checked-in baseline for all three cases. Harness/verification applicability is not asserted. A report with fabricated or incomplete metrics can pass. The baseline records aggregate values and prose methodology but no per-source byte/character values or digests, so changes to a listed source cannot be diagnosed or deterministically audited from the artifact itself.

9. **MAJOR — manifest item shape coverage contradicts the desired load-mode contract.** The helper requires every required item to use `required_reference` (`:61-66`) and never exercises `inline`, while the contract allows bounded materialized content and the task explicitly prioritizes delivered mandatory context. Either the schema must normatively define why all required content is materialized through a single referenced artifact and test that artifact, or tests must cover the supported `inline|required_reference` modes. As written, a builder that never exposes exact bounded materialized content can pass the item-shape checks.

10. **MAJOR — spec/OpenSpec are coherent on scope but leave expected deterministic policy data underspecified.** The documents correctly prohibit LLM/NLP, a second planner/policy source, product changes and policy/coverage changes, and select the existing prepare/package seam. However, neither the normative spec nor a reviewed fixture names the canonical source/rule sets expected for A-D/F. Because completeness is the safety property, leaving those sets solely to the future implementation's index makes tests circular rather than independently expected. Add reviewed canonical fixture expectations or exact assertions derived from canonical sources without importing implementation profile tables as the oracle.

11. **RED evidence — acceptable primary failure, but later branches are not yet independently demonstrated.** The retained run is deterministic and fails for missing behavior, not broken setup: three methods stop at absent package manifest and measurement stops at the absent executable. This is valid initial RED. It does not offset the sensitivity gaps above; after corrections, retain a new exact-source RED showing the expanded assertions still fail for the intended missing behavior.

## Required changes

1. Expand the executable matrix to prepare and inspect root, executor and reviewer packages; assert reviewer candidate/spec/evidence/review/load-on-demand bindings and fresh current-digest materialization.
2. Prove the actual prepare/package consumer route uses the compact required-context artifact and does not continue treating unrelated legacy sources as mandatory; exercise an existing admission/state consumer to prove manifest presence cannot create GREEN/approval.
3. Reconstruct and hash every bounded/full reference and the materialized artifact; bind and assert section-index digest/version; make invalid-section fallback source-specific.
4. Define and assert independent complete expected canonical source/rule inventories for UI, persistence/current-state, auth/security, harness/verification and unknown fallback.
5. Strengthen freshness/repeat checks to isolate canonical/index changes from candidate-source changes and cover all semantic identity/digest fields.
6. Cover historical goals, reviews and evidence as reconstructible load-on-demand references while explicit current evidence remains required.
7. Make three-replay measurement auditable and deterministic: assert all required metrics for all cases, exact baseline correspondence, source/input digests or per-source measurements, repeat equality, harness rules, and sensitive completeness. Keep token usage `UNKNOWN`.
8. Resolve and test the supported load-mode/materialization schema without weakening exact canonical content delivery.

No executor implementation should begin until these Gate 3 findings are corrected and independently rereviewed.

---

## Gate 3 rereview v2 — corrected test source

- Reviewer: independent Gate 3 agent `/root/gate3_review` (gpt-5.6-sol/low)
- Test author / correction author: root
- Reviewed correction: commit `e2c4b79ca4b2fe462d12bd59bb8ac036c66f4271`; candidate source `d33586e038446d500a1f6d853b2a622e1f4a7d0e24d6805d9d6da537f1c79f8d`; package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T212007Z-2e6071b3f7/package.json`; snapshot manifest SHA-256 `af98e01126b72fff71715075120fcb190edefd167e5876bcc6d17d68f97a451d` (empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`)
- Corrected RED: `python3 tests/Verification/delivery_harness_context_manifest_001_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789507194553557000-ab6246d1ce3a4b558d75884a7ae1f856.json`; exit 1, four intended failures at absent manifest/measurement behavior
- Verdict: `CHANGES_REQUIRED`

### Prior findings disposition

1. **Partially resolved.** Root, executor and reviewer roles are prepared; reviewer evidence and contracts are asserted. Exact candidate/spec/evidence/**review** references and reconstructible reviewer load-on-demand links are not completely asserted; see new finding A.
2. **Resolved.** `context_delivery` identifies the manifest artifact and legacy `rules` are required absent, making the public route observable.
3. **Resolved.** Required artifact digest, exact byte ranges/excerpt digests, section-index version/digest and source-specific invalid-heading fallback are asserted.
4. **Resolved.** The existing `state` consumer remains not merge/publication ready after manifest preparation.
5. **Resolved.** Independently declared exact rule inventories cover UI, persistence, auth, harness and the exact conservative full-source fallback.
6. **Resolved for the stated freshness risk.** Repeat manifest/artifact identity is asserted, and the updated canonical source digest must match current bytes, preventing reuse of the old binding.
7. **Partially resolved.** Goal/review/evidence history classes and digests are exercised, while explicit evidence stays in reviewer verification bindings. Reconstructible historical links are not validated beyond digest shape; see finding A.
8. **Partially resolved.** Deterministic repeat, baseline aggregate correspondence, all four numeric metrics and harness profile are asserted. Required document/unrelated inventories and full sensitive measurement completeness remain untested; see finding B.
9. **Resolved.** Both contract load modes are admitted and exact materialized content is checked independently of mode.
10. **Resolved.** Expected profile and conservative source inventories now live in the test as the reviewed independent oracle rather than being imported from the implementation table.
11. **Resolved.** Corrected exact-source RED remains isolated to absent T02 behavior and is retained.

### Remaining findings

A. **BLOCKING — reviewer and historical references are still not proven reconstructible as required by R4/R5 and case I/J.** The reviewer assertions check `contracts` and `evidence` equality only (`tests/Verification/delivery_harness_context_manifest_001_test.py:119-123`); they do not assert candidate/snapshot, spec digests, review references, or the reviewer manifest's load-on-demand links. Historical items are checked only for presence plus a 64-character digest (`:174-184`), without comparing that digest to canonical bytes or validating a content/path reference. An implementation may emit random digests or omit review/candidate reconstruction while passing. Assert the exact required reviewer reference classes and verify every load-on-demand reference against its canonical source bytes/path (including the historical goal, review and evidence fixtures).

B. **BLOCKING — the executable measurement can omit required acceptance fields and under-report sensitive completeness.** R6 requires, for each replay, documents previously passed/read whole and obvious historical/unrelated material in addition to bytes/chars/full-doc/load-on-demand counts. The corrected test compares only the four aggregate `before` values and four numeric `after` values (`:194-205`); it never requires `whole_sources` or `obviously_historical_or_unrelated` in the report. Its `input_digest` check accepts any 64 hex characters without tying it to the actual input. The persistence measurement checks only three sensitive IDs (`:208-209`), not the complete reviewed persistence inventory already declared in `EXPECTED`. A fabricated/minimal report can therefore pass. Assert exact baseline `whole_sources` and unrelated/history inventory propagation, recompute each input digest from its named input, and require the full persistence rule inventory (as already done for harness).

### Required changes for v3

1. Complete reviewer-package and load-on-demand reconstruction assertions described in finding A.
2. Complete the deterministic measurement assertions described in finding B.
3. Retain a refreshed exact-source RED and request independent rereview before executor dispatch.
