#!/usr/bin/env python3
"""LOCAL-INTEGRATION-ENV-001 A4-A5: atomic staging, replay and secret boundary."""
import json
import os
import stat
import subprocess
import tempfile
import threading
import time
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

root = Path(__file__).resolve().parents[2]
tool = root / "tools/delivery/local-integration-config"
legacy_a = "LEGACY_ATOMIC_A_149"
legacy_b = "LEGACY_ATOMIC_B_149"
bitrix = "BITRIX_PRIVATE_149"

def content(secret: str) -> str:
    return f"""FMONITOR_SOURCE_HOST=legacy-db.example
FMONITOR_SOURCE_PORT=3306
FMONITOR_SOURCE_NAME=fmonitor
FMONITOR_SOURCE_USER=readonly
FMONITOR_SOURCE_PASSWORD='{secret}'
FMONITOR_MIGRATION_CUTOFF=
FMONITOR_BITRIX_WEBHOOK_URL='https://portal.example/rest/7/{bitrix}/'
FMONITOR_BITRIX_DEPARTMENT_IDS_JSON='[71]'
"""

with tempfile.TemporaryDirectory() as raw:
    box = Path(raw)
    private = box / ".local"
    destination = private / "legacy-source.env"

    def stage(secret: str):
        source = box / (secret + ".env")
        source.write_text(content(secret))
        source.chmod(0o600)
        result = subprocess.run([str(tool), "stage", "legacy", str(source), str(destination)], text=True, capture_output=True)
        return result

    first = stage(legacy_a)
    assert first.returncode == 0, (first.stdout, first.stderr)
    assert stat.S_IMODE(private.stat().st_mode) == 0o700
    assert stat.S_IMODE(destination.stat().st_mode) == 0o600
    before = destination.read_bytes()
    assert legacy_a.encode() in before

    # Invalid replacement leaves the old complete snapshot but cannot invoke a consumer.
    bad = box / "bad.env"
    bad.write_text(content(legacy_b).replace("FMONITOR_SOURCE_PORT=3306", "FMONITOR_SOURCE_PORT=bad"))
    bad.chmod(0o600)
    rejected = subprocess.run([str(tool), "stage", "legacy", str(bad), str(destination)], text=True, capture_output=True)
    assert rejected.returncode == 64
    assert destination.read_bytes() == before
    assert legacy_a not in rejected.stdout + rejected.stderr and legacy_b not in rejected.stdout + rejected.stderr

    changed = stage(legacy_b)
    assert changed.returncode == 0
    assert legacy_b.encode() in destination.read_bytes() and legacy_a.encode() not in destination.read_bytes()

    observations = []
    start = threading.Barrier(3)
    stop = threading.Event()
    failures = []
    def repeated_writer(secret: str):
        start.wait()
        return [stage(secret) for _ in range(40)]
    def reader():
        start.wait()
        while not stop.is_set():
            try:
                value = destination.read_text()
                observations.append(value)
                if value not in complete_snapshots:
                    failures.append(value)
            except FileNotFoundError:
                failures.append("MISSING")
            time.sleep(0.0005)
    complete_snapshots = {
        content(legacy_a).split("FMONITOR_BITRIX_WEBHOOK_URL", 1)[0],
        content(legacy_b).split("FMONITOR_BITRIX_WEBHOOK_URL", 1)[0],
    }
    with ThreadPoolExecutor(max_workers=3) as pool:
        writer_a = pool.submit(repeated_writer, legacy_a)
        writer_b = pool.submit(repeated_writer, legacy_b)
        observer = pool.submit(reader)
        results = writer_a.result() + writer_b.result()
        stop.set(); observer.result()
    assert all(result.returncode == 0 for result in results)
    final = destination.read_text()
    assert observations and not failures, failures[:3]
    assert final in complete_snapshots and all(value in complete_snapshots for value in observations)
    assert stat.S_IMODE(destination.stat().st_mode) == 0o600
    assert not list(private.glob(".*.tmp-*")), list(private.iterdir())

    # Unsafe destinations fail closed.
    target = box / "outside"
    target.write_text("outside")
    destination.unlink()
    destination.symlink_to(target)
    unsafe = stage(legacy_a)
    assert unsafe.returncode != 0 and target.read_text() == "outside"
    destination.unlink()

    # A symlinked/unsafe private directory and non-regular destination fail closed.
    outside_dir = box / "outside-dir"; outside_dir.mkdir()
    private.rmdir(); private.symlink_to(outside_dir, target_is_directory=True)
    assert stage(legacy_a).returncode != 0 and list(outside_dir.iterdir()) == []
    private.unlink(); private.mkdir(mode=0o700)
    destination.mkdir()
    assert stage(legacy_a).returncode != 0 and destination.is_dir()
    destination.rmdir()

    # Deterministic publication failure preserves the prior complete destination and cleans temps.
    assert stage(legacy_a).returncode == 0
    preserved = destination.read_bytes()
    private.chmod(0o500)
    try:
        failed_write = stage(legacy_b)
        assert failed_write.returncode != 0
        assert destination.read_bytes() == preserved
    finally:
        private.chmod(0o700)
    assert not list(private.glob(".*.tmp-*"))

tracked = subprocess.run(["git", "check-ignore", ".env", ".env.private", ".local/legacy-source.env"], cwd=root, text=True, capture_output=True)
assert tracked.returncode == 0 and set(tracked.stdout.splitlines()) == {".env", ".env.private", ".local/legacy-source.env"}
dockerignore = (root / ".dockerignore").read_text().splitlines()
assert ".env" in dockerignore and ".env.*" in dockerignore and ".local" in dockerignore and "!.env.example" in dockerignore

makefile = (root / "Makefile").read_text()
integration_blocks = makefile.split("import-legacy:", 1)[1].split("down:", 1)[0]
assert " reset" not in integration_blocks and "--volumes" not in integration_blocks
for secret in (legacy_a, legacy_b, bitrix):
    assert secret not in makefile and secret not in (root / ".env.example").read_text()

print("PASS: LOCAL-INTEGRATION-ENV-001 atomic staging and secret boundary")
