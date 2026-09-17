# Code review: CHANGE-VERIFICATION-PLACEMENT-181

- Reviewer: independent Gate 5 agent `/root/issue181_gate5`
- Authors: root authored scope, specification, lifecycle artifacts and tests; separate executor authored the implementation
- Reviewed source: base `e245ba1c173cc09f9183380228a7532c8dc942d2` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T202129Z-ee08f922d6/snapshot`; snapshot manifest SHA-256 `99c1ec012ae2ec459f41a6716dc9072b69ced2b01537c80f5c518e28f65c0933`; patch SHA-256 `d60dac17f31447774d7a96490b9c87f449114c75984a6f66cb0aa02d3b55113c`; candidate source `7173b67e3d57b5ece845c7aa8f03f39e59be6b08a69c7b369c4dca823f50bff8`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T202129Z-ee08f922d6/package.json`; plan SHA-256 `50923e31421d0c7006965c83ecf6d4b9032bc1e6592684df62d3c789d5192f31`
- Specification: `specs/CHANGE-VERIFICATION-PLACEMENT-181.md`
- Gate 3: `reviews/tests/CHANGE-VERIFICATION-PLACEMENT-181.md`, final rereview verdict `APPROVED`
- Verdict: `APPROVED`

## Findings

None.

## Review assessment

The implementation confines the placement change to the existing planner, focused runner and reviewer-package seams. Semantic-integration-closure-only commands are retained in the canonical command list and exposed as CI obligations, while the focused runner skips them. Any acceptance, regression/changed-test, direct boundary or known-consumer reason promotes the same argv to local execution; reasons are canonicalized and the command remains deduplicated. The shipped-#187 reconstruction proves the requested reduction from 279 to 4 local commands while preserving the exact ordered previous CI argv inventory.

The package route remains fail closed for missing local acceptance evidence and invalid ownership, policy or verifier inventory. Its current #181 plan has no semantic escalation and therefore retains the pre-existing command schema, while still exposing `make test` under `ci_obligations`; the supplied local evidence covers both focused obligations and does not fabricate CI evidence. The existing CI selection and aggregate are unchanged, and tests retain non-success for missing, skipped, failed and cancelled mandatory integration work, including the local-GREEN/CI-failure boundary.

The diff does not alter FAST classification, review selection, registries, admission state, product code or CI aggregate behavior. The inventory addition only registers the new governance regression. The earlier Gate 3 findings are covered by the final 21-case executable matrix.

## Verification evidence

- Prepared exact-source evidence: `python3 tests/Verification/change_verification_placement_181_test.py` — `GREEN`, retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789676430566674000-6b24003988764a5bb2dc45afeff45ba8.json`.
- Prepared exact-source evidence: `python3 tests/Verification/change_verification_001_test.py` — `GREEN`, retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789676456767173000-13467ecfa5e7485aafd30057158d47cf.json`.
- Independent reviewer rerun: placement regression — 21 tests, all passed.
- Independent reviewer rerun: inherited planner regression — 18 tests, all passed.
- `git diff --check e245ba1c173cc09f9183380228a7532c8dc942d2` — passed.
- Python compilation of the changed planner, package consumer and acceptance test — passed.

The required full exact-source CI obligation is `make test`. It is intentionally pending and has no fabricated local result. This Gate 5 approval covers the reviewed candidate source; publication readiness still requires the planned exact-source GitHub CI to finish GREEN.
