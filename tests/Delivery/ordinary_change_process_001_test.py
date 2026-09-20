"""ORDINARY-CHANGE-PROCESS-001: public planner/harness acceptance A-L."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class OrdinaryChangeProcess(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="ordinary-process-")
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / "repo"
        for relative in (
            "tools/delivery", "tools/verification", ".quality-graph", "specs",
            "docs/operations", "openspec/changes/example", "tests/Delivery",
            "tests/Verification", "tests/Yii2", "tests/Read", "tests/Support",
            "tests/AssignmentOrderComposition", "tests/Otiz", "tests/Runtime",
            "tests/Jobs", "tests/InstallationProcess", "tests/Deployment",
            "app/YiiRuntime/Views", "app/YiiRuntime/Assets", "app/ReadModel",
            "app/IdentityAccess", "migrations",
        ):
            (self.root / relative).mkdir(parents=True, exist_ok=True)
        shutil.copytree(ROOT / "tools/delivery", self.root / "tools/delivery", dirs_exist_ok=True)
        for relative in ("tools/verification/inventory.py", "tools/verification/ci.py"):
            shutil.copy(ROOT / relative, self.root / relative)
        for relative in (
            "AGENTS.md", "PRODUCT.md", "CONTEXT.md", "docs/development-process.md",
            "docs/fmonitor-2-pilot-spec.md", "docs/fmonitor-2-pilot-data-model.md",
            "docs/operations/current-delivery-goal.md",
        ):
            target = self.root / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy(ROOT / relative, target)

        self.presentation = "app/YiiRuntime/Views/card.php"
        self.read = "app/ReadModel/Search.php"
        self.refactor = "tests/Support/ApplicationFixture.php"
        self.presentation_test = "tests/Yii2/card_presentation_test.py"
        self.read_test = "tests/Read/search_test.py"
        self.refactor_test = "tests/Delivery/application_fixture_test.py"
        self.policy_test = "tests/Verification/policy_test.py"
        self.integration_test = "tests/Verification/integration_test.py"
        self.e2e_test = "tests/Verification/e2e_test.py"
        self.environment_test = "tests/Verification/template_environment_test.py"
        for path, content in (
            (self.presentation, "<h1>Expected label</h1>\n"),
            (self.read, "<?php // read projection\n"),
            (self.refactor, "<?php // test helper\n"),
            (self.presentation_test, "print('presentation green')\n"),
            (self.read_test, "print('read green')\n"),
            (self.refactor_test, "print('refactor green')\n"),
            (self.policy_test, "print('policy green')\n"),
            (self.integration_test, "print('integration green')\n"),
            (self.e2e_test, "print('e2e green')\n"),
            (self.environment_test,
             "import os\nassert os.environ.get('ORDINARY_FIXTURE_TEMPLATE_ENV') == 'ready', 'template environment unavailable'\nprint('environment green')\n"),
            ("specs/REQUIREMENT.md", "# Current requirement\n"),
            ("specs/CHANGE-VERIFICATION-001.md", "planner\n"),
            ("quality-graph.yml", "version: 1\n"),
        ):
            target = self.root / path
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_text(content)
        self.policy = {
            "version": 1, "graph": "quality-graph.yml",
            "spec": "specs/CHANGE-VERIFICATION-001.md",
            "suite_inventory": "tools/verification/suites.tsv",
            "runtimes": {".py": "python3", ".php": "php"},
            "category_argv": {
                "unit": [["python3", self.presentation_test]],
                "governance": [["python3", self.policy_test]],
                "integration": [["python3", self.integration_test]],
                "e2e": [["python3", self.e2e_test]],
            },
            "boundaries": [
                {"name": "server-rendered-presentation", "patterns": ["app/YiiRuntime/Views/**"],
                 "categories": ["unit"], "tests": [self.presentation_test],
                 "fast_class": "bounded-server-rendered-presentation"},
                {"name": "ordinary-read", "patterns": ["app/ReadModel/**"],
                 "categories": ["unit"], "tests": [self.read_test]},
                {"name": "application-code", "patterns": ["app/YiiRuntime/Views/**"],
                 "categories": ["unit"], "tests": []},
                {"name": "auth", "patterns": ["app/IdentityAccess/**"],
                 "categories": ["governance"], "tests": []},
                {"name": "persistence", "patterns": ["migrations/**"],
                 "categories": ["integration"], "tests": []},
                {"name": "tests", "patterns": ["tests/**"],
                 "categories": ["governance"], "tests": []},
                {"name": "delivery-policy", "patterns": [".quality-graph/**", "tools/**", "specs/**", "openspec/**"],
                 "categories": ["governance"], "tests": []},
                {"name": "dependency-or-runtime", "patterns": ["Dockerfile"],
                 "categories": ["governance"], "tests": []},
            ],
            "verification_lanes": {
                "FAST": ["server-rendered-presentation"],
                "CRITICAL": ["auth", "delivery-policy"],
            },
            "fast_classes": {"bounded-server-rendered-presentation": {
                "companion_boundaries": [],
                "negative_boundaries_checked": ["authorization", "write", "schema", "admission"],
            }},
            "semantic_surfaces": [],
            "consumers": [{"owners": ["app/YiiRuntime/Views/**"],
                           "tests": [self.refactor_test, self.environment_test]}],
            "capability_ownership": [
                {"name": "presentation", "patterns": [self.presentation],
                 "verifiers": [self.presentation_test], "consumers": []},
                {"name": "read", "patterns": [self.read],
                 "verifiers": [self.read_test], "consumers": []},
            ],
            "full_categories": ["unit", "integration", "e2e", "governance"],
            "full_argv": ["make", "test"],
            "environment_profiles": {
                "unit": {"services": [], "languages": ["python3"]},
                "governance": {"services": [], "languages": ["python3"]},
                "integration": {"services": ["mariadb"], "languages": ["python3"]},
                "e2e": {"services": [], "languages": ["python3"]},
            },
            "service_probes": {"mariadb": ["python3", "tools/delivery/probe-environment.py", "service", "mariadb"]},
        }
        self.write_json(".quality-graph/verification-policy.json", self.policy)
        rows = [
            ("unit", "python3", self.presentation_test, "unit"),
            ("unit", "python3", self.read_test, "unit"),
            ("unit", "python3", self.refactor_test, "unit"),
            ("unit", "python3", self.policy_test, "governance"),
            ("unit", "python3", self.integration_test, "integration"),
            ("e2e", "python3", self.e2e_test, "e2e"),
            ("unit", "python3", self.environment_test, "unit"),
        ]
        rows.sort(key=lambda row: (row[0], row[2], row[1], row[3]))
        (self.root / "tools/verification/suites.tsv").write_text(
            "".join("\t".join(row) + "\n" for row in rows))
        (self.root / ".gitignore").write_text("/change.json\n__pycache__/\n*.py[cod]\n")
        self.git("init", "-q")
        self.git("config", "user.email", "fixture@example.invalid")
        self.git("config", "user.name", "fixture")
        self.git("add", ".")
        self.git("commit", "-qm", "base")
        self.base = self.git("rev-parse", "HEAD").stdout.strip()
        self.environment = dict(os.environ, FMONITOR_HARNESS_HOME=str(Path(self.temp.name) / "evidence"))

    def git(self, *args):
        return subprocess.run(["git", *args], cwd=self.root, text=True,
                              capture_output=True, check=True)

    def write_json(self, relative, value):
        target = self.root / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(json.dumps(value, ensure_ascii=False) + "\n")

    def declaration(self, kind, regression, classification="ORDINARY"):
        return {
            "intent": "COMPACT_MAINTENANCE", "issue": "fixture-general-rule",
            "change_kind": kind, "requirement_status": "CURRENT",
            "semantic_change": "AGREED_ORDINARY_CHANGE",
            "sensitivity": {"classification": classification, "boundaries": [],
                            "rationale": "Actual delta preserves access, money, readiness, writes, history, replay, offline, external effects and admission."},
            "canonical_requirements": [{"path": "specs/REQUIREMENT.md"}],
            "executable_regression": regression,
        }

    def prepare(self, paths, lifecycle, tests, role="root", evidence=None):
        self.write_json("change.json", {
            "change": "fixture-without-issue-or-path-whitelist",
            "planned_paths": paths,
            "lifecycle": lifecycle,
            "acceptances": [{"spec_id": "REQ", "acceptance_id": "ordinary",
                             "spec_path": "specs/REQUIREMENT.md", "seam": "public behavior",
                             "tests": tests}],
        })
        argv = ["python3", "tools/delivery/harness.py", "prepare", "--input", "change.json",
                "--base", self.base, "--role", role]
        if evidence:
            argv.extend(["--evidence", str(evidence)])
        result = subprocess.run(argv, cwd=self.root, env=self.environment,
                                text=True, capture_output=True)
        return result, json.loads(result.stdout) if result.returncode == 0 else None

    def evidence_for(self, package):
        records = []
        environment = dict(self.environment, ORDINARY_FIXTURE_TEMPLATE_ENV="ready")
        for obligation in package["local_obligations"]:
            argv = obligation["argv"]
            run = subprocess.run(
                ["python3", "tools/delivery/harness.py", "run", "--reason", "ordinary-route", "--", *argv],
                cwd=self.root, env=environment, text=True, capture_output=True)
            self.assertEqual(0, run.returncode, run.stderr + run.stdout)
            records.append(json.loads(run.stdout)["record_path"])
        return records

    def reviewer_and_ci(self, paths, lifecycle, tests):
        root_result, root_package = self.prepare(paths, lifecycle, tests)
        self.assertEqual(0, root_result.returncode, root_result.stderr)
        records = self.evidence_for(root_package)
        argv = ["python3", "tools/delivery/harness.py", "prepare", "--input", "change.json",
                "--base", self.base, "--role", "reviewer", "--gate", "5"]
        for record in records:
            argv.extend(["--evidence", record])
        reviewer = subprocess.run(argv, cwd=self.root, env=self.environment,
                                  text=True, capture_output=True)
        self.assertEqual(0, reviewer.returncode, reviewer.stderr)
        reviewer_package = json.loads(reviewer.stdout)
        self.assertEqual(root_package["candidate_source"], reviewer_package["candidate_source"])
        plan = json.loads(Path(reviewer_package["plan"]).read_text())
        selector_argv = ["python3", "tools/verification/ci.py", "plan", "--event", "push"]
        if plan["verification_lane"] == "FAST":
            selector_argv.extend(["--verification-plan", reviewer_package["plan"]])
        selection = subprocess.run(selector_argv, cwd=self.root, text=True, capture_output=True)
        self.assertEqual(0, selection.returncode, selection.stderr)
        return reviewer_package, json.loads(selection.stdout)

    def positive_route(self, path, kind, regression, expected_lane, change_regression=False):
        target = self.root / path
        target.write_text(target.read_text() + "// ordinary delta\n")
        planned = [path]
        if change_regression:
            regression_target = self.root / regression
            before = self.git("show", "HEAD:" + regression).stdout
            regression_target.write_text(before + "# changed regression assertion\n")
            self.assertNotEqual(before, regression_target.read_text())
            planned.append(regression)
        result, package = self.prepare(planned, self.declaration(kind, regression), [regression])
        self.assertEqual(0, result.returncode, "INTENDED_RED ordinary class unsupported: " + result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual(["final"], plan["required_reviews"],
                         "INTENDED_RED ordinary lifecycle still adds Gate 3")
        self.assertEqual(expected_lane, plan["verification_lane"])
        self.assertEqual("COMPACT_MAINTENANCE", package["lifecycle"]["route"])
        self.assertEqual(kind, package["lifecycle"]["change_kind"])
        self.assertEqual("single_author", package["lifecycle"]["authorship"])
        actual = {item["path"] for item in plan["paths"]["actual"]}
        if change_regression:
            self.assertIn(regression, actual)
        reviewer, selection = self.reviewer_and_ci(
            planned, self.declaration(kind, regression), [regression])
        self.assertEqual("reviewer", reviewer["role"])
        self.assertEqual("fast" if expected_lane == "FAST" else "full", selection["mode"])
        self.git("checkout", "--", path)
        if change_regression:
            self.git("checkout", "--", regression)

    def test_a_presentation_positive_public_route(self):
        self.positive_route(self.presentation, "PRESENTATION", self.presentation_test, "FAST")

    def test_a_read_positive_public_route_with_full_ci(self):
        self.positive_route(self.read, "READ", self.read_test, "STANDARD")

    def test_g_test_refactor_changed_regression_positive_public_route(self):
        self.positive_route(self.refactor, "APPLICATION_TEST_OR_REFACTOR",
                            self.refactor_test, "STANDARD", change_regression=True)

    def test_c_fast_presentation_includes_changed_test_consumers_and_environment(self):
        (self.root / self.presentation).write_text("<h1>Changed label</h1>\n")
        (self.root / self.presentation_test).write_text("print('changed regression')\n")
        result, package = self.prepare(
            [self.presentation, self.presentation_test],
            self.declaration("PRESENTATION", self.presentation_test), [self.presentation_test])
        self.assertEqual(0, result.returncode, "INTENDED_RED changed mapped test prevents FAST: " + result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual("FAST", plan["verification_lane"],
                         "INTENDED_RED changed mapped presentation regression prevents FAST")
        commands = [item["argv"] for item in plan["commands"]]
        self.assertIn(["python3", self.presentation_test], commands)
        self.assertIn(["python3", self.refactor_test], commands)
        self.assertIn(["python3", self.environment_test], commands)
        self.assertEqual(["final"], plan["required_reviews"])
        failed_environment = subprocess.run(["python3", self.environment_test], cwd=self.root,
                                            text=True, capture_output=True, env=self.environment)
        self.assertNotEqual(0, failed_environment.returncode)
        reviewer, selection = self.reviewer_and_ci(
            [self.presentation, self.presentation_test],
            self.declaration("PRESENTATION", self.presentation_test), [self.presentation_test])
        self.assertEqual("reviewer", reviewer["role"])
        self.assertEqual("fast", selection["mode"])
        self.assertEqual(plan["selected_checks"], selection["selected_checks"])

    def test_b_incomplete_fast_mapping_falls_back_to_full_without_gate3(self):
        incomplete = "app/YiiRuntime/Views/incomplete-owner.php"
        (self.root / incomplete).write_text("<h1>Changed label</h1>\n")
        result, package = self.prepare([incomplete],
            self.declaration("PRESENTATION", self.presentation_test), [self.presentation_test])
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(Path(package["plan"]).read_text())
        self.assertEqual("STANDARD", plan["verification_lane"],
                         "INTENDED_RED incomplete FAST mapping does not fall back to FULL")
        self.assertEqual(["final"], plan["required_reviews"])
        self.assertIn(["make", "test"], [item["argv"] for item in plan["ci_obligations"]])

    def test_d_h_l_sensitive_and_unknown_claims_keep_gate3(self):
        cases = [
            ("app/IdentityAccess/Permission.php", "<?php function grant(){}\n", "PRESENTATION", "ORDINARY"),
            ("migrations/V99.php", "<?php // schema\n", "READ", "ORDINARY"),
            (self.presentation, "<?php function persistHistory(){}\n", "PRESENTATION", "UNKNOWN"),
        ]
        for path, content, kind, classification in cases:
            with self.subTest(path=path, classification=classification):
                target = self.root / path
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_text(content)
                result, package = self.prepare([path, self.presentation_test],
                    self.declaration(kind, self.presentation_test, classification), [self.presentation_test])
                self.assertEqual(0, result.returncode, result.stderr)
                plan = json.loads(Path(package["plan"]).read_text())
                self.assertEqual(["gate3", "final"], plan["required_reviews"])
                self.assertNotEqual("COMPACT_MAINTENANCE", package["lifecycle"]["route"])
                if path != self.presentation:
                    target.unlink()
                else:
                    self.git("checkout", "--", path)

        mixed = self.root / self.presentation
        mixed.write_text("<h1>Label</h1>\n<?php function grantPermission(){}\n")
        result, package = self.prepare([self.presentation],
            self.declaration("PRESENTATION", self.presentation_test), [self.presentation_test])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(["gate3", "final"], json.loads(Path(package["plan"]).read_text())["required_reviews"])

        result, package = self.prepare(["tools/verification/ci.py"],
            self.declaration("APPLICATION_TEST_OR_REFACTOR", self.refactor_test), [self.refactor_test])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(["gate3", "final"], json.loads(Path(package["plan"]).read_text())["required_reviews"])

    def test_e_sensitive_diff_after_prepare_invalidates_exact_source(self):
        result, package = self.prepare([self.presentation],
            self.declaration("PRESENTATION", self.presentation_test), [self.presentation_test])
        self.assertEqual(0, result.returncode, result.stderr)
        sensitive = self.root / "app/IdentityAccess/Permission.php"
        sensitive.write_text("<?php function grant(){}\n")
        check = subprocess.run(["python3", "tools/delivery/change-verification.py", "check",
                                "--plan", package["plan"]], cwd=self.root,
                               text=True, capture_output=True)
        self.assertNotEqual(0, check.returncode)
        stale_reviewer = subprocess.run(
            ["python3", "tools/delivery/harness.py", "prepare", "--input", "change.json",
             "--base", self.base, "--role", "reviewer", "--gate", "5"], cwd=self.root,
            env=self.environment, text=True, capture_output=True)
        self.assertNotEqual(0, stale_reviewer.returncode)
        result, refreshed = self.prepare([self.presentation, "app/IdentityAccess/Permission.php"],
            self.declaration("PRESENTATION", self.presentation_test), [self.presentation_test])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(["gate3", "final"], json.loads(Path(refreshed["plan"]).read_text())["required_reviews"])

    def test_f_ci_admission_rejects_missing_failed_and_unknown_mandatory_checks(self):
        expected = {"plan": "success", "fast": "success", "harness": "skipped",
                    "unit": "success", "integration": "success", "e2e": "success",
                    "governance": "success"}
        for label, mutation in (
            ("missing", lambda value: value.pop("unit")),
            ("failed", lambda value: value.__setitem__("unit", "failure")),
            ("cancelled", lambda value: value.__setitem__("unit", "cancelled")),
            ("incomplete", lambda value: value.__setitem__("unit", "queued")),
            ("unknown", lambda value: value.__setitem__("unit", "unknown")),
        ):
            with self.subTest(label=label):
                evidence = dict(expected)
                mutation(evidence)
                result = subprocess.run(
                    ["python3", "tools/verification/ci.py", "aggregate", "--full", "true",
                     "--mode", "full", "--results", json.dumps(evidence)], cwd=ROOT,
                    text=True, capture_output=True)
                self.assertNotEqual(0, result.returncode)
                self.assertNotIn("VERIFY_OK", result.stdout)

    def test_j_k_correction_contract_controls_review_recompute(self):
        unchanged = self.declaration("READ", self.read_test)
        unchanged["correction"] = {"contract": "UNCHANGED", "risk": "UNCHANGED"}
        (self.root / self.read).write_text("<?php // corrected read projection\n")
        result, package = self.prepare([self.read], unchanged, [self.read_test])
        self.assertEqual(0, result.returncode, "INTENDED_RED correction declaration unsupported: " + result.stderr)
        self.assertEqual(["final"], json.loads(Path(package["plan"]).read_text())["required_reviews"],
                         "INTENDED_RED unchanged-contract correction adds Gate 3")
        sensitive = self.declaration("READ", self.read_test)
        sensitive["correction"] = {"contract": "CHANGED", "risk": "NEW_SENSITIVE_RISK"}
        result, package = self.prepare([self.read], sensitive, [self.read_test])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(["gate3", "final"], json.loads(Path(package["plan"]).read_text())["required_reviews"])

    def test_historical_diffs_are_assessed_not_claimed_as_reexecuted(self):
        policy_text = (ROOT / ".quality-graph/verification-policy.json").read_text()
        for identifier in ("#187", "#194", "#209"):
            self.assertNotIn(identifier, policy_text)
        descriptor = json.loads((ROOT / "tests/fixtures/delivery/ordinary-change-process/replays.json").read_text())
        expected = {
            "187": {"commit": "e245ba1c", "class": "PRESENTATION", "applicable": False,
                    "rationale": "Фактический merge сочетает read projections, Yii views/regressions и изменение verification policy; product-часть соответствует ordinary presentation/read, но весь historical diff является admission-policy change.",
                    "required_checks": "Gate 3 + final для полного historical diff; object card/list regressions; FULL CI",
                    "required_paths": {".quality-graph/verification-policy.json", "app/InstallationProcess/MariaDbYiiObjectCard.php", "app/YiiRuntime/Views/object-card.php", "tests/Yii2/yii2_object_card_001_test.php"}},
            "194": {"commit": "5bc6a225", "class": "APPLICATION_TEST_OR_REFACTOR", "applicable": True,
                    "rationale": "Фактический diff дедуплицирует test setup при сохранении schema expectations; delivery policy artifacts требуют собственного sensitive process, но аналогичная application-test delta является ordinary.",
                    "required_checks": "all changed schema tests; helper consumers; FULL CI",
                    "required_paths": {"tests/Support/CurrentProductionSchemaContract.php", "tests/Runtime/production_schema_frontier_001_test.php"}},
            "209": {"commit": "ae0a9596", "class": "READ", "applicable": True,
                    "rationale": "Фактическая product delta меняет completed filter read projection и связанные Yii/browser regressions без write/schema/access change.",
                    "required_checks": "read regression; Yii active queue; browser consumer; environment checks; CI breadth by complete mapping",
                    "required_paths": {"app/InspectionEvidence/MariaDbYiiChecklistRead.php", "tests/Yii2/yii2_construction_control_active_queue_001_test.php"}},
        }
        actual_descriptor = {item["issue"]: item for item in descriptor["historical"]}
        self.assertEqual(set(expected), set(actual_descriptor))
        for issue, expectation in expected.items():
            for key in ("commit", "class", "applicable", "rationale", "required_checks"):
                self.assertEqual(expectation[key], actual_descriptor[issue][key],
                                 f"historical #{issue} {key} assessment")
        commits = {item["issue"]: item["commit"] for item in descriptor["historical"]}
        observed = {}
        for issue, commit in commits.items():
            changed = subprocess.run(["git", "diff", "--name-only", commit + "^", commit],
                                     cwd=ROOT, text=True, capture_output=True, check=True).stdout.splitlines()
            self.assertTrue(changed, "historical replay must resolve an actual delta")
            self.assertTrue(expected[issue]["required_paths"] <= set(changed),
                            f"historical #{issue} factual paths")
            observed[issue] = {"class": expected[issue]["class"], "paths": changed,
                               "applicable": expected[issue]["applicable"],
                               "rationale": expected[issue]["rationale"],
                               "checks": expected[issue]["required_checks"]}
        self.assertEqual(set(commits), set(observed))
        self.assertTrue(any(path.startswith("tests/") for path in observed["194"]["paths"]))
        self.assertTrue(any("MariaDbYiiChecklistRead.php" in path for path in observed["209"]["paths"]))
        self.assertFalse(observed["187"]["applicable"], "admission-policy historical diff is not ordinary")
        self.assertTrue(observed["194"]["applicable"])
        self.assertTrue(observed["209"]["applicable"])
        self.assertIn("FULL CI", observed["194"]["checks"])
        self.assertIn("read regression", observed["209"]["checks"])

    def test_i_unseen_analogue_uses_general_rule_without_exact_path_registration(self):
        descriptor = json.loads((ROOT / "tests/fixtures/delivery/ordinary-change-process/replays.json").read_text())
        unseen = descriptor["unseen"]["fixture_path"]
        (self.root / unseen).write_text("<p>unseen analogue</p>\n")
        result, package = self.prepare([unseen],
            self.declaration(descriptor["unseen"]["class"], descriptor["unseen"]["regression"]),
            [descriptor["unseen"]["regression"]])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(["final"], json.loads(Path(package["plan"]).read_text())["required_reviews"],
                         "INTENDED_RED unseen analogue requires path whitelist")
        self.assertNotIn(unseen, json.dumps(self.policy))
        reviewer, selection = self.reviewer_and_ci(
            [unseen], self.declaration(descriptor["unseen"]["class"], descriptor["unseen"]["regression"]),
            [descriptor["unseen"]["regression"]])
        self.assertEqual("reviewer", reviewer["role"])
        self.assertIn(selection["mode"], {"fast", "full"})


if __name__ == "__main__":
    unittest.main(verbosity=2)
