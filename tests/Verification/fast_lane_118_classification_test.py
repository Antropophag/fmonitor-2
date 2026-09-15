"""DELIVERY-FAST-LANE-118-V1: public planner classification and sensitivity."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class FastLaneClassification(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="fast-lane-118-")
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        for path in ["tools/delivery", "tools/verification", ".quality-graph", "specs",
                     "web/views", "app/Infrastructure/Persistence", "app/IdentityAccess",
                     "tests/UI", "tests/Verification", "tests/InstallationProcess",
                     "tests/AssignmentOrderComposition", "tests/Otiz", "tests/Runtime", "tests/Jobs"]:
            (self.root / path).mkdir(parents=True, exist_ok=True)
        shutil.copytree(ROOT / "tools/delivery", self.root / "tools/delivery", dirs_exist_ok=True)
        shutil.copy(ROOT / "tools/verification/ci.py", self.root / "tools/verification/ci.py")
        shutil.copy(ROOT / "tools/verification/inventory.py", self.root / "tools/verification/inventory.py")
        (self.root / "specs/CHANGE-VERIFICATION-001.md").write_text("planner\n")
        (self.root / "specs/DELIVERY-FAST-LANE-118-V1.md").write_text("acceptance\n")
        (self.root / "quality-graph.yml").write_text("version: 1\n")
        self.oracle = "tests/UI/bounded_visibility_test.py"
        (self.root / self.oracle).write_text(
            "from pathlib import Path\n"
            "text=Path('web/views/card.js').read_text()\n"
            "assert 'reveal-template' in text, 'INTENDED_RED missing visible template action'\n")
        (self.root / "tests/Verification/policy_test.py").write_text("print('policy ok')\n")
        self.inventory = {self.oracle: "unit", "tests/Verification/policy_test.py": "governance"}
        self.policy = {
            "version": 1, "graph": "quality-graph.yml", "spec": "specs/CHANGE-VERIFICATION-001.md",
            "suite_inventory": "tools/verification/suites.tsv",
            "runtimes": {".py": "python3", ".php": "php", ".mjs": "node"},
            "category_argv": {"unit": [["python3", self.oracle]],
                              "governance": [["python3", "tests/Verification/policy_test.py"]]},
            "boundaries": [
                {"name": "bounded-ui", "patterns": ["web/views/**"], "categories": ["unit"], "tests": []},
                {"name": "persistence", "patterns": ["app/Infrastructure/Persistence/**"],
                 "categories": ["unit"], "tests": []},
                {"name": "auth", "patterns": ["app/IdentityAccess/**"], "categories": ["unit"],
                 "tests": []},
                {"name": "delivery-policy", "patterns": ["tools/**", ".quality-graph/**", "specs/**", "tests/**"],
                 "categories": ["governance"], "tests": []}],
            "verification_lanes": {"FAST": ["bounded-ui"], "CRITICAL": ["auth", "delivery-policy"]},
            "full_categories": ["unit", "integration", "e2e", "governance"],
            "full_argv": ["make", "test"]}
        self.write_inventory(self.inventory)
        self.write(".quality-graph/verification-policy.json", self.policy)
        (self.root / "web/views/card.js").write_text("const action='reveal-template';\n")
        self.input = {"change": "bounded-ui", "planned_paths": ["web/views/card.js"], "acceptances": [{
            "spec_id": "DELIVERY-FAST-LANE-118-V1", "acceptance_id": "F01-F10",
            "spec_path": "specs/DELIVERY-FAST-LANE-118-V1.md", "seam": "rendered card",
            "tests": [self.oracle]}]}
        self.write("change.json", self.input)
        for args in [("init", "-q"), ("config", "user.email", "test@example.invalid"),
                     ("config", "user.name", "Fast Lane"), ("add", "."), ("commit", "-qm", "base")]:
            subprocess.run(["git", *args], cwd=self.root, check=True, capture_output=True)
            if args == ("init", "-q"):
                (self.root / ".git/info/exclude").write_text("/change.json\n/plan.json\n")
        self.base = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=self.root, text=True).strip()
        (self.root / "web/views/card.js").write_text("const action='reveal-template'; // changed\n")

    def write(self, path, value):
        target = self.root / path
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(value, sort_keys=True) + "\n")

    def write_inventory(self, mapping, suites=None):
        suites = suites or {}
        rows = [(suites.get(path, 'unit'), 'php' if path.endswith('.php') else 'python3', path, category)
                for path, category in mapping.items()]
        rows.sort(key=lambda row: (row[0], row[2], row[1], row[3]))
        (self.root / 'tools/verification/suites.tsv').write_text(
            ''.join('\t'.join(row) + '\n' for row in rows))

    def git(self, *args):
        return subprocess.run(["git", *args], cwd=self.root, text=True, capture_output=True, check=True)

    def plan(self):
        result = subprocess.run(["python3", "tools/delivery/change-verification.py", "plan",
                                 "--base", self.base, "--input", "change.json", "--output", "plan.json"],
                                cwd=self.root, text=True, capture_output=True)
        return result, json.loads((self.root / "plan.json").read_text()) if result.returncode == 0 else None

    def test_f01_selects_exact_oracle_without_category_expansion(self):
        result, plan = self.plan()
        self.assertEqual(0, result.returncode, "INTENDED_RED FAST classification missing: " + result.stderr)
        self.assertEqual("FAST", plan["verification_lane"])
        self.assertEqual(["final"], plan["required_reviews"])
        self.assertEqual([["python3", self.oracle]], [check["argv"] for check in plan["selected_checks"]])
        self.assertNotIn(["make", "test"], [item["argv"] for item in plan["commands"]])
        self.assertNotIn("integration", plan["required_categories"])
        self.assertNotIn("e2e", plan["required_categories"])

    def test_f03_f04_and_f06_escalate(self):
        for paths, expected, reason in [
                (["app/Infrastructure/Persistence/store.php"], "STANDARD", "persistence"),
                (["web/views/card.js", "app/IdentityAccess/Session.php"], "CRITICAL", "auth"),
                (["web/views/card.js", "tools/delivery/change-verification.py"], "CRITICAL", "delivery-policy")]:
            with self.subTest(paths=paths):
                for path in paths:
                    target = self.root / path
                    target.parent.mkdir(parents=True, exist_ok=True)
                    if not target.exists(): target.write_text("changed\n")
                self.input["planned_paths"] = paths
                self.write("change.json", self.input)
                result, plan = self.plan()
                self.assertEqual(0, result.returncode, result.stderr)
                self.assertEqual(expected, plan["verification_lane"])
                self.assertNotEqual("FAST", plan["verification_lane"])
                self.assertIn(reason, plan["reasons"])
                self.assertTrue(plan["escalations"])

    def test_f07_unknown_closure_fails_closed(self):
        self.input["planned_paths"] = ["unknown/owner.txt"]
        self.write("change.json", self.input)
        result, plan = self.plan()
        self.assertNotEqual(0, result.returncode)
        self.assertIsNone(plan)
        self.assertIn("unknown or ambiguous boundary", result.stderr)

    def test_f10_defective_fixture_makes_selected_oracle_red(self):
        result, plan = self.plan()
        self.assertEqual(0, result.returncode, result.stderr)
        (self.root / "web/views/card.js").write_text("const action='hidden'; // defect\n")
        refreshed, _ = self.plan()
        self.assertEqual(0, refreshed.returncode, refreshed.stderr)
        run = subprocess.run(["python3", "tools/delivery/change-verification.py", "run",
                              "--plan", "plan.json", "--phase", "focused"], cwd=self.root,
                             text=True, capture_output=True)
        self.assertNotEqual(0, run.returncode)
        self.assertIn("INTENDED_RED missing visible template action", run.stderr + run.stdout)

    def test_f10_oracle_sensitivity_is_independently_demonstrable(self):
        argv = ["python3", self.oracle]
        healthy = subprocess.run(argv, cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, healthy.returncode, healthy.stderr)
        (self.root / "web/views/card.js").write_text("const action='hidden'; // defect\n")
        defective = subprocess.run(argv, cwd=self.root, text=True, capture_output=True)
        self.assertNotEqual(0, defective.returncode)
        self.assertIn("INTENDED_RED missing visible template action", defective.stderr)

    def test_shipped_policy_selects_real_ui_oracle_and_rejects_nested_wildcard(self):
        policy = json.loads((ROOT / ".quality-graph/verification-policy.json").read_text())
        inventory = {line.split('\t')[2]: line.split('\t')[3]
                     for line in (ROOT / 'tools/verification/suites.tsv').read_text().splitlines() if line}
        referenced = {test for boundary in policy["boundaries"] for test in boundary["tests"]}
        referenced.update(test for consumer in policy.get("consumers", []) for test in consumer["tests"])
        referenced.update(command[-1] for values in policy["category_argv"].values() for command in values
                          if command[-1].startswith("tests/"))
        for relative in referenced:
            target = self.root / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            if not target.exists(): target.write_text("fixture\n")
        oracle = "tests/Yii2/yii2_preopening_browser_001_test.php"
        inventory[oracle] = "e2e"
        (self.root / oracle).parent.mkdir(parents=True, exist_ok=True)
        (self.root / oracle).write_text("<?php exit(0);\n")
        for line in (ROOT / 'tools/verification/suites.tsv').read_text().splitlines():
            if not line:
                continue
            _suite, _runtime, relative, _category = line.split('\t')
            target = self.root / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            if not target.exists():
                target.write_text('fixture\n')
        shutil.copy2(ROOT / 'tools/verification/suites.tsv', self.root / 'tools/verification/suites.tsv')
        self.write(".quality-graph/verification-policy.json", policy)
        asset = self.root / "app/YiiRuntime/Assets/preopening.js"
        asset.parent.mkdir(parents=True, exist_ok=True)
        asset.write_text("before\n")
        self.git("add", ".")
        self.git("commit", "-qm", "shipped policy fixture")
        self.base = self.git("rev-parse", "HEAD").stdout.strip()
        asset.write_text("after\n")
        self.input["planned_paths"] = ["app/YiiRuntime/Assets/preopening.js"]
        self.input["acceptances"][0]["tests"] = [oracle]
        self.write("change.json", self.input)
        result, plan = self.plan()
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("FAST", plan["verification_lane"])
        self.assertEqual([["php", oracle]], [item["argv"] for item in plan["selected_checks"]])
        self.assertEqual([["php", oracle]], [item["argv"] for item in plan["commands"]])
        conventional_path = "openspec/changes/shipped-ui/verification-input.json"
        conventional = self.root / conventional_path
        conventional.parent.mkdir(parents=True, exist_ok=True)
        shipped_input = json.loads(json.dumps(self.input))
        shipped_input["planned_paths"].append(conventional_path)
        conventional.write_text(json.dumps(shipped_input, sort_keys=True) + "\n")
        self.git("add", "app/YiiRuntime/Assets/preopening.js", "openspec")
        self.git("commit", "-qm", "shipped bounded candidate")
        routed = subprocess.run(["python3", "tools/verification/ci.py", "plan", "--base", self.base,
                                 "--event", "pull_request"], cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, routed.returncode, routed.stderr)
        self.assertEqual("fast", json.loads(routed.stdout)["mode"])
        selected_run = subprocess.run(["python3", "tools/verification/ci.py", "run-fast-node",
                                       "--base", self.base, "--event", "pull_request"],
                                      cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, selected_run.returncode, selected_run.stderr)
        self.assertIn("FAST_SELECTED_OK", selected_run.stdout)
        jobs = {"plan": "success", "fast": "success", "harness": "skipped", "unit": "skipped",
                "integration": "skipped", "e2e": "skipped", "governance": "skipped"}
        admitted = subprocess.run(["python3", "tools/verification/ci.py", "aggregate", "--full", "false",
                                   "--mode", "fast", "--base", self.base, "--results", json.dumps(jobs)],
                                  cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, admitted.returncode, admitted.stderr)
        self.assertIn("FAST_VERIFY_OK", admitted.stdout)
        self.input["planned_paths"] = ["rapid-pilot/nested/unproved.js"]
        self.write("change.json", self.input)
        nested, _ = self.plan()
        self.assertNotEqual(0, nested.returncode)
        self.assertIn("unknown or ambiguous boundary", nested.stderr)

    def test_conventional_input_reconstructs_deterministically_and_ambiguity_fails_closed(self):
        absent = subprocess.run(["python3", "tools/verification/ci.py", "plan", "--base", self.base,
                                 "--event", "pull_request"], cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, absent.returncode, absent.stderr)
        self.assertNotEqual("fast", json.loads(absent.stdout)["mode"])
        conventional = self.root / "openspec/changes/bounded-ui/verification-input.json"
        conventional.parent.mkdir(parents=True, exist_ok=True)
        conventional.write_text(json.dumps(self.input, sort_keys=True) + "\n")
        self.git("add", "web/views/card.js", "openspec")
        self.git("commit", "-qm", "bounded candidate")
        first = subprocess.run(["python3", "tools/verification/ci.py", "plan", "--base", self.base,
                                "--event", "pull_request"], cwd=self.root, text=True, capture_output=True)
        second = subprocess.run(["python3", "tools/verification/ci.py", "plan", "--base", self.base,
                                 "--event", "pull_request"], cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, first.returncode, "INTENDED_RED conventional FAST input undiscovered: " + first.stderr)
        self.assertEqual(first.stdout, second.stdout, "independent reconstruction must be byte-equivalent")
        routed = json.loads(first.stdout)
        self.assertEqual("fast", routed["mode"], routed)
        current_head = self.git("rev-parse", "HEAD").stdout.strip()
        self.assertEqual(current_head, routed["head"])
        self.assertEqual("FAST", routed["verification_lane"])
        self.assertEqual(["final"], routed["required_reviews"])
        self.assertEqual(["bounded-ui"], routed["reasons"])
        self.assertEqual([], routed["escalations"])
        self.assertEqual({"selected_success": "required", "selected_failure": "failure",
                          "selected_missing": "failure", "selected_skipped": "failure",
                          "policy_unselected": "neutral", "source": "current_head"},
                         routed["admission_expectations"])
        self.assertEqual([["python3", self.oracle]], [item["argv"] for item in routed["selected_checks"]])
        selected_run = subprocess.run(["python3", "tools/verification/ci.py", "run-fast-node",
                                       "--base", self.base, "--event", "pull_request"],
                                      cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, selected_run.returncode, selected_run.stderr)
        self.assertIn("FAST_SELECTED_OK", selected_run.stdout)
        jobs = {"plan": "success", "fast": "success", "harness": "skipped",
                "unit": "skipped", "integration": "skipped", "e2e": "skipped", "governance": "skipped"}
        admitted = subprocess.run(["python3", "tools/verification/ci.py", "aggregate", "--full", "false",
                                   "--mode", "fast", "--base", self.base, "--results", json.dumps(jobs)],
                                  cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, admitted.returncode, admitted.stderr)
        self.assertIn("FAST_VERIFY_OK", admitted.stdout)
        other = self.root / "openspec/changes/other/verification-input.json"
        other.parent.mkdir(parents=True, exist_ok=True)
        other.write_text(conventional.read_text())
        self.git("add", "openspec")
        self.git("commit", "-qm", "ambiguous input")
        ambiguous = subprocess.run(["python3", "tools/verification/ci.py", "plan", "--base", self.base,
                                    "--event", "pull_request"], cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, ambiguous.returncode, ambiguous.stderr)
        self.assertNotEqual("fast", json.loads(ambiguous.stdout)["mode"])
        self.assertIn("multiple", json.loads(ambiguous.stdout)["reason"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
