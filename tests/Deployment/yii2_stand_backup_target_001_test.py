#!/usr/bin/env python3
"""Exact-target admission RED for YII2-STAND-BACKUP-CONSOLE-001."""
import json
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "Support"))
from stand_backup_contract import Fixture, ROOT, canonical, tree


class ExactTargetTest(unittest.TestCase):
    def setUp(self): self.fx = Fixture()
    def tearDown(self): self.fx.close()

    def test_full_invalid_matrix_has_no_effect_or_evidence_mutation(self):
        allowed_external = self.fx.root / "allowed-external"
        allowed_external.mkdir()
        symlink_parent = self.fx.root / "linked-parent"
        symlink_parent.symlink_to(allowed_external, target_is_directory=True)
        valid_volumes = {
            "database": {"name": "test-fm2-db", "observed_id": "db-volume-id"},
            "artifacts": {"name": "test-fm2-artifacts", "observed_id": "artifact-volume-id"},
            "sessions": {"name": "test-fm2-sessions", "observed_id": "session-volume-id"},
        }
        changes = [
            {"version": 2}, {"version": True}, {"version": None}, {"version": "1"},
            {"authorization_id": "wrong"}, {"authorization_id": 7},
            {"authorization_id": None}, {"authorization_id": []},
            {"source": "latest"}, {"source": None}, {"source": 7}, {"source": []},
            {"source": "f" * 63}, {"image": 42}, {"image": None}, {"image": []},
            {"image": "runtime:latest"}, {"compose_file": "relative.yaml"},
            {"compose_file": None}, {"compose_file": True}, {"compose_file": []},
            {"project": "default"}, {"project": "neighbor"},
            {"project": "${PROJECT}"}, {"project": []}, {"project": None}, {"project": 7},
            {"database": {"name": "unknown", "observed_id": "db-id"}},
            {"database": None}, {"database": []}, {"database": "test_fmonitor2"},
            {"database": {"name": "test_fmonitor2", "observed_id": ""}},
            {"database": {"name": "${DB}", "observed_id": "db-id"}},
            {"database": {"name": "test_fmonitor2", "observed_id": "${DB_ID}"}},
            {"database": {"name": None, "observed_id": "db-id"}},
            {"database": {"name": 7, "observed_id": "db-id"}},
            {"database": {"name": "test_fmonitor2", "observed_id": None}},
            {"database": {"name": "test_fmonitor2", "observed_id": 7}},
            {"database": {"name": "test_fmonitor2", "observed_id": "changed-db-id"}},
            {"evidence_root": "relative"}, {"evidence_root": "/"},
            {"evidence_root": None}, {"evidence_root": True}, {"evidence_root": []},
            {"evidence_root": str(Path.home())}, {"evidence_root": str(ROOT)},
            {"evidence_root": str(symlink_parent / "child")},
            {"inventory_digest": ""}, {"inventory_digest": None}, {"inventory_digest": 7},
            {"inventory_digest": []}, {"inventory_digest": "f" * 63},
            {"volumes": None}, {"volumes": []}, {"volumes": "volumes"},
            {"volumes": {**valid_volumes, "sessions": valid_volumes["database"]}},
            {"volumes": {**valid_volumes, "sessions": {"name": "test-fm2-sessions", "observed_id": "db-volume-id"}}},
            {"volumes": {**valid_volumes, "artifacts": {"name": "unknown", "observed_id": "artifact-volume-id"}}},
            {"volumes": {**valid_volumes, "artifacts": {"name": "test-fm2-artifacts", "observed_id": "changed-id"}}},
            {"volumes": {"database": valid_volumes["database"]}},
        ]
        manifests = [self.fx.write_manifest(**change) for change in changes]
        base = json.loads(self.fx.manifest.read_bytes())
        for missing in base:
            value = dict(base); value.pop(missing); manifests.append(self.fx.write_raw_manifest(canonical(value)))
        extra = dict(base); extra["extra"] = "forbidden"; manifests.append(self.fx.write_raw_manifest(canonical(extra)))
        nested_extra = json.loads(self.fx.manifest.read_bytes()); nested_extra["database"]["extra"] = "forbidden"; manifests.append(self.fx.write_raw_manifest(canonical(nested_extra)))
        for missing in ("name", "observed_id"):
            value = json.loads(self.fx.manifest.read_bytes()); value["database"].pop(missing); manifests.append(self.fx.write_raw_manifest(canonical(value)))
        for role in ("database", "artifacts", "sessions"):
            for missing in ("name", "observed_id"):
                value = json.loads(self.fx.manifest.read_bytes()); value["volumes"][role].pop(missing); manifests.append(self.fx.write_raw_manifest(canonical(value)))
            value = json.loads(self.fx.manifest.read_bytes()); value["volumes"][role]["extra"] = "forbidden"; manifests.append(self.fx.write_raw_manifest(canonical(value)))
            value = json.loads(self.fx.manifest.read_bytes()); value["volumes"][role]["name"] = "${VOLUME}"; manifests.append(self.fx.write_raw_manifest(canonical(value)))
            value = json.loads(self.fx.manifest.read_bytes()); value["volumes"][role]["observed_id"] = "${VOLUME_ID}"; manifests.append(self.fx.write_raw_manifest(canonical(value)))
            for wrong in (None, [], "volume", 7):
                value = json.loads(self.fx.manifest.read_bytes()); value["volumes"][role] = wrong; manifests.append(self.fx.write_raw_manifest(canonical(value)))
            for field in ("name", "observed_id"):
                for wrong in (None, [], 7, True):
                    value = json.loads(self.fx.manifest.read_bytes()); value["volumes"][role][field] = wrong; manifests.append(self.fx.write_raw_manifest(canonical(value)))
        manifests.extend([self.fx.write_raw_manifest(b"{"), self.fx.write_raw_manifest(b"[]\n")])
        compose_link = self.fx.root / "compose-link.yaml"; compose_link.symlink_to(ROOT / "deploy/runtime/compose.yaml")
        manifests.append(self.fx.write_manifest(compose_file=str(compose_link)))
        for manifest in manifests:
            with self.subTest(manifest=manifest.name):
                before = tree(self.fx.evidence)
                code, payload, _ = self.fx.run("create", manifest=manifest, operation_id="11111111-1111-4111-8111-111111111111")
                self.assertEqual((64, {"ok": False, "reason": "TARGET_INVALID"}), (code, payload))
                self.assertEqual(before, tree(self.fx.evidence))
                self.assertEqual([], self.fx.effects_list())

    def test_invalid_operation_id_rejects_before_evidence(self):
        for operation in (None, "", "not-a-uuid", "00000000-0000-0000-0000-000000000000"):
            with self.subTest(operation=operation):
                before = tree(self.fx.evidence)
                code, payload, _ = self.fx.run("create", operation_id=operation)
                self.assertEqual((64, {"ok": False, "reason": "TARGET_INVALID"}), (code, payload))
                self.assertEqual(before, tree(self.fx.evidence))

    def test_production_driver_and_fixture_guard_fail_closed(self):
        code, payload, _ = self.fx.run("create", operation_id="22222222-2222-4222-8222-222222222222", test_mode=False)
        self.assertNotEqual(0, code)
        self.assertEqual({"ok": False, "reason": "PRODUCTION_DRIVER_UNAVAILABLE"}, payload)
        self.assertEqual([], self.fx.effects_list())

        code, payload, _ = self.fx.run("create", operation_id="23232323-2323-4323-8323-232323232323",
                                       extra=("--fixture-driver", str(self.fx.fixture)), test_mode=False)
        self.assertNotEqual(0, code)
        self.assertEqual({"ok": False, "reason": "TEST_DRIVER_FORBIDDEN"}, payload)

        non_test = self.fx.write_manifest(project="production-fmonitor2")
        code, payload, _ = self.fx.run("create", manifest=non_test,
                                       operation_id="24242424-2424-4424-8424-242424242424")
        self.assertNotEqual(0, code)
        self.assertEqual({"ok": False, "reason": "TEST_DRIVER_FORBIDDEN"}, payload)
        self.assertEqual([], self.fx.effects_list())


if __name__ == "__main__": unittest.main(verbosity=2)
