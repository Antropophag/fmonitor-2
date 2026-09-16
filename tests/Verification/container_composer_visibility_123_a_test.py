#!/usr/bin/env python3
"""Executable contract for CONTAINER-COMPOSER-VISIBILITY-123-A."""

from __future__ import annotations

import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]
BASE = subprocess.check_output(
    ["git", "-C", str(ROOT), "rev-parse", "HEAD"], text=True
).strip()
RESULT_RE = re.compile(r"RUN_IN_PROFILE_RESULT (\{[^\n]+\})")


def run(argv: list[str], cwd: Path, timeout: int = 1200) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        argv,
        cwd=cwd,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        timeout=timeout,
        check=False,
    )


def tracked_digest(root: Path) -> str:
    names = subprocess.check_output(
        ["git", "-C", str(root), "ls-files", "-z"]
    ).split(b"\0")
    digest = hashlib.sha256()
    for raw_name in filter(None, names):
        name = raw_name.decode()
        path = root / name
        digest.update(raw_name + b"\0")
        if path.is_symlink():
            digest.update(b"L" + os.readlink(path).encode())
        else:
            digest.update(b"F" + path.read_bytes())
    return digest.hexdigest()


class Worktree:
    def __init__(self, label: str) -> None:
        self.parent = Path(tempfile.mkdtemp(prefix=f"fm123-{label}-"))
        self.root = self.parent / "checkout"

    def __enter__(self) -> Path:
        result = run(
            ["git", "-C", str(ROOT), "worktree", "add", "--detach", str(self.root), BASE],
            ROOT,
            timeout=60,
        )
        if result.returncode != 0:
            raise AssertionError(f"SETUP_FAILURE: worktree add: {result.stderr}")
        return self.root

    def __exit__(self, *_: object) -> None:
        run(["git", "-C", str(ROOT), "worktree", "remove", "--force", str(self.root)], ROOT, 60)
        shutil.rmtree(self.parent, ignore_errors=True)


def bootstrap(root: Path, profile: str = "governance") -> subprocess.CompletedProcess[str]:
    script = r'''
set -eu
php -r '
require "app/autoload.php";
require "vendor/autoload.php";
require "vendor/yiisoft/yii2/Yii.php";
$r=new ReflectionClass(FMonitor2\CandidateDependencyProbe::class);
$autoloaders=array_values(array_filter(get_included_files(),static fn($p)=>str_ends_with($p,"/autoload.php")));
echo json_encode([
  "marker"=>FMonitor2\CandidateDependencyProbe::VALUE,
  "project_origin"=>$r->getFileName(),
  "dependency_origin"=>(new ReflectionClass(Yii::class))->getFileName(),
  "autoloaders"=>$autoloaders,
  "host_stale_used"=>getenv("FMONITOR_STALE_VENDOR_USED")?:"0",
  "vendor_writable"=>is_writable("vendor"),
], JSON_THROW_ON_ERROR),"\\nYII_BOOTSTRAP_OK\\n";'
'''
    return run([str(root / "tools/delivery/run-in-profile"), profile, "sh", "-c", script], root)


def evidence(result: subprocess.CompletedProcess[str]) -> dict[str, object]:
    match = RESULT_RE.search(result.stderr)
    if not match:
        raise AssertionError(f"missing compact evidence\nstdout={result.stdout}\nstderr={result.stderr}")
    return json.loads(match.group(1))


def write_marker(root: Path, value: str) -> None:
    (root / "app/CandidateDependencyProbe.php").write_text(
        "<?php\ndeclare(strict_types=1);\nnamespace FMonitor2;\n"
        f"final class CandidateDependencyProbe {{ public const VALUE = {value!r}; }}\n"
    )


class ContainerComposerVisibilityTest(unittest.TestCase):
    maxDiff = None

    @classmethod
    def setUpClass(cls) -> None:
        docker = run(["docker", "version", "--format", "{{.Server.Version}}"], ROOT, 30)
        if docker.returncode != 0:
            raise AssertionError("SETUP_FAILURE: Docker daemon unavailable: " + docker.stderr)

    def assert_bootstrap(self, root: Path, expected_marker: str, profile: str = "governance") -> dict[str, object]:
        result = bootstrap(root, profile)
        self.assertEqual(
            0,
            result.returncode,
            "INTENDED_RED CCV123A-01 dependency layer is invisible before Yii behavior\n"
            f"stdout={result.stdout}\nstderr={result.stderr}",
        )
        self.assertIn("YII_BOOTSTRAP_OK", result.stdout)
        payload = json.loads(result.stdout.splitlines()[0])
        self.assertEqual(expected_marker, payload["marker"])
        self.assertEqual("/workspace/app/CandidateDependencyProbe.php", payload["project_origin"])
        self.assertRegex(payload["dependency_origin"], r"^/workspace/vendor/yiisoft/yii2/Yii\.php$")
        self.assertIn("/workspace/vendor/autoload.php", payload["autoloaders"])
        self.assertEqual("0", payload["host_stale_used"])
        self.assertFalse(payload["vendor_writable"], "container dependency view must be read-only")
        record = evidence(result)
        self.assertEqual(profile, record["profile"])
        self.assertEqual(0, record["exit_code"])
        self.assertGreaterEqual(record["duration_seconds"], 0)
        return record

    def test_a_b_d_i_j_two_clean_worktrees_candidate_origin_and_warm_repeat(self) -> None:
        measurements: list[dict[str, object]] = []
        for index in range(2):
            with Worktree(f"clean-{index}") as root:
                self.assertFalse((root / "vendor").exists(), "fresh host vendor must be absent")
                before = tracked_digest(root)
                marker = f"candidate-{index}"
                write_marker(root, marker)
                measurements.append(self.assert_bootstrap(root, marker))
                measurements.append(self.assert_bootstrap(root, marker))
                self.assertFalse((root / "vendor").exists(), "warm run created host vendor")
                self.assertEqual(before, tracked_digest(root), "tracked candidate changed during execution")
        print("CCV123A_MEASUREMENT " + json.dumps({"setup_failures_before_behavior": 0, "runs": measurements}, sort_keys=True))

    def test_c_stale_host_vendor_is_masked(self) -> None:
        with Worktree("stale") as root:
            write_marker(root, "stale-host-case")
            vendor = root / "vendor/yiisoft/yii2"
            vendor.mkdir(parents=True)
            (root / "vendor/autoload.php").write_text(
                "<?php putenv('FMONITOR_STALE_VENDOR_USED=1');\n"
            )
            (vendor / "Yii.php").write_text("<?php throw new RuntimeException('stale host vendor used');\n")
            self.assert_bootstrap(root, "stale-host-case")

    def test_e_changed_lock_never_silently_reuses_old_dependency_identity(self) -> None:
        with Worktree("changed-lock") as root:
            lock = root / "composer.lock"
            data = json.loads(lock.read_text())
            data["content-hash"] = "0" * 32
            lock.write_text(json.dumps(data, separators=(",", ":")) + "\n")
            write_marker(root, "changed-lock")
            result = bootstrap(root)
            self.assertNotEqual(0, result.returncode, "changed lock silently used previous dependency identity")
            self.assertNotIn("YII_BOOTSTRAP_OK", result.stdout)

    def test_f_corrupt_container_dependency_fails_without_host_fallback(self) -> None:
        with Worktree("corrupt-layer") as root:
            write_marker(root, "must-not-run")
            host_vendor = root / "vendor/yiisoft/yii2"
            host_vendor.mkdir(parents=True)
            (root / "vendor/autoload.php").write_text("<?php putenv('FMONITOR_STALE_VENDOR_USED=1');\n")
            (host_vendor / "Yii.php").write_text("<?php class Yii {}\n")
            recipe = root / "tools/delivery/Dockerfile.focused-checks"
            text = recipe.read_text()
            changed = re.sub(
                r"RUN composer install --working-dir=/workspace --no-interaction --no-scripts",
                "RUN mkdir -p /workspace/vendor",
                text,
                count=1,
            )
            self.assertNotEqual(text, changed, "SETUP_FAILURE: dependency install seam not found")
            recipe.write_text(changed)
            result = bootstrap(root)
            self.assertNotEqual(0, result.returncode, "corrupt container dependency fell back to host vendor")
            self.assertNotIn("YII_BOOTSTRAP_OK", result.stdout)

    def test_g_h_profiles_share_the_same_yii_dependency_seam(self) -> None:
        with Worktree("profiles") as root:
            write_marker(root, "profiles")
            for profile in ("governance", "integration", "browser"):
                self.assert_bootstrap(root, "profiles", profile)


if __name__ == "__main__":
    unittest.main(verbosity=2)
