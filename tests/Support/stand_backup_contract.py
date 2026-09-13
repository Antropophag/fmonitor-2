"""Independent fixtures for YII2-STAND-BACKUP-CONSOLE-001 public tests."""
from __future__ import annotations

import hashlib
import json
import os
import subprocess
import tempfile
import uuid
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
CLI = ROOT / "bin/yii"
CONTROLLER = ROOT / "app/YiiRuntime/Commands/StandBackupController.php"
AUTH = "owner-2026-09-13-issue-76-stand-backup"
CANARIES = ("secret-password", "mysql://secret-dsn", "private-secret-path", "SQL-PRIVATE")
PAYLOADS = {
    "database.sql": b"SQL-PRIVATE\nCREATE DATA\n",
    "artifacts.tar": b"ARTIFACT-BYTES\x00\x01",
    "sessions.json": b'{"sessions":["opaque-session"]}\n',
}


def canonical(value: object) -> bytes:
    return json.dumps(value, ensure_ascii=True, sort_keys=True, separators=(",", ":")).encode() + b"\n"


def digest(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def tree(root: Path) -> dict[str, tuple[str, bytes | str]]:
    if not root.exists():
        return {}
    result = {}
    for path in sorted(root.rglob("*")):
        rel = path.relative_to(root).as_posix()
        if path.is_symlink():
            result[rel] = ("symlink", os.readlink(path))
        elif path.is_file():
            result[rel] = ("file", path.read_bytes())
        elif path.is_dir():
            result[rel] = ("dir", "")
    return result


def published_tree(root: Path) -> dict[str, tuple[str, bytes | str]]:
    return {key: value for key, value in tree(root).items()
            if key == "verified.json" or key.startswith("bundles/")}


class Fixture:
    def __init__(self) -> None:
        self.temp = tempfile.TemporaryDirectory(prefix="fm2-stand-backup-")
        self.root = Path(self.temp.name).resolve()
        self.evidence = self.root / "evidence"
        self.evidence.mkdir()
        self.effects = self.root / "effects.jsonl"
        self.fixture = self.root / "fixture.json"
        self.manifest = self.write_manifest()
        self.write_driver()

    def close(self) -> None:
        self.temp.cleanup()

    def write_manifest(self, **changes: object) -> Path:
        value = {
            "version": 1,
            "authorization_id": AUTH,
            "source": "404aa6858b8fdd61ac0e8151f09a67000a387ead",
            "image": "fmonitor2-runtime@sha256:" + "2" * 64,
            "compose_file": str((ROOT / "deploy/runtime/compose.yaml").resolve()),
            "project": "test-fmonitor2-backup",
            "database": {"name": "test_fmonitor2", "observed_id": "db-id"},
            "volumes": {
                "database": {"name": "test-fm2-db", "observed_id": "db-volume-id"},
                "artifacts": {"name": "test-fm2-artifacts", "observed_id": "artifact-volume-id"},
                "sessions": {"name": "test-fm2-sessions", "observed_id": "session-volume-id"},
            },
            "evidence_root": str(self.evidence),
            "inventory_digest": "3" * 64,
        }
        value.update(changes)
        path = self.root / ("target-" + uuid.uuid4().hex + ".json")
        path.write_bytes(canonical(value))
        return path

    def write_raw_manifest(self, data: bytes) -> Path:
        path = self.root / ("target-" + uuid.uuid4().hex + ".json")
        path.write_bytes(data)
        return path

    def write_driver(self, **changes: object) -> None:
        value = {
            "effects_path": str(self.effects),
            "capacity_ok": True,
            "inventory_matches": True,
            "payloads": {name: data.decode("latin1") for name, data in PAYLOADS.items()},
            "reported": {name: {"size": 1, "sha256": "0" * 64} for name in PAYLOADS},
            "clock": "2026-09-13T10:00:00Z",
            "host": "fixture-host",
            "pid": 4242,
            "native_error": "secret-password mysql://secret-dsn private-secret-path SQL-PRIVATE",
        }
        value.update(changes)
        self.fixture.write_bytes(canonical(value))

    def effects_list(self) -> list[dict]:
        if not self.effects.exists():
            return []
        return [json.loads(line) for line in self.effects.read_text().splitlines()]

    def run(self, command: str, *, manifest: Path | None = None, operation_id: str | None = None,
            extra: tuple[str, ...] = (), test_mode: bool = True) -> tuple[int, dict, bytes]:
        if not CONTROLLER.is_file():
            raise AssertionError("INTENTIONAL_RED: PHP/Yii2 stand-backup command is missing")
        argv = ["php", str(CLI), "stand-backup/" + command,
                "--manifest=" + str(manifest or self.manifest)]
        if operation_id is not None:
            argv += ["--operation-id=" + operation_id]
        if test_mode:
            argv += ["--fixture-driver=" + str(self.fixture)]
        normalized=[]; index=0
        while index < len(extra):
            if extra[index].startswith("--") and index + 1 < len(extra):
                normalized.append(extra[index] + "=" + extra[index + 1]); index += 2
            else: normalized.append(extra[index]); index += 1
        argv += normalized + ["--interactive=0"]
        env = {"PATH": os.environ.get("PATH", "/usr/bin:/bin"), "LANG": "C", "LC_ALL": "C"}
        if test_mode:
            env["FMONITOR_STAND_BACKUP_TEST_MODE"] = "1"
        completed = subprocess.run(argv, cwd=self.root, env=env, stdout=subprocess.PIPE,
                                   stderr=subprocess.PIPE, timeout=15)
        if completed.stderr != b"":
            raise AssertionError(f"stderr is not empty: {completed.stderr!r}")
        decoder = json.JSONDecoder()
        text = completed.stdout.decode("utf-8")
        value, end = decoder.raw_decode(text)
        if text[end:] != "\n" or not isinstance(value, dict):
            raise AssertionError(f"stdout is not exactly one JSON object: {completed.stdout!r}")
        for canary in CANARIES + (str(ROOT), str(self.root), str(self.evidence), str(self.manifest)):
            if canary.encode() in completed.stdout:
                raise AssertionError(f"public output disclosed canary: {canary!r}")
        return completed.returncode, value, completed.stdout
