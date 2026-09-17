#!/usr/bin/env python3
"""CHANGE-VERIFICATION-PLACEMENT-181 executable placement regression."""
import importlib.util
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
SPEC = importlib.util.spec_from_file_location(
    "semantic_fixture", ROOT / "tests/Verification/change_verification_semantic_closure_153_test.py")
MODULE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)


class Placement(MODULE.SemanticClosure):
    def build_semantic(self):
        result = self.build(["app/Domain/CurrentState.php"])
        self.assertEqual(0, result.returncode, result.stderr)
        return self.plan()

    def test_semantic_only_closure_is_ci_only_but_full_ci_remains(self):
        plan = self.build_semantic()
        closure = [item for item in plan["commands"]
                   if "semantic integration closure" in item.get("rationales", [])]
        self.assertEqual(self.integration_checks, [item["argv"] for item in closure])
        self.assertTrue(all(item.get("execution") == "ci" for item in closure),
                        "INTENDED_RED semantic-only checks still execute locally")
        self.assertIn({"argv": ["make", "test"], "phase": "integration",
                       "execution": "ci", "rationales": [
                           "mandatory full CI for code, test, policy or unknown impact"]},
                      plan["commands"])

    def test_multiple_reasons_promote_once_to_local_and_preserve_reasons(self):
        self.policy["boundaries"][1]["tests"] = [self.DOWNSTREAM_A]
        self.policy["boundaries"][1]["categories"] = ["unit", "integration"]
        self.write_json(".quality-graph/verification-policy.json", self.policy)
        plan = self.build_semantic()
        matches = [item for item in plan["commands"] if item["argv"] == ["python3", self.DOWNSTREAM_A]]
        self.assertEqual(1, len(matches))
        self.assertEqual("local", matches[0].get("execution"),
                         "INTENDED_RED stronger local reason did not win")
        self.assertEqual(["changed boundary obligation", "semantic integration closure"],
                         matches[0].get("rationales"))
        first = json.dumps(self.plan()["commands"], sort_keys=True)
        self.policy["semantic_surfaces"].reverse()
        self.write_json(".quality-graph/verification-policy.json", self.policy)
        self.assertEqual(0, self.build(["app/Domain/CurrentState.php"]).returncode)
        self.assertEqual(first, json.dumps(self.plan()["commands"], sort_keys=True),
                         "discovery order changed deterministic plan")

    def test_changed_registered_test_and_known_consumers_stay_local(self):
        (self.root / self.DOWNSTREAM_A).write_text("raise SystemExit(0)\n# changed\n")
        plan = self.build_semantic()
        changed = next(item for item in plan["commands"]
                       if item["argv"] == ["python3", self.DOWNSTREAM_A])
        self.assertEqual("local", changed.get("execution"))
        self.assertEqual(["changed registered test", "semantic integration closure"],
                         changed.get("rationales"))

        frontier_spec = importlib.util.spec_from_file_location(
            "consumer_fixture", ROOT / "tests/Verification/change_verification_consumer_frontier_153_test.py")
        frontier_module = importlib.util.module_from_spec(frontier_spec); frontier_spec.loader.exec_module(frontier_module)
        fixture = frontier_module.ConsumerFrontier("runTest"); fixture.setUp()
        try:
            result = fixture.build(["app/Schema/CanonicalMigration.php"])
            self.assertEqual(0, result.returncode, result.stderr)
            consumer_plan = fixture.plan()
            for path in [fixture.MIGRATION, fixture.RECOVERY, fixture.RUNTIME]:
                command = next(item for item in consumer_plan["commands"] if item["argv"][-1] == path)
                self.assertEqual("local", command.get("execution"))
                self.assertIn("consumer ownership frontier", command.get("rationales", []))
        finally:
            fixture.doCleanups()

    def test_focused_runner_executes_local_and_not_ci_only(self):
        shutil.copy2(ROOT / "tools/delivery/harness.py", self.root / "tools/delivery/harness.py")
        shutil.copy2(ROOT / "tools/delivery/harness_context.py", self.root / "tools/delivery/harness_context.py")
        plan = self.build_semantic()
        markers = {}
        for path in [self.DIRECT, self.REPRESENTATIVE, self.DOWNSTREAM_A, self.DOWNSTREAM_Z]:
            marker = Path(self.tmp.name).parent / (Path(self.tmp.name).name + "-" + path.replace("/", "_") + ".ran")
            markers[path] = marker
            self.addCleanup(lambda value=marker: value.unlink(missing_ok=True))
            (self.root / path).write_text(
                "from pathlib import Path\nPath(%r).write_text('ran')\n" % str(marker))
        (self.root / "Makefile").write_text("governance:\n\t@true\n")
        self.command("git", "add", ".", check=True)
        self.command("git", "commit", "-qm", "executable placement fixture", check=True)
        self.base = self.command("git", "rev-parse", "HEAD", check=True).stdout.strip()
        # Rebuild after fixture byte changes so check/run sees an exact plan.
        plan = self.build_semantic()
        result = self.command("python3", "tools/delivery/change-verification.py", "run",
                              "--plan", "plan.json", "--phase", "focused")
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertTrue(markers[self.DIRECT].exists())
        for path in [self.REPRESENTATIVE, self.DOWNSTREAM_A, self.DOWNSTREAM_Z]:
            self.assertFalse(markers[path].exists(),
                             "INTENDED_RED CI-only verifier ran in focused phase")

    def test_broken_local_verifier_still_fails_focused(self):
        shutil.copy2(ROOT / "tools/delivery/harness.py", self.root / "tools/delivery/harness.py")
        shutil.copy2(ROOT / "tools/delivery/harness_context.py", self.root / "tools/delivery/harness_context.py")
        (self.root / "Makefile").write_text("governance:\n\t@true\n")
        (self.root / self.DIRECT).write_text("raise SystemExit(23)\n")
        self.command("git", "add", ".", check=True)
        self.command("git", "commit", "-qm", "controlled failing direct verifier", check=True)
        self.base = self.command("git", "rev-parse", "HEAD", check=True).stdout.strip()
        (self.root / "app/Domain/CurrentState.php").write_text("after\n")
        self.assertEqual(0, self.build(["app/Domain/CurrentState.php"]).returncode)
        result = self.command("python3", "tools/delivery/change-verification.py", "run",
                              "--plan", "plan.json", "--phase", "focused")
        self.assertNotEqual(0, result.returncode)
        self.assertIn(self.DIRECT, result.stdout + result.stderr)

    def test_promoted_multi_reason_command_executes_once(self):
        shutil.copy2(ROOT / "tools/delivery/harness.py", self.root / "tools/delivery/harness.py")
        shutil.copy2(ROOT / "tools/delivery/harness_context.py", self.root / "tools/delivery/harness_context.py")
        marker = Path(self.tmp.name).parent / (Path(self.tmp.name).name + "-promoted-count")
        self.addCleanup(lambda: marker.unlink(missing_ok=True))
        (self.root / self.DOWNSTREAM_A).write_text(
            "from pathlib import Path\np=Path(%r); p.write_text(str(int(p.read_text())+1) if p.exists() else '1')\n" % str(marker))
        (self.root / "Makefile").write_text("governance:\n\t@true\n")
        self.command("git", "add", ".", check=True)
        self.command("git", "commit", "-qm", "promoted execution fixture", check=True)
        self.base = self.command("git", "rev-parse", "HEAD", check=True).stdout.strip()
        (self.root / self.DOWNSTREAM_A).write_text(
            "from pathlib import Path\np=Path(%r); p.write_text(str(int(p.read_text())+1) if p.exists() else '1')\n# changed\n" % str(marker))
        plan = self.build_semantic()
        command = next(item for item in plan["commands"] if item["argv"][-1] == self.DOWNSTREAM_A)
        self.assertEqual("local", command.get("execution"))
        result = self.command("python3", "tools/delivery/change-verification.py", "run",
                              "--plan", "plan.json", "--phase", "focused")
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("1", marker.read_text(), "promoted command executed more than once")

    def test_plan_exposes_local_and_ci_obligations_for_package_consumers(self):
        plan = self.build_semantic()
        self.assertEqual(
            {"local", "ci"},
            {item.get("execution") for item in plan["commands"]},
            "INTENDED_RED reviewer package cannot distinguish local evidence from pending CI")
        self.assertTrue(plan.get("ci_obligations"),
                        "INTENDED_RED CI obligations are not explicit for package/CI consumers")

    def test_real_reviewer_package_accepts_local_evidence_and_lists_pending_ci(self):
        harness_spec = importlib.util.spec_from_file_location(
            "harness_contract", ROOT / "tests/Verification/delivery_harness_001_test.py")
        harness_module = importlib.util.module_from_spec(harness_spec); harness_spec.loader.exec_module(harness_module)
        fixture = harness_module.Harness("runTest"); fixture.setUp()
        try:
            policy_path = fixture.repo / ".quality-graph/verification-policy.json"
            policy = json.loads(policy_path.read_text())
            policy.setdefault("semantic_surfaces", []).append({
                "name": "package-placement-fixture", "patterns": [fixture.test],
                "category": "integration", "reason": "fixture semantic closure"})
            policy.setdefault("capability_ownership", []).append({
                "name": "package-placement-owner", "patterns": [fixture.test],
                "verifiers": [], "consumers": []})
            policy_path.write_text(json.dumps(policy, sort_keys=True) + "\n")
            run = fixture.cli("run", "--", "python3", fixture.test)
            self.assertEqual(0, run.returncode, run.stderr)
            record = json.loads(run.stdout)["record_path"]
            prepared = fixture.cli("prepare", "--input", fixture.input, "--base", fixture.base,
                                   "--role", "reviewer", "--gate", "5", "--evidence", record)
            self.assertEqual(0, prepared.returncode, "INTENDED_RED honest reviewer package refused: " + prepared.stderr)
            package = json.loads(prepared.stdout)
            plan = json.loads(Path(package["plan"]).read_text())
            expected_local = [item for item in plan["commands"] if item.get("execution") == "local"]
            expected_ci = plan.get("ci_obligations", [])
            self.assertTrue(expected_local, "INTENDED_RED planner local inventory absent")
            self.assertTrue(expected_ci, "INTENDED_RED planner CI inventory absent")
            self.assertEqual(expected_local, package.get("local_obligations"), "INTENDED_RED local inventory absent")
            self.assertEqual(expected_ci, package.get("ci_obligations"), "INTENDED_RED pending CI inventory absent")
            self.assertEqual(1, len(package["evidence"]), "CI-only evidence must not be fabricated")
            evidence = package["evidence"][0]
            self.assertEqual(["python3", fixture.test], evidence["argv"])
            self.assertEqual(package["candidate_source"], evidence["source"])
            self.assertIn(evidence["argv"], [item["argv"] for item in expected_local])
            self.assertTrue(all(item["argv"] not in [e["argv"] for e in package["evidence"]]
                                for item in expected_ci))
            missing = fixture.cli("prepare", "--input", fixture.input, "--base", fixture.base,
                                  "--role", "reviewer", "--gate", "5")
            self.assertNotEqual(0, missing.returncode, "missing local evidence was admitted")
            valid_policy = json.loads(policy_path.read_text())
            cases = []
            conflict = json.loads(json.dumps(valid_policy))
            conflict["capability_ownership"].append({
                "name": "package-placement-owner-conflict", "patterns": [fixture.test],
                "verifiers": [], "consumers": []})
            cases.append((conflict, "PROTECTED_CAPABILITY_OWNER_AMBIGUOUS"))
            invalid = json.loads(json.dumps(valid_policy))
            invalid["semantic_surfaces"][-1]["category"] = "unit"
            cases.append((invalid, "semantic surface requires integration category"))
            for candidate, diagnostic in cases:
                policy_path.write_text(json.dumps(candidate, sort_keys=True) + "\n")
                rejected = fixture.cli("prepare", "--input", fixture.input, "--base", fixture.base,
                                       "--role", "root")
                self.assertNotEqual(0, rejected.returncode)
                self.assertIn(diagnostic, rejected.stdout + rejected.stderr)
            policy_path.write_text(json.dumps(valid_policy, sort_keys=True) + "\n")
            catalog = fixture.repo / "tools/verification/suites.tsv"
            original_catalog = catalog.read_text()
            catalog.write_text("\n".join(line for line in original_catalog.splitlines()
                                         if not line.endswith("\tintegration")) + "\n")
            rejected = fixture.cli("prepare", "--input", fixture.input, "--base", fixture.base,
                                   "--role", "root")
            self.assertNotEqual(0, rejected.returncode)
            self.assertIn("SEMANTIC_INTEGRATION_CLOSURE_UNAVAILABLE", rejected.stdout + rejected.stderr)
        finally:
            fixture.doCleanups()

    def test_existing_full_ci_aggregate_rejects_missing_skip_failure_and_cancel(self):
        spec = importlib.util.spec_from_file_location("ci", ROOT / "tools/verification/ci.py")
        ci = importlib.util.module_from_spec(spec); spec.loader.exec_module(ci)
        expected = {name: value.casefold() for name, value in ci.expected_job_conclusions("full").items()}
        # The workflow exposes the two shards to the aggregate as one integration conclusion.
        good = {"plan": "success", "fast": "success", "unit": "success",
                "integration": "success", "e2e": "success", "governance": "success"}
        ci.aggregate("true", json.dumps(good))
        for state in (None, "skipped", "failure", "cancelled"):
            broken = dict(good)
            if state is None: broken.pop("integration")
            else: broken["integration"] = state
            with self.assertRaises(ValueError):
                ci.aggregate("true", json.dumps(broken))
        self.assertEqual("SUCCESS", ci.expected_job_conclusions("full")["Integration (1/2)"])
        self.assertEqual("SUCCESS", ci.expected_job_conclusions("full")["Integration (2/2)"])

    def test_shipped_187_before_after_contract_is_pinned_without_running_it(self):
        delivered = json.loads((ROOT / "tests/fixtures/delivery/issue-49-delivery.json").read_text())
        self.assertEqual("issue-49-object-identifiers", delivered["change"])
        self.assertEqual(8, len(delivered["planned_paths"]))
        with tempfile.TemporaryDirectory(prefix="issue-187-placement-") as directory:
            checkout = Path(directory) / "repo"
            subprocess.run(["git", "clone", "-q", "--shared", str(ROOT), str(checkout)], check=True)
            subprocess.run(["git", "checkout", "-q", "e245ba1c173cc09f9183380228a7532c8dc942d2"], cwd=checkout, check=True)
            before_run = subprocess.run([
                "python3", "tools/delivery/change-verification.py", "plan",
                "--base", "b509e9147b78b6a68008c287e8f4323e9879230f",
                "--input", "tests/fixtures/delivery/issue-49-delivery.json",
                "--output", "before.json"], cwd=checkout, text=True, capture_output=True)
            self.assertEqual(0, before_run.returncode, before_run.stderr)
            before = json.loads((checkout / "before.json").read_text())
            (checkout / "before.json").unlink()
            shutil.copy2(ROOT / "tools/delivery/change-verification.py",
                         checkout / "tools/delivery/change-verification.py")
            result = subprocess.run([
                "python3", "tools/delivery/change-verification.py", "plan",
                "--base", "b509e9147b78b6a68008c287e8f4323e9879230f",
                "--input", "tests/fixtures/delivery/issue-49-delivery.json",
                "--output", "plan.json"], cwd=checkout, text=True, capture_output=True)
            self.assertEqual(0, result.returncode, result.stderr)
            after = json.loads((checkout / "plan.json").read_text())
        local = [item for item in after["commands"] if item.get("execution") == "local"]
        self.assertEqual(4, len(local), "#187 after-local command count")
        self.assertEqual(279, len([item for item in before["commands"] if item["phase"] == "focused"]))
        old_ci = [item["argv"] for item in before["commands"]
                  if item["rationale"] == "semantic integration closure" or item["phase"] == "integration"]
        self.assertEqual(old_ci, [item["argv"] for item in after.get("ci_obligations", [])],
                         "#187 CI obligation inventory changed")


if __name__ == "__main__":
    unittest.main(verbosity=2)
