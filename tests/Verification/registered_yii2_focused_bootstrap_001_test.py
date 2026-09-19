#!/usr/bin/env python3
"""REGISTERED-FOCUSED-BOOTSTRAP-001 executable bootstrap contract."""
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

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
        if mode == "db-readiness-failure":
            print("database health timeout",file=sys.stderr);raise SystemExit(72)
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

    def run_launcher(self, mode="ok"):
        temporary, environment, state, marker = self.fake_environment(mode)
        self.addCleanup(temporary.cleanup)
        launcher = ROOT / "tools/delivery/run-in-profile"
        result = subprocess.run(
            [str(launcher), "browser", "php", TARGET], cwd=ROOT,
            env=environment, text=True, capture_output=True,
        )
        calls = [json.loads(line) for line in state.read_text().splitlines()] if state.exists() else []
        return result, calls, marker

    @unittest.skipIf(os.environ.get("RFB_GATE3_RED") == "1", "Gate 3 records one exact intended RED")
    def test_prepared_plan_uses_exact_focused_route_and_stable_identity(self):
        plan = self.plan()
        commands = [item for item in plan["commands"] if item.get("id") == "acceptance:yii2_main_navigation_001_test"]
        self.assertEqual(1, len(commands), "INTENDED_RED RFB001-E registered command identity missing")
        command = commands[0]
        self.assertEqual(
            ["tools/delivery/run-in-profile", "browser", "php", TARGET], command["argv"],
            "INTENDED_RED RFB001-E prepared command bypasses focused bootstrap",
        )
        self.assertEqual("acceptance", command["purpose"])
        self.assertEqual(["mariadb"], command["environment"]["services"])

    @unittest.skipIf(os.environ.get("RFB_GATE3_RED") == "1", "Gate 3 records one exact intended RED")
    def test_launcher_owns_only_its_database_and_runs_exact_selected_test(self):
        result, calls, marker = self.run_launcher()
        self.assertEqual(0, result.returncode, result.stderr)
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

    @unittest.skipIf(os.environ.get("RFB_GATE3_RED") == "1", "Gate 3 records one exact intended RED")
    def test_setup_failures_name_stage_and_never_execute_assertions(self):
        for mode, stage in (("docker-unavailable", "docker"),
                            ("dependency-failure", "dependencies"),
                            ("db-readiness-failure", "db_readiness")):
            with self.subTest(mode=mode):
                result, calls, marker = self.run_launcher(mode)
                self.assertNotEqual(0, result.returncode)
                self.assertFalse(marker.exists(), "acceptance child ran after setup failure")
                self.assertIn("SETUP_FAILURE", result.stderr)
                self.assertIn("stage=" + stage, result.stderr,
                              "INTENDED_RED RFB001-D setup stage is not explicit")
                self.assertNotIn("INTENDED_RED", result.stderr)
                if mode == "db-readiness-failure":
                    compose = [call for call in calls if call[:1] == ["compose"]]
                    self.assertTrue(any("down" in call for call in compose),
                                    "owned DB is not cleaned after readiness failure")

    def test_fixture_uses_only_container_asset_contract(self):
        fixture = (ROOT / "tests/Yii2/Yii2AuthFixture.php").read_text()
        self.assertIn("getenv('FMONITOR_SHLZ_CSS_PATH')", fixture,
                      "INTENDED_RED RFB001-C fixture still derives sibling shlz-ui")
        self.assertNotIn("dirname($this->repositoryRoot) . '/shlz-ui", fixture)
        dockerfile = (ROOT / "tools/delivery/Dockerfile.focused-checks").read_text()
        self.assertIn("COPY --from=shlz-ui /shlz-ui/packages/styles/dist/shlz.css", dockerfile)


if __name__ == "__main__":
    unittest.main()
