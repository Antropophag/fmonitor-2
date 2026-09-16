#!/usr/bin/env python3
"""SENSITIVE-OFFLINE-VERIFICATION-001 public planner regression A-L."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class SensitiveOfflineClassification(unittest.TestCase):
    SENSITIVE = [
        "app/YiiRuntime/Assets/checklist-sw.js",
        "app/YiiRuntime/Assets/checklist.js",
        "app/YiiRuntime/Assets/control-queue.js",
    ]
    ORACLE = "tests/Yii2/yii2_inspection_browser_001_test.php"

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix="sensitive-offline-132-")
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        for source in ["tools/delivery/change-verification.py", "tools/verification/inventory.py",
                       ".quality-graph/verification-policy.json", "tools/verification/suites.tsv",
                       "quality-graph.yml", "specs/CHANGE-VERIFICATION-001.md",
                       "specs/SENSITIVE-OFFLINE-VERIFICATION-001.md"]:
            target = self.root / source
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(ROOT / source, target)
        policy = json.loads((self.root / ".quality-graph/verification-policy.json").read_text())
        policy["capability_ownership"].append({
            "name": "sensitive-offline-persistence-fixture-owner",
            "patterns": ["app/Infrastructure/Persistence/Store.php"],
            "verifiers": [], "consumers": []})
        (self.root / ".quality-graph/verification-policy.json").write_text(
            json.dumps(policy, ensure_ascii=False, indent=2) + "\n")
        required = {self.ORACLE, "tests/Verification/architecture_guard_001_test.py"}
        for row in (self.root / "tools/verification/suites.tsv").read_text().splitlines():
            if row and not row.startswith("#"):
                required.add(row.split("\t")[2])
        for boundary in policy["boundaries"]:
            required.update(boundary["tests"])
        for path in required:
            target = self.root / path
            target.parent.mkdir(parents=True, exist_ok=True)
            if not target.exists():
                target.write_text("fixture\n")
        paths = self.SENSITIVE + ["app/YiiRuntime/Assets/pilot.css",
                                  "app/YiiRuntime/Views/checklist/card.php",
                                  "app/Infrastructure/Persistence/Store.php",
                                  "docs/operations/lifecycle.md",
                                  ".quality-graph/verification-policy.json"]
        for path in paths:
            target = self.root / path
            target.parent.mkdir(parents=True, exist_ok=True)
            if not target.exists():
                target.write_text("before\n")
        self.command("git", "init", "-q", check=True)
        (self.root / ".git/info/exclude").write_text("/input.json\n/plan.json\n")
        self.command("git", "config", "user.email", "test@example.invalid", check=True)
        self.command("git", "config", "user.name", "Contract", check=True)
        self.command("git", "add", ".", check=True)
        self.command("git", "commit", "-qm", "base", check=True)
        self.base = self.command("git", "rev-parse", "HEAD", check=True).stdout.strip()

    def command(self, *argv, check=False):
        return subprocess.run(argv, cwd=self.root, text=True, capture_output=True, check=check)

    def build(self, paths, acceptance_test="tests/Verification/architecture_guard_001_test.py"):
        for path in paths:
            target = self.root / path
            target.parent.mkdir(parents=True, exist_ok=True)
            if path == ".quality-graph/verification-policy.json":
                value = json.loads(target.read_text())
                target.write_text(json.dumps(value, ensure_ascii=False, indent=2) + "\n")
            else:
                target.write_text(target.read_text() + "changed\n" if target.exists() else "changed\n")
        value = {"change": "protect-sensitive-offline-fast", "planned_paths": paths,
                 "acceptances": [{"spec_id": "SENSITIVE-OFFLINE-VERIFICATION-001",
                                  "acceptance_id": "public-planner-classification",
                                  "spec_path": "specs/SENSITIVE-OFFLINE-VERIFICATION-001.md",
                                  "seam": "change-verification plan JSON",
                                  "tests": [acceptance_test]}]}
        (self.root / "input.json").write_text(json.dumps(value, sort_keys=True) + "\n")
        result = self.command("python3", "tools/delivery/change-verification.py", "plan",
                              "--base", self.base, "--input", "input.json", "--output", "plan.json")
        plan = json.loads((self.root / "plan.json").read_text()) if result.returncode == 0 else None
        return result, plan

    def test_a_b_c_sensitive_closed_set_is_critical_with_direct_oracle(self):
        for path in self.SENSITIVE:
            with self.subTest(path=path):
                result, plan = self.build([path], self.ORACLE)
                self.assertEqual(0, result.returncode, "INTENDED_RED: " + result.stderr)
                self.assertEqual("CRITICAL", plan["verification_lane"])
                self.assertEqual(["gate3", "final"], plan["required_reviews"])
                self.assertIn({"lane": "CRITICAL", "reason": "sensitive-offline-ui"}, plan["escalations"])
                self.assertIn(["php", self.ORACLE], [item["argv"] for item in plan["commands"]])
                self.command("git", "restore", ".", check=True)

    def test_d_i_sensitive_plus_ui_and_green_oracle_cannot_restore_fast(self):
        result, plan = self.build([self.SENSITIVE[1], "app/YiiRuntime/Assets/pilot.css"], self.ORACLE)
        self.assertEqual(0, result.returncode, "INTENDED_RED: " + result.stderr)
        self.assertEqual("CRITICAL", plan["verification_lane"])
        self.assertIn("e2e", plan["required_categories"])

    def test_e_f_g_h_policy_and_non_sensitive_controls(self):
        cases = [
            ([".quality-graph/verification-policy.json"], "CRITICAL", "delivery-policy"),
            (["app/YiiRuntime/Assets/pilot.css"], "FAST", None),
            (["app/YiiRuntime/Views/checklist/card.php"], "STANDARD", None),
            (["docs/operations/lifecycle.md"], "CRITICAL", "delivery-policy"),
        ]
        for paths, lane, sensitive_reason in cases:
            with self.subTest(paths=paths):
                result, plan = self.build(paths)
                self.assertEqual(0, result.returncode, result.stderr)
                self.assertEqual(lane, plan["verification_lane"])
                reasons = [item["reason"] for item in plan["escalations"]]
                self.assertNotIn("sensitive-offline-ui", reasons)
                if sensitive_reason:
                    self.assertIn(sensitive_reason, reasons)
                self.command("git", "restore", ".", check=True)

    def test_j_sensitive_and_semantic_escalations_compose(self):
        result, plan = self.build([self.SENSITIVE[2], "app/Infrastructure/Persistence/Store.php"], self.ORACLE)
        self.assertEqual(0, result.returncode, "INTENDED_RED: " + result.stderr)
        self.assertEqual("CRITICAL", plan["verification_lane"])
        self.assertIn("integration", plan["required_categories"])
        self.assertIn("e2e", plan["required_categories"])
        self.assertEqual(["persistence-semantics"], [item["surface"] for item in plan["semantic_escalations"]])

    def test_k_unknown_offline_asset_fails_closed(self):
        path = "app/YiiRuntime/Assets/checklist-offline-unknown.js"
        result, plan = self.build([path])
        self.assertNotEqual(0, result.returncode)
        self.assertIsNone(plan)
        self.assertIn("SETUP_FAILURE", result.stderr)

    def test_l_shipped_policy_explains_exact_sensitive_reason(self):
        policy = json.loads((self.root / ".quality-graph/verification-policy.json").read_text())
        boundary = next(item for item in policy["boundaries"] if item["name"] == "sensitive-offline-ui")
        self.assertEqual(self.SENSITIVE, boundary["patterns"])
        result, plan = self.build([self.SENSITIVE[0]])
        self.assertEqual(0, result.returncode, "INTENDED_RED: " + result.stderr)
        self.assertEqual([{"lane": "CRITICAL", "reason": "sensitive-offline-ui"}], plan["escalations"])

    def test_registered_direct_oracle_is_required_by_shipped_policy(self):
        inventory = self.root / "tools/verification/suites.tsv"
        inventory.write_text("\n".join(
            row for row in inventory.read_text().splitlines()
            if self.ORACLE not in row
        ) + "\n")
        result, plan = self.build([self.SENSITIVE[0]])
        self.assertNotEqual(0, result.returncode, "INTENDED_RED: unregistered boundary oracle admitted")
        self.assertIsNone(plan)
        self.assertIn("SETUP_FAILURE", result.stderr)


if __name__ == "__main__":
    unittest.main()
