# QUALITY-GRAPH-CURRENT-CI-001 independent code review

- Reviewer: separately tasked agent `/root/qg_gate5`
- Implementation authors: root and separately tasked implementation agents
- Baseline: `origin/main` / `8fd20da9adeba683e2659a5bb40d41da9816a2e4`
- Reviewed commit: `735456630c72362335c3f57f451f8baff6ad01ea`
- Specification: `specs/QUALITY-GRAPH-CURRENT-CI-001.md`; OpenSpec change `integrate-current-quality-graph`
- Approved Gate 3: `reviews/tests/QUALITY-GRAPH-CURRENT-CI-001.md`
- Approved supplemental Gate 3: `reviews/tests/QUALITY-GRAPH-REPORTING-ADMISSION-001.md` at `4db9c6e7`
- Verdict: **APPROVED**

## Standards

No blocking findings. The change preserves the existing CI jobs and commands in
one renamed workflow and adds only the reporting job after `verify`. The four test
categories still execute through the existing explicit inventory; integration
retains both isolated shards and `fail-fast: false`. The changed historical CI
test reads the new workflow path and otherwise preserves its assertions. The new
report, workflow and preflight tests each occur once in the full inventory.

The repository-owned code has narrow public seams: deterministic native report
rendering, a read-only admission preflight, a renderer and a drift checker. The
renderer binds the declaration's fast command to the actual workflow command and
hashes the declaration, workflows, report and preflight into the checked manifest.
The implementation contains no new business-data writer or custom publisher.

## Specification, security and integration

No blocking findings. Report generation consumes the complete seven-job outcome
map, distinguishes failed, cancelled and both forms of skipped evidence, and uses
the pull-request event head rather than the synthetic merge SHA. It validates all
input before publication, confines output to a non-symlink workspace-relative
directory, publishes the seven files atomically, permits byte-identical replay and
rejects partial or conflicting prior output without overwrite.

The trusted workflow executes no PR checkout or PR code. Its permissions are
limited to the stock publisher's read/check/comment/label surface, and all actions
use exact commit pins. Before invoking stock Quality Graph 0.1.7, the inline,
byte-checked preflight verifies the completed pull-request run identity, enumerates
all artifact pages, and requires exactly one non-expired artifact for every node
at the current attempt. It rejects unknown, malformed, duplicate, future, stale-only
and incomplete evidence while retaining complete earlier-attempt history.

The supplemental implementation closes the remaining fail-open case: artifact
presence alone cannot admit publication. Preflight now enumerates the complete
`filter=latest` jobs result, including page boundaries, and requires exactly one
`quality-results` job from the current attempt with `status=completed` and
`conclusion=success`. Therefore a failed, cancelled, missing, stale or duplicated
reporting job prevents the stock publisher from running even when seven partial
artifacts exist. Failed or cancelled source categories remain publishable when the
reporting job itself completed successfully, so negative CI results are preserved.

The current scope matches issue #25's recorded residual after closing historical
PR #37: alignment with the accepted current matrix, negative matrix and publisher.
The old PR explicitly was a broad historical experiment and was closed without
merge. Its machine-lineage, `check-evidence.php` and receipt subsystem are not a
remaining requirement of #25; importing them would add a separate governance
mechanism outside this change.

## Verification evidence

Independent checks on reviewed commit `73545663`:

- `python3 tests/Verification/quality_graph_preflight_001_test.py` — PASS,
  `QUALITY_GRAPH_PREFLIGHT_001_TESTS_PASSED`.
- `python3 -m unittest tests.Verification.quality_graph_current_report_001_test
  tests.Verification.quality_graph_current_workflow_001_test` — 14 tests, PASS.
- `make quality-graph-validate` — checker and renderer PASS with graph digest
  `ed77954b8e7e5aa796e0384c741919ba3e3b74813e65e9dd4e99c643422c4b56`.
- `make architecture-check` evidence — HTTP qualification PASS and all seven
  architecture rules PASS.
- Author evidence: final unit category 83 tests / 0 failures; retained old CI
  contract 15 tests / 0 failures; workflow suite 5 tests / 0 failures; YAML
  semantic parity and extracted inline-shell execution PASS.
- `git diff --check 73545663^..73545663` — PASS.

The unrelated uncommitted owner-resume edit to
`docs/operations/current-delivery-goal.md` is excluded from this verdict.

Full exact-head GitHub CI and the actual post-bootstrap publisher positive/negative
matrix remain required by the specification before issue #25 can be completed.
This Gate 5 approval reviews the implementation; it does not claim those platform
results, production readiness, merge completion, or closure of #25.

Blocking changes: None.

## Supplemental Gate 5 — inventory compatibility correction

- Triggering source: `4221646f6b189b38ad60dcec8f4fc71330fbc2b4`, PR 73 run
  `34349144148`
- Reviewed correction: `028ad2c435affda4a4323e37cb0c58697e9d9ef7`
- Approved supplemental Gate 3:
  `reviews/tests/QUALITY-GRAPH-INVENTORY-COMPAT-001.md`
- Verdict: **APPROVED**

No blocking findings. The terminal first full CI run completed all nine jobs and
isolated one governance failure in
`test_repository_baseline_membership`; `verify` then failed as required. Fast,
unit, E2E, both integration shards and `quality-results` succeeded. The seven
actual Result artifacts retained the exact head/run/attempt and reported
governance and verify as failed while the other five nodes remained passed. This
is correct fail-closed behavior and supplies real negative transport evidence.

The correction adds exactly three literal `unit` entries to the compatibility
allowlist for the three newly registered Quality Graph contract tests. Every path
already occurs exactly once in `tools/verification/suites.tsv` and
`tools/verification/categories.json`; it now occurs exactly once in
`added_by_suite['unit']`. No baseline hash or assertion changes, category moves,
wildcards, production source, manifest, declaration, workflow, report, preflight
or publisher changes are present in `4221646f..028ad2c4`.

Independent verification on `028ad2c4`:

- `python3 tests/Verification/verification_inventory_001_test.py` — 15 tests,
  PASS.
- Exact occurrence inspection — one catalog, one category and one compatibility
  entry for each of the three test paths.
- `git diff --check 4221646f..028ad2c4` — PASS.

The correction is suitable for the authoritative CI rerun. A successful rerun is
still required before claiming full exact-head CI success; this supplemental
approval does not convert run `34349144148` into a green run.
