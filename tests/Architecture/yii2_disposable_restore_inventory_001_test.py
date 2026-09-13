#!/usr/bin/env python3
"""YII2-DISPOSABLE-RESTORE-REHEARSAL-001 legacy inventory contract."""
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]

class Inventory(unittest.TestCase):
    def test_runtime_recovery_is_retained_for_named_executable_contracts(self):
        document = (ROOT / "docs/operations/yii2-disposable-restore-seams-inventory-2026-09-13.md").read_text()
        required = {
            "tests/Runtime/runtime_recovery_001_test.php": "old-format production backup/restore",
            "tests/Runtime/runtime_recovery_forward_update_001_test.php": "v22/v23 forward update",
            "tests/Runtime/runtime_jobs_recovery_001_test.php": "jobs recovery",
            "specs/PRODUCTION-RUNTIME-RESTORE-001.md": "schema",
            "docs/operations/runtime-recovery-runbook.md": "legacy recovery runbook",
        }
        for path, responsibility in required.items():
            self.assertTrue((ROOT / path).is_file(), path)
            self.assertIn(responsibility, document)
        self.assertTrue((ROOT / "app/RuntimeRestore/RuntimeRecovery.php").is_file())
        self.assertTrue((ROOT / "bin/fmonitor2-runtime-recovery.php").is_file())

if __name__ == "__main__":
    unittest.main(verbosity=2)
