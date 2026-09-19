#!/usr/bin/env python3
"""REGISTERED-FOCUSED-BOOTSTRAP-001 executable bootstrap contract."""
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile
import unittest
import sys

ROOT = Path(__file__).resolve().parents[2]
TARGET = "tests/Yii2/yii2_main_navigation_001_test.php"
CHANGE = "openspec/changes/unify-yii-main-navigation/verification-input.json"


class RegisteredYii2FocusedBootstrapTest(unittest.TestCase):
    maxDiff = None

    def plan(self):
        path = ROOT / "tools/delivery/change-verification.py"
        specification = importlib.util.spec_from_file_location("rfb_change_verification", path)
        module = importlib.util.module_from_spec(specification)
        specification.loader.exec_module(module)
        return module.build("origin/main", CHANGE)

    def current_plan(self):
        path = ROOT / "tools/delivery/change-verification.py"
        specification = importlib.util.spec_from_file_location("rfb_current_verification", path)
        module = importlib.util.module_from_spec(specification)
        specification.loader.exec_module(module)
        return module.build("origin/main", "openspec/changes/bootstrap-registered-yii2-focused-check/verification-input.json")

    @staticmethod
    def profile_result(record):
        stderr = Path(record["stderr_path"]).read_text()
        matches = re.findall(r"^RUN_IN_PROFILE_RESULT (\{[^\n]+\})$", stderr, re.MULTILINE)
        if len(matches) != 1:
            raise AssertionError("exactly one structured profile result required")
        return json.loads(matches[0])

    def fake_environment(self, mode="ok"):
        temporary = tempfile.TemporaryDirectory(prefix="rfb-fake-")
        root = Path(temporary.name)
        binary = root / "bin"
        binary.mkdir()
        state = root / "state.jsonl"
        docker = binary / "docker"
        docker.write_text(r'''#!/usr/bin/env python3
import hashlib,json,os,sys
args=sys.argv[1:]
state=os.environ["RFB_STATE"]
with open(state,"a") as stream: stream.write(json.dumps(args)+"\n")
mode=os.environ.get("RFB_MODE","ok")
if args[:1] in (["version"],["info"]):
    if mode == "docker-unavailable":
        print("daemon unavailable",file=sys.stderr);raise SystemExit(70)
    print("fake docker");raise SystemExit(0)
if args[:1] == ["build"]:
    if mode in ("docker-unavailable","dependency-failure"):
        print("dependency build failed",file=sys.stderr);raise SystemExit(71)
    values={}
    for i,value in enumerate(args[:-1]):
        if value == "--build-arg" and "=" in args[i+1]:
            key,item=args[i+1].split("=",1);values[key]=item
    open(state+".image","w").write(json.dumps(values));raise SystemExit(0)
if args[:2] == ["image","inspect"]:
    template=args[args.index("--format")+1] if "--format" in args else ""
    values=json.load(open(state+".image"))
    if "composer-lock-sha256" in template: print(values["COMPOSER_LOCK_SHA256"])
    elif "executable-source" in template: print(values["EXECUTABLE_SOURCE"])
    else: print("sha256:"+"1"*64)
    raise SystemExit(0)
if args[:1] == ["compose"]:
    if "config" in args:
        project=args[args.index("--project-name")+1] if "--project-name" in args else "fmonitor2-test"
        print(json.dumps({"networks":{"default":{"name":project+"_default"}}}));raise SystemExit(0)
    if "up" in args:
        if mode == "db-start-failure":
            print("database start failed",file=sys.stderr);raise SystemExit(72)
        raise SystemExit(0)
    if "exec" in args:
        if mode == "db-readiness-failure":
            print("database health timeout",file=sys.stderr);raise SystemExit(73)
        raise SystemExit(0)
    if "down" in args: raise SystemExit(0)
if args[:2] == ["network","inspect"]: raise SystemExit(0)
if args[:1] == ["run"]:
    calls=[json.loads(line) for line in open(state).read().splitlines()[:-1]]
    compose=next(call for call in calls if call[:1]==["compose"] and "--project-name" in call)
    expected_network=compose[compose.index("--project-name")+1]+"_default"
    required=["--network",expected_network,"--env","FMONITOR_TEST_DB_HOST=test-db","--env","FMONITOR_TEST_DB_PORT=3306"]
    for value in required:
        if value not in args: print("missing "+value,file=sys.stderr);raise SystemExit(73)
    if "FMONITOR_SHLZ_CSS_PATH=/shlz-ui/packages/styles/dist/shlz.css" not in args:
        print("missing pinned css path",file=sys.stderr);raise SystemExit(74)
    marker=os.environ["RFB_CHILD_MARKER"]
    open(marker,"w").write(json.dumps(args))
    if mode == "slow-child":
        import time;time.sleep(10)
    if mode == "test-503":
        print("HTTP 503 from tested application",file=sys.stderr);raise SystemExit(8)
    if mode == "intended-red":
        print("INTENDED_RED reviewer wrapped navigation",file=sys.stderr);raise SystemExit(8)
    raise SystemExit(0)
raise SystemExit(75)
''')
        docker.chmod(0o755)
        marker = root / "child.json"
        environment = dict(os.environ)
        environment.update({
            "PATH": str(binary) + os.pathsep + environment["PATH"],
            "RFB_STATE": str(state), "RFB_MODE": mode,
            "RFB_CHILD_MARKER": str(marker),
        })
        return temporary, environment, state, marker

    def reviewer_evidence(self, mode):
        plan = self.plan()
        command = next(item for item in plan["commands"]
                       if item.get("id") == "acceptance:yii2_main_navigation_001_test")
        temporary, environment, _, _ = self.fake_environment(mode)
        self.addCleanup(temporary.cleanup)
        evidence_home = Path(temporary.name) / "evidence"
        environment["FMONITOR_HARNESS_HOME"] = str(evidence_home)
        argv = ["python3", "tools/delivery/harness.py", "run",
                "--command-id", command["id"], "--purpose", command["purpose"],
                "--acceptance-id", "A1-A5-complete-main-navigation-matrix",
                "--command-environment", json.dumps(command["environment"], separators=(",", ":"))]
        if mode == "intended-red":
            argv += ["--intended-red", "INTENDED_RED reviewer wrapped navigation"]
        result = subprocess.run([*argv, "--", *command["argv"]], cwd=ROOT,
                                env=environment, text=True, capture_output=True)
        summary = json.loads(result.stdout)
        record = Path(summary["record_path"])
        self.assertEqual("INTENDED_RED" if mode == "intended-red" else "GREEN",
                         json.loads(record.read_text())["outcome"])
        return environment, record

    def reviewer_prepare(self, gate, environment, *evidence):
        command = ["python3", "tools/delivery/harness.py", "prepare",
                   "--input", CHANGE, "--base", "origin/main",
                   "--role", "reviewer", "--gate", str(gate)]
        for path in evidence:
            command += ["--evidence", str(path)]
        return subprocess.run(command, cwd=ROOT, env=environment,
                              text=True, capture_output=True)

    def run_launcher(self, mode="ok"):
        temporary, environment, state, marker = self.fake_environment(mode)
        self.addCleanup(temporary.cleanup)
        launcher = ROOT / "tools/delivery/run-in-profile"
        result = subprocess.run(
            [str(launcher), "browser", "--with-services", "php", TARGET], cwd=ROOT,
            env=environment, text=True, capture_output=True,
        )
        calls = [json.loads(line) for line in state.read_text().splitlines()] if state.exists() else []
        return result, calls, marker

    def test_prepared_plan_uses_exact_focused_route_and_stable_identity(self):
        plan = self.plan()
        commands = [item for item in plan["commands"] if item["argv"][-1] == TARGET]
        self.assertEqual(1, len(commands),
                         "INTENDED_RED RFB001-E exact test has duplicate host/container routes")
        command = commands[0]
        self.assertEqual(
            ["tools/delivery/run-in-profile", "browser", "--with-services", "php", TARGET], command["argv"],
            "INTENDED_RED RFB001-E prepared command bypasses focused bootstrap",
        )
        self.assertEqual("acceptance:yii2_main_navigation_001_test", command["id"])
        self.assertEqual("acceptance", command["purpose"])
        self.assertEqual(["mariadb"], command["environment"]["services"])
        self.assertEqual({"acceptance mapping", "changed registered test"},
                         set(command.get("rationales", [command.get("rationale")])),
                         "all selection reasons must merge onto the one route")
        self.assertEqual("local", command.get("execution", "local"),
                         "local execution priority must survive deduplication")

        own = self.current_plan()
        own_commands = [item for item in own["commands"]
                        if item["argv"][-1] == "tests/Verification/registered_yii2_focused_bootstrap_001_test.py"]
        self.assertEqual(1, len(own_commands), "current package acceptance command missing")
        self.assertNotIn("id", own_commands[0], "unrouted acceptance identity changed")
        self.assertNotIn("purpose", own_commands[0], "unrouted acceptance purpose changed")
        self.assertNotEqual("tools/delivery/run-in-profile", own_commands[0]["argv"][0],
                            "generic bootstrap diagnostic recursively wrapped")

        current_input = json.loads((ROOT / "openspec/changes/bootstrap-registered-yii2-focused-check/verification-input.json").read_text())
        self.assertEqual(["tests/Verification/registered_yii2_focused_bootstrap_001_test.py"],
                         current_input["acceptances"][0]["tests"])
        ordinary = [item for item in plan["commands"]
                    if item["argv"][-1] == "tests/Runtime/runtime_storage_001_test.php"]
        self.assertTrue(ordinary, "ordinary integration control command absent")
        for item in ordinary:
            self.assertNotEqual("tools/delivery/run-in-profile", item["argv"][0],
                                "generic integration command was promoted to heavy profile")
        policy = json.loads((ROOT / ".quality-graph/verification-policy.json").read_text())
        self.assertEqual({TARGET: "browser"}, policy.get("focused_command_profiles"),
                         "unregistered commands can enter heavy focused profile")

    def test_reviewer_prepare_normalizes_wrapped_navigation_evidence_exactly(self):
        red_environment, red = self.reviewer_evidence("intended-red")
        gate3 = self.reviewer_prepare(3, red_environment, red)
        self.assertEqual(0, gate3.returncode,
                         "INTENDED_RED Gate 3 rejected exact wrapped RED: " + gate3.stderr)

        missing = self.reviewer_prepare(3, red_environment)
        self.assertNotEqual(0, missing.returncode, "Gate 3 accepted missing evidence")

        substituted = red.with_name("substituted-" + red.name)
        altered = json.loads(red.read_text())
        altered["argv"][0] = "/attacker/tools/delivery/run-in-profile"
        substituted.write_text(json.dumps(altered))
        replacement = self.reviewer_prepare(3, red_environment, substituted)
        self.assertNotEqual(0, replacement.returncode,
                            "Gate 3 equated a different command by canonical suffix")

        sys.path.insert(0, str(ROOT / "tools/delivery"))
        try:
            import harness_context
            self.assertNotEqual(
                harness_context._normalized_argv(["/attacker/python3", TARGET]),
                harness_context._normalized_argv(["python3", TARGET]),
                "arbitrary interpreter path was canonicalized by basename",
            )
        finally:
            sys.path.pop(0)

        green_environment, green = self.reviewer_evidence("ok")
        gate5 = self.reviewer_prepare(5, green_environment, green)
        self.assertEqual(0, gate5.returncode,
                         "INTENDED_RED Gate 5 rejected exact wrapped GREEN: " + gate5.stderr)

        wrong_gate5 = self.reviewer_prepare(5, red_environment, red)
        self.assertNotEqual(0, wrong_gate5.returncode,
                            "Gate 5 accepted RED instead of required GREEN")

    def test_launcher_rejects_invalid_entry_without_child_execution(self):
        launcher = ROOT / "tools/delivery/run-in-profile"
        for argv in ([str(launcher), "browser"], [str(launcher), "unknown", "true"]):
            result = subprocess.run(argv, cwd=ROOT, text=True, capture_output=True)
            self.assertEqual(2, result.returncode)
            self.assertIn("usage:", result.stderr)

    def test_launcher_owns_only_its_database_and_runs_exact_selected_test(self):
        result, calls, marker = self.run_launcher()
        self.assertEqual(0, result.returncode, "INTENDED_RED RFB001-A launcher did not complete owned lifecycle")
        compose = [call for call in calls if call[:1] == ["compose"]]
        projects = [call[call.index("--project-name") + 1] for call in compose]
        self.assertTrue(projects, "INTENDED_RED RFB001-A no owned Compose project")
        self.assertEqual(1, len(set(projects)), "one project identity across lifecycle")
        self.assertNotEqual("fmonitor2-test", projects[0], "must not own/reset shared default project")
        self.assertTrue(any("up" in call and "--wait" in call and call[-1] == "test-db" for call in compose),
                        "INTENDED_RED RFB001-D stopped DB is not started and awaited")
        self.assertTrue(any("down" in call and "--volumes" in call for call in compose),
                        "INTENDED_RED RFB001-D owned resources are not cleaned")
        self.assertTrue(marker.exists(), "selected child was not executed")
        child = json.loads(marker.read_text())
        index = child.index("run-in-profile")
        self.assertEqual(["php", TARGET], child[index + 1:], "launcher broadened selected command")

        second, second_calls, _ = self.run_launcher()
        self.assertEqual(0, second.returncode, "INTENDED_RED RFB001-B repeated launcher failed")
        second_compose = [call for call in second_calls if call[:1] == ["compose"]]
        second_project = second_compose[0][second_compose[0].index("--project-name") + 1]
        self.assertNotEqual(projects[0], second_project,
                            "INTENDED_RED RFB001-D sequential runs reuse one project identity")
        for call in compose + second_compose:
            self.assertNotIn("--remove-orphans", call)
            self.assertNotIn("fmonitor2-test", call)

    def test_setup_failures_name_stage_and_never_execute_assertions(self):
        for mode, stage in (("docker-unavailable", "docker"),
                            ("dependency-failure", "dependencies"),
                            ("db-start-failure", "db_start"),
                            ("db-readiness-failure", "db_readiness")):
            with self.subTest(mode=mode):
                result, calls, marker = self.run_launcher(mode)
                self.assertNotEqual(0, result.returncode)
                self.assertFalse(marker.exists(), "acceptance child ran after setup failure")
                self.assertTrue("SETUP_FAILURE" in result.stderr,
                                "INTENDED_RED RFB001-D setup failure is not classified")
                self.assertTrue("stage=" + stage in result.stderr,
                                "INTENDED_RED RFB001-D setup stage is not explicit")
                self.assertNotIn("INTENDED_RED", result.stderr)
                if mode in ("db-start-failure", "db-readiness-failure"):
                    compose = [call for call in calls if call[:1] == ["compose"]]
                    self.assertTrue(any("down" in call for call in compose),
                                    "owned DB is not cleaned after readiness failure")

    def test_harness_retains_setup_stage_and_does_not_relabel_http_503(self):
        for mode, expected in (("dependency-failure", "SETUP_FAILURE"),
                               ("db-start-failure", "SETUP_FAILURE"),
                               ("db-readiness-failure", "SETUP_FAILURE"),
                               ("test-503", "REGRESSION_FAILURE")):
            with self.subTest(mode=mode):
                temporary, environment, state, marker = self.fake_environment(mode)
                self.addCleanup(temporary.cleanup)
                result = subprocess.run(
                    ["python3", "tools/delivery/harness.py", "run", "--",
                     "tools/delivery/run-in-profile", "browser", "--with-services", "php", TARGET],
                    cwd=ROOT, env=environment, text=True, capture_output=True,
                )
                summary = json.loads(result.stdout)
                record = json.loads(Path(summary["record_path"]).read_text())
                self.assertEqual(expected, record["outcome"])
                self.assertTrue(Path(record["stderr_path"]).is_file())
                self.assertTrue(Path(record["stdout_path"]).is_file())
                self.assertEqual(str(ROOT), record["cwd"])
                if expected == "SETUP_FAILURE":
                    self.assertIn("stage=", Path(record["stderr_path"]).read_text())
                    self.assertFalse(marker.exists())
                else:
                    self.assertEqual(8, record["exit_code"], "child assertion exit was not preserved")
                    self.assertTrue("HTTP 503" in Path(record["stderr_path"]).read_text(),
                                    "ordinary HTTP 503 was lost or reclassified")

        temporary, environment, state, _ = self.fake_environment("slow-child")
        self.addCleanup(temporary.cleanup)
        interrupted = subprocess.run(
            ["python3", "tools/delivery/harness.py", "run", "--timeout", "5", "--",
             "tools/delivery/run-in-profile", "browser", "--with-services", "php", TARGET],
            cwd=ROOT, env=environment, text=True, capture_output=True,
        )
        interrupted_record = json.loads(Path(json.loads(interrupted.stdout)["record_path"]).read_text())
        self.assertEqual("INTERRUPTED", interrupted_record["outcome"])
        calls = [json.loads(line) for line in state.read_text().splitlines()]
        self.assertTrue(any(call[:1] == ["compose"] and "down" in call for call in calls),
                        "INTENDED_RED RFB001-D interruption skipped owned cleanup")

    def test_real_clean_worktree_first_repeat_and_uncommitted_candidate(self):
        plan = self.plan()
        command = next(item for item in plan["commands"]
                       if item.get("id") == "acceptance:yii2_main_navigation_001_test")
        with tempfile.TemporaryDirectory(prefix="rfb-clean-parent-") as parent:
            checkout = Path(parent) / "candidate"
            subprocess.run(["git", "worktree", "add", "--detach", str(checkout), "HEAD"],
                           cwd=ROOT, check=True, capture_output=True, text=True)
            self.addCleanup(lambda: subprocess.run(
                ["git", "worktree", "remove", "--force", str(checkout)], cwd=ROOT,
                capture_output=True, text=True))
            self.assertFalse((checkout / "vendor").exists())
            self.assertFalse((checkout / "node_modules").exists())
            self.assertFalse((Path(parent) / "shlz-ui").exists())
            foreign = "rfb-foreign-" + os.urandom(5).hex()
            subprocess.run(["docker", "network", "create", foreign], check=True,
                           capture_output=True, text=True)
            self.addCleanup(lambda: subprocess.run(["docker", "network", "rm", foreign],
                                                    capture_output=True, text=True))

            def execute():
                return subprocess.run(
                    ["python3", "tools/delivery/harness.py", "run",
                     "--command-id", command["id"], "--purpose", command["purpose"],
                     "--acceptance-id", "Y2MN001-A-H", "--command-environment",
                     json.dumps(command["environment"], separators=(",", ":")),
                     "--", *command["argv"]], cwd=checkout, text=True, capture_output=True,
                )

            first = execute()
            self.assertEqual(0, first.returncode, first.stderr)
            first_record = json.loads(Path(json.loads(first.stdout)["record_path"]).read_text())
            second = execute()
            self.assertEqual(0, second.returncode, second.stderr)
            second_record = json.loads(Path(json.loads(second.stdout)["record_path"]).read_text())
            self.assertEqual(first_record["command_id"], second_record["command_id"])
            self.assertEqual(first_record["acceptance_id"], second_record["acceptance_id"])
            first_profile = self.profile_result(first_record)
            second_profile = self.profile_result(second_record)
            self.assertEqual(first_profile["image_digest"], second_profile["image_digest"],
                             "RFB001-B unchanged dependency image/layers were not reused")
            self.assertEqual(first_profile["source_digest"], second_profile["source_digest"])
            self.assertEqual(0, subprocess.run(["docker", "network", "inspect", foreign],
                                               capture_output=True).returncode,
                             "foreign resource was removed by owned cleanup")

            source = checkout / "app/YiiRuntime/MainNavigation.php"
            original = source.read_text()
            self.assertIn("Объекты монтажа", original)
            source.write_text(original.replace("Объекты монтажа", "Контролируемый дефект", 1))
            mutated = execute()
            self.assertNotEqual(0, mutated.returncode)
            mutated_record = json.loads(Path(json.loads(mutated.stdout)["record_path"]).read_text())
            self.assertEqual("REGRESSION_FAILURE", mutated_record["outcome"])
            self.assertIn("exact permitted MAIN membership", Path(mutated_record["stderr_path"]).read_text())
            self.assertNotEqual(first_record["source"], mutated_record["source"])
            source.write_text(original)
            restored = execute()
            self.assertEqual(0, restored.returncode, restored.stderr)

    def test_fixture_uses_only_container_asset_contract(self):
        fixture = (ROOT / "tests/Yii2/Yii2AuthFixture.php").read_text()
        self.assertIn("getenv('FMONITOR_SHLZ_CSS_PATH')", fixture,
                      "INTENDED_RED RFB001-C fixture still derives sibling shlz-ui")
        self.assertNotIn("dirname($this->repositoryRoot) . '/shlz-ui", fixture)
        dockerfile = (ROOT / "tools/delivery/Dockerfile.focused-checks").read_text()
        self.assertIn("COPY --from=shlz-ui /shlz-ui/packages/styles/dist/shlz.css", dockerfile)


if __name__ == "__main__":
    unittest.main()
