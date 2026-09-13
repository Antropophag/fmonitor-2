#!/usr/bin/env python3
"""Failure/preservation RED for YII2-STAND-BACKUP-CONSOLE-001."""
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "Support"))
from stand_backup_contract import Fixture, PAYLOADS, published_tree, tree


class PreservationTest(unittest.TestCase):
    def setUp(self): self.fx = Fixture()
    def tearDown(self): self.fx.close()

    def establish_verified(self):
        self.fx.write_driver()
        code, payload, _ = self.fx.run("create", operation_id="66666666-6666-4666-8666-666666666666")
        self.assertEqual(0, code)
        return payload

    def test_every_definite_failure_preserves_previous_bundle(self):
        self.establish_verified()
        baseline = published_tree(self.fx.evidence)
        cases = [
            {"capacity_ok": False}, {"inventory_matches": False},
            {"payloads": {"artifacts.tar": "A", "sessions.json": "{}"}},
            {"payloads": {**{k: v.decode("latin1") for k, v in PAYLOADS.items()}, "database.sql": ""}},
            {"payloads": {**{k: v.decode("latin1") for k, v in PAYLOADS.items()}, "unexpected.bin": "X"}},
            {"member_kind": {"database.sql": "symlink"}},
            {"member_kind": {"artifacts.tar": "directory"}},
            {"member_kind": {"database.sql": "device"}},
            {"member_kind": {"sessions.json": "unreadable"}},
            {"mutate_after_write": "database.sql"},
            {"mutate_after_first_read": "artifacts.tar"},
            {"driver_outcome": "failure"},
            {"publish_filesystem_matches": False},
            {"publish_outcome": "incomplete"},
        ]
        for index, change in enumerate(cases):
            with self.subTest(change=change):
                self.fx.write_driver(**change)
                operation = f"77777777-7777-4777-8777-{index:012d}"
                operations = self.fx.evidence / "operations.jsonl"
                before_records = [] if not operations.exists() else operations.read_text().splitlines()
                code, payload, _ = self.fx.run("create", operation_id=operation)
                self.assertNotEqual(0, code)
                self.assertEqual("BACKUP_INVALID", payload["reason"])
                self.assertEqual(baseline, published_tree(self.fx.evidence))
                records = operations.read_text().splitlines()
                self.assertEqual(len(before_records) + 1, len(records))
                record = __import__("json").loads(records[-1])
                self.assertEqual((operation, "BACKUP_INVALID"), (record["operation_id"], record["outcome"]))
                current = tree(self.fx.evidence)
                self.assertFalse(any(key.startswith("staging/") for key in current))
                self.assertNotIn("lease.json", current)
                self.assertFalse(any(effect.get("effect") in {"stop", "migrate", "reset", "start", "restore"} for effect in self.fx.effects_list()))

    def test_unknown_preserves_staging_lease_and_verified_history(self):
        self.establish_verified()
        baseline = published_tree(self.fx.evidence)
        for index, outcome in enumerate(("timeout", "interrupt", "effect_unknown")):
            with self.subTest(outcome=outcome):
                operation = f"88888888-8888-4888-8888-{index:012d}"
                self.fx.write_driver(driver_outcome=outcome)
                code, payload, _ = self.fx.run("create", operation_id=operation)
                self.assertNotEqual(0, code)
                self.assertEqual("OUTCOME_UNKNOWN", payload["outcome"])
                self.assertEqual(baseline, published_tree(self.fx.evidence))
                after = tree(self.fx.evidence)
                self.assertTrue(any(key.startswith(f"staging/{operation}") for key in after))
                lease = __import__("json").loads((self.fx.evidence / "lease.json").read_text())
                self.assertEqual(operation, lease["operation_id"])
                record = __import__("json").loads((self.fx.evidence / "operations.jsonl").read_text().splitlines()[-1])
                self.assertEqual((operation, "OUTCOME_UNKNOWN"), (record["operation_id"], record["outcome"]))
                self.assertFalse(any(effect.get("effect") in {"stop", "migrate", "reset", "start", "restore"} for effect in self.fx.effects_list()))
                if index < 2:
                    (self.fx.evidence / "lease.json").unlink()

    def test_secondary_definite_failure_boundaries_are_replay_consistent(self):
        for boundary, durable in (("failure_cleanup", False), ("failure_record", False),
                                  ("failure_lease_release", True), ("failure_directory_fsync", True)):
            fx = Fixture()
            try:
                fx.run("create", operation_id="63636363-6363-4363-8363-636363636363")
                prior = published_tree(fx.evidence)
                operation = "62626262-6262-4262-8262-626262626262"
                fx.write_driver(driver_outcome="failure", secondary_raise_at=boundary)
                code, payload, first_stdout = fx.run("create", operation_id=operation)
                records_path = fx.evidence / "operations.jsonl"
                records = [] if not records_path.exists() else [__import__("json").loads(line) for line in records_path.read_text().splitlines()]
                self.assertEqual(1 if durable else 0, sum(record.get("outcome") == "BACKUP_INVALID" for record in records))
                self.assertEqual(boundary != "failure_directory_fsync", (fx.evidence / "lease.json").exists())
                self.assertEqual(prior, published_tree(fx.evidence))
                effects = fx.effects_list(); fx.write_driver(driver_outcome="failure")
                replay_code, replay, replay_stdout = fx.run("create", operation_id=operation)
                if durable:
                    self.assertEqual("BACKUP_INVALID", payload["reason"])
                    self.assertEqual((code, first_stdout), (replay_code, replay_stdout))
                    self.assertFalse((fx.evidence / "lease.json").exists())
                else:
                    self.assertEqual("OUTCOME_UNKNOWN", payload["outcome"])
                    self.assertEqual((75, "LEASE_HELD"), (replay_code, replay["reason"]))
                self.assertEqual(effects, fx.effects_list())
            finally:
                fx.close()


if __name__ == "__main__": unittest.main(verbosity=2)
