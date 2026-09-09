#!/usr/bin/env python3
"""Executable acceptance for YII2-RUNTIME-001 at the real web/console seams."""

from __future__ import annotations

import http.client
import json
import os
from pathlib import Path
import socket
import subprocess
import tempfile
import time


ROOT = Path(__file__).resolve().parents[2]
SECRET = "YII2_TEST_SECRET_76"


def require(condition: bool, message: str) -> None:
    if not condition:
        raise AssertionError(message)


def clean_environment() -> dict[str, str]:
    return {key: value for key, value in os.environ.items() if not key.startswith("FMONITOR_")}


def invalid_environment(control: Path) -> dict[str, str]:
    environment = clean_environment()
    environment.update(
        {
            "FMONITOR_DB_HOST": "127.0.0.1",
            "FMONITOR_DB_PORT": "0",
            "FMONITOR_DB_NAME": "yii2_runtime_test",
            "FMONITOR_DB_USER": "yii2_runtime_test",
            "FMONITOR_DB_PASSWORD": SECRET,
            "FMONITOR_PROCESS_TABLE_PREFIX": "yii2_",
            "FMONITOR_LEGACY_TABLE_PREFIX": "yii2_",
            "FMONITOR_SESSION_STATE_ROOT": str(control / "sessions"),
            "FMONITOR_SESSION_INSTANCE": "yii2-test",
            "FMONITOR_ARTIFACT_STORAGE_ROOT": str(control / "artifacts"),
            "FMONITOR_ORIGINAL_DB_PASSWORD_FILE": str(control / "private" / SECRET),
            "FMONITOR_ORIGINAL_SAFE_LOG_FILE": str(control / "log" / f"{SECRET}.jsonl"),
            "FMONITOR_TRUSTED_REQUEST_HOST": "127.0.0.1",
            "FMONITOR_TRUSTED_REQUEST_SCHEME": "http",
        }
    )
    return environment


def free_port() -> int:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as listener:
        listener.bind(("127.0.0.1", 0))
        return int(listener.getsockname()[1])


class PhpServer:
    def __init__(self, router: Path, environment: dict[str, str]) -> None:
        self.port = free_port()
        self.process = subprocess.Popen(
            ["php", "-d", "display_errors=0", "-S", f"127.0.0.1:{self.port}", str(router)],
            cwd=ROOT,
            env=environment,
            stdin=subprocess.DEVNULL,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.PIPE,
            text=True,
        )
        deadline = time.monotonic() + 5
        while time.monotonic() < deadline:
            if self.process.poll() is not None:
                diagnostic = self.process.stderr.read() if self.process.stderr else ""
                raise AssertionError(f"SETUP_FAILURE: PHP server exited: {diagnostic[:500]}")
            try:
                with socket.create_connection(("127.0.0.1", self.port), timeout=0.1):
                    return
            except OSError:
                time.sleep(0.02)
        raise AssertionError("SETUP_FAILURE: PHP server did not listen")

    def close(self) -> None:
        self.process.terminate()
        try:
            self.process.wait(timeout=3)
        except subprocess.TimeoutExpired:
            self.process.kill()
            self.process.wait(timeout=3)
        if self.process.stderr:
            self.process.stderr.close()

    def request(self, method: str, path: str) -> tuple[int, dict[str, str], bytes]:
        connection = http.client.HTTPConnection("127.0.0.1", self.port, timeout=3)
        connection.request(method, path, headers={"Host": "127.0.0.1"})
        response = connection.getresponse()
        body = response.read()
        headers = {key.lower(): value for key, value in response.getheaders()}
        status = response.status
        connection.close()
        return status, headers, body


def assert_json_response(
    actual: tuple[int, dict[str, str], bytes], status: int, expected: dict[str, object]
) -> None:
    actual_status, headers, body = actual
    require(actual_status == status, f"expected HTTP {status}, got {actual_status}: {body!r}")
    require(headers.get("cache-control") == "no-store", "response must be non-cacheable")
    require(headers.get("content-type", "").lower().startswith("application/json"), "response must be JSON")
    require("set-cookie" not in headers, "health/error response must not create a session cookie")
    require(json.loads(body) == expected, f"unexpected JSON response: {body!r}")


def run_cli(arguments: list[str], environment: dict[str, str]) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        ["php", str(ROOT / "bin" / "yii"), *arguments],
        cwd=ROOT,
        env=environment,
        stdin=subprocess.DEVNULL,
        capture_output=True,
        text=True,
        timeout=5,
        check=False,
    )


def main() -> None:
    require(subprocess.run(["php", "-v"], capture_output=True).returncode == 0, "SETUP_FAILURE: PHP CLI unavailable")

    # Prove the stdlib/PHP HTTP harness before the intended missing Yii seam fails.
    baseline = PhpServer(ROOT / "public" / "runtime.php", clean_environment())
    try:
        assert_json_response(baseline.request("GET", "/health/live"), 200, {"ok": True})
    finally:
        baseline.close()

    web_entry = ROOT / "public" / "yii.php"
    console_entry = ROOT / "bin" / "yii"
    require(web_entry.is_file(), "INTENTIONAL_RED: YII2-RUNTIME-001 public/yii.php is absent")
    require(console_entry.is_file(), "INTENTIONAL_RED: YII2-RUNTIME-001 bin/yii is absent")

    unconfigured_server = PhpServer(web_entry, clean_environment())
    try:
        assert_json_response(unconfigured_server.request("GET", "/health/live"), 200, {"ok": True})
    finally:
        unconfigured_server.close()

    with tempfile.TemporaryDirectory(prefix="fmonitor-yii2-runtime-") as directory:
        control = Path(directory) / "state-that-must-remain-absent"
        environment = invalid_environment(control)
        server = PhpServer(web_entry, environment)
        try:
            assert_json_response(server.request("GET", "/health/live"), 200, {"ok": True})
            status, headers, body = server.request("HEAD", "/health/live")
            require(status == 200 and body == b"", "HEAD liveness must be 200 with empty body")
            require(headers.get("cache-control") == "no-store", "HEAD liveness must be non-cacheable")
            require(headers.get("content-type", "").lower().startswith("application/json"), "HEAD liveness must be JSON")
            require("set-cookie" not in headers, "HEAD liveness must not create a cookie")

            unavailable = server.request("GET", "/health/ready")
            assert_json_response(unavailable, 503, {"ok": False, "reason": "SERVICE_UNAVAILABLE"})
            require(SECRET not in repr(unavailable), "readiness response leaked secret/path marker")
            status, headers, body = server.request("HEAD", "/health/ready")
            require(status == 503 and body == b"", "HEAD unavailable readiness must be 503 with empty body")
            require(headers.get("cache-control") == "no-store", "HEAD readiness must be non-cacheable")
            require(headers.get("content-type", "").lower().startswith("application/json"), "HEAD readiness must be JSON")
            require("set-cookie" not in headers and SECRET not in repr(headers), "HEAD readiness leaked state/config")

            for route in ("/health/live", "/health/ready"):
                for method in ("POST", "PUT", "DELETE"):
                    status, headers, body = server.request(method, route)
                    require(status == 405, f"{method} {route} must return 405, got {status}: {body!r}")
                    allow = {allowed.strip() for allowed in headers.get("allow", "").split(",")}
                    require(allow == {"GET", "HEAD"}, f"{method} {route} must advertise only GET and HEAD")
                    require(headers.get("cache-control") == "no-store", f"{method} {route} must be non-cacheable")
                    require("set-cookie" not in headers and SECRET not in repr((headers, body)), f"{method} {route} leaked state/config")

            status, headers, body = server.request("GET", "/not-a-yii2-route")
            require(status == 404, f"unknown route must return 404, got {status}: {body!r}")
            require(headers.get("cache-control") == "no-store", "404 must be non-cacheable")
            require("set-cookie" not in headers and SECRET not in repr((headers, body)), "404 leaked state/config")
        finally:
            server.close()

        live = run_cli(["health/live"], clean_environment())
        require((live.returncode, live.stdout, live.stderr) == (0, '{"ok":true}\n', ""), f"console live contract failed: {live!r}")
        ready = run_cli(["health/ready"], environment)
        require(ready.returncode != 0, "unavailable console readiness must exit nonzero")
        require(ready.stdout == '{"ok":false,"reason":"SERVICE_UNAVAILABLE"}\n', f"unsafe readiness stdout: {ready.stdout!r}")
        require(ready.stderr == "" and SECRET not in ready.stdout + ready.stderr, "console readiness leaked diagnostic")
        require(not control.exists(), "health created private state despite invalid configuration")

    lock_path = ROOT / "composer.lock"
    require(lock_path.is_file(), "YII2-RUNTIME-001 requires one repository Composer lock")
    lock = json.loads(lock_path.read_text(encoding="utf-8"))
    packages = [package for package in lock.get("packages", []) if package.get("name") == "yiisoft/yii2"]
    require(len(packages) == 1, "Composer lock must contain exactly one yiisoft/yii2 package")
    version = str(packages[0].get("version", ""))
    require(version.startswith("2.0."), f"Yii lock must pin a 2.0 release, got {version!r}")

    common = ROOT / "config" / "yii" / "common.php"
    web_config = ROOT / "config" / "yii" / "web.php"
    console_config = ROOT / "config" / "yii" / "console.php"
    for path in (common, web_config, console_config):
        require(path.is_file(), f"shared Yii configuration file is absent: {path.relative_to(ROOT)}")
    require("common.php" in web_config.read_text(encoding="utf-8"), "web config must compose common config")
    require("common.php" in console_config.read_text(encoding="utf-8"), "console config must compose common config")

    framework_source = "\n".join(
        path.read_text(encoding="utf-8") for path in (web_entry, console_entry, common, web_config, console_config)
    )
    require("yii\\web\\Application" in framework_source, "web lifecycle must be owned by Yii Application")
    require("yii\\console\\Application" in framework_source, "console lifecycle must be owned by Yii Application")
    require("CREATE TABLE" not in framework_source.upper() and "ALTER TABLE" not in framework_source.upper(), "health wiring must contain no DDL")

    print("PASS: YII2-RUNTIME-001 isolated Yii2 web/console foundation")


if __name__ == "__main__":
    main()
