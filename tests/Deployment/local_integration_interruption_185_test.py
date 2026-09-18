#!/usr/bin/env python3
"""LOCAL-INTEGRATION-ENV-001: one-shot config interruption and tmpfs lifecycle."""
import json
import os
import re
import shutil
import subprocess
import tempfile
import time
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SECRET = "ISSUE185_INTERRUPTION_SECRET_CANARY"
WRAPPER = ROOT / "bin/fmonitor2-run-with-local-integration-config"
makefile = (ROOT / "Makefile").read_text()
compose = (ROOT / "deploy/runtime/compose.yaml").read_text()

assert shutil.which("docker"), "SETUP_FAILURE: Docker required"
with tempfile.TemporaryDirectory() as raw:
    box = Path(raw); source = box / "legacy.env"
    source.write_text("FMONITOR_SOURCE_HOST=invalid\nFMONITOR_SOURCE_PORT=3306\nFMONITOR_SOURCE_NAME=legacy\nFMONITOR_SOURCE_USER=reader\nFMONITOR_SOURCE_PASSWORD='" + SECRET + "'\nFMONITOR_MIGRATION_CUTOFF=\n")
    source.chmod(0o600)
    tag = "fmonitor2-issue185-interruption:" + os.urandom(5).hex()
    built = subprocess.run(["docker", "build", "--quiet", "--file", "deploy/runtime/Dockerfile", "--tag", tag, "."], cwd=ROOT, text=True, capture_output=True, timeout=300)
    assert built.returncode == 0, "SETUP_FAILURE: runtime image build failed\n" + built.stderr[-2000:]
    php = r'''$p=getenv("FMONITOR_LEGACY_SOURCE_CONFIG");if(posix_geteuid()!==10001||!is_readable($p)||!str_ends_with($p,".ready")||strpos(file_get_contents($p),"ISSUE185_INTERRUPTION_SECRET_CANARY")===false)exit(92);pcntl_async_signals(true);foreach([SIGTERM,SIGINT,SIGQUIT]as$s)pcntl_signal($s,function($caught)use($p){echo "CHILD_CAUGHT=$caught READY=".(is_file($p)?"1":"0")."\n";flush();usleep(350000);echo "CHILD_REAPABLE\n";flush();exit(40+$caught);});echo "CHILD_READY DELIVERED_READY=1\n";flush();while(true)sleep(1);'''
    tmpfs = "/run/fmonitor-local-integration:rw,noexec,nosuid,nodev,mode=0700,uid=10001,gid=10001"
    compatibility_tmpfs = "/run/fmonitor-secrets:rw,noexec,nosuid,nodev,mode=0700,uid=10001,gid=10001"
    base = ["docker", "run", "--detach", "--user", "0:0", "--volume", f"{source}:/run/fmonitor-input/config:ro", "--tmpfs", tmpfs, "--tmpfs", compatibility_tmpfs, "--stop-signal", "SIGTERM", "--entrypoint", "bin/fmonitor2-run-with-local-integration-config", tag, "legacy", "/run/fmonitor-input/config", "--", "php", "-r", php]
    try:
        for signal, number in (("TERM", 15), ("INT", 2), ("QUIT", 3)):
            name = "fm2-i185-interrupt-" + signal.lower() + "-" + os.urandom(4).hex()
            created = subprocess.run(base[:3] + ["--name", name] + base[3:], cwd=ROOT, text=True, capture_output=True)
            assert created.returncode == 0, created.stderr
            try:
                deadline = time.time() + 10; logs = None
                while time.time() < deadline:
                    logs = subprocess.run(["docker", "logs", name], text=True, capture_output=True)
                    if "CHILD_READY" in logs.stdout: break
                    time.sleep(0.05)
                assert logs is not None and "CHILD_READY DELIVERED_READY=1" in logs.stdout and SECRET not in logs.stdout + logs.stderr
                sent = subprocess.run(["docker", "stop", "--time", "5", name] if signal == "TERM" else ["docker", "kill", "--signal", signal, name], text=True, capture_output=True, timeout=10)
                assert sent.returncode == 0, sent.stderr
                waited = subprocess.run(["docker", "wait", name], text=True, capture_output=True, timeout=8)
                assert waited.returncode == 0 and int(waited.stdout.strip()) != 0, f"INTENDED_RED: {signal} returned success"
                logs = subprocess.run(["docker", "logs", name], text=True, capture_output=True)
                assert f"CHILD_CAUGHT={number} READY=1" in logs.stdout, f"INTENDED_RED: {signal} not forwarded while .ready existed"
                assert "CHILD_REAPABLE" in logs.stdout, f"INTENDED_RED: wrapper did not wait for {signal} child"
                state = json.loads(subprocess.run(["docker", "inspect", name, "--format", "{{json .State}}"], text=True, capture_output=True, check=True).stdout)
                assert state["Running"] is False and state["ExitCode"] != 0
                mounts = json.loads(subprocess.run(["docker", "inspect", name, "--format", "{{json .Mounts}}"], text=True, capture_output=True, check=True).stdout)
                tmpfs_mounts = json.loads(subprocess.run(["docker", "inspect", name, "--format", "{{json .HostConfig.Tmpfs}}"], text=True, capture_output=True, check=True).stdout)
                assert "/run/fmonitor-local-integration" in tmpfs_mounts
                assert all(not (item["Type"] in ("volume", "bind") and item["Destination"] == "/run/fmonitor-secrets") for item in mounts)
            finally:
                subprocess.run(["docker", "rm", "-f", name], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

        # SIGKILL cannot run cleanup; tmpfs confinement, not a trap, prevents persistence.
        name = "fm2-i185-interrupt-kill-" + os.urandom(4).hex()
        created = subprocess.run(base[:3] + ["--name", name] + base[3:], cwd=ROOT, text=True, capture_output=True)
        assert created.returncode == 0, created.stderr
        try:
            deadline = time.time() + 10
            while time.time() < deadline:
                logs = subprocess.run(["docker", "logs", name], text=True, capture_output=True)
                if "CHILD_READY" in logs.stdout: break
                time.sleep(0.05)
            assert "CHILD_READY DELIVERED_READY=1" in logs.stdout
            mounts = json.loads(subprocess.run(["docker", "inspect", name, "--format", "{{json .Mounts}}"], text=True, capture_output=True, check=True).stdout)
            tmpfs_mounts = json.loads(subprocess.run(["docker", "inspect", name, "--format", "{{json .HostConfig.Tmpfs}}"], text=True, capture_output=True, check=True).stdout)
            assert "/run/fmonitor-local-integration" in tmpfs_mounts
            assert all(not (item["Type"] == "volume" and item["Destination"] == "/run/fmonitor-secrets") for item in mounts)
            subprocess.run(["docker", "kill", "--signal", "KILL", name], check=True, text=True, capture_output=True)
            waited = subprocess.run(["docker", "wait", name], text=True, capture_output=True, timeout=8)
            assert waited.returncode == 0 and int(waited.stdout.strip()) != 0
        finally:
            subprocess.run(["docker", "rm", "-f", name], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        assert subprocess.run(["docker", "inspect", name], text=True, capture_output=True).returncode != 0

        # Make must select exactly the lifecycle exercised above.
        for target in ("import-legacy", "sync-workforce"):
            block = makefile.split(target + ":", 1)[1].split("\n\n", 1)[0]
            assert " local-integration " in block, f"INTENDED_RED: {target} does not select one-shot Compose service"
            assert "/run/fmonitor-secrets" not in block, f"INTENDED_RED: {target} persists one-shot config in runtime secrets volume"
        service_match = re.search(r"(?ms)^  local-integration:\n(.*?)(?=^  [A-Za-z0-9_-]+:\n|\Z)", compose)
        assert service_match is not None, "INTENDED_RED: one-shot Compose service missing"
        service = service_match.group(1)
        assert "stop_signal: SIGTERM" in service, "INTENDED_RED: one-shot stop signal does not match wrapper"
        assert "/run/fmonitor-local-integration" in service and "tmpfs:" in service, "INTENDED_RED: one-shot config is not tmpfs"
        assert "secrets:/run/fmonitor-secrets" not in service, "INTENDED_RED: one-shot service mounts persistent runtime secrets"
    finally:
        subprocess.run(["docker", "image", "rm", "-f", tag], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

print("PASS: LOCAL-INTEGRATION-ENV-001 interruption supervision and tmpfs")
