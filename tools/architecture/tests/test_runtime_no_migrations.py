#!/usr/bin/env python3
"""PRODUCTION-RUNTIME-NO-MIGRATIONS-001: runtime composition cannot invoke DDL owners."""
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

REPO = Path(__file__).resolve().parents[3]


class RuntimeNoMigrationsTest(unittest.TestCase):
    def inspect(self, path: str, source: str):
        with tempfile.TemporaryDirectory(prefix="fm2-runtime-no-migrations-") as directory:
            root = Path(directory)
            tool = root / "tools/architecture"
            tool.mkdir(parents=True)
            shutil.copyfile(REPO / "tools/architecture/check.py", tool / "check.py")
            for relative in (
                "rapid-pilot/legacy-migration/MigratedEvidenceDecisionLedger.php",
                "rapid-pilot/legacy-migration/MigratedEvidenceProjectionStore.php",
                "rapid-pilot/legacy-migration/MigrationQuarantineDecisionLedger.php",
            ):
                fixture = root / relative
                fixture.parent.mkdir(parents=True, exist_ok=True)
                fixture.write_text("<?php\n", encoding="utf-8")
            baseline = {
                "ddl_ownership": [], "sql_ownership": [], "dependency_direction": [],
                "rapid_pilot_boundary": [], "workforce_migration_ownership": [],
                "session_storage_ownership": [], "hotspots": {}, "public_seams": [],
            }
            (tool / "baseline.json").write_text(json.dumps(baseline), encoding="utf-8")
            target = root / path
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_text("<?php\n" + source + "\n", encoding="utf-8")
            completed = subprocess.run(
                [sys.executable, str(tool / "check.py"), "--json"], cwd=root,
                capture_output=True, text=True, timeout=30,
            )
            self.assertEqual("", completed.stderr, "SETUP_FAILURE: checker diagnostics")
            self.assertIn(completed.returncode, (0, 1), "SETUP_FAILURE: checker status")
            return completed.returncode, json.loads(completed.stdout)

    def test_01_readonly_readiness_and_configuration_apply_are_allowed(self):
        source = """
ProductionProcessSchemaMigration::isReady($db, $prefix);
ProductionProcessSchemaMigration::isCompleteCompatible($db, $prefix);
$configuration->apply();
"""
        status, result = self.inspect("app/Runtime/AllowedReadiness.php", source)
        self.assertEqual((0, True, []), (status, result["ok"], result["errors"]))

    def test_02_runtime_migration_invocations_are_ddl_ownership_findings(self):
        fixtures = {
            "canonical runner": "CanonicalMigrationApplication::run($db, $prefix, $migrations);",
            "named schema migration": "ProductionProcessSchemaMigration::apply($db, $prefix);",
            "variable schema migration": "$schema::apply($db, $prefix);",
            "imported alias": "use FMonitor2\\InstallationProcess\\ProductionProcessSchemaMigration as Upgrade;\nUpgrade::apply($db, $prefix);",
            "fully qualified multiline": "\\FMonitor2\\InstallationProcess\\ProductionProcessSchemaMigration\n    ::apply(\n        $db,\n        $prefix,\n    );",
            "variable canonical runner": "$runner = CanonicalMigrationApplication::class;\n$runner::run($db, $prefix, $migrations);",
        }
        for label, source in fixtures.items():
            with self.subTest(label=label):
                status, result = self.inspect("app/Runtime/ForbiddenMigration.php", source)
                self.assertEqual(1, status, "INTENTIONAL_RED: runtime migration invocation must fail")
                self.assertTrue(any(error.startswith("ddl_ownership: new violation") for error in result["errors"]))

    def test_03_public_front_controller_cannot_start_migration_or_demo_bootstrap(self):
        for source in (
            "require dirname(__DIR__) . '/bin/fmonitor2-migrate.php';",
            "require dirname(__DIR__) . '/rapid-pilot/docker-bootstrap.php';",
            "require dirname(__DIR__) . '/rapid-pilot/start.php';",
        ):
            with self.subTest(source=source):
                status, result = self.inspect("public/runtime.php", source)
                self.assertEqual(1, status, "INTENTIONAL_RED: production front controller startup hook must fail")
                self.assertTrue(any(error.startswith("ddl_ownership: new violation") for error in result["errors"]))


if __name__ == "__main__":
    unittest.main(verbosity=2)
