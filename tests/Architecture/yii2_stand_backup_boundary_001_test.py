#!/usr/bin/env python3
"""Runtime-boundary RED for YII2-STAND-BACKUP-CONSOLE-001."""
import json
import sys
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "tests/Support"))
from stand_backup_contract import CLI, Fixture, canonical, digest, published_tree


class BoundaryTest(unittest.TestCase):
    def test_public_seam_has_no_rapid_pilot_or_destructive_lifecycle(self):
        files = [ROOT / "app/YiiRuntime/Commands/StandBackupController.php",
                 ROOT / "app/RuntimeRestore/StandBackupApplication.php",
                 ROOT / "app/RuntimeRestore/StandBackupFilesystem.php"]
        self.assertTrue(all(path.is_file() for path in files), "INTENTIONAL_RED: PHP/Yii2 stand-backup owners are missing")
        source = "\n".join(path.read_text(encoding="utf-8") for path in files)
        self.assertNotIn("rapid-pilot", source)
        for forbidden in ("docker compose", "docker volume", "DROP DATABASE", "bin/yii"):
            self.assertNotIn(forbidden, source)
        self.assertFalse((ROOT / "tools/delivery/stand-backup.py").exists())

    def test_safe_output_applies_to_native_failure(self):
        fixture = Fixture()
        try:
            fixture.write_driver(driver_outcome="failure")
            code, payload, _ = fixture.run("create", operation_id="cccccccc-cccc-4ccc-8ccc-cccccccccccc")
            self.assertNotEqual(0, code)
            self.assertEqual("BACKUP_INVALID", payload["reason"])
            metadata = b"".join(path.read_bytes() for path in fixture.evidence.rglob("*") if path.is_file())
            for canary in (b"secret-password", b"mysql://secret-dsn", b"private-secret-path", str(ROOT).encode(), str(fixture.evidence).encode()):
                self.assertNotIn(canary, metadata)
        finally:
            fixture.close()

    def test_safe_output_applies_to_unknown_and_parser_failure(self):
        fixture = Fixture()
        try:
            fixture.write_driver(driver_outcome="timeout")
            code, payload, _ = fixture.run("create", operation_id="dddddddd-dddd-4ddd-8ddd-dddddddddddd")
            self.assertNotEqual(0, code); self.assertEqual("OUTCOME_UNKNOWN", payload["outcome"])
            malformed = fixture.write_raw_manifest(b'{"password":"secret-password"')
            code, payload, _ = fixture.run("create", manifest=malformed, operation_id="eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee")
            self.assertEqual(64, code); self.assertEqual({"ok": False, "reason": "TARGET_INVALID"}, payload)
        finally:
            fixture.close()

    def test_real_exception_boundaries_are_safe_and_fail_closed(self):
        for boundary, exception_type, outcome_key, outcome in (
            ("lease_create", "OSError", "outcome", "OUTCOME_UNKNOWN"),
            ("lease_write", "OSError", "outcome", "OUTCOME_UNKNOWN"),
            ("staging_create", "OSError", "outcome", "OUTCOME_UNKNOWN"),
            ("payload_fsync", "OSError", "reason", "BACKUP_INVALID"),
            ("bundle_rename", "OSError", "outcome", "OUTCOME_UNKNOWN"),
            ("bundle_dir_fsync", "OSError", "outcome", "OUTCOME_UNKNOWN"),
            ("success_record", "OSError", "outcome", "OUTCOME_UNKNOWN"),
            ("pointer_publish", "OSError", "outcome", "OUTCOME_UNKNOWN"),
            ("lease_release", "OSError", "outcome", "OUTCOME_UNKNOWN"),
            ("keyboard_interrupt", "KeyboardInterrupt", "outcome", "OUTCOME_UNKNOWN"),
            ("subprocess_error", "CalledProcessError", "reason", "BACKUP_INVALID"),
        ):
            fixture = Fixture()
            try:
                fixture.run("create", operation_id="56565656-aaaa-4656-8656-565656565656")
                prior_pointer = (fixture.evidence / "verified.json").read_bytes()
                prior_digest = json.loads(prior_pointer)["bundle_digest"]
                prior_bundle = published_tree(fixture.evidence)
                prior_bundle = {key: value for key, value in prior_bundle.items()
                                if key.startswith("bundles/" + prior_digest)}
                trace = fixture.root / "fault-trace.jsonl"
                fixture.write_driver(raise_at=boundary, raise_type=exception_type, trace_path=str(trace))
                code, payload, _ = fixture.run("create", operation_id="57575757-5757-4757-8757-575757575757")
                self.assertNotEqual(0, code); self.assertEqual(outcome, payload[outcome_key])
                events = [] if not trace.exists() else [__import__("json").loads(line)["event"] for line in trace.read_text().splitlines()]
                self.assertTrue(events, f"{boundary} was returned without entering its real boundary")
                self.assertEqual(["enter:" + boundary, "raise:" + exception_type], events[-2:])
                evidence = fixture.evidence
                current = published_tree(evidence)
                self.assertEqual(prior_bundle, {key: value for key, value in current.items()
                                                if key.startswith("bundles/" + prior_digest)})
                bundle_roots = {key.split("/")[1] for key in current if key.startswith("bundles/")}
                post_rename = boundary in {"bundle_dir_fsync", "success_record", "pointer_publish", "lease_release"}
                self.assertEqual(2 if post_rename else 1, len(bundle_roots))
                if boundary == "lease_release":
                    pointer = json.loads((evidence / "verified.json").read_bytes())
                    self.assertEqual("57575757-5757-4757-8757-575757575757", pointer["operation_id"])
                    self.assertIn(pointer["bundle_digest"], bundle_roots)
                else:
                    self.assertEqual(prior_pointer, (evidence / "verified.json").read_bytes())
                if boundary == "lease_create":
                    self.assertFalse((evidence / "lease.json").exists()); self.assertFalse((evidence / "staging").exists())
                elif boundary == "lease_write":
                    lease_bytes = (evidence / "lease.json").read_bytes()
                    target = digest(canonical(json.loads(fixture.manifest.read_bytes())))
                    self.assertEqual(canonical({"operation_id": "57575757-5757-4757-8757-575757575757",
                                                "target_digest": target, "host": "fixture-host", "pid": 4242}), lease_bytes)
                    self.assertFalse((evidence / "staging").exists())
                elif boundary in {"staging_create", "keyboard_interrupt"}:
                    lease = __import__("json").loads((evidence / "lease.json").read_text())
                    self.assertEqual("57575757-5757-4757-8757-575757575757", lease["operation_id"])
                    self.assertFalse(any((evidence / "staging").iterdir()) if (evidence / "staging").exists() else False)
                elif boundary in {"payload_fsync", "subprocess_error"}:
                    self.assertFalse((evidence / "lease.json").exists())
                    self.assertFalse(any((evidence / "staging").iterdir()) if (evidence / "staging").exists() else False)
                    records = [__import__("json").loads(line) for line in (evidence / "operations.jsonl").read_text().splitlines()]
                    self.assertEqual(["BACKUP_VERIFIED", "BACKUP_INVALID"], [record["outcome"] for record in records])
                self.assertFalse(any(effect.get("effect") in {"stop", "migrate", "reset", "start", "restore"} for effect in fixture.effects_list()))
            finally:
                fixture.close()


if __name__ == "__main__": unittest.main(verbosity=2)
