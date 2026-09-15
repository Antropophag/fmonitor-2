# Delivery record — issue #135 canonical verification inventory

Owner authorization: the 2026-09-15 issue #135 instruction authorizes OpenSpec propose followed by apply. Root authored scope, specification, and executable tests; `/root/executor` (gpt-5.6-sol/low) authored implementation; `/root/gate3_review` independently approved Gate 3 and subsequent test deltas. Final review remains independent.

Base and synchronization: implementation started from `25aee5524f790292d350175ba278bc47e282ed4c`. A final `git fetch origin main` on 2026-09-15 confirmed the same commit. Open product PR #151 contains one ordinary `suites.tsv` addition but no competing verification-inventory implementation; its unmerged product change was not absorbed.

Implemented boundary: `tools/verification/suites.tsv` is the sole editable suite/runtime/path/category inventory. `categories.json` is removed. Python consumers share `tools/verification/inventory.py`; the shell runner delegates listing to it. Registration is explicit and atomic. Existing change-verification prepare rejects added/untracked canonical tests with `UNREGISTERED_TEST`. Fast runs cheap inventory validation, while the governance category retains `verification_ci_001_test.py` exactly once. No product, classifier, workflow-orchestrator, rapid-pilot cleanup, architecture policy, or CI-performance behavior was changed.

RED evidence: registration of a newly discovered canonical test exposed the pre-registration discovery defect in record `1789474440035667000-f33d9f8d5cd74f648c73fecdfd9bd533`; the sole failure was `UNREGISTERED_TEST: tests/InstallationProcess/registered_test.php`. The independently approved test delta is recorded in `reviews/tests/VERIFICATION-CANONICAL-INVENTORY-001.md`.

The first Gate 5 review requested one correction: normal validation/list projections also had to reject noncanonical row order, not only offer a deterministic `canonicalize` projection. Root added independently approved sensitivity and canonical fixture ordering; the executor made normal `load` fail closed while preserving `canonicalize` as the explicit normalizer.

Focused GREEN evidence was rerun after the correction; final exact-source record identifiers are supplied by the final reviewer package and CI. The bounded commands are:

- inventory acceptance (21 tests);
- change-verification planner (18 tests);
- CI/category contract (18 tests);
- canonical roster: 427 tests, category counts unit 101 / integration 268 / e2e 46 / governance 12;
- strict OpenSpec validation, Python compile, shell syntax, active `categories.json` reference scan, and `git diff --check`: GREEN.

The constitutionally prohibited local full `make test` / `make verify` was not run. A post-correction Gate 5 re-review and GREEN exact-source GitHub CI run must complete before PR-ready status; merge, deployment, and repository settings remain out of scope.

First exact-source CI triage: Quality Graph run `34969985820` on commit `0fba8224` completed with primary failures in fast, governance, unit, and Integration 2/2; verify then failed downstream. The complete regression inventory identified three migration gaps: `run.sh` attempted to list the pseudo-suite `lint`, isolated `quality_graph_ci_setup_001_test.php` did not copy the new shared parser, and active delivery-harness/FAST fixtures still read `categories.json` or wrote three-column manifests. Root migrated only those direct consumer fixtures; executor finalized the runner dispatch correction. E2E and Integration 1/2 were GREEN. A corrected exact-source CI run remains required; the failed run is not evidence of admission.

Second exact-source CI triage: Quality Graph run `34974282041` on commit `e92b392b` was GREEN for plan, fast, e2e, both integration shards, and quality-results, but failed unit and governance; verify then failed downstream. The complete diagnostic inventory traced both primary failures to dynamic loading of the shared inventory parser creating `tools/verification/__pycache__` inside the candidate checkout. That filesystem mutation changed the planner source snapshot between repeated operations and produced deterministic-reconstruction and stale/tampered-plan failures. Root added an independently Gate-3-approved observable-side-effect characterization; the executor narrowly disabled bytecode emission only around the two existing dynamic imports and restored the prior interpreter setting in `finally`. No test classification, product behavior, or harness orchestration changed. A corrected exact-source CI run remains required; the failed run is not evidence of admission.
