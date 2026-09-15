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
        result = value.cli("prepare", "--input", value.input, "--base", value.base, "--role", role)
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
        self.assertEqual("UNKNOWN", package["context_metrics"]["token_usage"])
        for item in manifest["required_context"]:
            self.assertEqual("required_reference", item["load_mode"])
            self.assertRegex(item["digest"], r"^[0-9a-f]{64}$")
            self.assertTrue(item["source"])
            self.assertTrue(item["reason"])
            self.assertIn("content_reference", item)
        return value, package, manifest

    def rule_ids(self, manifest):
        return {item["rule_id"] for item in manifest["required_context"]}

    def test_A_to_D_profiles_and_J_history(self):
        _v, ui, ui_manifest = self.prepared(["app/YiiRuntime/Views/example.php"])
        self.assertIn("ui.presentation", self.rule_ids(ui_manifest))
        self.assertNotIn("persistence.current-state", self.rule_ids(ui_manifest))
        self.assertNotIn("security.authorization", self.rule_ids(ui_manifest))
        self.assertTrue(any(item["source"].startswith("docs/operations/current-delivery-goal-history") for item in ui_manifest["load_on_demand"]))

        _v, _p, persistence = self.prepared(["app/InstallationProcess/MariaDbExample.php"])
        self.assertTrue({"persistence.current-state", "domain.state-history", "security.authorization"} <= self.rule_ids(persistence))

        _v, _a, auth = self.prepared(["app/IdentityAccess/AuthorizeExample.php"])
        self.assertIn("security.authorization", self.rule_ids(auth))

        _v, _h, harness = self.prepared(["tools/delivery/example.py"])
        self.assertIn("delivery.verification-governance", self.rule_ids(harness))
        self.assertFalse(any(item["source"] in {"PRODUCT.md", "CONTEXT.md", "docs/fmonitor-2-pilot-spec.md"} for item in harness["required_context"]))
        self.assertLess(ui["context_metrics"]["mandatory_bytes"], 78637)

    def test_E_to_H_digest_freshness_unknown_fallback_and_repeat(self):
        value, first, manifest = self.prepared(["app/YiiRuntime/Views/example.php"])
        second = value.cli("prepare", "--input", value.input, "--base", value.base, "--role", "root")
        self.assertEqual(0, second.returncode, second.stderr)
        second_package = json.loads(second.stdout)
        self.assertEqual(first["context_manifest_sha256"], second_package["context_manifest_sha256"])

        (value.repo / "AGENTS.md").write_text((value.repo / "AGENTS.md").read_text() + "\nNew canonical instruction.\n")
        fresh = value.cli("prepare", "--input", value.input, "--base", value.base, "--role", "root")
        self.assertEqual(0, fresh.returncode, fresh.stderr)
        self.assertNotEqual(first["context_manifest_sha256"], json.loads(fresh.stdout)["context_manifest_sha256"])

        _v, _u, unknown = self.prepared(["unknown-boundary/example.xyz"])
        self.assertEqual("conservative", unknown["applicability"]["mode"])
        self.assertTrue(any(item["rule_id"] == "FULL_DOCUMENT" for item in unknown["required_context"]))

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
            broken["sections"][0]["heading"] = "## definitely absent heading"
            index.write_text(json.dumps(broken) + "\n")
            rebuilt = value.cli("prepare", "--input", value.input, "--base", value.base, "--role", "root")
            self.assertEqual(0, rebuilt.returncode, rebuilt.stderr)
            rebuilt_manifest = json.loads(Path(json.loads(rebuilt.stdout)["context_manifest"]).read_text())
            self.assertTrue(any(item["rule_id"] == "FULL_DOCUMENT" for item in rebuilt_manifest["required_context"]))

    def test_measurement_replays_three_completed_changes(self):
        result = subprocess.run([sys.executable, "tools/delivery/measure-task-context.py", "--baseline", "docs/operations/issue-157-task-context-manifest-baseline.json"], cwd=ROOT, text=True, capture_output=True)
        self.assertEqual(0, result.returncode, "INTENDED_RED deterministic measurement absent: " + result.stderr)
        report = json.loads(result.stdout)
        self.assertEqual("UNKNOWN", report["token_usage"])
        self.assertEqual({"bounded_presentation_ui", "persistence_current_state", "harness_verification"}, {item["id"] for item in report["cases"]})
        bounded = next(item for item in report["cases"] if item["id"] == "bounded_presentation_ui")
        self.assertLess(bounded["after"]["mandatory_bytes"], bounded["before"]["mandatory_bytes"])
        sensitive = next(item for item in report["cases"] if item["id"] == "persistence_current_state")
        self.assertTrue({"persistence.current-state", "domain.state-history", "security.authorization"} <= set(sensitive["after"]["required_rule_ids"]))


if __name__ == "__main__":
    unittest.main()
