#!/usr/bin/env python3
"""LOCAL-INTEGRATION-ENV-001 / #185: cross-UID container delivery contract."""
import json
import os
import shutil
import subprocess
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
WRAPPER = ROOT / "bin/fmonitor2-run-with-local-integration-config"
SECRET = "ISSUE185_CROSS_UID_SECRET_CANARY"

legacy = (
    "FMONITOR_SOURCE_HOST=legacy-db.invalid\n"
    "FMONITOR_SOURCE_PORT=3306\n"
    "FMONITOR_SOURCE_NAME=legacy\n"
    "FMONITOR_SOURCE_USER=reader\n"
    f"FMONITOR_SOURCE_PASSWORD='{SECRET}'\n"
    "FMONITOR_MIGRATION_CUTOFF=2026-09-18 00:00:00\n"
)
bitrix = json.dumps({"baseUrl": f"https://bitrix.invalid/rest/7/{SECRET}", "departments": [71]}) + "\n"

assert WRAPPER.is_file(), "INTENDED_RED: cross-UID container delivery wrapper missing"
makefile = (ROOT / "Makefile").read_text()
for target in ("import-legacy", "sync-workforce"):
    block = makefile.split(target + ":", 1)[1].split("\n\n", 1)[0]
    assert " local-integration " in block, f"INTENDED_RED: {target} bypasses container delivery service"
assert "USER 10001:10001" in (ROOT / "deploy/runtime/Dockerfile").read_text()
assert "COPY bin ./bin" in (ROOT / "deploy/runtime/Dockerfile").read_text()
compose = (ROOT / "deploy/runtime/compose.yaml").read_text()
assert 'entrypoint: ["bin/fmonitor2-run-with-local-integration-config"]' in compose

if shutil.which("docker") is None:
    raise AssertionError("SETUP_FAILURE: Docker is required for real runtime-UID evidence")

with tempfile.TemporaryDirectory() as raw:
    box = Path(raw)
    if os.getuid() == 10001:
        raise AssertionError("SETUP_FAILURE: host UID must differ from runtime UID 10001")
    configs = {"legacy": legacy, "bitrix": bitrix}
    tag = "fmonitor2-issue185-cross-uid:" + os.urandom(5).hex()
    build = subprocess.run(["docker", "build", "--quiet", "--file", "deploy/runtime/Dockerfile", "--tag", tag, "."], cwd=ROOT, text=True, capture_output=True, timeout=300)
    assert build.returncode == 0, "SETUP_FAILURE: runtime image build failed\n" + build.stderr[-2000:]
    try:
        for kind, document in configs.items():
            host = box / (kind + ".config")
            host.write_text(document)
            host.chmod(0o600)
            assert host.stat().st_uid != 10001 and (host.stat().st_mode & 0o777) == 0o600
            env_name = "FMONITOR_LEGACY_SOURCE_CONFIG" if kind == "legacy" else "FMONITOR_BITRIX_CONFIG"
            php = (
                '$p=getenv("' + env_name + '");'
                'if(posix_geteuid()!==10001||!is_string($p)||!is_file($p)||!is_readable($p)||is_link($p))exit(91);'
                '$v=file_get_contents($p);if(!is_string($v)||strpos($v,"ISSUE185_CROSS_UID_SECRET_CANARY")===false)exit(92);'
            )
            command = [
                "docker", "run", "--rm", "--user", "0:0",
                "--volume", f"{host}:/run/fmonitor-input/config:ro",
                "--tmpfs", "/run/fmonitor-local-integration:rw,noexec,nosuid,nodev,mode=0700,uid=10001,gid=10001",
                "--env", "FMONITOR_DB_HOST=127.0.0.1", "--env", "FMONITOR_DB_PORT=9",
                "--env", "FMONITOR_DB_NAME=synthetic", "--env", "FMONITOR_DB_USER=synthetic",
                "--env", "FMONITOR_DB_PASSWORD=synthetic", "--env", "FMONITOR_PROCESS_TABLE_PREFIX=fm2_",
                "--env", "FMONITOR_LEGACY_TABLE_PREFIX=fm2_",
                "--entrypoint", "bin/fmonitor2-run-with-local-integration-config", tag,
                kind, "/run/fmonitor-input/config", "--", "php", "-r", php,
            ]
            read = subprocess.run(command, cwd=ROOT, text=True, capture_output=True)
            combined = read.stdout + read.stderr
            assert read.returncode == 0, (kind, read.returncode, combined)
            assert SECRET not in combined

            route = "legacy-import/run" if kind == "legacy" else "workforce-sync/run"
            loader = subprocess.run(command[:-3] + ["php", "bin/yii", route, "--interactive=0"], cwd=ROOT, text=True, capture_output=True)
            assert loader.returncode == 69, "INTENDED_RED: canonical PHP loader did not read delivered config before isolated unavailable DB"
            assert SECRET not in loader.stdout + loader.stderr

            updated_secret = SECRET + "_UPDATED"
            host.write_text(document.replace(SECRET, updated_secret)); host.chmod(0o600)
            replay_php = php.replace(SECRET, updated_secret) + f'if(strpos($v,"{SECRET}\n")!==false)exit(93);'
            replay = subprocess.run(command[:-1] + [replay_php], cwd=ROOT, text=True, capture_output=True)
            assert replay.returncode == 0, "INTENDED_RED: replay did not use only updated snapshot"
            assert SECRET not in replay.stdout + replay.stderr and updated_secret not in replay.stdout + replay.stderr

            failed = subprocess.run(command[:-3] + ["php", "-r", "exit(23);"], cwd=ROOT, text=True, capture_output=True)
            assert failed.returncode == 23, "INTENDED_RED: cleanup masked importer failure"
            assert SECRET not in failed.stdout + failed.stderr

        history = subprocess.run(["docker", "history", "--no-trunc", tag], cwd=ROOT, text=True, capture_output=True)
        assert history.returncode == 0 and SECRET not in history.stdout + history.stderr
        logs = subprocess.run(["docker", "ps", "-aq", "--filter", f"ancestor={tag}"], cwd=ROOT, text=True, capture_output=True)
        assert logs.returncode == 0 and logs.stdout == "", "temporary config container was not removed"
    finally:
        subprocess.run(["docker", "image", "rm", "-f", tag], cwd=ROOT, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

print("PASS: LOCAL-INTEGRATION-ENV-001 cross-platform cross-UID delivery")
