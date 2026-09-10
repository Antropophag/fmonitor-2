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
            shutil.copytree(ROOT / "tools/delivery", self.root / "tools/delivery", dirs_exist_ok=True)
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

    def test_registered_legacy_verifier_is_required_but_arbitrary_script_is_rejected(self):
        legacy = "rapid-pilot/verify-calendar-projections.php"
        (self.root / "rapid-pilot").mkdir()
        (self.root / legacy).write_text("<?php // registered verifier\n")
        self.policy["boundaries"].append({"name": "legacy", "patterns": ["rapid-pilot/**"], "categories": ["integration"], "tests": []})
        self.write_json(".quality-graph/verification-policy.json", self.policy)
        inventory = json.loads((self.root / "tools/verification/categories.json").read_text())
        inventory[legacy] = "integration"
        self.write_json("tools/verification/categories.json", inventory)
        result = self.plan()
        self.assertEqual(0, result.returncode, "INTENDED_RED: registered legacy verifier must remain in the plan: " + result.stderr)
        commands = json.loads((self.root / "plan.json").read_text())["commands"]
        self.assertEqual(1, sum(command["argv"] == ["php", legacy] for command in commands))
        self.assertIn({"argv": ["php", legacy], "phase": "focused", "rationale": "changed registered test"}, commands)
        arbitrary = "rapid-pilot/arbitrary.php"
        (self.root / arbitrary).write_text("<?php // not registered\n")
        self.input["acceptances"][0]["tests"] = [arbitrary]
        self.write_json("change.json", self.input)
        result = self.plan()
        self.assertNotEqual(0, result.returncode, "an unregistered arbitrary script is not a test")

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

    def test_gate3_expectations_are_part_of_existing_mapping_and_strict(self):
        acceptance=self.input['acceptances'][0]
        acceptance['gate3_expected']={acceptance['tests'][0]:'GREEN'}
        self.write_json('change.json',self.input)
        result=self.plan()
        self.assertEqual(0,result.returncode,'INTENDED_RED expected outcomes cannot be mapped: '+result.stderr)
        plan=json.loads((self.root/'plan.json').read_text())
        self.assertEqual(acceptance['gate3_expected'],plan['acceptances'][0]['gate3_expected'])
        acceptance['gate3_expected']={acceptance['tests'][0]:'INTENDED_RED'}
        self.write_json('change.json',self.input)
        self.assertNotEqual(0,self.cli('check','--plan','plan.json').returncode)
        self.assertEqual(0,self.cli('refresh','--plan','plan.json').returncode)
        for bad in [{}, {'tests/other.php':'GREEN'}, {acceptance['tests'][0]:'APPROVED'}, []]:
            acceptance['gate3_expected']=bad;self.write_json('change.json',self.input)
            self.assertNotEqual(0,self.plan().returncode,'malformed intent cannot bypass RED')

    def test_six_short_success_logs_have_compact_diagnostic_delivery(self):
        (self.root/'app/PilotHttp/Action.php').write_text('before\n')
        files=['tests/InstallationProcess/log_'+str(i)+'_test.py' for i in range(6)]
        log=''.join('case %02d checked ... ok\n'%i for i in range(50))+'Ran 50 checks\nOK\n'
        for path in files:(self.root/path).write_text('print('+repr(log)+',end="")\n')
        self.input['planned_paths']=files
        self.input['acceptances'][0]['tests']=files
        self.write_json('change.json',self.input)
        for boundary in self.policy['boundaries']:
            if boundary['name']=='tests':boundary['categories']=['governance']
        self.policy['category_argv']['governance']=[['python3',files[0]]]
        self.write_json('.quality-graph/verification-policy.json',self.policy)
        self.assertEqual(0,self.plan().returncode)
        external=tempfile.TemporaryDirectory(prefix='short-output-evidence-');self.addCleanup(external.cleanup)
        env=dict(os.environ,FMONITOR_HARNESS_HOME=external.name)
        result=self.cli('run','--plan','plan.json','--phase','focused','--diagnostic',env=env)
        self.assertEqual(0,result.returncode,result.stderr)
        results=json.loads(result.stdout)['results']
        self.assertEqual(6,len(results))
        self.assertTrue(all(r['outcome']=='GREEN' for r in results))
        records=[json.loads(Path(r['record_path']).read_text()) for r in results]
        raw=sum(r['output_bytes'] for r in records)
        self.assertEqual(6*len(log.encode()),raw)
        self.assertLess(len(result.stdout.encode()),raw,'INTENDED_RED metadata/success logs inflate delivered output')
        for r in records:self.assertEqual(log,Path(r['stdout_path']).read_text())

    def test_harness_diagnostic_retains_all_selected_results(self):
        self.assertEqual(0, self.plan().returncode)
        external = tempfile.TemporaryDirectory(prefix='diagnostic-executables-'); self.addCleanup(external.cleanup)
        directory = Path(external.name)
        runner = directory / 'php'
        runner.write_text('#!/bin/sh\necho "assertion failure: $1"\ncase "$1" in *action_001*) exit 7;; *global_calls*) exit 8;; *) exit 0;; esac\n')
        runner.chmod(0o700)
        env = dict(os.environ, PATH=str(directory)+os.pathsep+os.environ['PATH'], FMONITOR_HARNESS_HOME=str(directory/'evidence'))
        result = self.cli('run', '--plan', 'plan.json', '--phase', 'focused', '--diagnostic', env=env)
        self.assertNotEqual(0, result.returncode)
        self.assertTrue(result.stdout.strip().startswith('{'), 'INTENDED_RED structured diagnostic missing: '+result.stderr)
        inventory = json.loads(result.stdout)['results']
        self.assertEqual([7,8,0], [r['exit_code'] for r in inventory])
        self.assertEqual(['REGRESSION_FAILURE','REGRESSION_FAILURE','GREEN'], [r['outcome'] for r in inventory])
        self.assertIn('action_001_test.php', result.stdout, 'INTENDED_RED diagnostic results missing')
        self.assertIn('pilot_http_auth_001_global_calls_test.php', result.stdout)
        self.assertIn('runtime_storage_001_test.php', result.stdout)
        records = [json.loads(p.read_text()) for p in (directory/'evidence').rglob('*.json')]
        checks = [r for r in records if 'exit_code' in r and 'argv' in r]
        self.assertEqual([7, 8, 0], [r['exit_code'] for r in sorted(checks, key=lambda r:r['started_at'])])

    def test_harness_refresh_and_confirmed_consumers(self):
        consumers = {
            'tests/InstallationProcess/inspection_evidence_schema_001_test.php',
            'tests/Verification/characterize_inspection_photo_upload_001_test.php',
            'tests/Verification/characterize_inspection_photo_rejections_001_test.php',
            'tests/Verification/characterize_inspection_photo_revoke_001_test.php',
            'tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php',
        }
        shutil.copy2(ROOT / '.quality-graph/verification-policy.json', self.root / '.quality-graph/verification-policy.json')
        shutil.copy2(ROOT / 'tools/verification/categories.json', self.root / 'tools/verification/categories.json')
        (self.root / 'app/PilotHttp/Action.php').write_text('before\n')
        for owner in ['app/InspectionEvidence/MariaDbYiiChecklist.php', 'app/InspectionEvidence/MariaDbYiiChecklistAdmission.php', 'app/PilotHttp/ChecklistSync.php']:
            self.assertTrue((ROOT / owner).is_file(), 'consumer evidence must name a real owner')
            self.input['planned_paths'] = [owner]
            self.write_json('change.json', self.input)
            result = self.plan(); self.assertEqual(0, result.returncode, result.stderr)
            paths = {x['argv'][1] for x in json.loads((self.root / 'plan.json').read_text())['commands']}
            self.assertTrue(consumers <= paths, 'INTENDED_RED confirmed consumers absent: '+repr(consumers-paths))
        self.input['planned_paths'] = ['app/PilotHttp/LocalView.php']
        self.write_json('change.json', self.input)
        result = self.cli('refresh', '--plan', 'plan.json')
        self.assertEqual(0, result.returncode, 'INTENDED_RED automatic refresh absent: '+result.stderr)
        paths = {x['argv'][1] for x in json.loads((self.root / 'plan.json').read_text())['commands']}
        self.assertFalse(consumers & paths, 'local presentation must not inherit shared-owner checks')
        self.assertIn('obligations_changed', result.stdout)
        self.assertEqual(0, self.cli('check', '--plan', 'plan.json').returncode)

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
