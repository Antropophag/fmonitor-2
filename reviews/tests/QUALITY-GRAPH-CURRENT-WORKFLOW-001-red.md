# QUALITY-GRAPH-CURRENT-CI-001 workflow RED

Author: root. Gate3 pending; this record is not an independent approval.
Baseline: 7d43c2e7a0d2843b5b817c840cdb70f66d1b36fb.
Command: `python3 tests/Verification/quality_graph_current_workflow_001_test.py`.
Result: exit1, 5 intended failures; the current category runner lacks graph
integration, stock publisher and public validation command. Fixture paths are
qualified before mutation; no new production code exists yet.

Protected semantics: exactly one run per category; two integration shards;
current aggregate; docs-only conditions; read-only runner; complete always-report
needs; native collector gets report-generation outcome, each artifact gets current
attempt. Trusted publisher forbids PR checkout/commands and content/deployment writes.
Only old workflow test path will change after rename, with its existing assertions
preserved. Inline preflight must equal the standalone tested source.

Test SHA256: `44ea7e588fbbbed3f7d20a3e0e50e45b36124928f2b09404e4140799346b3898`.
Raw command output: /tmp/fmonitor-qg-workflow-red.log (local diagnostic).
