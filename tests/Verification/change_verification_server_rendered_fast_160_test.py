"""FAST-SERVER-RENDERED-PRESENTATION-001: cases A-O via actual planner."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class ServerRenderedFast(unittest.TestCase):
    NEGATIVE = [
        "schema-migration", "persistence-write", "authoritative-current-state",
        "domain-application-mutation", "authorization-rbac-session-admission",
        "csrf-security", "offline-cache-service-worker-synchronization",
        "external-integration", "jobs-outbox-scheduler", "verification-admission-policy",
        "runtime-deployment-configuration", "product-spec-semantics",
    ]

    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="fast-server-rendered-160-")
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / "repo"
        self.root.mkdir()
        for path in ["tools/delivery", "tools/verification", ".quality-graph", "specs",
                     "app/YiiRuntime/Views", "app/YiiRuntime/Assets", "app/YiiRuntime/Controllers",
                     "app/IdentityAccess", "app/ReadModel", "app/CurrentState",
                     "app/Infrastructure/Persistence", "app/Security", "app/Integrations",
                     "app/Jobs", "app/DomainMutation", "migrations", "config", "routes",
                     "tests/Yii2", "tests/Verification", "tests/UI", "tests/AssignmentOrderComposition",
                     "tests/Otiz", "tests/Runtime", "tests/Jobs", "tests/InstallationProcess",
                     "tests/Deployment", "docs/operations", "openspec/changes/x"]:
            (self.root / path).mkdir(parents=True, exist_ok=True)
        shutil.copytree(ROOT / "tools/delivery", self.root / "tools/delivery", dirs_exist_ok=True)
        shutil.copy(ROOT / "tools/verification/inventory.py", self.root / "tools/verification/inventory.py")
        shutil.copy(ROOT / "tools/verification/ci.py", self.root / "tools/verification/ci.py")
        for path in ["AGENTS.md","PRODUCT.md","CONTEXT.md","docs/development-process.md","docs/operations/current-delivery-goal.md"]:
            shutil.copy(ROOT/path,self.root/path)
        (self.root / "quality-graph.yml").write_text("version: 1\n")
        (self.root / "specs/CHANGE-VERIFICATION-001.md").write_text("planner\n")
        (self.root / "specs/FAST-SERVER-RENDERED-PRESENTATION-001.md").write_text("A-O\n")
        self.oracle = "tests/Yii2/rendered_navigation_test.py"
        (self.root / self.oracle).write_text(
            "from pathlib import Path\n"
            "text=Path('app/YiiRuntime/Views/card.php').read_text()\n"
            "assert 'Correct label' in text, 'INTENDED_RED presentation label missing'\n")
        (self.root / "tests/Verification/policy_test.py").write_text("print('policy ok')\n")
        self.second_oracle = "tests/Yii2/second_rendered_oracle.py"
        (self.root / self.second_oracle).write_text("print('second oracle')\n")
        (self.root / "tests/Verification/integration_test.py").write_text("print('integration')\n")
        (self.root / "tests/Verification/e2e_test.py").write_text("print('e2e')\n")
        self.policy = self.make_policy()
        self.write(".quality-graph/verification-policy.json", self.policy)
        rows=[("unit","python3",self.oracle,"unit"),("unit","python3",self.second_oracle,"unit"),
              ("unit","python3","tests/Verification/policy_test.py","governance"),
              ("unit","python3","tests/Verification/integration_test.py","integration"),
              ("e2e","python3","tests/Verification/e2e_test.py","e2e")]
        rows.sort(key=lambda row:(row[0],row[2],row[1],row[3]))
        (self.root / "tools/verification/suites.tsv").write_text("".join("\t".join(row)+"\n" for row in rows))
        (self.root / "app/YiiRuntime/Views/card.php").write_text("<h1>Correct label</h1>\n")
        (self.root / "app/YiiRuntime/Views/nav.php").write_text("<nav aria-current=\"page\">Correct label</nav>\n")
        (self.root / "app/YiiRuntime/Assets/presentation.css").write_text("h1 { color: black; }\n")
        (self.root / ".gitignore").write_text("/change.json\n/plan.json\n")
        self.git("init", "-q"); self.git("config", "user.email", "x@example.invalid"); self.git("config", "user.name", "x")
        self.git("add", "."); self.git("commit", "-qm", "base")
        self.base = self.git("rev-parse", "HEAD").stdout.strip()
        (self.root / "app/YiiRuntime/Views/card.php").write_text("<h1>Correct label!</h1>\n")

    def make_policy(self):
        def boundary(name, patterns, tests=None, fast_class=None):
            value = {"name": name, "patterns": patterns, "categories": ["unit"], "tests": tests or []}
            if fast_class is not None: value["fast_class"] = fast_class
            return value
        boundaries = [
            boundary("server-rendered-presentation", ["app/YiiRuntime/Views/card.php", "app/YiiRuntime/Views/nav.php"], [self.oracle], "bounded-server-rendered-presentation"),
            boundary("bounded-ui", ["app/YiiRuntime/Assets/presentation.css"]),
            boundary("auth", ["app/IdentityAccess/**"]),
            boundary("security", ["app/Security/**"]),
            boundary("controller", ["app/YiiRuntime/Controllers/**"]),
            boundary("read-model", ["app/ReadModel/**"]),
            boundary("current-state", ["app/CurrentState/**"]),
            boundary("persistence", ["app/Infrastructure/Persistence/**", "migrations/**"]),
            boundary("sensitive-offline-ui", ["app/YiiRuntime/Assets/checklist-sw.js"]),
            boundary("external-integration", ["app/Integrations/**"]),
            boundary("jobs", ["app/Jobs/**"]),
            boundary("domain-mutation", ["app/DomainMutation/**"]),
            boundary("runtime-config", ["config/**", "routes/**"]),
            {"name":"delivery-policy","patterns":["tools/**","tests/**","specs/**","openspec/**",".quality-graph/**"],"categories":["governance"],"tests":[]},
        ]
        return {"version":1,"graph":"quality-graph.yml","spec":"specs/CHANGE-VERIFICATION-001.md",
                "suite_inventory":"tools/verification/suites.tsv","runtimes":{".py":"python3",".php":"php"},
                "category_argv":{"unit":[["python3",self.oracle]],"governance":[["python3","tests/Verification/policy_test.py"]],
                "integration":[["python3","tests/Verification/integration_test.py"]],"e2e":[["python3","tests/Verification/e2e_test.py"]]},
                "boundaries":boundaries,"verification_lanes":{"FAST":["bounded-ui","server-rendered-presentation"],
                "CRITICAL":["auth","security","sensitive-offline-ui","delivery-policy"]},
                "fast_classes":{"bounded-server-rendered-presentation":{"companion_boundaries":["bounded-ui"],
                "negative_boundaries_checked":self.NEGATIVE}},
                "semantic_surfaces":[{"name":"current-state-authority","patterns":["app/CurrentState/**"],"category":"integration","reason":"#153A"}],
                "full_categories":["unit","integration","e2e","governance"],"full_argv":["make","test"]}

    def git(self, *args):
        return subprocess.run(["git", *args], cwd=self.root, text=True, capture_output=True, check=True)

    def write(self, path, value):
        target=self.root/path; target.parent.mkdir(parents=True,exist_ok=True); target.write_text(json.dumps(value)+"\n")

    def plan(self, paths, tests=None):
        value={"change":"fixture","planned_paths":paths,"acceptances":[{"spec_id":"FAST-SERVER-RENDERED-PRESENTATION-001","acceptance_id":"A-O","spec_path":"specs/FAST-SERVER-RENDERED-PRESENTATION-001.md","seam":"rendered HTTP","tests":tests or [self.oracle]}]}
        self.write("change.json",value)
        result=subprocess.run(["python3","tools/delivery/change-verification.py","plan","--base",self.base,"--input","change.json","--output","plan.json"],cwd=self.root,text=True,capture_output=True)
        return result, json.loads((self.root/"plan.json").read_text()) if result.returncode==0 else None

    def touch(self, path, text="changed\n"):
        target=self.root/path; target.parent.mkdir(parents=True,exist_ok=True); target.write_text(text)

    def assert_fast(self, paths):
        result,plan=self.plan(paths); self.assertEqual(0,result.returncode,result.stderr)
        self.assertEqual("FAST",plan["verification_lane"]); self.assertEqual(["final"],plan["required_reviews"])
        self.assertEqual("bounded-server-rendered-presentation",plan["fast_class"])
        self.assertEqual(self.oracle,plan["selected_public_oracle"])
        self.assertEqual(self.NEGATIVE,plan["negative_boundaries_checked"])
        self.assertIn("registered public oracle",plan["fast_reason"])

    def test_a_b_c_healthy_label_navigation_and_css_are_fast(self):
        self.assert_fast(["app/YiiRuntime/Views/card.php"])
        self.touch("app/YiiRuntime/Views/nav.php","<nav aria-current=\"page\">Correct label!</nav>\n")
        self.assert_fast(["app/YiiRuntime/Views/nav.php"])
        self.touch("app/YiiRuntime/Assets/presentation.css","h1 { color: navy; }\n")
        self.assert_fast(["app/YiiRuntime/Views/card.php","app/YiiRuntime/Assets/presentation.css"])

    def test_shipped_policy_registers_only_reviewed_server_rendered_owners_and_oracle(self):
        policy=json.loads((ROOT/".quality-graph/verification-policy.json").read_text())
        owned=[item for item in policy["boundaries"] if item.get("fast_class")=="bounded-server-rendered-presentation"]
        self.assertEqual(1,len(owned),"INTENDED_RED shipped presentation boundary absent")
        self.assertEqual(["tests/Yii2/yii2_feedback_001_test.php"],owned[0]["tests"])
        self.assertEqual({"app/YiiRuntime/Views/feedback-confirmation.php"},set(owned[0]["patterns"]))
        application=next(item for item in policy["boundaries"] if item["name"]=="application-code")
        for mixed in ["app/YiiRuntime/MainNavigation.php","app/YiiRuntime/ViewSupport.php",
                      "app/YiiRuntime/Views/objects.php","app/YiiRuntime/Views/users.php"]:
            matches=[pattern for pattern in application["patterns"] if __import__("fnmatch").fnmatchcase(mixed,pattern)]
            self.assertTrue(matches,"mixed semantic owner must remain application-code: "+mixed)
        self.assertEqual(self.NEGATIVE,policy["fast_classes"]["bounded-server-rendered-presentation"]["negative_boundaries_checked"])

    def test_d_to_l_sensitive_and_semantic_neighbors_are_not_fast(self):
        cases=[("app/IdentityAccess/Permission.php","CRITICAL"),("app/Security/Csrf.php","CRITICAL"),
               ("app/YiiRuntime/Controllers/MutateController.php","STANDARD"),("app/DomainMutation/Command.php","STANDARD"),
               ("app/ReadModel/Query.php","STANDARD"),("app/Infrastructure/Persistence/Writer.php","STANDARD"),("migrations/V99.php","STANDARD"),
               ("app/CurrentState/Owner.php","STANDARD"),("app/YiiRuntime/Assets/checklist-sw.js","CRITICAL"),
               ("app/Integrations/External.php","STANDARD"),("app/Jobs/Outbox.php","STANDARD"),
               ("openspec/changes/x/spec.md","CRITICAL"),("tools/delivery/policy.py","CRITICAL"),("routes/new.php","STANDARD")]
        for path,lane in cases:
            with self.subTest(path=path):
                self.touch(path); result,plan=self.plan(["app/YiiRuntime/Views/card.php",path])
                self.assertEqual(0,result.returncode,result.stderr); self.assertEqual(lane,plan["verification_lane"]); self.assertNotEqual("FAST",lane)
                if "CurrentState" in path: self.assertTrue(plan["semantic_escalations"],"#153A escalation missing")
                (self.root/path).unlink()

    def test_m_missing_oracle_and_n_unknown_fail_closed(self):
        self.policy["boundaries"][0]["tests"]=[]; self.write(".quality-graph/verification-policy.json",self.policy)
        result,plan=self.plan(["app/YiiRuntime/Views/card.php"]); self.assertNotEqual(0,result.returncode); self.assertIsNone(plan)
        self.assertIn("FAST presentation boundary requires exactly one registered public oracle",result.stderr)
        self.write(".quality-graph/verification-policy.json",self.make_policy())
        result,plan=self.plan(["unknown/mixed.php"]); self.assertNotEqual(0,result.returncode); self.assertIsNone(plan)

    def test_m_multiple_registered_public_oracles_fail_closed(self):
        self.policy["boundaries"][0]["tests"].append(self.second_oracle)
        self.write(".quality-graph/verification-policy.json",self.policy)
        result,plan=self.plan(["app/YiiRuntime/Views/card.php"])
        self.assertNotEqual(0,result.returncode); self.assertIsNone(plan)
        self.assertIn("FAST presentation boundary requires exactly one registered public oracle",result.stderr)

    def test_o_public_prepare_selected_run_detects_defective_variant(self):
        result,plan=self.plan(["app/YiiRuntime/Views/card.php"]); self.assertEqual(0,result.returncode,result.stderr)
        env=dict(os.environ,FMONITOR_HARNESS_HOME=str(Path(self.temp.name)/"evidence"))
        self.touch("app/YiiRuntime/Views/card.php","<h1>Defective</h1>\n")
        prepared=subprocess.run(["python3","tools/delivery/harness.py","prepare","--input","change.json","--base",self.base,"--role","executor"],cwd=self.root,env=env,text=True,capture_output=True)
        self.assertEqual(0,prepared.returncode,"INTENDED_RED public prepare missing new class: "+prepared.stderr)
        package=json.loads(prepared.stdout); prepared_plan=json.loads(Path(package["plan"]).read_text())
        self.assertEqual(self.oracle,prepared_plan["selected_public_oracle"])
        run=subprocess.run(["python3","tools/delivery/change-verification.py","run","--plan",package["plan"],"--phase","focused"],cwd=self.root,env=env,text=True,capture_output=True)
        self.assertNotEqual(0,run.returncode); self.assertIn("INTENDED_RED presentation label missing",run.stdout+run.stderr)


if __name__ == "__main__": unittest.main(verbosity=2)
