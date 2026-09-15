#!/usr/bin/env python3
"""Executable specification for ARCHITECTURE-FILE-SIZE-ADVISORY-001."""
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

REPO = Path(__file__).resolve().parents[2]
EMPTY = {"ddl_ownership": [], "sql_ownership": [], "dependency_direction": [],
         "rapid_pilot_boundary": [], "workforce_migration_ownership": [],
         "session_storage_ownership": [], "hotspots": {}, "public_seams": []}


class FileSizeAdvisoryTest(unittest.TestCase):
    def fixture(self, files, baseline=None, *arguments):
        temporary = tempfile.TemporaryDirectory(prefix="fm2-size-advisory-")
        root = Path(temporary.name)
        tool = root / "tools/architecture"
        tool.mkdir(parents=True)
        shutil.copy2(REPO / "tools/architecture/check.py", tool / "check.py")
        expected = dict(EMPTY)
        expected.update(baseline or {})
        (tool / "baseline.json").write_text(json.dumps(expected), encoding="utf-8")
        for relative, content in files.items():
            target = root / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_text(content, encoding="utf-8")
        run = subprocess.run([sys.executable, str(tool / "check.py"), "--json", *arguments],
                             cwd=root, text=True, capture_output=True, timeout=30)
        return temporary, root, run

    @staticmethod
    def large(extra=""):
        return "<?php\n" + "\n".join("// review" for _ in range(149)) + ("\n" + extra if extra else "")

    def assert_advisory_pass(self, files, baseline=None):
        temporary, _, run = self.fixture(files, baseline)
        with temporary:
            result = json.loads(run.stdout)
            self.assertEqual((0, True, []), (run.returncode, result["ok"], result["errors"]))
            self.assertTrue(result["advisories"])
            self.assertTrue(all(item.startswith("file_size:") for item in result["advisories"]))

    def test_01_149_to_150_comment_or_blank_is_advisory(self):
        path = "app/SizeCrossing.php"
        self.assert_advisory_pass({path: self.large()}, {"hotspots": {}})

    def test_02_existing_hotspot_growth_is_advisory(self):
        path = "app/GrowingHotspot.php"
        self.assert_advisory_pass({path: self.large("// growth")}, {"hotspots": {path: 150}})

    def test_03_new_large_file_is_advisory(self):
        self.assert_advisory_pass({"app/NewLargeFile.php": self.large()})

    def test_04_renamed_large_file_is_advisory_only(self):
        self.assert_advisory_pass({"app/MovedLargeFile.php": self.large()},
                                  {"hotspots": {"app/OldLargeFile.php": 150}})

    def test_05_mixed_sql_error_and_size_advisory(self):
        temporary, _, run = self.fixture({"app/LargeService.php": self.large('$db->query("SELECT id FROM secret");')})
        with temporary:
            result = json.loads(run.stdout)
            self.assertEqual((1, False), (run.returncode, result["ok"]))
            self.assertTrue(any(x.startswith("sql_ownership:") for x in result["errors"]))
            self.assertTrue(result["advisories"])

    def test_06_ddl_and_runtime_migration_remain_blocking(self):
        for source in ('$db->query("CREATE TABLE forbidden(id INT)");',
                       "ExampleSchemaMigration::apply($db, $prefix);"):
            with self.subTest(source=source):
                temporary, _, run = self.fixture({"app/Runtime/LargeRuntime.php": self.large(source)})
                with temporary:
                    result = json.loads(run.stdout)
                    self.assertEqual(1, run.returncode)
                    self.assertTrue(any(x.startswith("ddl_ownership:") for x in result["errors"]))
                    self.assertTrue(result["advisories"])

    def test_07_dependency_direction_remains_blocking(self):
        temporary, _, run = self.fixture({"app/Otiz/LargeDependency.php": self.large("use FMonitor2\\PilotHttp\\PilotHttp;")})
        with temporary:
            result = json.loads(run.stdout)
            self.assertEqual(1, run.returncode)
            self.assertTrue(any(x.startswith("dependency_direction:") for x in result["errors"]))
            self.assertTrue(result["advisories"])

    def test_08_human_output_shows_advisory_on_pass(self):
        temporary, root, _ = self.fixture({"app/LargeHuman.php": self.large()})
        with temporary:
            run = subprocess.run([sys.executable, str(root / "tools/architecture/check.py")], cwd=root,
                                 text=True, capture_output=True, timeout=30)
            self.assertEqual(0, run.returncode)
            self.assertIn("ADVIS", run.stdout.upper())

    def test_09_human_output_shows_advisory_on_failure(self):
        temporary, root, _ = self.fixture(
            {"app/LargeHumanFailure.php": self.large('$db->query("SELECT id FROM secret");')})
        with temporary:
            run = subprocess.run([sys.executable, str(root / "tools/architecture/check.py")], cwd=root,
                                 text=True, capture_output=True, timeout=30)
            self.assertEqual(1, run.returncode)
            self.assertIn("sql_ownership:", run.stdout)
            self.assertIn("ADVIS", run.stdout.upper())

    def test_10_size_baseline_update_preserves_meaningful_exceptions(self):
        meaningful = dict(EMPTY)
        meaningful.update({"sql_ownership": ["sql|app/Kept.php|deadbeef"],
                           "ddl_ownership": ["ddl|app/Kept.php|cafebabe"],
                           "public_seams": ["app/Kept.php::updateKept"]})
        temporary, root, run = self.fixture(
            {"app/Large.php": self.large('$db->query("SELECT id FROM newly_forbidden");')}, meaningful,
                                            "--write-size-baseline")
        with temporary:
            self.assertEqual(0, run.returncode, run.stderr)
            updated = json.loads((root / "tools/architecture/baseline.json").read_text())
            for key in meaningful:
                if key != "hotspots":
                    self.assertEqual(meaningful[key], updated[key], key)
            self.assertEqual({"app/Large.php": 151}, updated["hotspots"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
