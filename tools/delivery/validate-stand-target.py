#!/usr/bin/env python3
"""Validate an immutable stand target without observing or changing the stand."""

from __future__ import annotations

import hashlib
import json
import os
import pwd
import re
import sys
from pathlib import Path
from typing import Any


INVALID = '{"ok":false,"reason":"TARGET_INVALID"}\n'
AUTHORIZATION_ID = "owner-2026-09-13-issue-76-stand-reset"
IMAGE_PATTERN = re.compile(r"^[^@\s]+@sha256:[0-9a-f]{64}$")
ROOT = Path(__file__).resolve().parents[2]
COMPOSE_FILE = (ROOT / "deploy/runtime/compose.yaml").resolve()
TOP_LEVEL_KEYS = {
    "version",
    "authorization_id",
    "compose_file",
    "project",
    "services",
    "database",
    "volumes",
    "current_image",
    "candidate_image",
    "evidence_root",
    "observed",
}
SERVICES = {
    "database": "db",
    "prepare": "prepare",
    "migration": "migrate",
    "php": "php",
    "web": "web",
    "worker": "jobs-worker",
    "scheduler": "jobs-scheduler",
}
VOLUME_KEYS = {"database", "state", "secrets"}
OBSERVED_KEYS = {
    "project_id",
    "database_volume_id",
    "state_volume_id",
    "secrets_volume_id",
}


def invalid() -> int:
    sys.stdout.write(INVALID)
    return 64


def is_plain_string(value: Any) -> bool:
    return isinstance(value, str) and bool(value) and "${" not in value


def contains_unresolved(value: Any) -> bool:
    if isinstance(value, str):
        return "${" in value
    if isinstance(value, list):
        return any(contains_unresolved(item) for item in value)
    if isinstance(value, dict):
        return any(
            contains_unresolved(key) or contains_unresolved(item)
            for key, item in value.items()
        )
    return False


def exact_object(value: Any, keys: set[str]) -> bool:
    return isinstance(value, dict) and set(value) == keys


def canonical_absolute_path(value: Any) -> Path | None:
    if not is_plain_string(value):
        return None
    path = Path(value)
    if not path.is_absolute() or str(path) != os.path.normpath(str(path)):
        return None
    if path.is_symlink():
        return None
    try:
        resolved = path.resolve(strict=False)
    except (OSError, RuntimeError):
        return None
    return resolved if resolved == path else None


def is_within(path: Path, root: Path) -> bool:
    return path == root or root in path.parents


def valid_manifest(manifest: Any) -> bool:
    if not exact_object(manifest, TOP_LEVEL_KEYS) or contains_unresolved(manifest):
        return False
    if type(manifest["version"]) is not int or manifest["version"] != 1:
        return False
    if manifest["authorization_id"] != AUTHORIZATION_ID:
        return False

    compose_file = canonical_absolute_path(manifest["compose_file"])
    if compose_file != COMPOSE_FILE:
        return False
    if not is_plain_string(manifest["project"]) or manifest["project"] == "default":
        return False
    if not is_plain_string(manifest["database"]):
        return False

    services = manifest["services"]
    if not exact_object(services, set(SERVICES)) or services != SERVICES:
        return False
    if len(set(services.values())) != len(services):
        return False

    volumes = manifest["volumes"]
    if not exact_object(volumes, VOLUME_KEYS):
        return False
    if not all(is_plain_string(item) for item in volumes.values()):
        return False
    if len(set(volumes.values())) != len(volumes):
        return False

    if not isinstance(manifest["current_image"], str) or not IMAGE_PATTERN.fullmatch(manifest["current_image"]):
        return False
    if not isinstance(manifest["candidate_image"], str) or not IMAGE_PATTERN.fullmatch(manifest["candidate_image"]):
        return False

    evidence_root = canonical_absolute_path(manifest["evidence_root"])
    if evidence_root is None or evidence_root == Path("/"):
        return False
    try:
        home = Path(pwd.getpwuid(os.getuid()).pw_dir).resolve(strict=False)
    except (KeyError, OSError):
        return False
    if is_within(evidence_root, home) or is_within(evidence_root, ROOT):
        return False

    observed = manifest["observed"]
    if not exact_object(observed, OBSERVED_KEYS):
        return False
    if not all(is_plain_string(item) for item in observed.values()):
        return False
    if len(set(observed.values())) != len(observed):
        return False
    return True


def main(argv: list[str]) -> int:
    if len(argv) != 2:
        return invalid()
    try:
        with open(argv[1], "r", encoding="utf-8") as stream:
            manifest = json.load(stream)
    except (OSError, UnicodeError, json.JSONDecodeError):
        return invalid()
    if not valid_manifest(manifest):
        return invalid()

    canonical = json.dumps(
        manifest, sort_keys=True, separators=(",", ":"), ensure_ascii=False
    ).encode("utf-8")
    result = {
        "digest": hashlib.sha256(canonical).hexdigest(),
        "ok": True,
        "outcome": "TARGET_VALID",
    }
    sys.stdout.write(json.dumps(result, sort_keys=True, separators=(",", ":")) + "\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))
