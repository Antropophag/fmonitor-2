#!/usr/bin/env python3
"""YII2-STAND-RESTORE-CONTROL-001 ownership RED."""
import unittest
from pathlib import Path
R=Path(__file__).resolve().parents[2]
class T(unittest.TestCase):
 def test_owner_and_legacy_inventory(self):
  a=R/"app/RuntimeRestore/StandRestoreApplication.php";c=R/"app/YiiRuntime/Commands/StandRestoreController.php"
  if not a.is_file():print("INTENDED_RED: restore application owner missing")
  self.assertTrue(a.is_file());self.assertTrue(c.is_file());self.assertNotIn("RuntimeRecovery",a.read_text()+c.read_text());self.assertIn("'stand-restore'",(R/"config/yii/console.php").read_text());self.assertTrue((R/"bin/fmonitor2-runtime-recovery.php").is_file())
  inventory=(R/"docs/operations/yii2-stand-restore-control-delivery-2026-09-13.md").read_text();required={"tests/Runtime/runtime_recovery_001_test.php":"old-format restore","tests/Runtime/runtime_recovery_forward_update_001_test.php":"historical v22/v23 forward migration","tests/Runtime/runtime_jobs_recovery_001_test.php":"jobs recovery","specs/PRODUCTION-RUNTIME-RESTORE-001.md":"schema v22-v24 compatibility","specs/PRODUCTION-JOBS-RECOVERY-001.md":"production jobs recovery","docs/operations/runtime-recovery-runbook.md":"legacy runbook command"}
  bindings={"tests/Runtime/runtime_recovery_001_test.php":"fmonitor2-runtime-recovery.php","tests/Runtime/runtime_recovery_forward_update_001_test.php":"fmonitor2-runtime-recovery.php","tests/Runtime/runtime_jobs_recovery_001_test.php":"fmonitor2-runtime-recovery.php","specs/PRODUCTION-RUNTIME-RESTORE-001.md":"fmonitor2-runtime-recovery.php","specs/PRODUCTION-JOBS-RECOVERY-001.md":"fmonitor2-runtime-recovery.php","docs/operations/runtime-recovery-runbook.md":"fmonitor2-runtime-recovery.php"}
  for path,responsibility in required.items():self.assertIn(path,inventory);self.assertIn(responsibility,inventory);self.assertTrue((R/path).is_file());self.assertIn(bindings[path],(R/path).read_text(),path+' retains legacy production binding')
if __name__=="__main__":unittest.main(verbosity=2)
