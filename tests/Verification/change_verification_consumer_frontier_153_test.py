#!/usr/bin/env python3
"""CONSUMER-OWNERSHIP-FRONTIER-153-B public planner regression."""
import copy
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class ConsumerFrontier(unittest.TestCase):
    ACCEPTANCE = "tests/Unit/acceptance_001_test.py"
    INTEGRATION = "tests/Integration/slice_a_001_test.py"
    INTEGRATION_Z = "tests/Integration/slice_a_z_001_test.py"
    MIGRATION = "tests/Consumers/migration_verifier_001_test.py"
    RECOVERY = "tests/Consumers/current_schema_recovery_001_test.py"
    RUNTIME = "tests/Consumers/runtime_inventory_001_test.py"
    ASSIGNMENT = "tests/Consumers/current_assignment_001_test.py"
    ASSIGNMENT_RUNTIME = "tests/Consumers/assignment_runtime_001_test.py"

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix="consumer-frontier-153b-")
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        for directory in ["tools/delivery", "tools/verification", ".quality-graph", "specs",
                          "tests/Unit", "tests/Integration", "tests/Consumers",
                          "tests/InstallationProcess", "tests/AssignmentOrderComposition",
                          "tests/Verification", "tests/Runtime", "tests/Otiz", "tests/Jobs",
                          "app/Schema", "app/Domain", "app/YiiRuntime/Views"]:
            (self.root / directory).mkdir(parents=True, exist_ok=True)
        for source in ["tools/delivery/change-verification.py", "tools/verification/inventory.py"]:
            shutil.copy2(ROOT / source, self.root / source)
        (self.root / "quality-graph.yml").write_text("version: 1\n")
        (self.root / "specs/CHANGE-VERIFICATION-001.md").write_text("planner\n")
        (self.root / "specs/FIXTURE-001.md").write_text("fixture\n")
        self.verifiers = [self.ACCEPTANCE, self.INTEGRATION, self.INTEGRATION_Z, self.MIGRATION, self.RECOVERY,
                          self.RUNTIME, self.ASSIGNMENT, self.ASSIGNMENT_RUNTIME]
        for verifier in self.verifiers:
            (self.root / verifier).write_text("raise SystemExit(0)\n")
        self.inventory_rows = [
            f"unit\tpython3\t{self.ACCEPTANCE}\tunit",
            f"db\tpython3\t{self.INTEGRATION}\tintegration",
            f"db\tpython3\t{self.INTEGRATION_Z}\tintegration",
            f"unit\tpython3\t{self.MIGRATION}\tunit",
            f"unit\tpython3\t{self.RECOVERY}\tunit",
            f"unit\tpython3\t{self.RUNTIME}\tunit",
            f"unit\tpython3\t{self.ASSIGNMENT}\tunit",
            f"unit\tpython3\t{self.ASSIGNMENT_RUNTIME}\tunit",
        ]
        self.write_inventory(self.inventory_rows)
        self.capabilities = [
            self.capability("canonical-schema-frontier", ["app/Schema/CanonicalMigration.php"],
                            [self.MIGRATION], ["current-schema-recovery"]),
            self.capability("current-schema-recovery", [], [self.RECOVERY], ["runtime-schema-inventory"]),
            self.capability("runtime-schema-inventory", [], [self.RUNTIME], []),
            self.capability("current-assignment", ["app/Domain/CurrentAssignment.php"],
                            [self.ASSIGNMENT], ["assignment-runtime"]),
            self.capability("assignment-runtime", [], [self.ASSIGNMENT_RUNTIME], []),
        ]
        self.policy = {
            "version": 1,
            "graph": "quality-graph.yml",
            "spec": "specs/CHANGE-VERIFICATION-001.md",
            "suite_inventory": "tools/verification/suites.tsv",
            "runtimes": {".py": "python3", ".php": "php", ".mjs": "node"},
            "category_argv": {
                "unit": [["python3", self.ACCEPTANCE]],
                "integration": [["python3", self.INTEGRATION]],
                "governance": [["make", "governance"]],
            },
            "boundaries": [
                {"name": "application-code", "patterns": ["app/Schema/**", "app/Domain/**"],
                 "categories": ["unit"], "tests": []},
                {"name": "bounded-ui", "patterns": ["app/YiiRuntime/Views/**"],
                 "categories": ["unit"], "tests": []},
                {"name": "tests", "patterns": ["tests/**"], "categories": ["governance"], "tests": []},
                {"name": "delivery-policy", "patterns": ["specs/**", "tools/**", ".quality-graph/**"],
                 "categories": ["governance"], "tests": []},
            ],
            "semantic_surfaces": [
                {"name": "schema-migration-frontier", "patterns": ["app/Schema/**"],
                 "category": "integration", "reason": "schema frontier requires integration closure"},
                {"name": "current-state-authority", "patterns": ["app/Domain/CurrentAssignment.php"],
                 "category": "integration", "reason": "current state affects downstream consumers"},
            ],
            "capability_ownership": copy.deepcopy(self.capabilities),
            "verification_lanes": {"FAST": ["bounded-ui"], "CRITICAL": []},
            "full_categories": ["unit", "integration", "e2e", "governance"],
            "full_argv": ["make", "test"],
        }
        self.write_json(".quality-graph/verification-policy.json", self.policy)
        for path in ["app/Schema/CanonicalMigration.php", "app/Domain/CurrentAssignment.php",
                     "app/YiiRuntime/Views/card.php"]:
            (self.root / path).write_text("before\n")
        self.command("git", "init", "-q", check=True)
        (self.root / ".git/info/exclude").write_text("/input.json\n/plan.json\n")
        self.command("git", "config", "user.email", "test@example.invalid", check=True)
        self.command("git", "config", "user.name", "Contract", check=True)
        self.command("git", "add", ".", check=True)
        self.command("git", "commit", "-qm", "base", check=True)
        self.base = self.command("git", "rev-parse", "HEAD", check=True).stdout.strip()

    @staticmethod
    def capability(name, patterns, verifiers, consumers):
        return {"name": name, "patterns": patterns, "verifiers": verifiers, "consumers": consumers}

    def command(self, *argv, check=False):
        return subprocess.run(argv, cwd=self.root, text=True, capture_output=True, check=check)

    def write_json(self, path, value):
        (self.root / path).write_text(json.dumps(value, sort_keys=True) + "\n")

    def write_inventory(self, rows):
        key = lambda row: (lambda fields: (fields[0], fields[2], fields[1], fields[3]))(row.split("\t"))
        (self.root / "tools/verification/suites.tsv").write_text("\n".join(sorted(rows, key=key)) + "\n")

    def commit_policy_baseline(self):
        self.write_json(".quality-graph/verification-policy.json", self.policy)
        self.command("git", "add", ".", check=True)
        self.command("git", "commit", "-qm", "policy fixture", check=True)
        self.base = self.command("git", "rev-parse", "HEAD", check=True).stdout.strip()

    def build(self, paths):
        for path in paths:
            (self.root / path).write_text("after\n")
        self.write_json("input.json", {"change": "consumer-fixture", "planned_paths": paths,
            "acceptances": [{"spec_id": "FIXTURE-001", "acceptance_id": "frontier",
                "spec_path": "specs/FIXTURE-001.md", "seam": "public planner",
                "tests": [self.ACCEPTANCE]}]})
        return self.command("python3", "tools/delivery/change-verification.py", "plan",
                            "--base", self.base, "--input", "input.json", "--output", "plan.json")

    def plan(self):
        return json.loads((self.root / "plan.json").read_text())

    def selected_paths(self):
        return [item["argv"][1] for item in self.plan()["commands"]
                if len(item["argv"]) == 2 and item["argv"][0] == "python3"]

    def expansions(self):
        return self.plan().get("consumer_expansions", [])

    @staticmethod
    def expected(path, root, chain, verifier):
        return {"changed_path": path, "root_capability": root, "consumer_chain": chain,
                "verifier": verifier, "argv": ["python3", verifier]}

    def test_before_measurement_exposes_slice_a_gap(self):
        result = self.build(["app/Schema/CanonicalMigration.php"])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual([], self.expansions(), "BEFORE must have no ownership evidence")
        selected = self.selected_paths()
        self.assertNotIn(self.MIGRATION, selected, "BEFORE direct migration consumer unexpectedly selected")
        self.assertNotIn(self.RECOVERY, selected, "BEFORE recovery consumer unexpectedly selected")
        self.assertNotIn(self.RUNTIME, selected, "BEFORE indirect runtime consumer unexpectedly selected")
        self.assertIn(self.INTEGRATION, selected, "Slice A conservative integration closure must remain")

    def test_a_c_schema_frontier_expands_direct_recovery_and_indirect(self):
        result = self.build(["app/Schema/CanonicalMigration.php"])
        self.assertEqual(0, result.returncode, result.stderr)
        expected = [
            self.expected("app/Schema/CanonicalMigration.php", "canonical-schema-frontier",
                          ["canonical-schema-frontier"], self.MIGRATION),
            self.expected("app/Schema/CanonicalMigration.php", "canonical-schema-frontier",
                          ["canonical-schema-frontier", "current-schema-recovery"], self.RECOVERY),
            self.expected("app/Schema/CanonicalMigration.php", "canonical-schema-frontier",
                          ["canonical-schema-frontier", "current-schema-recovery", "runtime-schema-inventory"],
                          self.RUNTIME),
        ]
        self.assertEqual(expected, self.expansions(), "INTENDED_RED direct/transitive frontier absent")
        for verifier in [self.MIGRATION, self.RECOVERY, self.RUNTIME]:
            self.assertIn(verifier, self.selected_paths())

    def test_d_current_assignment_expands_all_consumers(self):
        path = "app/Domain/CurrentAssignment.php"
        result = self.build([path]); self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual([
            self.expected(path, "current-assignment", ["current-assignment"], self.ASSIGNMENT),
            self.expected(path, "current-assignment", ["current-assignment", "assignment-runtime"],
                          self.ASSIGNMENT_RUNTIME),
        ], self.expansions(), "INTENDED_RED current-assignment consumers absent")

    def test_e_f_missing_or_ambiguous_protected_owner_fails_closed(self):
        cases = [
            ([], "PROTECTED_CAPABILITY_OWNER_MISSING: app/Schema/CanonicalMigration.php"),
            (self.capabilities + [self.capability("duplicate-owner", ["app/Schema/**"], [], [])],
             "PROTECTED_CAPABILITY_OWNER_AMBIGUOUS: app/Schema/CanonicalMigration.php"),
        ]
        for capabilities, diagnostic in cases:
            with self.subTest(diagnostic=diagnostic):
                self.policy["capability_ownership"] = capabilities
                self.commit_policy_baseline()
                result = self.build(["app/Schema/CanonicalMigration.php"])
                self.assertNotEqual(0, result.returncode, "INTENDED_RED invalid ownership admitted")
                self.assertIn(diagnostic, result.stderr)
                self.setUp()

    def test_g_stale_consumer_capability_fails_closed(self):
        self.policy["capability_ownership"][0]["consumers"] = ["missing-capability"]
        self.commit_policy_baseline()
        result = self.build(["app/Schema/CanonicalMigration.php"])
        self.assertNotEqual(0, result.returncode, "INTENDED_RED stale capability admitted")
        self.assertIn("CAPABILITY_CONSUMER_UNKNOWN: canonical-schema-frontier: missing-capability", result.stderr)

    def test_h_i_unregistered_or_removed_verifier_fails_closed(self):
        cases = ["unregistered", "removed"]
        for case in cases:
            with self.subTest(case=case):
                if case == "unregistered":
                    self.write_inventory([row for row in self.inventory_rows if self.MIGRATION not in row])
                    diagnostic = f"CAPABILITY_VERIFIER_UNREGISTERED: canonical-schema-frontier: {self.MIGRATION}"
                else:
                    (self.root / self.MIGRATION).unlink()
                    diagnostic = f"CAPABILITY_VERIFIER_MISSING: canonical-schema-frontier: {self.MIGRATION}"
                self.command("git", "add", "-A", check=True)
                self.command("git", "commit", "-qm", case, check=True)
                self.base = self.command("git", "rev-parse", "HEAD", check=True).stdout.strip()
                result = self.build(["app/Schema/CanonicalMigration.php"])
                self.assertNotEqual(0, result.returncode, "INTENDED_RED stale verifier admitted")
                self.assertIn(diagnostic, result.stderr)
                self.setUp()

    def test_j_order_and_cycle_are_finite_and_canonical(self):
        self.policy["capability_ownership"][2]["consumers"] = ["canonical-schema-frontier"]
        self.commit_policy_baseline()
        self.assertEqual(0, self.build(["app/Schema/CanonicalMigration.php"]).returncode)
        first = self.expansions()
        self.assertEqual(3, len(first), "INTENDED_RED cycle fixture produced no consumer frontier")
        self.policy["capability_ownership"] = list(reversed(self.policy["capability_ownership"]))
        self.commit_policy_baseline()
        self.assertEqual(0, self.build(["app/Schema/CanonicalMigration.php"]).returncode)
        self.assertEqual(first, self.expansions(), "INTENDED_RED declaration order changed closure")

    def test_j_diamond_preserves_each_chain_and_deduplicates_execution(self):
        branch_a = self.capability("schema-branch-a", [], [], ["runtime-schema-inventory"])
        branch_b = self.capability("schema-branch-b", [], [], ["runtime-schema-inventory"])
        self.policy["capability_ownership"][0]["consumers"] = ["schema-branch-b", "schema-branch-a"]
        self.policy["capability_ownership"].extend([branch_b, branch_a])
        self.commit_policy_baseline()
        path = "app/Schema/CanonicalMigration.php"
        result = self.build([path]); self.assertEqual(0, result.returncode, result.stderr)
        runtime = [item for item in self.expansions() if item["verifier"] == self.RUNTIME]
        self.assertEqual([
            self.expected(path, "canonical-schema-frontier",
                          ["canonical-schema-frontier", "schema-branch-a", "runtime-schema-inventory"],
                          self.RUNTIME),
            self.expected(path, "canonical-schema-frontier",
                          ["canonical-schema-frontier", "schema-branch-b", "runtime-schema-inventory"],
                          self.RUNTIME),
        ], runtime, "INTENDED_RED diamond causal chains incomplete or unstable")
        self.assertEqual(1, self.selected_paths().count(self.RUNTIME), "verifier execution not deduplicated")

    def test_policy_metadata_is_strict_and_fail_closed(self):
        invalid = [
            ("CAPABILITY_OWNERSHIP_MALFORMED", "not-a-list"),
            ("CAPABILITY_OWNERSHIP_MALFORMED", [{"name": "broken"}]),
            ("CAPABILITY_NAME_INVALID", [self.capability("", ["app/Schema/**"], [], [])]),
            ("CAPABILITY_PATTERNS_INVALID",
             [{"name": "broken", "patterns": ["app/Schema/**", "app/Schema/**"], "verifiers": [], "consumers": []}]),
            ("CAPABILITY_VERIFIERS_INVALID",
             [{"name": "broken", "patterns": ["app/Schema/**"], "verifiers": [self.MIGRATION, self.MIGRATION], "consumers": []}]),
            ("CAPABILITY_CONSUMERS_INVALID",
             [{"name": "broken", "patterns": ["app/Schema/**"], "verifiers": [], "consumers": ["next", "next"]},
              self.capability("next", [], [], [])]),
            ("CAPABILITY_NAME_DUPLICATE",
             [self.capability("same", ["app/Schema/A.php"], [], []),
              self.capability("same", ["app/Schema/B.php"], [], [])]),
        ]
        for diagnostic, capabilities in invalid:
            with self.subTest(diagnostic=diagnostic):
                self.policy["capability_ownership"] = capabilities
                self.commit_policy_baseline()
                result = self.build(["app/Schema/CanonicalMigration.php"])
                self.assertNotEqual(0, result.returncode, "INTENDED_RED malformed ownership admitted")
                self.assertIn(diagnostic, result.stderr)
                self.setUp()

    def test_k_direct_selection_deduplicates_execution_not_evidence(self):
        self.policy["category_argv"]["unit"].append(["python3", self.MIGRATION])
        self.commit_policy_baseline()
        result = self.build(["app/Schema/CanonicalMigration.php"])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(1, self.selected_paths().count(self.MIGRATION))
        self.assertTrue(any(item["verifier"] == self.MIGRATION for item in self.expansions()))
        self.assertIn(self.RUNTIME, self.selected_paths(), "INTENDED_RED indirect consumer lost")

    def test_l_presentation_only_has_no_frontier(self):
        result = self.build(["app/YiiRuntime/Views/card.php"])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual([], self.expansions())
        self.assertEqual([], self.plan().get("semantic_escalations", []))
        self.assertEqual("FAST", self.plan()["verification_lane"])
        self.assertEqual(["final"], self.plan()["required_reviews"])
        self.assertEqual([self.ACCEPTANCE], self.selected_paths())

    def test_m_slice_a_closure_and_plan_check_are_preserved(self):
        result = self.build(["app/Schema/CanonicalMigration.php"])
        self.assertEqual(0, result.returncode, result.stderr)
        plan = self.plan()
        self.assertEqual(["schema-migration-frontier"],
                         [item["surface"] for item in plan["semantic_escalations"]])
        expected_integration = sorted([self.INTEGRATION, self.INTEGRATION_Z])
        selected_integration = [path for path in self.selected_paths() if path in expected_integration]
        self.assertEqual(expected_integration, selected_integration)
        self.assertEqual([["python3", path] for path in expected_integration],
                         plan["semantic_escalations"][0]["added_checks"])
        self.assertEqual("STANDARD", plan["verification_lane"])
        self.assertEqual(["gate3", "final"], plan["required_reviews"])
        checked = self.command("python3", "tools/delivery/change-verification.py", "check",
                               "--plan", "plan.json")
        self.assertEqual(0, checked.returncode, checked.stderr)


if __name__ == "__main__":
    unittest.main(verbosity=2)
