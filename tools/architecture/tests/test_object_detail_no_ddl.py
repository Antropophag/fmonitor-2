#!/usr/bin/env python3
"""OBJECT-DETAIL-NO-DDL-RATCHET-001: public architecture CLI regression."""
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

REPO = Path(__file__).resolve().parents[3]
# Literal historical adversarial inputs, fixed before removing importer DDL.
# The test never reads the runtime importer to derive its expectations.
HISTORICAL_DDL = ['$target->query("CREATE TABLE IF NOT EXISTS `{$p}fm2_pilot_object_details`(object_id BIGINT UNSIGNED PRIMARY KEY,schema_version VARCHAR(80) NOT NULL,content_sha256 CHAR(64) NOT NULL,payload_json LONGTEXT NOT NULL,captured_at VARCHAR(40) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");', '$target->query("CREATE TABLE IF NOT EXISTS `{$p}fm2_pilot_object_detail_quarantine`(object_id BIGINT UNSIGNED PRIMARY KEY,code VARCHAR(80) NOT NULL,schema_version VARCHAR(80) NOT NULL,content_sha256 CHAR(64) NOT NULL,captured_at VARCHAR(40) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");']
RUNTIME_PATH = "rapid-pilot/import-production-object-details.php"
FINGERPRINTS = ("0869fae855bd5c76", "5e45e35f56e1f931")


class ObjectDetailNoDdlRatchetTest(unittest.TestCase):
    def run_fixture(self, path, source):
        with tempfile.TemporaryDirectory(prefix="fm2-object-detail-architecture-") as directory:
            root = Path(directory)
            tool = root / "tools/architecture"
            tool.mkdir(parents=True)
            shutil.copyfile(REPO / "tools/architecture/check.py", tool / "check.py")
            shutil.copyfile(REPO / "tools/architecture/baseline.json", tool / "baseline.json")
            target = root / path
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_text(source, encoding="utf-8")
            result = subprocess.run(
                [sys.executable, str(tool / "check.py"), "--json"],
                cwd=root, capture_output=True, text=True, timeout=30,
            )
            self.assertEqual("", result.stderr, "SETUP_FAILURE: architecture CLI diagnostics")
            self.assertIn(result.returncode, (0, 1), "SETUP_FAILURE: architecture CLI status")
            try:
                envelope = json.loads(result.stdout)
            except json.JSONDecodeError as error:
                self.fail(f"SETUP_FAILURE: architecture CLI did not emit JSON: {error}")
            self.assertIsInstance(envelope.get("ok"), bool)
            self.assertIsInstance(envelope.get("errors"), list)
            return result.returncode, envelope

    def test_01_canonical_owner_is_accepted(self):
        status, result = self.run_fixture(
            "app/InstallationProcess/ObjectDetailSnapshotEngineSchemaMigration.php",
            "<?php\n" + "\n".join(HISTORICAL_DDL) + "\n",
        )
        self.assertEqual((0, True, []), (status, result["ok"], result["errors"]))

    def test_02_readonly_runtime_precondition_is_accepted(self):
        status, result = self.run_fixture(
            RUNTIME_PATH,
            "<?php\nFMonitor2\\InstallationProcess\\ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($db, $prefix);\n",
        )
        self.assertEqual((0, True, []), (status, result["ok"], result["errors"]))

    def test_03_old_runtime_ddl_cannot_return(self):
        status, result = self.run_fixture(
            RUNTIME_PATH, "<?php\n" + "\n".join(HISTORICAL_DDL) + "\n",
        )
        self.assertEqual(1, status, "RED: historical importer CREATE must no longer be grandfathered")
        self.assertIs(result["ok"], False)
        for fingerprint in FINGERPRINTS:
            self.assertIn(
                f"ddl_ownership: new violation (1x): ddl|{RUNTIME_PATH}|{fingerprint}",
                result["errors"],
                "Both exact removed DDL exceptions must be rejected at the public CLI",
            )


if __name__ == "__main__":
    unittest.main(verbosity=2)
