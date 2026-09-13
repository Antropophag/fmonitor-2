#!/usr/bin/env python3
"""Concrete bundle RED for YII2-STAND-BACKUP-CONSOLE-001."""
import json
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "Support"))
from stand_backup_contract import CANARIES, Fixture, PAYLOADS, ROOT, canonical, digest, tree


class ConcreteBundleTest(unittest.TestCase):
    def setUp(self): self.fx = Fixture()
    def tearDown(self): self.fx.close()

    def test_bytes_manifest_digest_and_atomic_verified_pointer(self):
        operation = "33333333-3333-4333-8333-333333333333"
        code, payload, _ = self.fx.run("create", operation_id=operation)
        self.assertEqual(0, code)
        self.assertEqual("BACKUP_VERIFIED", payload["outcome"])
        bundle = self.fx.evidence / "bundles" / payload["bundle_digest"]
        self.assertTrue(bundle.is_dir())
        for name, expected in PAYLOADS.items():
            self.assertEqual(expected, (bundle / name).read_bytes())
        restore = json.loads((bundle / "manifest.json").read_bytes())
        accepted = json.loads(self.fx.manifest.read_bytes())
        expected_restore = {
            "version": 1,
            "target_digest": digest(canonical(accepted)),
            "source": "404aa6858b8fdd61ac0e8151f09a67000a387ead",
            "image": "fmonitor2-runtime@sha256:" + "2" * 64,
            "database": {"name": "test_fmonitor2", "observed_id": "db-id"},
            "volumes": {
                "database": {"name": "test-fm2-db", "observed_id": "db-volume-id"},
                "artifacts": {"name": "test-fm2-artifacts", "observed_id": "artifact-volume-id"},
                "sessions": {"name": "test-fm2-sessions", "observed_id": "session-volume-id"},
            },
            "operation_id": operation,
            "files": {name: {"size": len(data), "sha256": digest(data)} for name, data in PAYLOADS.items()},
        }
        expected_bytes = canonical(expected_restore)
        expected_digest = digest(expected_bytes)
        self.assertEqual(expected_restore, restore)
        self.assertEqual(expected_digest, payload["bundle_digest"])
        self.assertEqual(expected_bytes, (bundle / "manifest.json").read_bytes())
        pointer = json.loads((self.fx.evidence / "verified.json").read_bytes())
        self.assertEqual({"version": 1, "bundle_digest": expected_digest,
                          "target_digest": expected_restore["target_digest"],
                          "operation_id": operation}, pointer)
        metadata = canonical(restore) + canonical(pointer)
        for canary in CANARIES + (str(ROOT), str(self.fx.root), str(self.fx.evidence), str(self.fx.manifest)):
            self.assertNotIn(canary.encode(), metadata)

    def test_verify_recomputes_bytes_and_is_read_only(self):
        _, created, _ = self.fx.run("create", operation_id="44444444-4444-4444-8444-444444444444")
        before = {p.name: p.read_bytes() for p in (self.fx.evidence / "bundles" / created["bundle_digest"]).iterdir()}
        effects = self.fx.effects_list()
        code, verified, _ = self.fx.run("verify", operation_id="55555555-5555-4555-8555-555555555555", extra=("--bundle-digest", created["bundle_digest"]))
        self.assertEqual((0, "BACKUP_VERIFIED", created["bundle_digest"]), (code, verified["outcome"], verified["bundle_digest"]))
        self.assertEqual(effects, self.fx.effects_list())
        self.assertEqual(before, {p.name: p.read_bytes() for p in (self.fx.evidence / "bundles" / created["bundle_digest"]).iterdir()})

    def test_verify_detects_every_published_corruption_read_only(self):
        corruptions = ("database.sql", "artifacts.tar", "sessions.json", "manifest.json", "verified.json")
        for name in corruptions:
            with self.subTest(name=name):
                fx = Fixture()
                try:
                    _, created, _ = fx.run("create", operation_id="45454545-4545-4545-8545-454545454545")
                    bundle = fx.evidence / "bundles" / created["bundle_digest"]
                    path = fx.evidence / "verified.json" if name == "verified.json" else bundle / name
                    path.write_bytes(path.read_bytes() + b"CORRUPT")
                    before = tree(fx.evidence); effects = fx.effects_list()
                    code, payload, _ = fx.run("verify", operation_id="46464646-4646-4646-8646-464646464646", extra=("--bundle-digest", created["bundle_digest"]))
                    self.assertNotEqual(0, code); self.assertEqual("BACKUP_INVALID", payload["reason"])
                    self.assertEqual(before, tree(fx.evidence)); self.assertEqual(effects, fx.effects_list())
                finally:
                    fx.close()

    def test_verify_rejects_member_shape_changes(self):
        for mode in ("missing", "empty", "symlink", "directory", "unreadable", "unexpected"):
            with self.subTest(mode=mode):
                fx = Fixture()
                try:
                    _, created, _ = fx.run("create", operation_id="47474747-4747-4747-8747-474747474747")
                    bundle = fx.evidence / "bundles" / created["bundle_digest"]
                    member = bundle / "database.sql"
                    if mode == "missing": member.unlink()
                    elif mode == "empty": member.write_bytes(b"")
                    elif mode == "symlink": member.unlink(); member.symlink_to(bundle / "artifacts.tar")
                    elif mode == "directory": member.unlink(); member.mkdir()
                    elif mode == "unreadable": member.chmod(0)
                    else: (bundle / "unexpected.bin").write_bytes(b"X")
                    before = tree(fx.evidence)
                    code, payload, _ = fx.run("verify", operation_id="48484848-4848-4848-8848-484848484848", extra=("--bundle-digest", created["bundle_digest"]))
                    self.assertNotEqual(0, code); self.assertEqual("BACKUP_INVALID", payload["reason"])
                    self.assertEqual(before, tree(fx.evidence))
                finally:
                    fx.close()

    def test_verify_rejects_digest_escape_and_symlink_before_external_read(self):
        for candidate in ("../" + "a" * 61, "A" * 64, "g" * 64, "a/b" + "c" * 61):
            with self.subTest(candidate=candidate):
                before = tree(self.fx.evidence); effects = self.fx.effects_list()
                code, payload, _ = self.fx.run("verify", operation_id="49494949-4949-4949-8949-494949494949", extra=("--bundle-digest", candidate))
                self.assertEqual((64, {"ok": False, "reason": "TARGET_INVALID"}), (code, payload))
                self.assertEqual(before, tree(self.fx.evidence)); self.assertEqual(effects, self.fx.effects_list())
        bundles = self.fx.evidence / "bundles"; bundles.mkdir()
        digest_name = "a" * 64; (bundles / digest_name).symlink_to(self.fx.root)
        before = tree(self.fx.evidence)
        code, payload, _ = self.fx.run("verify", operation_id="50505050-5050-4050-8050-505050505050", extra=("--bundle-digest", digest_name))
        self.assertNotEqual(0, code); self.assertEqual("BACKUP_INVALID", payload["reason"])
        self.assertEqual(before, tree(self.fx.evidence))

    def test_verify_rejects_coherent_wrong_restore_identity_and_schema(self):
        mutations = (
            lambda value: value.update(extra="secret-password"),
            lambda value: value.update(database={"name": "other", "observed_id": "db-id"}),
            lambda value: value["volumes"]["artifacts"].update(observed_id="other-id"),
            lambda value: value.update(operation_id="not-a-uuid"),
            lambda value: value["files"]["database.sql"].update(extra="private-secret-path"),
            lambda value: value["files"].update(unexpected={"size": 1, "sha256": "0" * 64}),
        )
        for mutate in mutations:
            fx = Fixture()
            try:
                _, created, _ = fx.run("create", operation_id="51515151-5151-4151-8151-515151515151")
                old = fx.evidence / "bundles" / created["bundle_digest"]
                restore = json.loads((old / "manifest.json").read_bytes()); mutate(restore)
                new_bytes = canonical(restore); new_digest = digest(new_bytes)
                (old / "manifest.json").write_bytes(new_bytes); old.rename(old.parent / new_digest)
                pointer = json.loads((fx.evidence / "verified.json").read_bytes()); pointer["bundle_digest"] = new_digest
                (fx.evidence / "verified.json").write_bytes(canonical(pointer))
                before = tree(fx.evidence)
                code, payload, _ = fx.run("verify", operation_id="52525252-5252-4252-8252-525252525252", extra=("--bundle-digest", new_digest))
                self.assertNotEqual(0, code); self.assertEqual("BACKUP_INVALID", payload["reason"])
                self.assertEqual(before, tree(fx.evidence))
            finally:
                fx.close()

    def test_create_rejects_corrupt_or_symlink_existing_destination(self):
        for mode in ("corrupt", "symlink"):
            fx = Fixture()
            try:
                operation = "53535353-5353-4353-8353-535353535353"
                target = digest(canonical(json.loads(fx.manifest.read_bytes())))
                restore = {"version": 1, "target_digest": target,
                           "source": "404aa6858b8fdd61ac0e8151f09a67000a387ead",
                           "image": "fmonitor2-runtime@sha256:" + "2" * 64,
                           "database": {"name": "test_fmonitor2", "observed_id": "db-id"},
                           "volumes": {"database": {"name": "test-fm2-db", "observed_id": "db-volume-id"},
                                       "artifacts": {"name": "test-fm2-artifacts", "observed_id": "artifact-volume-id"},
                                       "sessions": {"name": "test-fm2-sessions", "observed_id": "session-volume-id"}},
                           "operation_id": operation,
                           "files": {name: {"size": len(data), "sha256": digest(data)} for name, data in PAYLOADS.items()}}
                bundle_digest = digest(canonical(restore)); bundles = fx.evidence / "bundles"; bundles.mkdir()
                destination = bundles / bundle_digest
                if mode == "symlink": destination.symlink_to(fx.root)
                else: destination.mkdir(); (destination / "corrupt").write_bytes(b"bad")
                before = tree(fx.evidence)
                code, payload, _ = fx.run("create", operation_id=operation)
                self.assertNotEqual(0, code); self.assertEqual("BACKUP_INVALID", payload["reason"])
                for key, value in before.items(): self.assertEqual(value, tree(fx.evidence)[key])
                self.assertFalse((fx.evidence / "verified.json").exists())
            finally:
                fx.close()

    def test_hostile_atomic_temp_symlink_and_member_swap_do_not_escape_evidence(self):
        for mode in ("atomic-temp-symlink", "swap-member-after-lstat"):
            fx = Fixture()
            try:
                external = fx.root / "external-canary"; external.write_bytes(b"EXTERNAL-UNCHANGED")
                if mode == "atomic-temp-symlink":
                    (fx.evidence / "verified.json.tmp").symlink_to(external)
                else:
                    fx.write_driver(swap_member_after_lstat="database.sql", swap_target=str(external))
                before = external.read_bytes()
                code, payload, _ = fx.run("create", operation_id="65656565-6565-4565-8565-656565656565")
                self.assertNotEqual(0, code)
                self.assertIn(payload.get("reason") or payload.get("outcome"), ("BACKUP_INVALID", "OUTCOME_UNKNOWN"))
                self.assertEqual(before, external.read_bytes())
                self.assertFalse((fx.evidence / "verified.json").exists())
            finally:
                fx.close()

    def test_persistent_exclusive_temp_failure_returns_safe_bounded_outcome(self):
        fx = Fixture()
        try:
            fx.write_driver(atomic_exclusive_failure="permission")
            started = __import__("time").monotonic()
            code, payload, _ = fx.run("create", operation_id="66666666-aaaa-4666-8666-666666666666")
            elapsed = __import__("time").monotonic() - started
            self.assertLess(elapsed, 2.0)
            self.assertNotEqual(0, code)
            self.assertIn(payload.get("reason") or payload.get("outcome"), ("BACKUP_INVALID", "OUTCOME_UNKNOWN"))
            self.assertFalse((fx.evidence / "verified.json").exists())
        finally:
            fx.close()


if __name__ == "__main__": unittest.main(verbosity=2)
