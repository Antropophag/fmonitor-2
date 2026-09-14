#!/usr/bin/env python3
"""YII2-LOCAL-QUICKSTART-001: public Make seam without real Docker effects."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[2]

with tempfile.TemporaryDirectory() as raw:
    sandbox = Path(raw)
    checkout = sandbox / "checkout"
    checkout.mkdir()
    for relative in ("Makefile", ".env.example"):
        shutil.copy2(root / relative, checkout / relative)
    for relative in ("deploy/runtime/compose.yaml", "deploy/runtime/Dockerfile"):
        target = checkout / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(root / relative, target)

    values = {
        "COMPOSE_PROJECT_NAME": "fm2-local-contract",
        "FMONITOR_RUNTIME_IMAGE": "fmonitor2-runtime:contract",
        "FMONITOR_HTTP_PORT": "18093",
        "FMONITOR_DB_NAME": "fmonitor2",
        "FMONITOR_DB_USER": "fmonitor_runtime",
        "FMONITOR_DB_PASSWORD": "db-secret-contract",
        "FMONITOR_MIGRATION_DB_USER": "root",
        "FMONITOR_MIGRATION_DB_PASSWORD": "migration-secret-contract",
        "FMONITOR_PROCESS_TABLE_PREFIX": "fm2_",
        "FMONITOR_LEGACY_TABLE_PREFIX": "fm2_",
        "FMONITOR_SESSION_INSTANCE": "local-contract",
        "FMONITOR_YII_COOKIE_VALIDATION_KEY": "c" * 32,
        "FMONITOR_YII_IDENTITY_KEY": "i" * 32,
        "FMONITOR_TRUSTED_REQUEST_HOST": "127.0.0.1:18093",
        "FMONITOR_TRUSTED_REQUEST_SCHEME": "http",
        "FMONITOR_INITIAL_OWNER_EMAIL": "owner@example.test",
        "FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD": "owner-secret-contract",
    }
    (checkout / ".env").write_text("".join(f"{key}={value}\n" for key, value in values.items()))

    fake_bin = sandbox / "bin"
    fake_bin.mkdir()
    trace = sandbox / "docker.trace"
    docker = fake_bin / "docker"
    docker.write_text("#!/bin/sh\nprintf '%s\\n' \"$*\" >>\"$FMONITOR_TEST_TRACE\"\nexit 0\n")
    docker.chmod(0o755)
    env = {**os.environ, "PATH": f"{fake_bin}:{os.environ['PATH']}", "FMONITOR_TEST_TRACE": str(trace)}

    def make(target):
        return subprocess.run(
            ["make", "--no-print-directory", target], cwd=checkout, env=env,
            text=True, capture_output=True,
        )

    first = make("up")
    assert first.returncode == 0, first.stderr
    first_trace = trace.read_text()
    assert "deploy/runtime/compose.yaml" in first_trace, "LEGACY_MAKE_UP_TRACE"
    assert "fmonitor2-pilot" not in first_trace and " rapid-pilot/" not in first_trace
    for expected in ("up --detach --wait db", "run --rm prepare", "run --rm migrate", "provision-initial-admin", "up --detach --wait php web"):
        assert expected in first_trace, (expected, first_trace)
    assert "http://127.0.0.1:18093" in first.stdout

    trace.write_text("")
    second = make("up")
    assert second.returncode == 0, second.stderr
    second_trace = trace.read_text()
    assert "--volumes" not in second_trace
    assert "up --detach --wait php web" in second_trace

    trace.write_text("")
    stopped = make("down")
    assert stopped.returncode == 0 and "--volumes" not in trace.read_text()

    trace.write_text("")
    reset = make("reset")
    assert reset.returncode == 0
    assert "deploy/runtime/compose.yaml" in trace.read_text()
    assert "down --volumes --remove-orphans" in trace.read_text()

    (checkout / ".env").write_text((checkout / ".env").read_text().replace("owner-secret-contract", "replace_with_a_strong_unique_password"))
    trace.write_text("")
    rejected = make("up")
    assert rejected.returncode != 0
    assert trace.read_text() == "", "invalid config must fail before Docker effects"
    combined = rejected.stdout + rejected.stderr
    assert "replace_with_a_strong_unique_password" not in combined

print("PASS: YII2-LOCAL-QUICKSTART-001 public Make lifecycle")
