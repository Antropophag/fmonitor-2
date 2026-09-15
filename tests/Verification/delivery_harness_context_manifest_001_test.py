#!/usr/bin/env python3
"""TASK-CONTEXT-MANIFEST-001: public prepare/package cases A-L."""
import hashlib
import importlib.util
import json
from pathlib import Path
import subprocess
import sys
import unittest

ROOT = Path(__file__).resolve().parents[2]
SPEC = importlib.util.spec_from_file_location("delivery_harness_contract", ROOT / "tests/Verification/delivery_harness_001_test.py")
BASE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(BASE)


class ContextManifest(unittest.TestCase):
    COMMON = {"instruction.constitution", "delivery.workflow", "current.actionable-goal"}
    EXPECTED = {
        "ui": COMMON | {"ui.presentation"},
        "persistence": COMMON | {"product.core", "product.context", "pilot.behavior", "pilot.data-model", "persistence.current-state", "domain.state-history", "security.authorization"},
        "auth": COMMON | {"product.core", "product.context", "pilot.behavior", "security.authorization", "security.session-csrf-secrets"},
        "harness": COMMON | {"delivery.verification-governance"},
    }
    CONSERVATIVE_FULL = {"AGENTS.md", "docs/development-process.md", "docs/operations/current-delivery-goal.md", "PRODUCT.md", "CONTEXT.md", "docs/fmonitor-2-pilot-spec.md", "docs/fmonitor-2-pilot-data-model.md"}

    def fixture(self):
        value = BASE.Harness(methodName="runTest")
        value.setUp()
        self.addCleanup(value.doCleanups)
        for relative in ("PRODUCT.md", "CONTEXT.md", "docs/fmonitor-2-pilot-spec.md", "docs/fmonitor-2-pilot-data-model.md"):
            target = value.repo / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_text((ROOT / relative).read_text())
        index = ROOT / "tools/delivery/context-sections.json"
        if index.exists():
            (value.repo / "tools/delivery/context-sections.json").write_bytes(index.read_bytes())
        history = value.repo / "docs/operations/current-delivery-goal-history-old.md"
        history.write_text("historical goal that must not be mandatory inline\n")
        value.git("add", ".")
        value.git("commit", "-qm", "context fixture")
        return value

    def prepared(self, paths, role="root"):
        value = self.fixture()
        for relative in paths:
            target = value.repo / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            if not target.exists():
                target.write_text("fixture\n")
        spec = json.loads((value.repo / value.input).read_text())
        spec["change"] = "context-case"
        spec["planned_paths"] = paths + [value.test]
        value.write(value.input, spec)
        extra = []
        if role == "reviewer":
            current_review = value.repo / "reviews/tests/CURRENT.md"
            current_review.parent.mkdir(parents=True, exist_ok=True)
            current_review.write_text("current review input\n")
            run = value.cli("run", "--", sys.executable, value.test)
            self.assertEqual(0, run.returncode, run.stderr)
            extra = ["--evidence", json.loads(run.stdout)["record_path"]]
        result = value.cli("prepare", "--input", value.input, "--base", value.base, "--role", role, *extra)
        self.assertEqual(0, result.returncode, "INTENDED_RED task-context prepare absent: " + result.stderr)
        package = json.loads(result.stdout)
        self.assertIn("context_manifest", package, "INTENDED_RED package does not expose manifest")
        manifest_path = Path(package["context_manifest"])
        self.assertTrue(manifest_path.is_file())
        self.assertEqual(hashlib.sha256(manifest_path.read_bytes()).hexdigest(), package["context_manifest_sha256"])
        manifest = json.loads(manifest_path.read_text())
        self.assertEqual("fmonitor-task-context-v1", manifest["schema"])
        self.assertEqual(package["candidate_source"], manifest["task"]["source"])
        self.assertEqual(value.base, manifest["task"]["base"])
        self.assertEqual("context-case", manifest["task"]["change"])
        self.assertEqual(role, manifest["task"]["role"])
        self.assertTrue(Path(package["required_context_path"]).is_file())
        self.assertEqual({"mode": "task_context_manifest", "path": package["required_context_path"]}, package["context_delivery"])
        self.assertFalse(package.get("rules"), "legacy whole-document rules must not remain a second mandatory route")
        self.assertEqual("UNKNOWN", package["context_metrics"]["token_usage"])
        self.assertRegex(manifest["section_index"]["digest"], r"^[0-9a-f]{64}$")
        self.assertEqual(1, manifest["section_index"]["version"])
        materialized = json.loads(Path(package["required_context_path"]).read_text())
        self.assertEqual(package["required_context_sha256"], hashlib.sha256(Path(package["required_context_path"]).read_bytes()).hexdigest())
        self.assertEqual(len(manifest["required_context"]), len(materialized["items"]))
        for item in manifest["required_context"]:
            self.assertIn(item["load_mode"], {"inline", "required_reference"})
            self.assertRegex(item["digest"], r"^[0-9a-f]{64}$")
            self.assertTrue(item["source"])
            self.assertTrue(item["reason"])
            self.assertIn("content_reference", item)
            source_bytes = (value.repo / item["source"]).read_bytes()
            ref = item["content_reference"]
            excerpt = source_bytes[ref["start_byte"]:ref["end_byte"]]
            self.assertEqual(item["digest"], hashlib.sha256(excerpt).hexdigest())
            delivered = next(entry for entry in materialized["items"] if entry["rule_id"] == item["rule_id"] and entry["source"] == item["source"])
            self.assertEqual(excerpt.decode(), delivered["content"])
        return value, package, manifest

    def rule_ids(self, manifest):
        return {item["rule_id"] for item in manifest["required_context"]}

    def test_A_to_D_profiles_and_J_history(self):
        _v, ui, ui_manifest = self.prepared(["app/YiiRuntime/Views/example.php"])
        self.assertEqual(self.EXPECTED["ui"], self.rule_ids(ui_manifest))
        self.assertNotIn("persistence.current-state", self.rule_ids(ui_manifest))
        self.assertNotIn("security.authorization", self.rule_ids(ui_manifest))
        self.assertTrue(any(item["source"].startswith("docs/operations/current-delivery-goal-history") for item in ui_manifest["load_on_demand"]))

        _v, _p, persistence = self.prepared(["app/InstallationProcess/MariaDbExample.php"])
        self.assertEqual(self.EXPECTED["persistence"], self.rule_ids(persistence))

        _v, _a, auth = self.prepared(["app/IdentityAccess/AuthorizeExample.php"])
        self.assertEqual(self.EXPECTED["auth"], self.rule_ids(auth))

        _v, _h, harness = self.prepared(["tools/delivery/example.py"])
        self.assertEqual(self.EXPECTED["harness"], self.rule_ids(harness))
        self.assertFalse(any(item["source"] in {"PRODUCT.md", "CONTEXT.md", "docs/fmonitor-2-pilot-spec.md"} for item in harness["required_context"]))
        self.assertLess(ui["context_metrics"]["mandatory_bytes"], 78637)

        _v, executor, executor_manifest = self.prepared(["tools/delivery/example.py"], role="executor")
        self.assertEqual("executor", executor_manifest["task"]["role"])
        _v, reviewer, reviewer_manifest = self.prepared(["tools/delivery/example.py"], role="reviewer")
        self.assertEqual("reviewer", reviewer_manifest["task"]["role"])
        self.assertTrue(reviewer["evidence"])
        self.assertEqual(reviewer["contracts"], reviewer_manifest["product_spec"]["contracts"])
        self.assertEqual(reviewer["evidence"], reviewer_manifest["verification"]["evidence"])
        self.assertEqual(reviewer["candidate_source"], reviewer_manifest["verification"]["candidate_source"])
        self.assertEqual(reviewer["snapshot"], reviewer_manifest["verification"]["snapshot"])
        self.assertIn("reviews/tests/CURRENT.md", reviewer_manifest["verification"]["reviews"])
        for contract in reviewer_manifest["product_spec"]["references"]:
            self.assertEqual(hashlib.sha256((_v.repo / contract["source"]).read_bytes()).hexdigest(), contract["digest"])

    def test_E_to_H_digest_freshness_unknown_fallback_and_repeat(self):
        value, first, manifest = self.prepared(["app/YiiRuntime/Views/example.php"])
        second = value.cli("prepare", "--input", value.input, "--base", value.base, "--role", "root")
        self.assertEqual(0, second.returncode, second.stderr)
        second_package = json.loads(second.stdout)
        self.assertEqual(first["context_manifest_sha256"], second_package["context_manifest_sha256"])
        self.assertEqual(first["required_context_sha256"], second_package["required_context_sha256"])

        (value.repo / "AGENTS.md").write_text((value.repo / "AGENTS.md").read_text() + "\nNew canonical instruction.\n")
        fresh = value.cli("prepare", "--input", value.input, "--base", value.base, "--role", "root")
        self.assertEqual(0, fresh.returncode, fresh.stderr)
        fresh_package = json.loads(fresh.stdout)
        self.assertNotEqual(first["context_manifest_sha256"], fresh_package["context_manifest_sha256"])
        fresh_manifest = json.loads(Path(fresh_package["context_manifest"]).read_text())
        old_agents = next(item for item in manifest["required_context"] if item["source"] == "AGENTS.md")
        new_agents = next(item for item in fresh_manifest["required_context"] if item["source"] == "AGENTS.md")
        self.assertNotEqual(old_agents["source_digest"], new_agents["source_digest"])
        self.assertEqual(hashlib.sha256((value.repo / "AGENTS.md").read_bytes()).hexdigest(), new_agents["source_digest"])

        _v, _u, unknown = self.prepared(["unknown-boundary/example.xyz"])
        self.assertEqual("conservative", unknown["applicability"]["mode"])
        full_sources = {item["source"] for item in unknown["required_context"] if item["rule_id"] == "FULL_DOCUMENT"}
        self.assertEqual(self.CONSERVATIVE_FULL, full_sources)

    def test_I_K_L_reviewer_reconstruction_index_safety_and_no_false_green(self):
        value, package, manifest = self.prepared(["tools/delivery/example.py"])
        for item in manifest["required_context"]:
            source = value.repo / item["source"]
            self.assertTrue(source.is_file())
            self.assertEqual(hashlib.sha256(source.read_bytes()).hexdigest(), item["source_digest"])
        self.assertEqual("NOT_REVIEWED", package["approval"])

        index = value.repo / "tools/delivery/context-sections.json"
        if index.exists():
            broken = json.loads(index.read_text())
            corrupted_source = broken["sections"][0]["source"]
            broken["sections"][0]["heading"] = "## definitely absent heading"
            index.write_text(json.dumps(broken) + "\n")
            rebuilt = value.cli("prepare", "--input", value.input, "--base", value.base, "--role", "root")
            self.assertEqual(0, rebuilt.returncode, rebuilt.stderr)
            rebuilt_manifest = json.loads(Path(json.loads(rebuilt.stdout)["context_manifest"]).read_text())
            self.assertTrue(any(item["rule_id"] == "FULL_DOCUMENT" and item["source"] == corrupted_source for item in rebuilt_manifest["required_context"]))

        state = value.cli("state")
        self.assertEqual(0, state.returncode, state.stderr)
        state_value = json.loads(state.stdout)
        self.assertFalse(state_value["merge_ready"])
        self.assertFalse(state_value["publication_ready"])

        for relative in ("reviews/tests/OLD.md", "docs/operations/old-evidence.md"):
            target = value.repo / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_text("historical\n")
        historical = value.cli("prepare", "--input", value.input, "--base", value.base, "--role", "root")
        self.assertEqual(0, historical.returncode, historical.stderr)
        historical_manifest = json.loads(Path(json.loads(historical.stdout)["context_manifest"]).read_text())
        lod = {item["source"]: item for item in historical_manifest["load_on_demand"]}
        for relative in ("docs/operations/current-delivery-goal-history-old.md", "reviews/tests/OLD.md", "docs/operations/old-evidence.md"):
            self.assertIn(relative, lod)
            self.assertEqual(hashlib.sha256((value.repo / relative).read_bytes()).hexdigest(), lod[relative]["digest"])
            self.assertEqual({"path": relative}, lod[relative]["content_reference"])

    def test_measurement_replays_three_completed_changes(self):
        result = subprocess.run([sys.executable, "tools/delivery/measure-task-context.py", "--baseline", "docs/operations/issue-157-task-context-manifest-baseline.json"], cwd=ROOT, text=True, capture_output=True)
        self.assertEqual(0, result.returncode, "INTENDED_RED deterministic measurement absent: " + result.stderr)
        report = json.loads(result.stdout)
        repeat = subprocess.run([sys.executable, "tools/delivery/measure-task-context.py", "--baseline", "docs/operations/issue-157-task-context-manifest-baseline.json"], cwd=ROOT, text=True, capture_output=True, check=True)
        self.assertEqual(report, json.loads(repeat.stdout))
        self.assertEqual("UNKNOWN", report["token_usage"])
        self.assertEqual({"bounded_presentation_ui", "persistence_current_state", "harness_verification"}, {item["id"] for item in report["cases"]})
        baseline = json.loads((ROOT / "docs/operations/issue-157-task-context-manifest-baseline.json").read_text())
        baseline_cases = {item["id"]: item for item in baseline["cases"]}
        for item in report["cases"]:
            expected = baseline_cases[item["id"]]
            self.assertEqual(expected["mandatory_bytes"], item["before"]["mandatory_bytes"])
            self.assertEqual(expected["mandatory_characters"], item["before"]["mandatory_characters"])
            self.assertEqual(expected["whole_documents"], item["before"]["whole_documents"])
            self.assertEqual(expected["load_on_demand_references"], item["before"]["load_on_demand_references"])
            self.assertEqual(expected["whole_sources"], item["before"]["whole_sources"])
            self.assertEqual(expected["obviously_historical_or_unrelated"], item["before"]["obviously_historical_or_unrelated"])
            self.assertEqual(hashlib.sha256((ROOT / expected["input"]).read_bytes()).hexdigest(), item["input_digest"])
            for key in ("mandatory_bytes", "mandatory_characters", "whole_documents", "load_on_demand_references"):
                self.assertIsInstance(item["after"][key], int)
        bounded = next(item for item in report["cases"] if item["id"] == "bounded_presentation_ui")
        self.assertLess(bounded["after"]["mandatory_bytes"], bounded["before"]["mandatory_bytes"])
        sensitive = next(item for item in report["cases"] if item["id"] == "persistence_current_state")
        self.assertEqual(self.EXPECTED["persistence"], set(sensitive["after"]["required_rule_ids"]))
        harness = next(item for item in report["cases"] if item["id"] == "harness_verification")
        self.assertEqual(self.EXPECTED["harness"], set(harness["after"]["required_rule_ids"]))


if __name__ == "__main__":
    unittest.main()
