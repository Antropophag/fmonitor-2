#!/usr/bin/env python3
"""PILOT-JOBS-STARTUP-001: isolated smoke of the ordinary root Compose stack."""

from __future__ import annotations

import json
import os
import pathlib
import re
import secrets
import shutil
import signal
import subprocess
import sys
import tempfile
import time


ROOT = pathlib.Path(__file__).resolve().parents[2]
FIXTURE_SERVER = ROOT / "tests/Support/bitrix_delivery_https_server.py"
TOKEN = "COMPOSE_FIXTURE_TOKEN_" + secrets.token_hex(8)


def run(command: list[str], *, env: dict[str, str], timeout: int = 180, check: bool = True) -> subprocess.CompletedProcess[str]:
    try:
        result = subprocess.run(
            command,
            cwd=ROOT,
            env=env,
            stdin=subprocess.DEVNULL,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            timeout=timeout,
        )
    except subprocess.TimeoutExpired as error:
        raise AssertionError(f"SETUP_FAILURE: bounded command timed out: {command[:4]}") from error
    if check and result.returncode != 0:
        safe_output = (result.stdout + result.stderr).replace(TOKEN, "[secret]")
        raise AssertionError(f"command failed ({result.returncode}): {command[:4]}\n{safe_output[-4000:]}")
    return result


def wait_for(path: pathlib.Path, seconds: int = 10) -> None:
    deadline = time.monotonic() + seconds
    while time.monotonic() < deadline:
        if path.is_file():
            return
        time.sleep(0.05)
    raise AssertionError(f"SETUP_FAILURE: fixture did not publish {path.name}")


def write(path: pathlib.Path, value: str, mode: int = 0o600) -> None:
    path.write_text(value, encoding="utf-8")
    path.chmod(mode)


def compose(base: list[str], args: list[str], env: dict[str, str], timeout: int = 180) -> subprocess.CompletedProcess[str]:
    return run(base + args, env=env, timeout=timeout)


def sql(base: list[str], env: dict[str, str], statement: str) -> list[str]:
    result = compose(base, ["exec", "-T", "mariadb", "mariadb", "-N", "-uroot", "-pfmonitor2_demo_root_local", "fmonitor2_demo", "-e", statement], env, 30)
    return [line for line in result.stdout.splitlines() if line]


def main() -> int:
    if shutil.which("docker") is None or shutil.which("openssl") is None:
        raise AssertionError("SETUP_FAILURE: docker and openssl are required")

    identity = secrets.token_hex(6)
    project = "fm2pilotjobs" + identity
    image = "fmonitor2-pilot-jobs-smoke:" + identity
    fixture_root = pathlib.Path(tempfile.mkdtemp(prefix="fm2-pilot-jobs-compose-"))
    override = fixture_root / "compose.override.yaml"
    server: subprocess.Popen[str] | None = None
    env = dict(os.environ)
    env.update({"FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD": "compose-fixture-password", "COMPOSE_PROJECT_NAME": project})
    base = ["docker", "compose", "--progress", "quiet", "--project-name", project, "--file", str(ROOT / "compose.yaml"), "--file", str(override)]

    try:
        write(fixture_root / "scenario.json", '{"mode":"full"}\n')
        write(fixture_root / "requests.jsonl", "")
        run(["openssl", "req", "-x509", "-newkey", "rsa:2048", "-nodes", "-days", "1", "-subj", "/CN=ComposeSmokeCA", "-keyout", str(fixture_root / "ca.key"), "-out", str(fixture_root / "ca.crt")], env=env, timeout=30)
        run(["openssl", "req", "-newkey", "rsa:2048", "-nodes", "-subj", "/CN=host.docker.internal", "-keyout", str(fixture_root / "server.key"), "-out", str(fixture_root / "server.csr")], env=env, timeout=30)
        write(fixture_root / "leaf.ext", "subjectAltName=DNS:host.docker.internal\nextendedKeyUsage=serverAuth\n")
        run(["openssl", "x509", "-req", "-days", "1", "-in", str(fixture_root / "server.csr"), "-CA", str(fixture_root / "ca.crt"), "-CAkey", str(fixture_root / "ca.key"), "-CAcreateserial", "-extfile", str(fixture_root / "leaf.ext"), "-out", str(fixture_root / "server.crt")], env=env, timeout=30)
        server_source = FIXTURE_SERVER.read_text(encoding="utf-8").replace("Server(('127.0.0.1',0),Handler)", "Server(('0.0.0.0',0),Handler)")
        fixture_server = fixture_root / "server.py"
        write(fixture_server, server_source)
        server = subprocess.Popen([sys.executable, str(fixture_server), str(fixture_root)], cwd=ROOT, stdin=subprocess.DEVNULL, stdout=subprocess.DEVNULL, stderr=subprocess.PIPE, text=True)
        wait_for(fixture_root / "ready.json")
        fixture_port = int(json.loads((fixture_root / "ready.json").read_text(encoding="utf-8"))["port"])

        config = fixture_root / "bitrix-workforce.json"
        write(config, json.dumps({"baseUrl": f"https://host.docker.internal:{fixture_port}/rest/7/{TOKEN}/", "departments": [71]}, separators=(",", ":")))
        # The root file remains the caller under test. The override only isolates its
        # image, host ports, private files and Docker resources from every live stand.
        write(override, f"""services:
  pilot:
    image: {image}
    build: !reset null
    ports: !override []
  workforce-sync:
    image: {image}
    environment:
      FMONITOR_BITRIX_CA_FILE: /run/fmonitor-fixture/ca.crt
    extra_hosts:
      - host.docker.internal:host-gateway
    volumes:
      - type: bind
        source: {fixture_root / 'ca.crt'}
        target: /run/fmonitor-fixture/ca.crt
        read_only: true
  workforce-scheduler:
    image: {image}
  mariadb:
    ports: !override []
secrets:
  bitrix-workforce:
    file: {config}
""")

        run(["docker", "build", "--quiet", "--tag", image, "."], env=env, timeout=600)
        compose(base, ["config", "--quiet"], env, 30)
        compose(base, ["up", "--detach", "--wait", "pilot", "mariadb"], env, 240)
        compose(base, ["up", "--detach", "--wait", "--no-deps", "--force-recreate", "workforce-sync", "workforce-scheduler"], env, 180)

        services = set(compose(base, ["ps", "--status", "running", "--services"], env, 30).stdout.split())
        assert {"pilot", "mariadb", "workforce-sync", "workforce-scheduler"} <= services, "root services must all be running"
        delivery_deadline = time.monotonic() + 45
        while True:
            requests = [json.loads(line) for line in (fixture_root / "requests.jsonl").read_text(encoding="utf-8").splitlines()]
            if len(requests) >= 2:
                break
            if time.monotonic() >= delivery_deadline:
                raise AssertionError("native workforce delivery did not complete")
            time.sleep(0.5)
        assert [request["request"]["start"] for request in requests] == [0, 50], "native HTTPS delivery must fetch both pages"
        assert all(request["path"].endswith("/user.get") for request in requests), "only the native workforce endpoint is called"

        manifest = json.loads(compose(base, ["exec", "-T", "pilot", "sh", "-c", "cat /home/fmonitor/.local/state/fmonitor2/pilot-demo/*/active.json"], env, 30).stdout)
        prefix = manifest["processPrefix"]
        assert re.fullmatch(r"[A-Za-z0-9_]{1,25}", prefix), "pilot publishes a validated process prefix"
        quoted_prefix = prefix.replace("`", "``")
        persistence_deadline = time.monotonic() + 45
        while True:
            before = sql(base, env, f"SELECT COUNT(*) FROM `{quoted_prefix}fm2_workforce_catalog`; SELECT COUNT(*) FROM `{quoted_prefix}fm2_workforce_sync_runs` WHERE status='completed'; SELECT COUNT(*) FROM `{quoted_prefix}fm2_jobs`; SELECT COUNT(*) FROM `{quoted_prefix}fm2_job_events`; SELECT COUNT(*) FROM `{quoted_prefix}fm2_scheduler_slots`;")
            if len(before) == 5 and before[0:3] == ["51", "1", "1"] and int(before[3]) >= 3 and before[4] == "1":
                break
            if time.monotonic() >= persistence_deadline:
                raise AssertionError(f"native delivery did not persist its complete Jobs lifecycle: {before}")
            time.sleep(0.5)
        assert before[0:3] == ["51", "1", "1"], f"native delivery and one scheduled job must persist: {before}"
        assert int(before[3]) >= 3 and before[4] == "1", f"Jobs lifecycle facts must persist: {before}"

        compose(base, ["restart", "workforce-sync", "workforce-scheduler"], env, 90)
        deadline = time.monotonic() + 45
        while True:
            health = compose(base, ["ps", "--format", "json", "workforce-sync", "workforce-scheduler"], env, 30)
            rows = [json.loads(line) for line in health.stdout.splitlines() if line]
            if len(rows) == 2 and all(row.get("Health") == "healthy" and row.get("State") == "running" for row in rows):
                break
            if time.monotonic() >= deadline:
                raise AssertionError("worker and scheduler did not become healthy after restart")
            time.sleep(1)
        after = sql(base, env, f"SELECT COUNT(*) FROM `{quoted_prefix}fm2_workforce_catalog`; SELECT COUNT(*) FROM `{quoted_prefix}fm2_workforce_sync_runs`; SELECT COUNT(*) FROM `{quoted_prefix}fm2_jobs`; SELECT COUNT(*) FROM `{quoted_prefix}fm2_scheduler_slots`; SELECT COUNT(*)-COUNT(DISTINCT schedule_key) FROM `{quoted_prefix}fm2_scheduler_slots`;")
        assert after[0] == before[0] and int(after[1]) >= int(before[1]) and int(after[2]) >= int(before[2]) and int(after[3]) >= int(before[4]), f"restart must preserve workforce and Jobs facts: before={before} after={after}"
        assert after[4] == "0", f"restart must not duplicate any scheduler slot: {after}"
        output = compose(base, ["logs", "--no-color", "workforce-sync", "workforce-scheduler"], env, 30).stdout
        assert TOKEN not in output, "private Bitrix token must not enter service output"
        print("PASS: PILOT-JOBS-STARTUP-001 isolated root Compose delivery and restart")
        return 0
    finally:
        run(base + ["down", "--volumes", "--remove-orphans"], env=env, timeout=90, check=False)
        run(["docker", "image", "rm", image], env=env, timeout=90, check=False)
        if server is not None and server.poll() is None:
            server.send_signal(signal.SIGTERM)
            try:
                server.wait(timeout=5)
            except subprocess.TimeoutExpired:
                server.kill()
                server.wait(timeout=5)
        shutil.rmtree(fixture_root, ignore_errors=True)


if __name__ == "__main__":
    raise SystemExit(main())
