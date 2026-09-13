#!/usr/bin/env python3
"""Executable contract for YII2-STAND-TARGET-COMPOSE-001."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
CLI = ROOT / "tools/delivery/validate-stand-target.py"
COMPOSE = ROOT / "deploy/runtime/compose.yaml"
TEMPLATE = ROOT / "tools/delivery/compose.runtime.yaml.in"


class StandTargetComposeContract(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.root = Path(self.temporary.name).resolve()
        self.evidence_root = self.root / "evidence"
        self.evidence_root.mkdir()
        self.external_marker = self.root / "external-called"
        self.fake_bin = self.root / "bin"
        self.fake_bin.mkdir()
        for name in ("docker", "mysql", "mariadb", "curl", "ssh"):
            executable = self.fake_bin / name
            executable.write_text(f'#!/bin/sh\ntouch "{self.external_marker}"\nexit 99\n')
            executable.chmod(0o700)
        self.canary = self.evidence_root / "canary"
        self.canary.write_bytes(b"UNCHANGED")
        self.manifest = {
            "version": 1,
            "authorization_id": "owner-2026-09-13-issue-76-stand-reset",
            "compose_file": str(COMPOSE.resolve()),
            "project": "fmonitor2-stand",
            "services": {"database": "db", "prepare": "prepare", "migration": "migrate", "php": "php", "web": "web", "worker": "jobs-worker", "scheduler": "jobs-scheduler"},
            "database": "fmonitor2",
            "volumes": {"database": "fm2-database", "state": "fm2-state", "secrets": "fm2-secrets"},
            "current_image": "runtime@sha256:" + "1" * 64,
            "candidate_image": "runtime@sha256:" + "2" * 64,
            "evidence_root": str(self.evidence_root),
            "observed": {"project_id": "project-1", "database_volume_id": "db-1", "state_volume_id": "state-1", "secrets_volume_id": "secrets-1"},
        }

    def tearDown(self):
        self.temporary.cleanup()

    def snapshot(self):
        return {str(path.relative_to(self.root)): path.read_bytes() if path.is_file() else None for path in self.root.rglob("*")}

    def invoke(self, manifest, serialized=None, cwd=None, locale="C", hostile="do-not-leak"):
        manifest_path = self.root / "manifest.json"
        manifest_path.write_text(serialized or json.dumps(manifest))
        before = self.snapshot()
        environment = {"PATH": str(self.fake_bin), "LC_ALL": locale, "HOME": str(Path.home()), "HOSTILE_SECRET": hostile}
        profile = f'(version 1)(allow default)(deny network*)(deny file-write*)(allow file-write* (subpath "{self.root}"))'
        command = [sys.executable, "-B", str(CLI), str(manifest_path)]
        if Path("/usr/bin/sandbox-exec").is_file():
            command = ["/usr/bin/sandbox-exec", "-p", profile, *command]
        result = subprocess.run(command, cwd=cwd or self.root, env=environment, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        self.assertEqual(before, self.snapshot())
        self.assertFalse(self.external_marker.exists())
        self.assertEqual("", result.stderr)
        self.assertNotIn(hostile, result.stdout)
        return result.returncode, json.loads(result.stdout), result.stdout

    def assert_invalid(self, changes=None, remove=None):
        value = json.loads(json.dumps(self.manifest))
        if changes:
            value.update(changes)
        if remove:
            value.pop(remove)
        expected = (64, {"ok": False, "reason": "TARGET_INVALID"}, '{"ok":false,"reason":"TARGET_INVALID"}\n')
        self.assertEqual(expected, self.invoke(value))

    def test_invalid_matrix(self):
        cases = [
            ({}, "database"), ({"extra": "x"}, None), ({"version": "1"}, None), ({"version": 2}, None),
            ({"authorization_id": "bad"}, None), ({"compose_file": "relative.yaml"}, None),
            ({"compose_file": str((ROOT / "compose.yaml").resolve())}, None), ({"project": "default"}, None),
            ({"project": "${PROJECT}"}, None), ({"database": "${DB}"}, None), ({"current_image": []}, None),
            ({"current_image": ""}, None), ({"current_image": "runtime:latest"}, None), ({"candidate_image": "runtime:latest"}, None),
            ({"evidence_root": "relative"}, None), ({"evidence_root": "/"}, None), ({"evidence_root": str(Path.home())}, None),
            ({"evidence_root": str(ROOT)}, None), ({"services": []}, None),
            ({"services": {**self.manifest["services"], "extra": "x"}}, None),
            ({"services": {**self.manifest["services"], "worker": "web"}}, None), ({"volumes": []}, None),
            ({"volumes": {"database": "same", "state": "same", "secrets": "same"}}, None), ({"observed": {}}, None),
            ({"observed": {**self.manifest["observed"], "project_id": ""}}, None),
            ({"observed": dict.fromkeys(self.manifest["observed"], "same")}, None),
        ]
        for changes, remove in cases:
            with self.subTest(changes=changes, remove=remove):
                self.assert_invalid(changes, remove)
        linked_parent = self.root / "linked-parent"
        linked_parent.symlink_to(ROOT)
        self.assert_invalid({"evidence_root": str(linked_parent / "child")})

    def test_valid_digest_is_canonical_and_environment_independent(self):
        canonical = json.dumps(self.manifest, sort_keys=True, separators=(",", ":"), ensure_ascii=False)
        digest = hashlib.sha256(canonical.encode()).hexdigest()
        payload = {"digest": digest, "ok": True, "outcome": "TARGET_VALID"}
        expected = (0, payload, json.dumps(payload, sort_keys=True, separators=(",", ":")) + "\n")
        variants = [json.dumps(self.manifest, indent=4), json.dumps(dict(reversed(list(self.manifest.items())))), " \n" + json.dumps(self.manifest) + "\n"]
        for index, serialized in enumerate(variants):
            with self.subTest(index=index):
                self.assertEqual(expected, self.invoke(self.manifest, serialized, Path("/") if index == 1 else self.root, "C.UTF-8" if index == 2 else "C", f"secret-{index}"))

    def test_compose_is_exact_parsed_yii2_topology(self):
        self.assertEqual(TEMPLATE.read_bytes(), COMPOSE.read_bytes())
        environment = {key: "x" for key in ("FMONITOR_DB_PASSWORD", "FMONITOR_MIGRATION_DB_PASSWORD", "FMONITOR_YII_COOKIE_VALIDATION_KEY", "FMONITOR_YII_IDENTITY_KEY")}
        environment.update({"PATH": os.environ["PATH"], "FMONITOR_RUNTIME_IMAGE": "runtime@sha256:" + "a" * 64, "FMONITOR_DB_NAME": "fmonitor2", "FMONITOR_DB_USER": "runtime", "FMONITOR_MIGRATION_DB_USER": "migration", "FMONITOR_HTTP_PORT": "18092", "FMONITOR_PROCESS_TABLE_PREFIX": "fm2_", "FMONITOR_LEGACY_TABLE_PREFIX": "fm2_", "FMONITOR_SESSION_INSTANCE": "stand", "FMONITOR_TRUSTED_REQUEST_HOST": "127.0.0.1:18092", "FMONITOR_TRUSTED_REQUEST_SCHEME": "http", "FMONITOR_BITRIX_ORIGIN": "https://example.invalid", "FMONITOR_BITRIX_WEBHOOK_USER_ID": "1", "FMONITOR_BITRIX_DEPARTMENT_IDS_JSON": "[]", "FMONITOR_BITRIX_TOKEN_HOST_FILE": "/run/secrets/token", "FMONITOR_BITRIX_CA_HOST_FILE": "/run/secrets/ca"})
        result = subprocess.run(["docker", "compose", "-f", str(COMPOSE), "--profile", "jobs", "--profile", "deployment", "config", "--format", "json"], env=environment, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        self.assertEqual(0, result.returncode, result.stderr)
        services = json.loads(result.stdout)["services"]
        self.assertEqual({"db", "prepare", "migrate", "php", "web", "jobs-worker", "jobs-scheduler"}, set(services))
        self.assertEqual(["php", "bin/yii", "schema-migrate/run", "--interactive=0"], services["migrate"]["command"])
        self.assertEqual(["php", "bin/yii", "jobs/worker", "--interactive=0"], services["jobs-worker"]["command"])
        self.assertEqual(["php", "bin/yii", "jobs/scheduler", "--interactive=0"], services["jobs-scheduler"]["command"])
        for name in ("php", "jobs-worker", "jobs-scheduler"):
            self.assertEqual("service_completed_successfully", services[name]["depends_on"]["migrate"]["condition"])
        self.assertNotIn("rapid-pilot", COMPOSE.read_text())


if __name__ == "__main__":
    result = unittest.main(verbosity=2, exit=False).result
    if result.wasSuccessful():
        print("YII2_STAND_TARGET_COMPOSE_001_OK")
    raise SystemExit(0 if result.wasSuccessful() else 1)
