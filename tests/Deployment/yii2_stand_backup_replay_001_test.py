#!/usr/bin/env python3
"""Replay/concurrency RED for YII2-STAND-BACKUP-CONSOLE-001."""
import json
import os
import subprocess
import sys
import time
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "Support"))
from stand_backup_contract import CANARIES, CLI, CONTROLLER, Fixture, canonical, tree


class ReplayTest(unittest.TestCase):
    def setUp(self): self.fx = Fixture()
    def tearDown(self): self.fx.close()

    def test_semantic_replay_and_conflict(self):
        operation = "99999999-9999-4999-8999-999999999999"
        first = self.fx.run("create", operation_id=operation)
        effects = self.fx.effects_list(); evidence = tree(self.fx.evidence)
        second = self.fx.run("create", operation_id=operation)
        self.assertEqual(first, second)
        self.assertEqual(effects, self.fx.effects_list())
        self.assertEqual(evidence, tree(self.fx.evidence))

    def test_durable_unknown_replay_preserves_lease_history_staging_and_effects(self):
        operation = "63636363-aaaa-4363-8363-636363636363"
        self.fx.write_driver(driver_outcome="timeout")
        first = self.fx.run("create", operation_id=operation)
        self.assertEqual("OUTCOME_UNKNOWN", first[1]["outcome"])
        before = tree(self.fx.evidence); effects = self.fx.effects_list()
        second = self.fx.run("create", operation_id=operation)
        third = self.fx.run("create", operation_id=operation)
        self.assertEqual(first, second); self.assertEqual(first, third)
        self.assertEqual(before, tree(self.fx.evidence)); self.assertEqual(effects, self.fx.effects_list())
        other = self.fx.run("create", operation_id="64646464-6464-4464-8464-646464646464")
        self.assertEqual((75, "LEASE_HELD"), (other[0], other[1]["reason"]))
        self.assertEqual(before, tree(self.fx.evidence)); self.assertEqual(effects, self.fx.effects_list())
        changed = self.fx.write_manifest(image="fmonitor2-runtime@sha256:" + "4" * 64)
        code, payload, _ = self.fx.run("create", manifest=changed, operation_id=operation)
        self.assertEqual((65, {"ok": False, "reason": "OPERATION_CONFLICT"}), (code, payload))
        self.assertEqual(before, tree(self.fx.evidence))

    def test_held_lease_is_not_removed_or_entered(self):
        lease = self.fx.evidence / "lease.json"
        lease.write_bytes(canonical({"operation_id": "other", "target_digest": "x", "host": "old", "pid": 1}))
        before = tree(self.fx.evidence)
        code, payload, _ = self.fx.run("create", operation_id="aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa")
        self.assertEqual((75, {"ok": False, "reason": "LEASE_HELD"}), (code, payload))
        self.assertEqual(before, tree(self.fx.evidence))
        self.assertEqual([], self.fx.effects_list())

    def test_two_processes_publish_once(self):
        self.assertTrue(CONTROLLER.is_file(), "INTENTIONAL_RED: PHP/Yii2 stand-backup command is missing")
        release = self.fx.root / "release-first-driver"
        self.fx.write_driver(hold_after_lease_until=str(release))
        operation = "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb"
        argv = ["php", str(CLI), "stand-backup/create", "--manifest=" + str(self.fx.manifest), "--operation-id=" + operation, "--fixture-driver=" + str(self.fx.fixture), "--interactive=0"]
        env = {"PATH": os.environ.get("PATH", "/usr/bin:/bin"), "LANG": "C", "LC_ALL": "C", "FMONITOR_STAND_BACKUP_TEST_MODE": "1"}
        first = subprocess.Popen(argv, cwd=self.fx.root, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        deadline = time.monotonic() + 5
        while not (self.fx.evidence / "lease.json").exists() and time.monotonic() < deadline: time.sleep(0.01)
        self.assertTrue((self.fx.evidence / "lease.json").exists(), "first process never acquired lease")
        second = subprocess.run(argv, cwd=self.fx.root, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=5)
        release.write_bytes(b"release\n")
        first_stdout, first_stderr = first.communicate(timeout=10)
        results = [(first.returncode, first_stdout, first_stderr), (second.returncode, second.stdout, second.stderr)]
        payloads = []
        for code, stdout, stderr in results:
            self.assertEqual(b"", stderr)
            text = stdout.decode(); payload, end = json.JSONDecoder().raw_decode(text)
            self.assertEqual("\n", text[end:])
            for canary in CANARIES + (str(self.fx.root), str(self.fx.evidence), str(self.fx.manifest)):
                self.assertNotIn(canary.encode(), stdout)
            payloads.append((code, payload))
        self.assertIn((0, next(payload for code, payload in payloads if payload.get("outcome") == "BACKUP_VERIFIED")), payloads)
        held = [(code, payload) for code, payload in payloads if payload.get("reason") == "LEASE_HELD"]
        self.assertEqual([(75, {"ok": False, "reason": "LEASE_HELD"})], held)
        self.assertEqual(1, len(list((self.fx.evidence / "bundles").iterdir())))
        self.assertEqual(1, sum(e.get("effect") == "backup" for e in self.fx.effects_list()))

    def test_corrupt_operation_history_fails_closed_without_driver(self):
        corruptions = ("malformed", "missing-field", "duplicate", "conflict", "symlink", "directory")
        for mode in corruptions:
            fx = Fixture()
            try:
                operation = "54545454-5454-4454-8454-545454545454"
                fx.run("create", operation_id=operation)
                history = fx.evidence / "operations.jsonl"
                original = history.read_bytes()
                if mode == "malformed": history.write_bytes(original + b"{")
                elif mode == "missing-field": history.write_bytes(canonical({"operation_id": operation}))
                elif mode == "duplicate": history.write_bytes(original + original)
                elif mode == "conflict":
                    value = json.loads(original); value["target_digest"] = "f" * 64
                    history.write_bytes(original + canonical(value))
                elif mode == "symlink": history.unlink(); history.symlink_to(fx.fixture)
                else: history.unlink(); history.mkdir()
                before = tree(fx.evidence); effects = fx.effects_list()
                code, payload, _ = fx.run("create", operation_id=operation)
                self.assertNotEqual(0, code); self.assertEqual("OUTCOME_UNKNOWN", payload["outcome"])
                self.assertEqual(before, tree(fx.evidence)); self.assertEqual(effects, fx.effects_list())
            finally:
                fx.close()

    def test_unreadable_regular_operation_history_fails_closed(self):
        fx = Fixture()
        try:
            operation = "55555555-aaaa-4555-8555-555555555555"
            fx.run("create", operation_id=operation)
            history = fx.evidence / "operations.jsonl"; original = history.read_bytes()
            published = {key: value for key, value in tree(fx.evidence).items()
                         if key == "verified.json" or key.startswith("bundles/")}
            effects = fx.effects_list(); history.chmod(0)
            try:
                code, payload, _ = fx.run("create", operation_id=operation)
            finally:
                history.chmod(0o600)
            self.assertNotEqual(0, code); self.assertEqual("OUTCOME_UNKNOWN", payload["outcome"])
            self.assertEqual(original, history.read_bytes()); self.assertEqual(effects, fx.effects_list())
            self.assertEqual(published, {key: value for key, value in tree(fx.evidence).items()
                                        if key == "verified.json" or key.startswith("bundles/")})
        finally:
            fx.close()

    def test_publication_crash_boundaries_never_repeat_driver(self):
        for boundary, recoverable in (("after_bundle_rename", False), ("after_success_record", True), ("after_pointer", True)):
            fx = Fixture()
            try:
                operation = "56565656-5656-4656-8656-565656565656"
                trace = fx.root / "publish-trace.jsonl"
                fx.write_driver(interrupt_at=boundary, trace_path=str(trace))
                code, payload, _ = fx.run("create", operation_id=operation)
                self.assertNotEqual(0, code); self.assertEqual("OUTCOME_UNKNOWN", payload["outcome"])
                effects = fx.effects_list()
                fx.write_driver(trace_path=str(trace))
                code, replay, _ = fx.run("create", operation_id=operation)
                if recoverable:
                    self.assertEqual((0, "BACKUP_VERIFIED"), (code, replay["outcome"]))
                else:
                    self.assertEqual((75, "LEASE_HELD"), (code, replay["reason"]))
                self.assertEqual(effects, fx.effects_list())
                events = [json.loads(line)["event"] for line in trace.read_text().splitlines()]
                expected_prefix = ["fsync_payloads", "rename_bundle", "fsync_bundles"]
                self.assertEqual(expected_prefix, events[:3])
                if boundary != "after_bundle_rename": self.assertLess(events.index("append_success_record"), events.index("publish_pointer") if "publish_pointer" in events else len(events))
            finally:
                fx.close()

    def test_success_durable_publication_order_is_complete(self):
        trace = self.fx.root / "publish-trace.jsonl"
        self.fx.write_driver(trace_path=str(trace))
        code, payload, _ = self.fx.run("create", operation_id="58585858-5858-4858-8858-585858585858")
        self.assertEqual((0, "BACKUP_VERIFIED"), (code, payload["outcome"]))
        events = [json.loads(line)["event"] for line in trace.read_text().splitlines()]
        self.assertEqual(["fsync_payloads", "rename_bundle", "fsync_bundles",
                          "append_success_record", "fsync_operations",
                          "publish_pointer", "fsync_evidence_pointer",
                          "release_lease", "fsync_evidence_lease"], events)

    def test_real_publication_faults_preserve_phase_and_recover_without_driver(self):
        cases = (("bundle_rename", False), ("bundle_dir_fsync", False), ("success_record", False),
                 ("pointer_publish", True), ("lease_release", True))
        for boundary, durable_record in cases:
            fx = Fixture()
            try:
                operation = "59595959-5959-4959-8959-595959595959"
                trace = fx.root / "real-fault-trace.jsonl"
                fx.write_driver(raise_at=boundary, trace_path=str(trace))
                code, payload, _ = fx.run("create", operation_id=operation)
                self.assertNotEqual(0, code); self.assertEqual("OUTCOME_UNKNOWN", payload["outcome"])
                records_path = fx.evidence / "operations.jsonl"
                records = [] if not records_path.exists() else [json.loads(line) for line in records_path.read_text().splitlines()]
                successes = [record for record in records if record.get("outcome") == "BACKUP_VERIFIED"]
                self.assertEqual(1 if durable_record else 0, len(successes))
                self.assertTrue((fx.evidence / "lease.json").exists())
                staging = fx.evidence / "staging" / operation
                if boundary == "bundle_rename": self.assertTrue(staging.is_dir())
                if boundary == "bundle_dir_fsync": self.assertFalse(staging.exists())
                effects = fx.effects_list(); fx.write_driver(trace_path=str(trace))
                code, replay, _ = fx.run("create", operation_id=operation)
                if durable_record:
                    self.assertEqual((0, "BACKUP_VERIFIED"), (code, replay["outcome"]))
                else:
                    self.assertEqual((75, "LEASE_HELD"), (code, replay["reason"]))
                self.assertEqual(effects, fx.effects_list())
                final_records = [] if not records_path.exists() else records_path.read_text().splitlines()
                self.assertEqual(len(records), len(final_records))
            finally:
                fx.close()

    def test_semantically_malicious_history_is_never_replayed(self):
        mutations = ("secret-result", "failure-exit", "unknown-result", "success-bundle-mismatch")
        for mutation in mutations:
            fx = Fixture()
            try:
                operation = "60606060-6060-4060-8060-606060606060"
                fx.run("create", operation_id=operation)
                history = fx.evidence / "operations.jsonl"; record = json.loads(history.read_text())
                if mutation == "secret-result":
                    record["result"] = {"ok": True, "outcome": "BACKUP_VERIFIED", "bundle_digest": record["bundle_digest"], "secret": "secret-password"}
                elif mutation == "failure-exit":
                    record.pop("bundle_digest"); record.update(outcome="BACKUP_INVALID", result={"ok": False, "reason": "BACKUP_INVALID"}, exit_code=0)
                elif mutation == "unknown-result":
                    record.pop("bundle_digest"); record.update(outcome="OUTCOME_UNKNOWN", result={"ok": False, "reason": "BACKUP_INVALID"}, exit_code=70)
                else:
                    record["bundle_digest"] = "f" * 64
                history.write_bytes(canonical(record)); before = tree(fx.evidence); effects = fx.effects_list()
                code, payload, _ = fx.run("create", operation_id=operation)
                self.assertNotEqual(0, code); self.assertEqual("OUTCOME_UNKNOWN", payload["outcome"])
                self.assertEqual(before, tree(fx.evidence)); self.assertEqual(effects, fx.effects_list())
            finally:
                fx.close()

    def test_dangling_history_symlink_noncanonical_pointer_and_foreign_lease_fail_closed(self):
        for mode in ("dangling-history", "noncanonical-pointer", "foreign-lease"):
            fx = Fixture()
            try:
                operation = "61616161-6161-4161-8161-616161616161"
                fx.run("create", operation_id=operation)
                if mode == "dangling-history":
                    history = fx.evidence / "operations.jsonl"; history.unlink(); history.symlink_to(fx.root / "missing-history")
                elif mode == "noncanonical-pointer":
                    pointer = fx.evidence / "verified.json"; pointer.write_text(json.dumps(json.loads(pointer.read_text()), indent=2) + "\n")
                else:
                    (fx.evidence / "lease.json").write_bytes(canonical({"operation_id": "other", "target_digest": "f" * 64, "host": "other", "pid": 1}))
                before = tree(fx.evidence); effects = fx.effects_list()
                code, payload, _ = fx.run("create", operation_id=operation)
                self.assertNotEqual(0, code); self.assertEqual("OUTCOME_UNKNOWN", payload["outcome"])
                self.assertEqual(before, tree(fx.evidence)); self.assertEqual(effects, fx.effects_list())
            finally:
                fx.close()


if __name__ == "__main__": unittest.main(verbosity=2)
