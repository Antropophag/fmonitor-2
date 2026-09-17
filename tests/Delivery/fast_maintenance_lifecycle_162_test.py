"""FAST-MAINTENANCE-LIFECYCLE-001: public harness cases A-N."""
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class FastMaintenanceLifecycle(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="fast-maintenance-162-")
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / "repo"
        for relative in ["tools/delivery", "tools/verification", ".quality-graph",
                         "specs", "app/YiiRuntime/Views", "app/IdentityAccess",
                         "app/YiiRuntime/Assets", "migrations", "tests/Yii2",
                         "tests/Verification", "tests/AssignmentOrderComposition", "tests/Otiz",
                         "tests/Runtime", "tests/Jobs", "tests/InstallationProcess",
                         "docs/operations", "openspec/changes/existing"]:
            (self.root / relative).mkdir(parents=True, exist_ok=True)
        shutil.copytree(ROOT / "tools/delivery", self.root / "tools/delivery", dirs_exist_ok=True)
        for relative in ["tools/verification/inventory.py", "tools/verification/ci.py"]:
            shutil.copy(ROOT / relative, self.root / relative)
        # The fixture isolates harness routing from the independently tested additive
        # planner-input schema. Production must still add lifecycle to the real allowlist.
        planner = self.root / "tools/delivery/change-verification.py"
        planner.write_text(planner.read_text().replace(
            '{"change", "planned_paths", "acceptances", "dependency_workspaces"}',
            '{"change", "planned_paths", "acceptances", "dependency_workspaces", "lifecycle"}'))
        for relative in ["AGENTS.md", "PRODUCT.md", "CONTEXT.md", "docs/development-process.md",
                         "docs/fmonitor-2-pilot-spec.md", "docs/fmonitor-2-pilot-data-model.md",
                         "docs/operations/current-delivery-goal.md"]:
            target = self.root / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy(ROOT / relative, target)
        self.oracle = "tests/Yii2/existing_presentation_test.py"
        self.other_oracle = "tests/Yii2/other_mapped_test.py"
        self.requirement = "specs/FEEDBACK-001.md"
        (self.root / self.oracle).write_text(
            "from pathlib import Path\n"
            "text=Path('app/YiiRuntime/Views/card.php').read_text()\n"
            "assert 'Correct label' in text, 'INTENDED_RED existing presentation regression'\n")
        (self.root / self.other_oracle).write_text(
            "raise AssertionError('INTENDED_RED wrong mapped regression')\n")
        shutil.copy(ROOT / self.requirement, self.root / self.requirement)
        (self.root / "specs/CHANGE-VERIFICATION-001.md").write_text("planner\n")
        (self.root / "tests/Verification/policy_test.py").write_text("print('policy')\n")
        (self.root / "tests/Verification/integration_test.py").write_text("print('integration')\n")
        (self.root / "tests/Verification/e2e_test.py").write_text("print('e2e')\n")
        (self.root / "app/YiiRuntime/Views/card.php").write_text("<h1>Correct label</h1>\n")
        self.outside = Path(self.temp.name) / "outside"
        self.outside.mkdir()
        (self.outside / "requirement.md").write_text("outside\n")
        (self.root / "specs/link").symlink_to(self.outside, target_is_directory=True)
        (self.root / "specs/canonical").mkdir()
        (self.root / "specs/canonical/requirement.md").write_text("inside\n")
        (self.root / "quality-graph.yml").write_text("version: 1\n")
        policy = {
            "version": 1, "graph": "quality-graph.yml", "spec": "specs/CHANGE-VERIFICATION-001.md",
            "suite_inventory": "tools/verification/suites.tsv", "runtimes": {".py": "python3", ".php": "php"},
            "category_argv": {
                "unit": [["python3", self.oracle]],
                "governance": [["python3", "tests/Verification/policy_test.py"]],
                "integration": [["python3", "tests/Verification/integration_test.py"]],
                "e2e": [["python3", "tests/Verification/e2e_test.py"]],
            },
            "boundaries": [
                {"name": "server-rendered-presentation", "patterns": ["app/YiiRuntime/Views/card.php"],
                 "categories": ["unit"], "tests": [self.oracle],
                 "fast_class": "bounded-server-rendered-presentation"},
                {"name": "application-code", "patterns": ["app/YiiRuntime/Views/**", "app/InstallationProcess/**"], "categories": ["unit"], "tests": []},
                {"name": "auth", "patterns": ["app/IdentityAccess/**"], "categories": ["unit"], "tests": []},
                {"name": "sensitive-offline-ui", "patterns": ["app/YiiRuntime/Assets/checklist-sw.js"], "categories": ["unit"], "tests": []},
                {"name": "persistence", "patterns": ["migrations/**"], "categories": ["integration"], "tests": []},
                {"name": "tests", "patterns": ["tests/**"], "categories": ["governance"], "tests": []},
                {"name": "delivery-policy", "patterns": [".github/**", "tools/**", "specs/**", "openspec/**"],
                 "categories": ["governance"], "tests": []},
                {"name": "dependency-or-runtime", "patterns": ["Dockerfile"],
                 "categories": ["governance"], "tests": []},
            ],
            "verification_lanes": {"FAST": ["server-rendered-presentation"],
                                   "CRITICAL": ["auth", "sensitive-offline-ui", "delivery-policy"]},
            "fast_classes": {"bounded-server-rendered-presentation": {
                "companion_boundaries": [], "negative_boundaries_checked": ["product-spec-semantics", "verification-admission-policy"]}},
            "semantic_surfaces": [
                {"name": "schema-migration-frontier", "patterns": ["app/**/*Migration*.php", "app/**/*Schema*.php"],
                 "category": "integration", "reason": "schema and migration frontier require integration closure"},
                {"name": "domain-application-contract", "patterns": ["app/InstallationProcess/**"],
                 "category": "integration", "reason": "domain application contracts affect downstream consumers"},
            ],
            "capability_ownership": [{
                "name": "installation-schema-owner", "patterns": ["app/InstallationProcess/**"],
                "verifiers": ["tests/Verification/integration_test.py"], "consumers": []}],
            "full_categories": ["unit", "integration", "e2e", "governance"],
            "full_argv": ["make", "test"],
        }
        self.write_json(".quality-graph/verification-policy.json", policy)
        rows = [("unit", "python3", self.oracle, "unit"),
                ("unit", "python3", self.other_oracle, "unit"),
                ("unit", "python3", "tests/Verification/policy_test.py", "governance"),
                ("unit", "python3", "tests/Verification/integration_test.py", "integration"),
                ("e2e", "python3", "tests/Verification/e2e_test.py", "e2e")]
        rows.sort(key=lambda row: (row[0], row[2], row[1], row[3]))
        (self.root / "tools/verification/suites.tsv").write_text("".join("\t".join(row) + "\n" for row in rows))
        # Imports performed by the public harness create bytecode beside the
        # copied modules on runners that do not have a global Python ignore.
        # Keep that runtime byproduct out of the fixture's authoritative diff.
        (self.root / ".gitignore").write_text("/change.json\n__pycache__/\n*.py[cod]\n")
        self.git("init", "-q")
        self.git("config", "user.email", "x@example.invalid")
        self.git("config", "user.name", "x")
        self.git("add", ".")
        self.git("commit", "-qm", "base")
        self.base = self.git("rev-parse", "HEAD").stdout.strip()
        (self.root / "app/YiiRuntime/Views/card.php").write_text("<h1>Defective</h1>\n")
        self.environment = dict(os.environ, FMONITOR_HARNESS_HOME=str(Path(self.temp.name) / "evidence"))

    def git(self, *args):
        return subprocess.run(["git", *args], cwd=self.root, text=True, capture_output=True, check=True)

    def write_json(self, relative, value):
        target = self.root / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(value, ensure_ascii=False) + "\n")

    def declaration(self, **overrides):
        value = {"intent": "FAST_MAINTENANCE", "issue": "#historical-presentation",
                 "semantic_change": False, "requirement_status": "CURRENT",
                 "canonical_requirements": [{"path": self.requirement}],
                 "executable_regression": self.oracle}
        value.update(overrides)
        return value

    def compact_declaration(self, **overrides):
        value = {"intent": "COMPACT_MAINTENANCE", "issue": "#183-fixture",
                 "change_kind": "BOUNDED_FIX", "requirement_status": "CURRENT",
                 "semantic_change": "ESTABLISHED_BEHAVIOR_FIX",
                 "sensitivity": {"classification": "ORDINARY", "boundaries": [],
                                 "rationale": "No rights, secrets, money, persistence, replay, "
                                              "irreversible operation, or admission policy changes."},
                 "canonical_requirements": [{"path": self.requirement}],
                 "executable_regression": self.oracle}
        value.update(overrides)
        return value

    def prepare(self, *, paths=None, lifecycle=None, role="root", evidence=None):
        value = {"change": "historical-presentation-maintenance",
                 "planned_paths": paths or ["app/YiiRuntime/Views/card.php"],
                 "acceptances": [{"spec_id": "FEEDBACK-001", "acceptance_id": "existing-label",
                                  "spec_path": self.requirement, "seam": "rendered HTTP",
                                  "tests": [self.oracle, self.other_oracle]}]}
        if lifecycle is not None:
            value["lifecycle"] = lifecycle
        self.write_json("change.json", value)
        argv = ["python3", "tools/delivery/harness.py", "prepare", "--input", "change.json",
                "--base", self.base, "--role", role]
        if evidence:
            argv.extend(["--evidence", str(evidence)])
        result = subprocess.run(argv, cwd=self.root,
                                env=self.environment, text=True, capture_output=True)
        package = json.loads(result.stdout) if result.returncode == 0 else None
        return result, package

    def red_evidence(self, test=None, marker="INTENDED_RED existing presentation regression"):
        test = test or self.oracle
        result = subprocess.run(["python3", "tools/delivery/harness.py", "run", "--reason", "fixture-red",
                                 "--intended-red", marker,
                                 "--acceptance-id", "existing-label", "--", "python3", test],
                                cwd=self.root, env=self.environment, text=True, capture_output=True)
        self.assertNotEqual(0, result.returncode)
        summary = json.loads(result.stdout)
        self.assertEqual("INTENDED_RED", summary["outcome"])
        return summary["record_path"]

    def test_shipped_planner_accepts_opaque_lifecycle_metadata(self):
        source = (ROOT / "tools/delivery/change-verification.py").read_text()
        self.assertIn('"lifecycle"', source[ source.index("malformed change input") - 500:
                                             source.index("malformed change input")],
                      "INTENDED_RED planner input schema rejects harness lifecycle metadata")

    def test_a_b_c_eligible_package_uses_compact_route_and_preserves_gates(self):
        result, package = self.prepare(lifecycle=self.declaration())
        self.assertEqual(0, result.returncode, "INTENDED_RED lifecycle routing absent: " + result.stderr)
        lifecycle = package["lifecycle"]
        self.assertEqual("FAST_MAINTENANCE", lifecycle["route"])
        self.assertEqual("eligible_planner_fast_existing_requirement", lifecycle["reason"])
        self.assertEqual(False, lifecycle["semantic_change"])
        self.assertEqual("bounded-server-rendered-presentation", lifecycle["fast_class"])
        self.assertEqual(self.oracle, lifecycle["executable_regression"])
        expected = hashlib.sha256((self.root / self.requirement).read_bytes()).hexdigest()
        self.assertEqual([{"path": self.requirement, "sha256": expected}], lifecycle["canonical_requirements"])
        self.assertEqual({"required": True, "status": "PENDING"}, lifecycle["executable_red"])
        self.assertEqual({"required": True, "status": "PENDING"}, lifecycle["final_review"])
        self.assertEqual({"required": True, "status": "UNKNOWN"}, lifecycle["ci"])
        self.assertEqual("IN_PROGRESS", lifecycle["disposition"])
        self.assertEqual(["final"], json.loads(Path(package["plan"]).read_text())["required_reviews"])
        self.assertNotIn("acceptance_text", json.dumps(lifecycle))

        blocked, _ = self.prepare(lifecycle=self.declaration(), role="executor")
        self.assertNotEqual(0, blocked.returncode)
        self.assertIn("FAST maintenance executor requires intended RED evidence", blocked.stderr)
        admitted, executor_package = self.prepare(lifecycle=self.declaration(), role="executor",
                                                   evidence=self.red_evidence())
        self.assertEqual(0, admitted.returncode, admitted.stderr)
        self.assertEqual("INTENDED_RED", executor_package["lifecycle"]["executable_red"]["status"])
        wrong_red, _ = self.prepare(lifecycle=self.declaration(), role="executor", evidence=self.red_evidence(
            self.other_oracle, "INTENDED_RED wrong mapped regression"))
        self.assertNotEqual(0, wrong_red.returncode)
        self.assertIn("declared executable regression", wrong_red.stderr)

    def test_d_requirement_digest_drift_makes_public_state_stale(self):
        result, package = self.prepare(lifecycle=self.declaration())
        self.assertEqual(0, result.returncode, result.stderr)
        (self.root / self.requirement).write_text("# Changed canonical behavior\n")
        state = subprocess.run(["python3", "tools/delivery/harness.py", "state"], cwd=self.root,
                               env=self.environment, text=True, capture_output=True)
        self.assertEqual(0, state.returncode, state.stderr)
        active = json.loads(state.stdout)["active_binding"]
        self.assertEqual("STALE", active["lifecycle"]["freshness"])
        self.assertEqual("requirement_digest_changed", active["lifecycle"]["freshness_reason"])
        self.assertNotEqual("PR_READY", active["lifecycle"]["disposition"])
        apply_attempt, _ = self.prepare(lifecycle=self.declaration(), role="executor")
        self.assertNotEqual(0, apply_attempt.returncode)
        self.assertIn("stale FAST maintenance binding requires root rebuild", apply_attempt.stderr)

    def test_e_to_j_semantic_sensitive_policy_and_non_fast_routes_require_openspec(self):
        cases = [
            (["app/YiiRuntime/Views/card.php", "specs/NEW-BEHAVIOR.md"], "planner_not_fast"),
            (["app/YiiRuntime/Views/card.php", "app/IdentityAccess/Permission.php"], "planner_not_fast"),
            (["app/YiiRuntime/Views/card.php", "migrations/V99.php"], "planner_not_fast"),
            (["app/YiiRuntime/Views/card.php", "app/YiiRuntime/Assets/checklist-sw.js"], "planner_not_fast"),
            (["app/YiiRuntime/Views/card.php", "tools/delivery/policy.py"], "planner_not_fast"),
            (["app/YiiRuntime/Views/unregistered.php"], "planner_not_fast"),
        ]
        for paths, reason in cases:
            with self.subTest(paths=paths):
                for relative in paths:
                    target = self.root / relative
                    if not target.exists():
                        target.parent.mkdir(parents=True, exist_ok=True)
                        target.write_text("changed\n")
                result, package = self.prepare(paths=paths, lifecycle=self.declaration())
                self.assertEqual(0, result.returncode, result.stderr)
                self.assertEqual("OPENSPEC_REQUIRED", package["lifecycle"]["route"])
                self.assertEqual(reason, package["lifecycle"]["reason"])
                for relative in paths[1:]:
                    if relative != "app/YiiRuntime/Views/card.php":
                        (self.root / relative).unlink(missing_ok=True)

    def test_o_full_ci_bounded_fix_uses_one_author_and_final_review(self):
        result, package = self.prepare(
            paths=["app/YiiRuntime/Views/card.php", self.oracle],
            lifecycle=self.compact_declaration())
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual("STANDARD", plan["verification_lane"])
        self.assertIn(["make", "test"], [item["argv"] for item in plan["commands"]])
        self.assertEqual(["final"], plan["required_reviews"])
        self.assertEqual("COMPACT_MAINTENANCE", package["lifecycle"]["route"])
        self.assertEqual("single_author", package["lifecycle"]["authorship"])
        self.assertEqual("ORDINARY", package["lifecycle"]["sensitivity"]["classification"])

        blocked, _ = self.prepare(paths=["app/YiiRuntime/Views/card.php", self.oracle],
                                  lifecycle=self.compact_declaration(), role="executor")
        self.assertNotEqual(0, blocked.returncode)
        self.assertIn("compact maintenance executor requires intended RED evidence", blocked.stderr)
        admitted, executor = self.prepare(
            paths=["app/YiiRuntime/Views/card.php", self.oracle],
            lifecycle=self.compact_declaration(), role="executor", evidence=self.red_evidence())
        self.assertEqual(0, admitted.returncode, admitted.stderr)
        self.assertEqual("INTENDED_RED", executor["lifecycle"]["executable_red"]["status"])

    def test_p_metadata_fix_keeps_full_ci_but_drops_duplicate_lifecycle(self):
        recipe = self.root / "tools/delivery/Dockerfile.focused-checks"
        before = subprocess.run(
            ["git", "show", "c94c54247e85ebaf4e3bfd3b8b63d4b661ef27d4^:tools/delivery/Dockerfile.focused-checks"],
            cwd=ROOT, text=True, capture_output=True, check=True).stdout
        after = subprocess.run(
            ["git", "show", "c94c54247e85ebaf4e3bfd3b8b63d4b661ef27d4:tools/delivery/Dockerfile.focused-checks"],
            cwd=ROOT, text=True, capture_output=True, check=True).stdout
        self.assertGreaterEqual(before.count("ARG COMPOSER_LOCK_SHA256"), 2)
        self.assertIn("FROM common AS governance", before)
        self.assertIn("FROM common AS integration", before)
        self.assertIn("FROM common AS browser", before)
        recipe.write_text(before)
        self.git("add", "tools/delivery/Dockerfile.focused-checks")
        self.git("commit", "-qm", "fixture pre-180 recipe")
        self.base = self.git("rev-parse", "HEAD").stdout.strip()
        recipe.write_text(after)
        result, package = self.prepare(paths=["tools/delivery/Dockerfile.focused-checks", self.oracle],
                                       lifecycle=self.compact_declaration())
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertNotEqual("FAST", plan["verification_lane"])
        self.assertEqual(["final"], plan["required_reviews"])
        self.assertIn(["make", "test"], [item["argv"] for item in plan["commands"]])
        self.assertEqual("COMPACT_MAINTENANCE", package["lifecycle"]["route"])

        recipe.write_text(recipe.read_text().replace("RUN apt-get update", "RUN echo arbitrary-change && apt-get update", 1))
        result, package = self.prepare(paths=["tools/delivery/Dockerfile.focused-checks", self.oracle],
                                       lifecycle=self.compact_declaration())
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(["gate3", "final"], json.loads(Path(package["plan"]).read_text())["required_reviews"])

    def test_p2_workflow_change_is_sensitive_even_when_declared_ordinary(self):
        workflow = self.root / ".github/workflows/changed.yml"
        workflow.parent.mkdir(parents=True, exist_ok=True)
        workflow.write_text("name: changed\n")
        result, package = self.prepare(paths=[".github/workflows/changed.yml"],
                                       lifecycle=self.compact_declaration())
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual(["gate3", "final"], plan["required_reviews"])
        self.assertEqual("PRELIMINARY_REVIEW_REQUIRED", package["lifecycle"]["route"])

    def test_p4_schema_semantic_surface_is_sensitive_without_escalating_all_app_code(self):
        schema = self.root / "app/InstallationProcess/ControlEngineerAssignmentDefinitionSchemaMigration.php"
        schema.parent.mkdir(parents=True, exist_ok=True)
        schema.write_text("<?php // schema migration\n")
        result, package = self.prepare(paths=[str(schema.relative_to(self.root))],
                                       lifecycle=self.compact_declaration())
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual(["gate3", "final"], plan["required_reviews"])
        self.assertEqual("PRELIMINARY_REVIEW_REQUIRED", package["lifecycle"]["route"])

    def test_p3_arbitrary_root_dockerfile_change_is_sensitive(self):
        (self.root / "Dockerfile").write_text("FROM scratch\nRUN echo arbitrary\n")
        result, package = self.prepare(paths=["Dockerfile"],
                                       lifecycle=self.compact_declaration())
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual(["gate3", "final"], plan["required_reviews"])
        self.assertEqual("PRELIMINARY_REVIEW_REQUIRED", package["lifecycle"]["route"])

    def test_q_sensitive_semantics_and_admission_policy_keep_preliminary_review(self):
        sensitive = self.compact_declaration(sensitivity={
            "classification": "SENSITIVE", "boundaries": ["rights"],
            "rationale": "Authorization semantics change."})
        result, package = self.prepare(paths=["app/YiiRuntime/Views/card.php"],
                                       lifecycle=sensitive)
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual(["gate3", "final"], plan["required_reviews"])
        self.assertEqual("PRELIMINARY_REVIEW_REQUIRED", package["lifecycle"]["route"])

        ordinary_lie = self.compact_declaration()
        (self.root / "tools/delivery/policy.py").write_text("changed\n")
        result, package = self.prepare(paths=["tools/delivery/policy.py"], lifecycle=ordinary_lie)
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual(["gate3", "final"], plan["required_reviews"])
        self.assertEqual("PRELIMINARY_REVIEW_REQUIRED", package["lifecycle"]["route"])
        self.assertEqual("known_sensitive_boundary", package["lifecycle"]["reason"])

        transition = self.compact_declaration(
            issue="#183", sensitivity={"classification": "SENSITIVE",
                "boundaries": ["admission-policy"], "rationale": "Policy transition."},
            transition_authorization="OWNER_2026-09-17_ISSUE_183_NO_GATE3")
        result, package = self.prepare(paths=["tools/delivery/policy.py"], lifecycle=transition)
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual(["gate3", "final"], plan["required_reviews"])
        self.assertEqual("PRELIMINARY_REVIEW_REQUIRED", package["lifecycle"]["route"])

    def test_r_compact_record_is_resumable_and_requirement_drift_is_stale(self):
        result, package = self.prepare(paths=["app/YiiRuntime/Views/card.php", self.oracle],
                                       lifecycle=self.compact_declaration())
        self.assertEqual(0, result.returncode, result.stderr)
        state = subprocess.run(["python3", "tools/delivery/harness.py", "state"], cwd=self.root,
                               env=self.environment, text=True, capture_output=True, check=True)
        active = json.loads(state.stdout)["active_binding"]
        self.assertEqual("COMPACT_MAINTENANCE", active["lifecycle"]["route"])
        self.assertEqual("CURRENT", active["lifecycle"]["freshness"])
        findings = Path(self.temp.name) / "findings.json"
        findings.write_text(json.dumps({"open": ["bounded correction"]}))
        correction = subprocess.run([
            "python3", "tools/delivery/harness.py", "prepare", "--input", "change.json",
            "--base", self.base, "--role", "root", "--previous", package["snapshot"],
            "--findings", str(findings)], cwd=self.root, env=self.environment,
            text=True, capture_output=True)
        self.assertEqual(0, correction.returncode, correction.stderr)
        correction_package = json.loads(correction.stdout)
        self.assertEqual("COMPACT_MAINTENANCE", correction_package["lifecycle"]["route"])
        self.assertTrue(Path(correction_package["delta"]).is_file())
        self.assertEqual(["final"], json.loads(Path(correction_package["plan"]).read_text())["required_reviews"])
        (self.root / self.requirement).write_text("# drift\n")
        state = subprocess.run(["python3", "tools/delivery/harness.py", "state"], cwd=self.root,
                               env=self.environment, text=True, capture_output=True, check=True)
        active = json.loads(state.stdout)["active_binding"]
        self.assertEqual("STALE", active["lifecycle"]["freshness"])
        self.assertNotEqual("PR_READY", active["lifecycle"]["disposition"])

    def test_s_invalid_or_foreign_transition_declarations_fail_closed(self):
        cases = [
            self.compact_declaration(sensitivity={"classification": "ORDINARY",
                                                  "boundaries": [], "rationale": ""}),
            self.compact_declaration(
                issue="#not-183", transition_authorization="OWNER_2026-09-17_ISSUE_183_NO_GATE3",
                sensitivity={"classification": "SENSITIVE", "boundaries": ["admission-policy"],
                             "rationale": "Sensitive policy."}),
        ]
        for declaration in cases:
            with self.subTest(issue=declaration["issue"]):
                result, package = self.prepare(paths=["tools/delivery/policy.py"],
                                               lifecycle=declaration)
                self.assertEqual(0, result.returncode, result.stderr)
                plan = json.loads(Path(package["plan"]).read_text())
                self.assertEqual(["gate3", "final"], plan["required_reviews"])
                self.assertNotEqual("COMPACT_MAINTENANCE", package["lifecycle"]["route"])

    def test_t_issue183_transition_is_bound_to_exact_delivery_identity(self):
        spec = importlib.util.spec_from_file_location(
            "change_verification_183", ROOT / "tools/delivery/change-verification.py")
        planner = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(planner)
        input_name = "docs/operations/issue-183-delivery.json"
        change = json.loads((ROOT / input_name).read_text())
        base = "1245b44523f258294de4ac949e139dc2af26e07e"
        self.assertTrue(planner.issue183_transition_authorized(change, base, input_name))
        self.assertEqual("tests/fixtures/delivery/issue-183-transition-authorization.txt",
                         planner.ISSUE183_TRANSITION["requirement"])
        advanced_goal = {**change, "current_goal": "next-task"}
        self.assertTrue(planner.issue183_transition_authorized(advanced_goal, base, input_name))
        changed_basis = json.loads(json.dumps(change))
        changed_basis["lifecycle"]["canonical_requirements"][0]["sha256"] = "0" * 64
        variants = [
            (change, "0" * 40, input_name),
            (change, base, "docs/operations/copied-issue-183.json"),
            ({**change, "change": "later-maintenance-task"}, base, input_name),
            (changed_basis, base, input_name),
        ]
        for candidate, candidate_base, candidate_input in variants:
            with self.subTest(base=candidate_base, input=candidate_input,
                              change=candidate["change"]):
                self.assertFalse(planner.issue183_transition_authorized(
                    candidate, candidate_base, candidate_input))

    def test_fast_planner_declaration_failures_have_distinct_fail_closed_reasons(self):
        cases = [
            (self.declaration(semantic_change=True), "semantic_change_not_false"),
            (self.declaration(requirement_status="OWNER_DECISION_REQUIRED"), "NEEDS_OWNER:owner_decision_required"),
            ({}, "lifecycle_intent_not_fast_maintenance"),
            (self.declaration(issue=""), "maintenance_issue_missing"),
            (self.declaration(executable_regression="tests/Yii2/not_mapped.py"), "executable_regression_not_mapped"),
            (self.declaration(canonical_requirements=[{"path": "../escape.md"}]), "canonical_requirement_invalid"),
        ]
        for declaration, reason in cases:
            with self.subTest(reason=reason):
                result, package = self.prepare(lifecycle=declaration)
                self.assertEqual(0, result.returncode, result.stderr)
                self.assertEqual("OPENSPEC_REQUIRED", package["lifecycle"]["route"])
                self.assertEqual(reason, package["lifecycle"]["reason"])

    def test_c_and_ci_public_admission_reject_missing_final_unknown_and_wrong_source(self):
        prepared, package = self.prepare(lifecycle=self.declaration())
        self.assertEqual(0, prepared.returncode, prepared.stderr)
        self.assertEqual("FAST_MAINTENANCE", package["lifecycle"]["route"])
        spec = importlib.util.spec_from_file_location("admission162", ROOT / "tools/delivery/admission.py")
        admission = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(admission)
        binding = {"repository": "Antropophag/fmonitor-2", "pr": 162, "head": "a" * 40,
                   "base": "b" * 40, "candidate": package["candidate_source"], "mode": "fast",
                   "policy_digest": admission.policy_digest(ROOT), "workflow": admission.WORKFLOW,
                   "run_id": 1620, "attempt": 1}
        jobs = [{"name": name, "status": "COMPLETED", "conclusion": conclusion,
                 "binding": dict(binding)} for name, conclusion in admission.expected_jobs(ROOT, "fast").items()]
        healthy = {"binding": binding, "current": {"head": binding["head"], "base": binding["base"]},
                   "ci": {"binding": dict(binding), "jobs": jobs},
                   "preflight": {"outcome": "GREEN", "failures": [], "binding": dict(binding)},
                   "reviews": [{"gate": gate, "verdict": "APPROVED", "author": "executor",
                                "reviewer": "independent", "binding": dict(binding)} for gate in (3, 5)],
                   "authorization": None, "enforcement": "ENFORCEMENT_NOT_CONFIGURED"}
        def admitted(value):
            path = Path(self.temp.name) / "observation.json"
            path.write_text(json.dumps(value))
            result = subprocess.run(["python3", str(ROOT / "tools/delivery/harness.py"), "prepare-merge",
                                     "--observation", str(path)], cwd=ROOT, text=True, capture_output=True,
                                    env=self.environment)
            return result, json.loads(result.stdout)
        result, value = admitted(healthy)
        self.assertEqual(0, result.returncode, value)
        self.assertTrue(value["merge_ready"])
        self.assertEqual("SUCCESS", value["ci"]["status"])
        missing_final = json.loads(json.dumps(healthy))
        missing_final["reviews"] = [item for item in missing_final["reviews"] if item["gate"] != 5]
        result, value = admitted(missing_final)
        self.assertNotEqual(0, result.returncode)
        self.assertFalse(value["merge_ready"])
        self.assertIn("reviews_not_approved", value["reasons"])
        unknown_ci = json.loads(json.dumps(healthy))
        unknown_ci["ci"]["jobs"][0]["status"] = "IN_PROGRESS"
        result, value = admitted(unknown_ci)
        self.assertNotEqual(0, result.returncode)
        self.assertFalse(value["merge_ready"])
        self.assertEqual("PENDING", value["ci"]["status"])
        self.assertIn("ci_job_pending:plan", value["reasons"])
        failed_ci = json.loads(json.dumps(healthy))
        failed_ci["ci"]["jobs"][0]["conclusion"] = "FAILURE"
        result, value = admitted(failed_ci)
        self.assertNotEqual(0, result.returncode)
        self.assertFalse(value["merge_ready"])
        self.assertEqual("FAILURE", value["ci"]["status"])
        self.assertIn("ci_job_conclusion:plan:expected_success", value["reasons"])
        wrong_source = json.loads(json.dumps(healthy))
        wrong_source["current"]["head"] = "c" * 40
        result, value = admitted(wrong_source)
        self.assertNotEqual(0, result.returncode)
        self.assertFalse(value["merge_ready"])
        self.assertIn("head_or_base_changed", value["reasons"])

    def test_k_missing_requirement_fails_closed_without_aborting_normal_route(self):
        declaration = self.declaration(canonical_requirements=[{"path": "specs/MISSING.md"}])
        result, package = self.prepare(lifecycle=declaration)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("OPENSPEC_REQUIRED", package["lifecycle"]["route"])
        self.assertEqual("canonical_requirement_missing", package["lifecycle"]["reason"])

    def test_k_parent_symlink_escape_is_invalid_and_becomes_stale(self):
        escaped = self.declaration(canonical_requirements=[{"path": "specs/link/requirement.md"}])
        result, package = self.prepare(lifecycle=escaped)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("OPENSPEC_REQUIRED", package["lifecycle"]["route"])
        self.assertEqual("canonical_requirement_invalid", package["lifecycle"]["reason"])

        nested = self.root / "specs/canonical"
        current = self.declaration(canonical_requirements=[{"path": "specs/canonical/requirement.md"}])
        result, package = self.prepare(lifecycle=current)
        self.assertEqual(0, result.returncode, result.stderr)
        shutil.rmtree(nested)
        nested.symlink_to(self.outside, target_is_directory=True)
        state = subprocess.run(["python3", "tools/delivery/harness.py", "state"], cwd=self.root,
                               env=self.environment, text=True, capture_output=True)
        self.assertEqual(0, state.returncode, state.stderr)
        lifecycle = json.loads(state.stdout)["active_binding"]["lifecycle"]
        self.assertEqual("STALE", lifecycle["freshness"])
        self.assertEqual("canonical_requirement_missing", lifecycle["freshness_reason"])

    def test_l_conflicting_requirements_need_owner_and_normal_discovery(self):
        result, package = self.prepare(lifecycle=self.declaration(requirement_status="CONFLICTING"))
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("OPENSPEC_REQUIRED", package["lifecycle"]["route"])
        self.assertEqual("NEEDS_OWNER:conflicting_requirements", package["lifecycle"]["reason"])

    def test_m_historical_replay_reduces_artifacts_not_quality_guarantees(self):
        policy = json.loads((ROOT / ".quality-graph/verification-policy.json").read_text())
        presentation = next(item for item in policy["boundaries"]
                            if item.get("fast_class") == "bounded-server-rendered-presentation")
        self.assertEqual(["app/YiiRuntime/Views/feedback-confirmation.php"], presentation["patterns"])
        self.assertEqual(["tests/Yii2/yii2_feedback_001_test.php"], presentation["tests"])
        result, package = self.prepare(lifecycle=self.declaration(
            canonical_requirements=[{"path": self.requirement}], executable_regression=self.oracle))
        self.assertEqual(0, result.returncode, result.stderr)
        replay_plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual("FAST", replay_plan["verification_lane"])
        self.assertEqual("bounded-server-rendered-presentation", replay_plan["fast_class"])
        replay_identity = {"issue": "#historical-presentation", "requirement": self.requirement,
                           "regression": self.oracle}
        canonical = (ROOT / "specs/FEEDBACK-001.md").read_bytes()
        def duplicated(kind):
            return json.dumps({"kind": kind, **replay_identity}).encode() + b"\n" + canonical
        before_values = [
            duplicated("duplicating-executable-spec"),
            duplicated("proposal"),
            duplicated("delta-spec"),
            duplicated("design"),
            duplicated("tasks"),
            json.dumps({"kind": "verification-input", **replay_identity}).encode(),
            (ROOT / "tests/Yii2/yii2_feedback_001_test.php").read_bytes(),
            json.dumps({"kind": "gate3-review", **replay_identity}).encode(),
            json.dumps({"kind": "final-review", **replay_identity}).encode(),
            json.dumps({"kind": "delivery-record", **replay_identity}).encode(),
        ]
        compact = json.dumps(package["lifecycle"], ensure_ascii=False, sort_keys=True).encode()
        compact_input = json.dumps({"lifecycle": self.declaration(), **replay_identity}, sort_keys=True).encode()
        after_values = [compact_input, (ROOT / "tests/Yii2/yii2_feedback_001_test.php").read_bytes(),
                        json.dumps({"kind": "final-review", **replay_identity}).encode(), compact]
        def metrics(values):
            return {"artifacts": len(values), "bytes": sum(map(len, values)),
                    "characters": sum(len(value.decode("utf-8")) for value in values)}
        after_metrics = {"artifacts": len(after_values), "bytes": sum(map(len, after_values)),
                         "characters": sum(len(value.decode("utf-8")) for value in after_values)}
        lifecycle = package["lifecycle"]
        report = {"before": {**metrics(before_values), "created_artifacts": 9,
                             "independent_reviews": 2, "phase_stops": 1},
                  "after": {**after_metrics, "created_artifacts": 2,
                            "independent_reviews": len(replay_plan["required_reviews"]), "phase_stops": 0},
                  "quality": {"executable_regression": lifecycle["executable_regression"] == self.oracle,
                              "final_review": lifecycle["final_review"]["required"] is True,
                              "exact_source_ci": lifecycle["ci"]["required"] is True,
                              "canonical_traceability": lifecycle["canonical_requirements"][0]["path"] == self.requirement},
                  "token_usage": "UNKNOWN"}
        self.assertEqual(10, report["before"]["artifacts"])
        self.assertEqual(4, report["after"]["artifacts"])
        self.assertEqual((9, 2), (report["before"]["created_artifacts"], report["after"]["created_artifacts"]))
        self.assertLess(report["after"]["bytes"], report["before"]["bytes"])
        self.assertEqual((2, 1), (report["before"]["independent_reviews"], report["after"]["independent_reviews"]))
        self.assertTrue(all(report["quality"].values()))
        self.assertEqual("UNKNOWN", report["token_usage"])
        print("HISTORICAL_FAST_MAINTENANCE_REPLAY=" + json.dumps(report, sort_keys=True))

    def test_n_legacy_openspec_input_keeps_normal_route(self):
        result, package = self.prepare(lifecycle=None)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("OPENSPEC_REQUIRED", package["lifecycle"]["route"])
        self.assertEqual("lifecycle_intent_not_fast_maintenance", package["lifecycle"]["reason"])
        change = ROOT / "openspec/changes/minimal-fast-maintenance-lifecycle"
        self.assertTrue(all((change / name).is_file() for name in
                            ("proposal.md", "design.md", "tasks.md", "verification-input.json")))
        self.assertTrue((change / "specs/delivery/fast-maintenance-lifecycle/spec.md").is_file())
        openspec = shutil.which("openspec")
        if openspec is None:
            return
        status = subprocess.run([openspec, "status", "--change", "minimal-fast-maintenance-lifecycle", "--json"],
                                cwd=ROOT, text=True, capture_output=True)
        self.assertEqual(0, status.returncode, status.stderr)
        self.assertEqual("spec-driven", json.loads(status.stdout)["schemaName"])
        validation = subprocess.run([openspec, "validate", "minimal-fast-maintenance-lifecycle", "--strict"],
                                    cwd=ROOT, text=True, capture_output=True)
        self.assertEqual(0, validation.returncode, validation.stderr)


if __name__ == "__main__":
    unittest.main(verbosity=2)
