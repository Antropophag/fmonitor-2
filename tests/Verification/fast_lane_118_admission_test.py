"""DELIVERY-FAST-LANE-118-V1 minimal exact-source FAST admission."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class FastLaneAdmission(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="fast-admission-118-")
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        (self.root / "tools/verification").mkdir(parents=True)
        shutil.copy(ROOT / "tools/verification/ci.py", self.root / "tools/verification/ci.py")
        subprocess.run(["git", "init", "-q"], cwd=self.root, check=True)
        subprocess.run(["git", "config", "user.email", "test@example.invalid"], cwd=self.root, check=True)
        subprocess.run(["git", "config", "user.name", "Admission"], cwd=self.root, check=True)
        (self.root / "seed").write_text("seed")
        subprocess.run(["git", "add", "."], cwd=self.root, check=True)
        subprocess.run(["git", "commit", "-qm", "seed"], cwd=self.root, check=True)
        self.head = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=self.root, text=True).strip()
        self.plan = {"head": self.head, "verification_lane": "FAST", "selected_checks": [
            {"id": "ui-oracle", "argv": ["python3", "tests/UI/bounded_visibility_test.py"]}]}
        (self.root / "plan.json").write_text(json.dumps(self.plan))

    def admit(self, checks, source=None):
        results = {"source": source or self.head, "checks": checks,
                   "policy_unselected": {"integration": "not_selected_by_policy", "e2e": "not_selected_by_policy"}}
        return subprocess.run(["python3", "tools/verification/ci.py", "aggregate", "--full", "false",
                               "--mode", "fast", "--fast-plan", "plan.json", "--results", json.dumps(results)],
                              cwd=self.root, text=True, capture_output=True)

    def test_selected_success_and_policy_unselected_are_admitted(self):
        result = self.admit({"ui-oracle": "success"})
        self.assertEqual(0, result.returncode, "INTENDED_RED FAST admission missing: " + result.stderr)
        self.assertIn("FAST_VERIFY_OK", result.stdout)

    def test_selected_failure_missing_or_skip_block(self):
        cases = [({"ui-oracle": "failure"}, "selected check failed: ui-oracle"),
                 ({}, "missing selected check: ui-oracle"),
                 ({"ui-oracle": "skipped"}, "unexpectedly skipped selected check: ui-oracle")]
        for checks, diagnostic in cases:
            with self.subTest(checks=checks):
                result = self.admit(checks)
                self.assertNotEqual(0, result.returncode)
                self.assertIn(diagnostic, result.stderr.lower())

    def test_stale_source_blocks(self):
        result = self.admit({"ui-oracle": "success"}, source="0" * 40)
        self.assertNotEqual(0, result.returncode)
        self.assertIn("source", result.stderr.lower())

    def test_ci_plan_routes_exact_fast_plan_without_categories(self):
        result = subprocess.run(["python3", "tools/verification/ci.py", "plan", "--base", self.head,
                                 "--event", "pull_request", "--verification-plan", "plan.json"],
                                cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, result.returncode, "INTENDED_RED FAST CI route missing: " + result.stderr)
        routed = json.loads(result.stdout)
        self.assertEqual(("fast", False, []), (routed["mode"], routed["full"], routed["categories"]))
        self.assertEqual(self.plan["selected_checks"], routed["selected_checks"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
