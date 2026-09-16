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
BASE = subprocess.check_output(["git", "-C", str(ROOT), "rev-parse", "HEAD"], text=True).strip()
RESULT_RE = re.compile(r"RUN_IN_PROFILE_RESULT (\{[^\n]+\})")
DEPENDENCY_DIRS = ("vendor", "node_modules", ".venv")


def run(
    argv: list[str], cwd: Path, timeout: int = 1200, env: dict[str, str] | None = None
) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        argv,
        cwd=cwd,
        env=os.environ | (env or {}),
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        timeout=timeout,
        check=False,
    )


def source_details(root: Path) -> dict[str, object]:
    code = (
        "import importlib.util,json,pathlib;"
        "p=pathlib.Path('tools/delivery/harness.py').resolve();"
        "s=importlib.util.spec_from_file_location('candidate_harness',p);"
        "m=importlib.util.module_from_spec(s);s.loader.exec_module(m);"
        "print(json.dumps(m.source_details(),sort_keys=True))"
    )
    result = run(["python3", "-c", code], root, 60)
    if result.returncode != 0:
        raise AssertionError("SETUP_FAILURE: candidate identity: " + result.stderr)
    return json.loads(result.stdout)


def tracked_digest(root: Path) -> str:
    names = subprocess.check_output(["git", "-C", str(root), "ls-files", "-z"]).split(b"\0")
    digest = hashlib.sha256()
    for raw_name in filter(None, names):
        path = root / raw_name.decode()
        digest.update(raw_name + b"\0")
        if not os.path.lexists(path):
            digest.update(b"missing")
        elif path.is_symlink():
            digest.update(b"L" + os.readlink(path).encode())
        else:
            digest.update(b"X" if os.access(path, os.X_OK) else b"F")
            digest.update(path.read_bytes())
    return digest.hexdigest()


def dependency_inventory(root: Path) -> dict[str, str]:
    inventory: dict[str, str] = {}
    for name in DEPENDENCY_DIRS:
        path = root / name
        if not os.path.lexists(path):
            inventory[name] = "ABSENT"
            continue
        digest = hashlib.sha256()
        for entry in sorted(path.rglob("*")) if path.is_dir() else [path]:
            relative = str(entry.relative_to(root)).encode()
            digest.update(relative + b"\0")
            if entry.is_symlink():
                digest.update(b"L" + os.readlink(entry).encode())
            elif entry.is_file():
                digest.update(b"F" + entry.read_bytes())
            else:
                digest.update(b"D")
        inventory[name] = digest.hexdigest()
    return inventory


class Worktree:
    def __init__(self, label: str) -> None:
        self.parent = Path(tempfile.mkdtemp(prefix=f"fm123-{label}-"))
        self.root = self.parent / "checkout"

    def __enter__(self) -> Path:
        result = run(["git", "-C", str(ROOT), "worktree", "add", "--detach", str(self.root), BASE], ROOT, 60)
        if result.returncode != 0:
            raise AssertionError(f"SETUP_FAILURE: worktree add: {result.stderr}")
        return self.root

    def __exit__(self, *_: object) -> None:
        run(["git", "-C", str(ROOT), "worktree", "remove", "--force", str(self.root)], ROOT, 60)
        shutil.rmtree(self.parent, ignore_errors=True)


class FrozenSnapshot:
    def __init__(self, root: Path, label: str) -> None:
        self.root = root
        self.parent = Path(tempfile.mkdtemp(prefix=f"fm123-snapshot-{label}-"))
        self.path = self.parent / "snapshot"
        self.executable_digest = str(source_details(root)["executable_digest"])

    def __enter__(self) -> "FrozenSnapshot":
        result = run(
            ["python3", "tools/delivery/review-source.py", "capture", "--repo", str(self.root), "--output", str(self.path)],
            self.root,
            60,
        )
        if result.returncode != 0:
            raise AssertionError("SETUP_FAILURE: freeze candidate: " + result.stderr)
        return self

    def __exit__(self, *_: object) -> None:
        shutil.rmtree(self.parent, ignore_errors=True)


def bootstrap(
    root: Path, profile: str = "governance", snapshot: Path | None = None
) -> subprocess.CompletedProcess[str]:
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
  "source_digest"=>getenv("FMONITOR_EXECUTED_SOURCE")?:"",
], JSON_THROW_ON_ERROR),"\nYII_BOOTSTRAP_OK\n";'
'''
    env = {"FMONITOR_EXECUTION_SNAPSHOT": str(snapshot)} if snapshot else None
    return run([str(root / "tools/delivery/run-in-profile"), profile, "sh", "-c", script], root, env=env)


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

    def assert_bootstrap(
        self, root: Path, expected_marker: str, profile: str = "governance", snapshot: FrozenSnapshot | None = None
    ) -> dict[str, object]:
        expected_source = snapshot.executable_digest if snapshot else str(source_details(root)["executable_digest"])
        result = bootstrap(root, profile, snapshot.path if snapshot else None)
        self.assertEqual(
            0,
            result.returncode,
            "INTENDED_RED CCV123A-01 exact candidate/dependency composition is unavailable\n"
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
        self.assertRegex(
            str(payload["source_digest"]),
            r"^[0-9a-f]{64}$",
            "INTENDED_RED CCV123A-01 frozen executable source identity is unavailable",
        )
        self.assertEqual(expected_source, payload["source_digest"])
        self.assertEqual(expected_source, record.get("source_digest"), "executed source evidence")
        label = run(
            ["docker", "image", "inspect", str(record["image_digest"]), "--format", '{{index .Config.Labels "org.fmonitor.executable-source"}}'],
            root,
            30,
        )
        self.assertEqual([0, expected_source], [label.returncode, label.stdout.strip()], "image/source identity")
        return record

    def test_a_b_c_i_o_clean_worktrees_origins_warm_and_isolation(self) -> None:
        measurements: list[dict[str, object]] = []
        for index in range(2):
            with Worktree(f"clean-{index}") as root:
                before_dependencies = dependency_inventory(root)
                before_tracked = tracked_digest(root)
                marker = f"candidate-{index}"
                write_marker(root, marker)
                measurements.append(self.assert_bootstrap(root, marker))
                measurements.append(self.assert_bootstrap(root, marker))
                self.assertEqual(before_dependencies, dependency_inventory(root), "host dependency inventory changed")
                self.assertEqual(before_tracked, tracked_digest(root), "tracked candidate changed during execution")
        print("CCV123A_MEASUREMENT " + json.dumps({"setup_failures_before_behavior": 0, "runs": measurements}, sort_keys=True))

    def test_d_frozen_candidate_ignores_later_host_mutation(self) -> None:
        with Worktree("frozen") as root:
            write_marker(root, "frozen-value")
            with FrozenSnapshot(root, "frozen") as snapshot:
                write_marker(root, "later-host-value")
                self.assert_bootstrap(root, "frozen-value", snapshot=snapshot)

    def test_e_candidate_deletion_and_mode_are_materialized(self) -> None:
        with Worktree("deletion-mode") as root:
            deleted = "app/YiiRuntime/Assets/pilot.css"
            self.assertTrue((root / deleted).is_file(), "SETUP_FAILURE: tracked deletion witness")
            (root / deleted).unlink()
            executable = root / "app/candidate-mode-probe"
            executable.write_text("#!/bin/sh\nexit 0\n")
            executable.chmod(0o755)
            with FrozenSnapshot(root, "deletion-mode") as snapshot:
                result = run(
                    [str(root / "tools/delivery/run-in-profile"), "governance", "sh", "-c", 'test ! -e "$1" && test -x "$2"', "probe", deleted, "app/candidate-mode-probe"],
                    root,
                    env={"FMONITOR_EXECUTION_SNAPSHOT": str(snapshot.path)},
                )
            self.assertEqual(0, result.returncode, "candidate deletion/mode not materialized: " + result.stderr)

    def test_f_stale_host_vendor_is_ignored_and_preserved(self) -> None:
        with Worktree("stale") as root:
            write_marker(root, "stale-host-case")
            vendor = root / "vendor/yiisoft/yii2"
            vendor.mkdir(parents=True)
            (root / "vendor/autoload.php").write_text("<?php putenv('FMONITOR_STALE_VENDOR_USED=1');\n")
            (vendor / "Yii.php").write_text("<?php throw new RuntimeException('stale host vendor used');\n")
            before = dependency_inventory(root)
            self.assert_bootstrap(root, "stale-host-case")
            self.assertEqual(before, dependency_inventory(root), "foreign host vendor changed")

    def test_g_corrupt_container_dependency_fails_without_fallback(self) -> None:
        with Worktree("corrupt-layer") as root:
            write_marker(root, "must-not-run")
            host_vendor = root / "vendor/yiisoft/yii2"
            host_vendor.mkdir(parents=True)
            (root / "vendor/autoload.php").write_text("<?php putenv('FMONITOR_STALE_VENDOR_USED=1');\n")
            (host_vendor / "Yii.php").write_text("<?php class Yii {}\n")
            recipe = root / "tools/delivery/Dockerfile.focused-checks"
            text = recipe.read_text()
            changed = re.sub(
                r"RUN composer install --working-dir=(/opt/fmonitor|/workspace) --no-interaction --no-scripts",
                lambda match: f"RUN mkdir -p {match.group(1)}/vendor",
                text,
                count=1,
            )
            self.assertNotEqual(text, changed, "SETUP_FAILURE: dependency install seam not found")
            recipe.write_text(changed)
            result = bootstrap(root)
            self.assertNotEqual(0, result.returncode, "corrupt container dependency fell back")
            self.assertNotIn("YII_BOOTSTRAP_OK", result.stdout)

    def test_h_changed_lock_rejects_stale_dependency_identity(self) -> None:
        with Worktree("changed-lock") as root:
            lock = root / "composer.lock"
            data = json.loads(lock.read_text())
            data["content-hash"] = "0" * 32
            lock.write_text(json.dumps(data, separators=(",", ":")) + "\n")
            write_marker(root, "changed-lock")
            result = bootstrap(root)
            self.assertNotEqual(0, result.returncode, "changed lock silently reused dependencies")
            self.assertNotIn("YII_BOOTSTRAP_OK", result.stdout)

    def test_j_source_read_only_and_allowed_artifacts_writable(self) -> None:
        with Worktree("readonly") as root:
            before = tracked_digest(root)
            result = run(
                [str(root / "tools/delivery/run-in-profile"), "governance", "sh", "-c", "set -eu; ! printf x >> app/autoload.php; touch /tmp/fm123-artifact; mkdir -p .local; touch .local/fm123-artifact"],
                root,
            )
            self.assertEqual(0, result.returncode, "read-only source/writable artifacts contract: " + result.stderr)
            self.assertEqual(before, tracked_digest(root), "host candidate changed")
            self.assertEqual("ABSENT", dependency_inventory(root)["vendor"])

    def test_k_l_m_profiles_share_yii_dependency_seam(self) -> None:
        with Worktree("profiles") as root:
            write_marker(root, "profiles")
            before = dependency_inventory(root)
            for profile in ("governance", "integration", "browser"):
                self.assert_bootstrap(root, "profiles", profile)
            self.assertEqual(before, dependency_inventory(root))


if __name__ == "__main__":
    unittest.main(verbosity=2)
