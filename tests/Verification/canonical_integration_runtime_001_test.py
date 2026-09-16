#!/usr/bin/env python3
"""Executable contract for CANONICAL-INTEGRATION-RUNTIME-001."""

from __future__ import annotations

import json
import os
from pathlib import Path
import re
import socket
import subprocess
import unittest
import uuid


ROOT = Path(__file__).resolve().parents[2]
RESULT_RE = re.compile(r"RUN_IN_PROFILE_RESULT (\{[^\n]+\})")
DEPENDENCY_PATHS = ("vendor", "node_modules", ".venv")


def run(argv: list[str], env: dict[str, str], timeout: int = 1200) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        argv,
        cwd=ROOT,
        env=os.environ | env,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        timeout=timeout,
        check=False,
    )


def free_host_port() -> int:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as listener:
        listener.bind(("127.0.0.1", 0))
        return int(listener.getsockname()[1])


def dependency_inventory() -> dict[str, str]:
    inventory: dict[str, str] = {}
    for name in DEPENDENCY_PATHS:
        path = ROOT / name
        inventory[name] = "ABSENT" if not os.path.lexists(path) else "PRESENT"
    return inventory


def evidence(result: subprocess.CompletedProcess[str]) -> dict[str, object]:
    match = RESULT_RE.search(result.stderr)
    if match is None:
        raise AssertionError(f"missing RUN_IN_PROFILE_RESULT\nstdout={result.stdout}\nstderr={result.stderr}")
    return json.loads(match.group(1))


class CanonicalIntegrationRuntimeTest(unittest.TestCase):
    maxDiff = None

    def test_pdo_mysql_and_db_backed_yii_execute_in_public_profile(self) -> None:
        before = dependency_inventory()
        project = "cir001-" + uuid.uuid4().hex[:12]
        host_port = free_host_port()
        self.assertNotEqual(23306, host_port, "fixture must not inherit the known conflicting host port")
        compose_env = {
            "COMPOSE_PROJECT_NAME": project,
            "FMONITOR_TEST_DB_PORT": str(host_port),
        }
        compose = ["docker", "compose", "-f", "compose.test.yaml"]
        try:
            started = run(compose + ["up", "-d", "--wait", "test-db"], compose_env, 180)
            self.assertEqual(0, started.returncode, "SETUP_FAILURE: disposable MariaDB: " + started.stderr)

            probe_code = r'''
require "vendor/autoload.php";
require "vendor/yiisoft/yii2/Yii.php";
$payload=[
  "extension"=>extension_loaded("pdo_mysql"),
  "drivers"=>PDO::getAvailableDrivers(),
  "php_binary"=>PHP_BINARY,
  "yii_origin"=>(new ReflectionClass(Yii::class))->getFileName(),
  "vendor_origin"=>realpath("vendor/autoload.php"),
  "source_digest"=>getenv("FMONITOR_EXECUTED_SOURCE")?:"",
];
echo json_encode($payload,JSON_THROW_ON_ERROR),"\n";
if(!$payload["extension"]||!in_array("mysql",$payload["drivers"],true)){
  fwrite(STDERR,"INTEGRATION_PDO_MYSQL_MISSING\n");
  exit(42);
}
echo "CIR001_DRIVER_OK\n";
'''
            probe = run(
                ["tools/delivery/run-in-profile", "integration", "php", "-r", probe_code],
                compose_env,
            )
            self.assertEqual(
                0,
                probe.returncode,
                "INTENDED_RED CIR001-01 INTEGRATION_PDO_MYSQL_MISSING in the canonical focused-check image\n"
                f"container_payload={probe.stdout}",
            )
            self.assertIn("CIR001_DRIVER_OK", probe.stdout)
            payload = json.loads(probe.stdout.splitlines()[0])
            self.assertTrue(payload["extension"])
            self.assertIn("mysql", payload["drivers"])
            self.assertRegex(payload["php_binary"], r"^/usr/local/bin/php$")
            self.assertEqual("/workspace/vendor/yiisoft/yii2/Yii.php", payload["yii_origin"])
            self.assertEqual("/workspace/vendor/autoload.php", payload["vendor_origin"])
            self.assertRegex(payload["source_digest"], r"^[0-9a-f]{64}$")
            probe_record = evidence(probe)
            self.assertEqual("integration", probe_record["profile"])
            self.assertEqual(0, probe_record["exit_code"])
            self.assertEqual(payload["source_digest"], probe_record["source_digest"])

            behavior = run(
                [
                    "tools/delivery/run-in-profile",
                    "integration",
                    "php",
                    "-d",
                    "display_errors=0",
                    "tests/Yii2/yii2_user_access_001_test.php",
                ],
                compose_env,
            )
            self.assertEqual(
                0,
                behavior.returncode,
                "CIR001-02 DB-backed Yii did not reach expected application behavior\n"
                f"stdout={behavior.stdout}\nstderr={behavior.stderr}",
            )
            self.assertIn("PASS: YII2-USER-ACCESS-001", behavior.stdout)
            self.assertNotIn("could not find driver", behavior.stdout + behavior.stderr)
            behavior_record = evidence(behavior)
            self.assertEqual("integration", behavior_record["profile"])
            self.assertEqual(0, behavior_record["exit_code"])
            self.assertEqual(payload["source_digest"], behavior_record["source_digest"])
        finally:
            stopped = run(compose + ["down", "--volumes", "--remove-orphans"], compose_env, 180)
            if stopped.returncode != 0:
                self.fail("SETUP_FAILURE: disposable MariaDB cleanup: " + stopped.stderr)

        self.assertEqual(before, dependency_inventory(), "host dependency inventory changed")


if __name__ == "__main__":
    unittest.main()
