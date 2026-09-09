# Test review: QUALITY-GRAPH-CURRENT-CI-001

- Reviewer: separately tasked agent `/root/qg_gate3`
- Test authors: `/root/qg_report_tests`, `/root/qg_remaining_review`, root
- Reviewed commit: `f02639ce985074604f45622e835af149466f3352` (present in review HEAD `c92889f67f540f96a768fa425d38687a581112ae`)
- Specification: `specs/QUALITY-GRAPH-CURRENT-CI-001.md` SHA-256 `e140f33bfd844891d099b59f84ec70c58dd0b68bd90a835d8420464951d56b92`; OpenSpec change `integrate-current-quality-graph`
- Public seams: `python3 tools/delivery/quality-graph-report.py`, `python3 tools/delivery/quality-graph-preflight.py`, `python3 tools/delivery/check-current-quality-graph.py --root PATH`, and the two GitHub workflows they validate
- Red commands and intended failures: report and preflight stop at their missing public CLI; workflow suite reports five failures for the absent checker/current workflow/trusted publisher
- Verdict: `APPROVED`

## Findings

No blocking findings.

Traceability is complete across the normative specification, matching OpenSpec
delta/design/tasks, three executable test files and three authored RED records.
Tests exercise repository-owned CLI/process and workflow boundaries rather than
private functions. Expected node names, titles, Result v0 shape, status mappings,
failure kinds and provenance are literal test data independent of the future
implementation.

The report matrix distinguishes the PR event head from synthetic `GITHUB_SHA`,
preserves failure/cancellation/skips, accepts a full-run dependency skip only with
failed verify, and constrains docs-only to the exact four skipped categories. It
rejects malformed/duplicate/incomplete outcomes, contradictory provenance, path
escape/symlinks, partial prior output and conflicting replay without mutation.

The preflight matrix uses an isolated localhost GET-only GitHub API fixture. It
admits complete current evidence for successful, failed and cancelled completed
runs, and admits retained history only alongside the complete current attempt. It
rejects missing, expired, unknown, malformed, duplicate, stale-only and future
attempt artifacts; stale event identity; run/head/repository mismatches; malformed
or failed API responses; and non-terminating pagination. This protects the trusted
publisher boundary without treating the historical probe under
`tools/delivery/probes/` as a canonical test or implementation.

The workflow test fixes the new authoritative runner path at
`.github/workflows/quality-graph.yml`, requires removal of the old runner, preserves
the existing category commands and two integration shards, and checks complete
`always()` reporting needs, every terminal job outcome, current attempt artifact
names, native collection, pinned stock action, inline-tested preflight bytes and
publisher-only write capabilities with no PR checkout or command/approval surface.
This is the accepted path transition. During implementation,
`tests/Verification/verification_ci_001_test.py` still reads the old
`.github/workflows/repository-verification.yml`; only that path must be updated to
the new YAML while retaining its existing assertions.

RED is deterministic and caused by the three intentionally absent production
seams. The tests use temporary workspaces/servers and do not contact production or
GitHub. Captured hashes match the reviewed files.

## Verification evidence

- `python3 -m py_compile` for all three authored Python tests: exit 0.
- `python3 -m unittest tests.Verification.quality_graph_current_report_001_test`:
  intended RED, missing `tools/delivery/quality-graph-report.py`.
- `python3 tests/Verification/quality_graph_preflight_001_test.py`: intended RED,
  first qualified positive case reaches missing `tools/delivery/quality-graph-preflight.py`.
- `python3 -m unittest tests.Verification.quality_graph_current_workflow_001_test`:
  intended RED, five failures for missing checker/current workflows.
- `openspec validate integrate-current-quality-graph --strict`: valid.
- `git diff --check`: exit 0.
- Reviewed test SHA-256: report `b5fec5960c45833f3c474a98ac78e94d2d320f84be46b71e719624b71f2f1edd`;
  preflight `2f5439b6ac95d63932f792c828ea7631768b00e034d784085efd474100bd026b`;
  workflow `44ea7e588fbbbed3f7d20a3e0e50e45b36124928f2b09404e4140799346b3898`.

## Required changes

None. Gate 4 implementation may proceed against these frozen expectations.
