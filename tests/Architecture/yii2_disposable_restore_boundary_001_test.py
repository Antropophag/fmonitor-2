#!/usr/bin/env python3
"""YII2-DISPOSABLE-RESTORE-REHEARSAL-001 ownership and boundary RED."""
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]

class Contract(unittest.TestCase):
    def test_single_restore_owner_and_driver_ports(self):
        app = (ROOT / "app/RuntimeRestore/StandRestoreApplication.php").read_text()
        controller = (ROOT / "app/YiiRuntime/Commands/StandRestoreController.php").read_text()
        expected = [
            ROOT / "app/RuntimeRestore/StandRestoreDriver.php",
            ROOT / "app/RuntimeRestore/ProductionStandRestoreDriver.php",
            ROOT / "app/RuntimeRestore/RecordingStandRestoreDriver.php",
            ROOT / "app/RuntimeRestore/StandRestoreAuthorization.php",
        ]
        missing = [str(path.relative_to(ROOT)) for path in expected if not path.is_file()]
        if missing:
            print("INTENDED_RED: missing production-shaped restore boundaries: " + ", ".join(missing))
        self.assertEqual([], missing)
        self.assertNotIn("RuntimeRecovery", app + controller)
        self.assertNotIn("FMONITOR_STAND_RESTORE_TEST_MODE", app)
        self.assertIn("StandRestoreDriver", app)
        self.assertIn("StandRestoreApplication", controller)

    def test_legacy_responsibilities_are_retained_and_named(self):
        inventory = (ROOT / "docs/operations/yii2-disposable-restore-seams-inventory-2026-09-13.md").read_text()
        for token in ("old-format production backup/restore", "v22/v23 forward update", "schema", "jobs recovery", "legacy recovery runbook"):
            self.assertIn(token, inventory)
        self.assertTrue((ROOT / "app/RuntimeRestore/RuntimeRecovery.php").is_file())
        self.assertTrue((ROOT / "bin/fmonitor2-runtime-recovery.php").is_file())

if __name__ == "__main__":
    unittest.main(verbosity=2)
