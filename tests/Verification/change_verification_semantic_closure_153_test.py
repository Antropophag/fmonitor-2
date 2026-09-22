#!/usr/bin/env python3
"""CHANGE-VERIFICATION-SEMANTIC-CLOSURE-001 public planner regression."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class SemanticClosure(unittest.TestCase):
    DIRECT = "tests/Unit/direct_001_test.py"
    REPRESENTATIVE = "tests/Integration/representative_001_test.py"
    DOWNSTREAM_A = "tests/Integration/downstream_a_001_test.py"
    DOWNSTREAM_Z = "tests/Integration/downstream_z_001_test.py"

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix="semantic-closure-153-")
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        dirs = ["tools/delivery", "tools/verification", ".quality-graph", "specs",
                "tests/Unit", "tests/Integration", "tests/InstallationProcess",
                "tests/AssignmentOrderComposition", "tests/Verification", "tests/Runtime",
                "tests/Otiz", "tests/Jobs", "app/Domain",
                "app/Infrastructure/Persistence", "app/RuntimeRestore",
                "app/YiiRuntime/Views", "app/YiiRuntime/Assets", "migrations", "docs/operations"]
        for name in dirs:
            (self.root / name).mkdir(parents=True, exist_ok=True)
        for source in ["tools/delivery/change-verification.py", "tools/verification/inventory.py"]:
            shutil.copy2(ROOT / source, self.root / source)
        (self.root / "quality-graph.yml").write_text("version: 1\n")
        (self.root / "specs/CHANGE-VERIFICATION-001.md").write_text("planner\n")
        (self.root / "specs/FIXTURE-001.md").write_text("fixture\n")
        for test in [self.DIRECT, self.REPRESENTATIVE, self.DOWNSTREAM_A, self.DOWNSTREAM_Z]:
            (self.root / test).write_text("raise SystemExit(0)\n")
        self.rows = [f"unit\tpython3\t{self.DIRECT}\tunit",
                     f"db\tpython3\t{self.DOWNSTREAM_Z}\tintegration",
                     f"db\tpython3\t{self.REPRESENTATIVE}\tintegration",
                     f"db\tpython3\t{self.DOWNSTREAM_A}\tintegration"]
        self.integration_checks = [["python3", path] for path in
                                   sorted([self.DOWNSTREAM_A, self.DOWNSTREAM_Z, self.REPRESENTATIVE])]
        self.inventory(self.rows)
        self.policy = {
            "version": 1, "graph": "quality-graph.yml",
            "spec": "specs/CHANGE-VERIFICATION-001.md",
            "suite_inventory": "tools/verification/suites.tsv",
            "runtimes": {".py": "python3", ".php": "php", ".mjs": "node"},
            "category_argv": {"unit": [["python3", self.DIRECT]], "governance": [["make", "governance"]]},
            "boundaries": [
                {"name": "bounded-ui", "patterns": ["app/YiiRuntime/Assets/**"], "categories": ["unit"], "tests": []},
                {"name": "application-code", "patterns": ["app/Domain/**", "app/RuntimeRestore/**", "app/YiiRuntime/*.php", "app/YiiRuntime/Views/**"], "categories": ["unit"], "tests": []},
                {"name": "persistence", "patterns": ["app/Infrastructure/Persistence/**", "migrations/**"], "categories": ["unit"], "tests": []},
                {"name": "delivery-policy", "patterns": ["docs/**", "specs/**", "tools/**", ".quality-graph/**"], "categories": ["governance"], "tests": []},
                {"name": "tests", "patterns": ["tests/**"], "categories": ["governance"], "tests": []},
            ],
            "semantic_surfaces": [
                self.surface("current-state-authority", ["app/Domain/CurrentState.php"], "authoritative current-state changes affect downstream consumers"),
                self.surface("persistence-semantics", ["app/Infrastructure/Persistence/**"], "persisted state semantics require integration closure"),
                self.surface("schema-migration-frontier", ["migrations/**"], "schema and migration frontier require integration closure"),
                self.surface("domain-application-contract", ["app/Domain/Application.php"], "domain application contracts affect downstream consumers"),
                self.surface("recovery-representation", ["app/RuntimeRestore/**"], "persisted recovery representation requires integration closure"),
            ],
            "capability_ownership": [{
                "name": "semantic-closure-fixture-owner",
                "patterns": ["app/Domain/CurrentState.php", "app/Domain/Application.php",
                             "app/Infrastructure/Persistence/**", "migrations/**", "app/RuntimeRestore/**"],
                "verifiers": [], "consumers": []}],
            "verification_lanes": {"FAST": ["bounded-ui"], "CRITICAL": []},
            "full_categories": ["unit", "integration", "e2e", "governance"],
            "full_argv": ["make", "test"],
        }
        self.policy["category_argv"]["integration"] = [["python3", self.REPRESENTATIVE]]
        self.write_json(".quality-graph/verification-policy.json", self.policy)
        self.paths = ["app/Domain/CurrentState.php", "app/Domain/Application.php", "app/YiiRuntime/FeedbackApplication.php",
                      "app/Infrastructure/Persistence/Store.php", "app/Infrastructure/Persistence/ZStore.php",
                      "app/RuntimeRestore/State.php",
                      "app/YiiRuntime/Views/card.php", "app/YiiRuntime/Assets/pilot.css",
                      "migrations/m001.php", "docs/operations/lifecycle.md"]
        for path in self.paths:
            (self.root / path).write_text("before\n")
        self.command("git", "init", "-q", check=True)
        (self.root / ".git/info/exclude").write_text("/input.json\n/plan.json\n")
        self.command("git", "config", "user.email", "test@example.invalid", check=True)
        self.command("git", "config", "user.name", "Contract", check=True)
        self.command("git", "add", ".", check=True); self.command("git", "commit", "-qm", "base", check=True)
        self.base = self.command("git", "rev-parse", "HEAD", check=True).stdout.strip()

    @staticmethod
    def surface(name, patterns, reason):
        return {"name": name, "patterns": patterns, "category": "integration", "reason": reason}

    def command(self, *argv, check=False):
        return subprocess.run(argv, cwd=self.root, text=True, capture_output=True, check=check)

    def write_json(self, path, value):
        (self.root / path).write_text(json.dumps(value, sort_keys=True) + "\n")

    def inventory(self, rows):
        key = lambda row: (lambda f: (f[0], f[2], f[1], f[3]))(row.split("\t"))
        (self.root / "tools/verification/suites.tsv").write_text("\n".join(sorted(rows, key=key)) + "\n")

    def build(self, paths):
        for path in paths:
            (self.root / path).write_text("after\n")
        value = {"change": "semantic-fixture", "planned_paths": paths, "acceptances": [{
            "spec_id": "FIXTURE-001", "acceptance_id": "direct-green", "spec_path": "specs/FIXTURE-001.md",
            "seam": "public planner", "tests": [self.DIRECT]}]}
        self.write_json("input.json", value)
        return self.command("python3", "tools/delivery/change-verification.py", "plan", "--base", self.base,
                        "--input", "input.json", "--output", "plan.json")

    def plan(self):
        return json.loads((self.root / "plan.json").read_text())

    def check(self):
        return self.command("python3", "tools/delivery/change-verification.py", "check", "--plan", "plan.json")

    def assert_closure(self, path, surface):
        result = self.build([path]); self.assertEqual(0, result.returncode, result.stderr)
        plan = self.plan(); commands = [item["argv"] for item in plan["commands"]]
        self.assertIn("integration", plan["required_categories"],
                      "INTENDED_RED semantic integration category omitted")
        selected_integration = [argv for argv in commands if argv in self.integration_checks]
        self.assertEqual(self.integration_checks, selected_integration,
                         "INTENDED_RED complete canonical integration set omitted")
        item = next((x for x in plan.get("semantic_escalations", []) if x.get("surface") == surface), None)
        self.assertIsNotNone(item, "INTENDED_RED structured semantic escalation missing")
        self.assertEqual([path], item["paths"]); self.assertEqual("integration", item["required_category"])
        self.assertEqual(self.integration_checks, item["added_checks"])
        expected = next(value["reason"] for value in self.policy["semantic_surfaces"] if value["name"] == surface)
        self.assertEqual(expected, item["reason"])

    def test_a_d_protected_surface_matrix(self):
        cases = [("app/Domain/CurrentState.php", "current-state-authority"),
                 ("app/Infrastructure/Persistence/Store.php", "persistence-semantics"),
                 ("migrations/m001.php", "schema-migration-frontier"),
                 ("app/RuntimeRestore/State.php", "recovery-representation")]
        for path, surface in cases:
            with self.subTest(surface=surface):
                self.assert_closure(path, surface); self.setUp()

    def test_e_missing_registered_integration_verifier_fails_closed(self):
        self.inventory([row for row in self.rows if "\tintegration" not in row])
        result = self.build(["app/Domain/CurrentState.php"])
        self.assertNotEqual(0, result.returncode, "INTENDED_RED missing verifier admitted")
        self.assertIn("SEMANTIC_INTEGRATION_CLOSURE_UNAVAILABLE: current-state-authority: integration", result.stderr)

    def test_f_g_direct_green_does_not_replace_valid_closure(self):
        self.assertEqual(0, self.command("python3", self.DIRECT).returncode)
        self.assertEqual(0, self.build(["app/Domain/Application.php"]).returncode)
        self.assertEqual(0, self.check().returncode)
        plan = self.plan(); plan["commands"] = [x for x in plan["commands"] if x["argv"] not in self.integration_checks]
        self.write_json("plan.json", plan)
        rejected = self.check(); self.assertNotEqual(0, rejected.returncode, "INTENDED_RED incomplete plan admitted")
        self.assertIn("stale or tampered verification plan", rejected.stderr)

    def test_h_i_local_presentation_and_docs_do_not_escalate(self):
        for path in ["app/YiiRuntime/Views/card.php", "docs/operations/lifecycle.md"]:
            with self.subTest(path=path):
                result = self.build([path]); self.assertEqual(0, result.returncode, result.stderr)
                self.assertEqual([], self.plan().get("semantic_escalations", [])); self.setUp()

    def test_j_existing_fast_semantics_are_unchanged(self):
        self.assertEqual(0, self.build(["app/YiiRuntime/Assets/pilot.css"]).returncode)
        plan = self.plan(); self.assertEqual("FAST", plan["verification_lane"])
        self.assertEqual(["final"], plan["required_reviews"]); self.assertEqual([], plan.get("semantic_escalations", []))
        self.assertEqual([["python3", self.DIRECT]], [x["argv"] for x in plan["commands"]])

    def test_k_mixed_ui_and_persistence_escalates(self):
        paths = ["app/YiiRuntime/Views/card.php", "app/Infrastructure/Persistence/Store.php"]
        result = self.build(paths); self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(["persistence-semantics"],
                         [x["surface"] for x in self.plan().get("semantic_escalations", [])],
                         "INTENDED_RED mixed protected change did not escalate")

    def test_l_domain_contract_has_machine_readable_reason(self):
        self.assert_closure("app/Domain/Application.php", "domain-application-contract")

    def test_repository_evidenced_yii_application_contract_is_protected(self):
        shutil.copy2(ROOT / ".quality-graph/verification-policy.json",
                     self.root / ".quality-graph/verification-policy.json")
        policy = json.loads((self.root / ".quality-graph/verification-policy.json").read_text())
        if not any("app/YiiRuntime/FeedbackApplication.php" in item.get("patterns", [])
                   for item in policy["capability_ownership"]):
            policy["capability_ownership"].append({
                "name": "feedback-application-fixture-owner",
                "patterns": ["app/YiiRuntime/FeedbackApplication.php"],
                "verifiers": [], "consumers": []})
        self.write_json(".quality-graph/verification-policy.json", policy)
        shutil.copy2(ROOT / "tools/verification/suites.tsv",
                     self.root / "tools/verification/suites.tsv")
        for row in (self.root / "tools/verification/suites.tsv").read_text().splitlines():
            if not row or row.startswith("#"):
                continue
            _suite, _runtime, test, _category = row.split("\t")
            target = self.root / test; target.parent.mkdir(parents=True, exist_ok=True)
            if not target.exists(): target.write_text("fixture\n")
        self.command("git", "add", ".", check=True)
        self.command("git", "commit", "-qm", "shipped policy fixture", check=True)
        self.base = self.command("git", "rev-parse", "HEAD", check=True).stdout.strip()
        path = "app/YiiRuntime/FeedbackApplication.php"; (self.root / path).write_text("after\n")
        oracle = "tests/Verification/change_verification_001_test.py"
        self.write_json("input.json", {"change": "feedback-contract", "planned_paths": [path], "acceptances": [{
            "spec_id": "FIXTURE-001", "acceptance_id": "feedback-contract", "spec_path": "specs/FIXTURE-001.md",
            "seam": "public planner with shipped policy", "tests": [oracle]}]})
        result = self.command("python3", "tools/delivery/change-verification.py", "plan", "--base", self.base,
                              "--input", "input.json", "--output", "plan.json")
        self.assertEqual(0, result.returncode, result.stderr)
        matches = [item for item in self.plan().get("semantic_escalations", [])
                   if item["surface"] == "domain-application-contract"]
        self.assertEqual([path], matches[0]["paths"] if matches else [],
                         "INTENDED_RED shipped Yii application contract is unprotected")

    def test_l_paths_checks_and_surface_order_are_exact_and_sorted(self):
        paths = ["app/Infrastructure/Persistence/ZStore.php", "app/Domain/CurrentState.php",
                 "app/Infrastructure/Persistence/Store.php"]
        result = self.build(paths); self.assertEqual(0, result.returncode, result.stderr)
        items = self.plan().get("semantic_escalations", [])
        self.assertEqual(["current-state-authority", "persistence-semantics"],
                         [item["surface"] for item in items], "INTENDED_RED surface order is not stable")
        persistence = items[1]
        self.assertEqual(sorted(paths[::2]), persistence["paths"])
        self.assertEqual(self.integration_checks, persistence["added_checks"])
        self.assertEqual("persisted state semantics require integration closure", persistence["reason"])

    def test_policy_metadata_is_strict_and_fail_closed(self):
        invalid = [
            ("duplicate semantic surface", self.policy["semantic_surfaces"] + [dict(self.policy["semantic_surfaces"][0])]),
            ("invalid semantic surface name", [self.surface("", ["app/Domain/**"], "reason")]),
            ("semantic surface requires patterns", [self.surface("broken", [], "reason")]),
            ("semantic surface requires reason", [self.surface("broken", ["app/Domain/**"], "")]),
            ("semantic surface requires integration category",
             [{"name": "broken", "patterns": ["app/Domain/**"], "category": "unit", "reason": "reason"}]),
            ("malformed semantic surface", [{"patterns": ["app/Domain/**"], "category": "integration", "reason": "reason"}]),
            ("malformed semantic surface", [{"name": "broken", "category": "integration", "reason": "reason"}]),
            ("malformed semantic surface", [{"name": "broken", "patterns": ["app/Domain/**"], "reason": "reason"}]),
            ("malformed semantic surface", [{"name": "broken", "patterns": ["app/Domain/**"], "category": "integration"}]),
        ]
        for diagnostic, surfaces in invalid:
            with self.subTest(diagnostic=diagnostic):
                self.policy["semantic_surfaces"] = surfaces
                self.write_json(".quality-graph/verification-policy.json", self.policy)
                result = self.build(["app/Domain/CurrentState.php"])
                self.assertNotEqual(0, result.returncode, "INTENDED_RED malformed semantic metadata admitted")
                self.assertIn(diagnostic, result.stderr)
                self.setUp()

    def test_overlapping_surfaces_emit_deterministic_multi_match(self):
        self.policy["semantic_surfaces"].append(
            self.surface("domain-overlap", ["app/Domain/CurrentState.php"], "overlap remains explicit"))
        self.write_json(".quality-graph/verification-policy.json", self.policy)
        result = self.build(["app/Domain/CurrentState.php"]); self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(["current-state-authority", "domain-overlap"],
                         [item["surface"] for item in self.plan().get("semantic_escalations", [])])


if __name__ == "__main__":
    unittest.main(verbosity=2)
