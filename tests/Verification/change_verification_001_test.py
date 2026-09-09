"""CHANGE-VERIFICATION-001: isolated public CLI contract."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class ChangeVerification(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="change-verification-")
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        for relative in ["tools/delivery", "tools/verification", ".quality-graph", "specs", "app/PilotHttp", "app/Infrastructure/Persistence", "app/Otiz", "tests/InstallationProcess", "tests/Runtime", "tests/Otiz"]:
            (self.root / relative).mkdir(parents=True, exist_ok=True)
        source = ROOT / "tools/delivery/change-verification.py"
        if source.exists():
            shutil.copy2(source, self.root / "tools/delivery/change-verification.py")
        (self.root / "specs/CHANGE-VERIFICATION-001.md").write_text("contract\n")
        (self.root / "specs/EXAMPLE-001.md").write_text("acceptance contract\n")
        (self.root / "quality-graph.yml").write_text("version: 1\n")
        (self.root / "tools/verification/categories.json").write_text(json.dumps({
            "tests/InstallationProcess/action_001_test.php": "integration",
            "tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php": "integration",
            "tests/Runtime/runtime_storage_001_test.php": "integration",
            "tests/Otiz/snapshot_publication_001_test.php": "integration",
        }))
        self.policy = {
            "version": 1,
            "graph": "quality-graph.yml",
            "spec": "specs/CHANGE-VERIFICATION-001.md",
            "inventory": "tools/verification/categories.json",
            "runtimes": {".py": "python3", ".php": "php", ".mjs": "node"},
            "category_argv": {
                "integration": [["php", "tests/Runtime/runtime_storage_001_test.php"]],
                "governance": [["python3", "tests/Verification/change_verification_001_test.py"]]
            },
            "boundaries": [
                {"name": "pilot-http", "patterns": ["app/PilotHttp/**"], "categories": ["integration"],
                 "tests": ["tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php"]},
                {"name": "persistence", "patterns": ["app/Infrastructure/Persistence/**"], "categories": ["integration"],
                 "tests": ["tests/Runtime/runtime_storage_001_test.php"]},
                {"name": "money", "patterns": ["app/Otiz/**"], "categories": ["integration"],
                 "tests": ["tests/Otiz/snapshot_publication_001_test.php"]},
                {"name": "tests", "patterns": ["tests/**"], "categories": ["integration"], "tests": []},
                {"name": "verification-policy", "patterns": [".quality-graph/**", "tools/**", "specs/**"],
                 "categories": ["governance"], "tests": []},
            ],
            "full_categories": ["unit", "integration", "e2e", "governance"],
            "full_argv": ["make", "test"],
        }
        self.write_json(".quality-graph/verification-policy.json", self.policy)
        (self.root / "app/PilotHttp/Action.php").write_text("before\n")
        (self.root / "tests/InstallationProcess/action_001_test.php").write_text("<?php\n")
        (self.root / "tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php").write_text("<?php\n")
        (self.root / "tests/Runtime/runtime_storage_001_test.php").write_text("<?php\n")
        (self.root / "tests/Otiz/snapshot_publication_001_test.php").write_text("<?php\n")
        self.git("init", "-q")
        (self.root / ".git/info/exclude").write_text("/change.json\n/plan.json\n")
        self.git("config", "user.email", "test@example.invalid")
        self.git("config", "user.name", "Contract")
        self.git("add", ".")
        self.git("commit", "-qm", "base")
        self.base = self.git("rev-parse", "HEAD").stdout.strip()
        self.input = {"change": "example", "planned_paths": ["app/PilotHttp/Action.php"], "acceptances": [
            {"spec_id": "EXAMPLE-001", "acceptance_id": "http-action-accepted", "spec_path": "specs/EXAMPLE-001.md",
             "seam": "http:action", "tests": ["tests/InstallationProcess/action_001_test.php"]}
        ]}
        self.write_json("change.json", self.input)
        (self.root / "app/PilotHttp/Action.php").write_text("after\n")

    def write_json(self, path, value):
        (self.root / path).write_text(json.dumps(value, sort_keys=True) + "\n")

    def git(self, *args):
        return subprocess.run(["git", *args], cwd=self.root, text=True, capture_output=True, check=True)

    def cli(self, *args, env=None):
        return subprocess.run(["python3", "tools/delivery/change-verification.py", *args], cwd=self.root,
                              text=True, capture_output=True, env=env, timeout=20)

    def plan(self):
        return self.cli("plan", "--base", self.base, "--input", "change.json", "--output", "plan.json")

    def test_plan_covers_declared_actual_acceptance_and_boundary_obligations(self):
        result = self.plan()
        self.assertEqual(0, result.returncode, "INTENDED_RED planner missing: " + result.stderr)
        plan = json.loads((self.root / "plan.json").read_text())
        self.assertEqual(["app/PilotHttp/Action.php"], plan["paths"]["planned"])
        self.assertEqual([{"path": "app/PilotHttp/Action.php", "status": "unstaged"}], plan["paths"]["actual"])
        commands = plan["commands"]
        self.assertEqual([
            ["php", "tests/InstallationProcess/action_001_test.php"],
            ["php", "tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php"],
            ["php", "tests/Runtime/runtime_storage_001_test.php"],
            ["make", "test"],
        ], [item["argv"] for item in commands])
        self.assertEqual(["focused", "focused", "focused", "integration"], [item["phase"] for item in commands])
        self.assertTrue(all(item["rationale"] for item in commands))
        first = (self.root / "plan.json").read_bytes()
        self.assertEqual(0, self.plan().returncode)
        self.assertEqual(first, (self.root / "plan.json").read_bytes(), "canonical plan is deterministic")

    def test_repository_policy_maps_deployment_sources(self):
        """Use the real policy through the public CLI, in a disposable repository."""
        policy = json.loads((ROOT / ".quality-graph/verification-policy.json").read_text())
        inventory = json.loads((ROOT / "tools/verification/categories.json").read_text())
        inventory["tests/InstallationProcess/action_001_test.php"] = "integration"
        commands = [command for values in policy["category_argv"].values() for command in values]
        paths = [command[1] for command in commands]
        paths += [test for boundary in policy["boundaries"] for test in boundary["tests"]]
        for relative in paths:
            target = self.root / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            if not target.exists():
                target.write_text("fixture\n")
        self.write_json(".quality-graph/verification-policy.json", policy)
        self.write_json("tools/verification/categories.json", inventory)
        self.input["planned_paths"] = ["deploy/runtime/Dockerfile", "deploy/runtime/compose.yaml", "deploy/yii2/Dockerfile"]
        self.write_json("change.json", self.input)
        result = self.plan()
        self.assertEqual(0, result.returncode, "INTENDED_RED: deployment must have an explicit verification boundary: " + result.stderr)
        plan = json.loads((self.root / "plan.json").read_text())
        selected = {item["path"]: item["name"] for item in plan["boundaries"]}
        for relative in self.input["planned_paths"]:
            self.assertEqual("dependency-or-runtime", selected[relative])
        self.assertTrue({"unit", "governance"}.issubset(plan["required_categories"]))
        self.assertIn({"argv": ["make", "test"], "phase": "integration", "rationale": "mandatory full CI for code, test, policy or unknown impact"}, plan["commands"])

    def test_git_snapshot_includes_committed_staged_unstaged_untracked_and_deleted(self):
        self.git("add", "app/PilotHttp/Action.php")
        self.git("commit", "-qm", "committed change")
        (self.root / "app/PilotHttp/Staged.php").write_text("x")
        self.git("add", "app/PilotHttp/Staged.php")
        (self.root / "app/PilotHttp/Untracked.php").write_text("x")
        (self.root / "tests/InstallationProcess/action_001_test.php").unlink()
        self.assertEqual(0, self.plan().returncode)
        actual = {(x["path"], x["status"]) for x in json.loads((self.root / "plan.json").read_text())["paths"]["actual"]}
        self.assertIn(("app/PilotHttp/Action.php", "committed"), actual)
        self.assertIn(("app/PilotHttp/Staged.php", "staged"), actual)
        self.assertIn(("app/PilotHttp/Untracked.php", "untracked"), actual)
        self.assertIn(("tests/InstallationProcess/action_001_test.php", "deleted"), actual)

    def test_unknown_ambiguous_empty_and_bad_mappings_fail_closed(self):
        cases = []
        bad = dict(self.input, planned_paths=["mystery/file.xyz"]); cases.append((bad, self.policy))
        ambiguous = json.loads(json.dumps(self.policy)); ambiguous["boundaries"].append({"name": "overlap", "patterns": ["app/**"], "categories": ["unit"], "tests": []}); cases.append((self.input, ambiguous))
        cases.append((dict(self.input, acceptances=[]), self.policy))
        duplicate = json.loads(json.dumps(self.input)); duplicate["acceptances"].append(duplicate["acceptances"][0]); cases.append((duplicate, self.policy))
        omitted = json.loads(json.dumps(self.input)); omitted["acceptances"][0]["tests"] = []; cases.append((omitted, self.policy))
        no_category_command = json.loads(json.dumps(self.policy)); no_category_command["category_argv"].pop("integration"); cases.append((self.input, no_category_command))
        unsafe = json.loads(json.dumps(self.input)); unsafe["acceptances"][0]["tests"] = ["../escape.php"]; cases.append((unsafe, self.policy))
        runtime = json.loads(json.dumps(self.input)); runtime["acceptances"][0]["tests"] = ["tests/InstallationProcess/action.rb"]; cases.append((runtime, self.policy))
        for change, policy in cases:
            with self.subTest(change=change, boundaries=len(policy["boundaries"])):
                self.write_json("change.json", change); self.write_json(".quality-graph/verification-policy.json", policy)
                result = self.plan()
                self.assertNotEqual(0, result.returncode)
                self.assertIn("SETUP_FAILURE", result.stderr)
                self.assertFalse((self.root / "plan.json").exists())
        (self.root / "change.json").write_text('{"change":"a","change":"b"}')
        result = self.plan(); self.assertNotEqual(0, result.returncode); self.assertIn("duplicate JSON key", result.stderr)

    def test_check_rejects_every_bound_drift_and_plan_tampering(self):
        mutations = [
            lambda: (self.root / "change.json").write_text(json.dumps(dict(self.input, change="changed"))),
            lambda: (self.root / "quality-graph.yml").write_text("changed\n"),
            lambda: (self.root / "tools/verification/categories.json").write_text("{}\n"),
            lambda: (self.root / ".quality-graph/verification-policy.json").write_text(json.dumps(dict(self.policy, version=2))),
            lambda: (self.root / "specs/CHANGE-VERIFICATION-001.md").write_text("changed\n"),
            lambda: (self.root / "tools/delivery/change-verification.py").write_text((self.root / "tools/delivery/change-verification.py").read_text() + "\n# harmless source drift\n"),
            lambda: (self.root / "app/PilotHttp/New.php").write_text("new\n"),
            lambda: (self.root / "app/PilotHttp/Action.php").write_text("different working content\n"),
            lambda: self.git("mv", "app/PilotHttp/Action.php", "app/PilotHttp/Renamed.php"),
            lambda: (self.root / "app/PilotHttp/Action.php").unlink(),
            lambda: (self.root / "specs/EXAMPLE-001.md").write_text("changed product acceptance\n"),
        ]
        for mutate in mutations:
            with self.subTest(mutation=mutate):
                self.setUp(); self.assertEqual(0, self.plan().returncode); mutate()
                result = self.cli("check", "--plan", "plan.json")
                self.assertNotEqual(0, result.returncode); self.assertIn("SETUP_FAILURE", result.stderr)
        self.setUp(); self.assertEqual(0, self.plan().returncode)
        plan = json.loads((self.root / "plan.json").read_text()); plan["commands"].pop(); self.write_json("plan.json", plan)
        result = self.cli("check", "--plan", "plan.json"); self.assertNotEqual(0, result.returncode)

    def test_run_uses_argv_without_shell_and_preserves_red(self):
        future = "tests/InstallationProcess/future_001_test.php"
        self.input["acceptances"][0]["tests"] = [future]
        self.write_json("change.json", self.input)
        self.assertEqual(0, self.plan().returncode)
        (self.root / future).write_text("fixture")
        # File creation makes the plan stale and must block before interpreter invocation.
        trace = self.root / "trace"
        external = tempfile.TemporaryDirectory(prefix="change-verification-bin-"); self.addCleanup(external.cleanup)
        bindir = Path(external.name)
        runner = bindir / "php"; runner.write_text("#!/bin/sh\nprintf '%s\\n' \"$1\" >> \"$TRACE\"\nexit 7\n"); runner.chmod(0o700)
        env = dict(os.environ, PATH=str(bindir) + os.pathsep + os.environ["PATH"], TRACE=str(trace))
        result = self.cli("run", "--plan", "plan.json", "--phase", "focused", env=env)
        self.assertNotEqual(0, result.returncode); self.assertFalse(trace.exists())
        self.assertEqual(0, self.plan().returncode)
        result = self.cli("run", "--plan", "plan.json", "--phase", "focused", env=env)
        self.assertEqual(7, result.returncode, "child RED is preserved")
        self.assertEqual([future], trace.read_text().splitlines())

    def test_http_persistence_and_money_boundaries_add_concrete_commands(self):
        self.input["planned_paths"] = ["app/PilotHttp/Action.php", "app/Infrastructure/Persistence/Store.php", "app/Otiz/Money.php"]
        self.write_json("change.json", self.input)
        result = self.plan(); self.assertEqual(0, result.returncode, result.stderr)
        argvs = [x["argv"] for x in json.loads((self.root / "plan.json").read_text())["commands"]]
        for required in [
            ["php", "tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php"],
            ["php", "tests/Runtime/runtime_storage_001_test.php"],
            ["php", "tests/Otiz/snapshot_publication_001_test.php"],
        ]:
            self.assertIn(required, argvs)

    def test_exact_category_binding_cannot_be_masked_by_another_boundary(self):
        inventory = json.loads((self.root / "tools/verification/categories.json").read_text())
        inventory["tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php"] = "governance"
        self.write_json("tools/verification/categories.json", inventory)
        self.input["planned_paths"].append(".quality-graph/other-policy.json")
        self.write_json("change.json", self.input)
        result = self.plan()
        self.assertNotEqual(0, result.returncode, "INTENDED_RED aggregate category masks wrong HTTP binding")
        self.assertIn("SETUP_FAILURE", result.stderr)
        self.assertFalse((self.root / "plan.json").exists())

    def test_shipped_policy_has_concrete_boundaries_and_bounded_governance_focus(self):
        expected = {
            "app/PilotHttp/Action.php": ["php", "tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php"],
            "app/Infrastructure/Persistence/Store.php": ["php", "tests/Runtime/runtime_storage_001_test.php"],
            "app/Otiz/Money.php": ["php", "tests/Otiz/snapshot_publication_001_test.php"],
        }
        for planned, required in expected.items():
            with self.subTest(planned=planned):
                self.setUp()
                shutil.copy2(ROOT / ".quality-graph/verification-policy.json", self.root / ".quality-graph/verification-policy.json")
                shutil.copy2(ROOT / "tools/verification/categories.json", self.root / "tools/verification/categories.json")
                (self.root / "app/PilotHttp/Action.php").write_text("before\n")
                self.input["planned_paths"] = [planned]
                self.write_json("change.json", self.input)
                result = self.plan(); self.assertEqual(0, result.returncode, result.stderr)
                argvs = [x["argv"] for x in json.loads((self.root / "plan.json").read_text())["commands"]]
                self.assertIn(required, argvs)
        self.setUp()
        shutil.copy2(ROOT / ".quality-graph/verification-policy.json", self.root / ".quality-graph/verification-policy.json")
        shutil.copy2(ROOT / "tools/verification/categories.json", self.root / "tools/verification/categories.json")
        (self.root / "app/PilotHttp/Action.php").write_text("before\n")
        planned = "tests/Verification/change_verification_001_test.py"
        self.input["planned_paths"] = [planned]
        self.input["acceptances"][0]["tests"] = [planned]
        self.write_json("change.json", self.input)
        result = self.plan(); self.assertEqual(0, result.returncode, result.stderr)
        argvs = [x["argv"] for x in json.loads((self.root / "plan.json").read_text())["commands"]]
        self.assertNotIn(["php", "tests/Runtime/runtime_storage_001_test.php"], argvs,
                         "INTENDED_RED governance-only focus must not require prepared MariaDB")

    def test_registered_acceptance_adds_its_inventory_category(self):
        shutil.copy2(ROOT / ".quality-graph/verification-policy.json", self.root / ".quality-graph/verification-policy.json")
        shutil.copy2(ROOT / "tools/verification/categories.json", self.root / "tools/verification/categories.json")
        (self.root / "app/PilotHttp/Action.php").write_text("before\n")
        self.input["planned_paths"] = ["app/IdentityAccess/Command.php"]
        self.input["acceptances"][0]["tests"] = ["tests/Runtime/runtime_storage_001_test.php"]
        self.write_json("change.json", self.input)
        result = self.plan()
        self.assertEqual(0, result.returncode, "INTENDED_RED mapped integration acceptance rejected: " + result.stderr)
        plan = json.loads((self.root / "plan.json").read_text())
        self.assertIn("integration", plan["required_categories"])
        self.assertIn(["php", "tests/Runtime/runtime_storage_001_test.php"], [x["argv"] for x in plan["commands"]])

    def test_changed_registered_test_schedules_itself(self):
        shutil.copy2(ROOT / ".quality-graph/verification-policy.json", self.root / ".quality-graph/verification-policy.json")
        shutil.copy2(ROOT / "tools/verification/categories.json", self.root / "tools/verification/categories.json")
        (self.root / "app/PilotHttp/Action.php").write_text("before\n")
        changed = "tests/Verification/verification_inventory_001_test.py"
        target = self.root / changed
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text("# changed registered verifier\n")
        self.git("add", changed)
        self.git("commit", "-qm", "change registered verifier")
        self.input["planned_paths"] = [changed]
        self.input["acceptances"][0]["tests"] = ["tests/Verification/future_acceptance_001_test.py"]
        self.write_json("change.json", self.input)
        result = self.plan(); self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads((self.root / "plan.json").read_text())
        argv = ["python3", changed]
        self.assertIn(argv, [x["argv"] for x in plan["commands"]],
                      "INTENDED_RED changed registered verifier omitted")
        self.assertEqual(1, [x["argv"] for x in plan["commands"]].count(argv))


if __name__ == "__main__":
    unittest.main(verbosity=2)
