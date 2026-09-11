"""QUALITY-GRAPH-CURRENT-CI-001: public drift checker and retained CI boundary."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
CHECK = ROOT / "tools/delivery/check-current-quality-graph.py"
FILES = ["quality-graph.yml", ".quality-graph/current-ci-manifest.json",
         ".github/workflows/quality-graph.yml", ".github/workflows/quality-graph-publish.yml",
         "tools/delivery/quality-graph-preflight.py", "tools/delivery/quality-graph-report.py"]
PIN = "alchemmist/quality-graph@caf5366a04ca01b230f1df5585d0fbd9693d7bef"
NODES = ["plan", "fast", "unit", "integration", "e2e", "governance", "verify"]


class CurrentGraphWorkflow(unittest.TestCase):
    def check(self, root):
        self.assertTrue(CHECK.is_file(), "RED: public current-CI graph validation command is absent")
        return subprocess.run(["python3", str(CHECK), "--root", str(root)],
                              capture_output=True, text=True, timeout=30)

    def test_real_declaration_and_workflows_validate(self):
        result = self.check(ROOT)
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        self.assertIn("QUALITY_GRAPH_VALIDATION_OK", result.stdout)

    def test_drift_is_rejected_for_each_published_boundary(self):
        self.assertTrue(CHECK.is_file(), "RED: public current-CI graph validation command is absent")
        for path in FILES:
            with self.subTest(path=path), tempfile.TemporaryDirectory() as directory:
                copy = Path(directory)
                for name in FILES:
                    target = copy / name
                    target.parent.mkdir(parents=True, exist_ok=True)
                    shutil.copyfile(ROOT / name, target)
                target = copy / path
                target.write_text(target.read_text() + "\n# unintended drift\n")
                result = self.check(copy)
                self.assertNotEqual(0, result.returncode, path)
                self.assertIn("QUALITY_GRAPH_VALIDATION_FAILURE", result.stdout + result.stderr)

    def test_no_second_runner_and_preserved_categories(self):
        workflow_path = ROOT / ".github/workflows/quality-graph.yml"
        self.assertTrue(workflow_path.is_file(), "RED: current category runner has no graph integration")
        self.assertFalse((ROOT / ".github/workflows/repository-verification.yml").exists())
        self.assertFalse((ROOT / ".github/workflows/quality-graph-push.yml").exists())
        workflow = workflow_path.read_text()
        for category in ["unit", "integration", "e2e", "governance"]:
            self.assertEqual(1, workflow.count("run: make test CATEGORY=" + category))
        self.assertNotIn("run: make fresh-test-verify", workflow)
        self.assertNotIn("run: make verify", workflow)
        self.assertIn("shard: [1, 2]", workflow)
        self.assertIn("fail-fast: false", workflow)
        self.assertIn('"integration":"${{ needs.integration.result }}"', workflow)
        self.assertIn('ci.py aggregate --full "$FULL" --mode "$MODE" --results "$RESULTS"', workflow)
        self.assertEqual(4, workflow.count("if: needs.plan.outputs.full == 'true'"))
        self.assertNotIn("checks: write", workflow)
        self.assertNotIn("issues: write", workflow)
        for node in NODES:
            self.assertIn("node-id: " + node, workflow)
            self.assertIn("quality-result-" + node + "-${{ github.run_attempt }}", workflow)
        self.assertEqual(7, workflow.count("adapter: native"))
        self.assertEqual(7, workflow.count("uses: " + PIN))

    def test_reporting_keeps_all_outcomes_and_current_attempt(self):
        path = ROOT / ".github/workflows/quality-graph.yml"
        self.assertTrue(path.is_file(), "RED: current category runner has no graph integration")
        workflow = path.read_text()
        self.assertIn("\n  quality-results:\n", workflow)
        report = workflow.split("\n  quality-results:\n", 1)[1]
        self.assertIn("always()", report)
        self.assertIn("github.event_name == 'pull_request'", report)
        self.assertIn("needs.plan.outputs.mode != 'harness'", report)
        self.assertIn("needs: [plan, fast, harness, unit, integration, e2e, governance, verify]", report)
        self.assertNotIn("continue-on-error: true", report)
        self.assertIn("id: reports", report)
        for node in NODES:
            self.assertIn('"' + node + '":"${{ needs.' + node + '.result }}"', report)
        self.assertEqual(7, report.count("command-outcome: ${{ steps.reports.outcome }}"))
        self.assertEqual(7, report.count("name: quality-result-"))
        self.assertEqual(7, report.count("-${{ github.run_attempt }}"))
        self.assertEqual(7, report.count("if-no-files-found: error"))

    def test_trusted_standard_publisher_has_no_pr_execution(self):
        path = ROOT / ".github/workflows/quality-graph-publish.yml"
        self.assertTrue(path.is_file(), "RED: trusted stock publisher is absent")
        publisher = path.read_text()
        self.assertIn("workflow_run:", publisher)
        self.assertIn("checks: write", publisher)
        self.assertIn("issues: write", publisher)
        self.assertIn("contents: read", publisher)
        self.assertIn("actions: read", publisher)
        self.assertIn("uses: " + PIN, publisher)
        for forbidden in ["actions/checkout", "issue_comment:", "pull_request_target:",
                          "contents: write", "deployments: write", "write-all",
                          "operation: command", "operation: approve"]:
            self.assertNotIn(forbidden, publisher)
        source = (ROOT / "tools/delivery/quality-graph-preflight.py").read_text()
        self.assertIn("\n".join("          " + line if line else "" for line in source.splitlines()), publisher)


if __name__ == "__main__":
    unittest.main()
