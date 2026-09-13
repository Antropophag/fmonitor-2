#!/usr/bin/env python3
"""Guarded real rollback is a distinct authorized operation."""
import json,os,subprocess,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];RUNNER=ROOT/"tools/delivery/yii2-disposable-restore-rehearsal.php"
class Rollback(unittest.TestCase):
 def test_without_rollback_authorization_stops_before_effect(self):
  p=subprocess.run(["php",str(RUNNER),"rollback","--interactive=0"],cwd=ROOT,env={"PATH":os.environ.get("PATH","/usr/bin:/bin")},capture_output=True,text=True)
  if p.returncode!=77:print("INTENDED_RED: separately authorized rollback entrypoint is missing")
  decoded=json.loads(p.stdout) if p.stdout else None
  self.assertEqual((77,{"ok":False,"reason":"ACTION_NOT_AUTHORIZED"},""),(p.returncode,decoded,p.stderr))
 @unittest.skipUnless(os.environ.get("FMONITOR_DISPOSABLE_ROLLBACK_AUTHORIZATION"),"real destructive rollback action is not authorized")
 def test_authorized_real_rollback(self):
  auth_path=os.environ["FMONITOR_DISPOSABLE_ROLLBACK_AUTHORIZATION"];outer=json.loads(Path(auth_path).read_text());auth=json.loads(Path(outer["rollback_authorization"]).read_text());p=subprocess.run(["php",str(RUNNER),"rollback","--authorization="+auth_path,"--interactive=0"],cwd=ROOT,capture_output=True,text=True,timeout=900);self.assertEqual(0,p.returncode,p.stderr);e=json.loads(p.stdout);self.assertEqual("ROLLBACK_VERIFIED",e["outcome"]);self.assertNotEqual(e["candidate_operation_id"],e["rollback_operation_id"]);self.assertEqual("FAILED_RETAINED",e["candidate_outcome"]);self.assertEqual((outer["rollback_operation_id"],outer["known_good_bundle_digest"]),(e["rollback_operation_id"],e["bundle_digest"]));self.assertNotEqual(json.loads(Path(outer["candidate_failure"]).read_text())["operation_id"],outer["rollback_operation_id"])
  password=Path(auth["credential_files"]["database"]).read_text().strip();compose=["docker","compose","--project-name",auth["project"],"--file",auth["compose_file"]];db=subprocess.run([*compose,"exec","-T","-e","MYSQL_PWD","db","mariadb","-N","-B","-u",auth["database_user"],auth["database"],"-e","SELECT id,fact FROM fm2_restore_rehearsal_probe ORDER BY id; SELECT state,deduplication_key FROM fm2_jobs WHERE deduplication_key='restore-rehearsal-job'; SELECT state FROM fm2_outbox WHERE idempotency_key='restore-rehearsal-outbox'"],env={**os.environ,"MYSQL_PWD":password},capture_output=True,text=True,timeout=60);self.assertEqual((0,"7\tknown-history\nqueued\trestore-rehearsal-job\npending"),(db.returncode,db.stdout.strip()));ready=subprocess.run(["curl","--fail","--silent","--show-error",auth["health"]["ready"]],capture_output=True,text=True,timeout=30);self.assertEqual(0,ready.returncode,ready.stderr)
  for volume,command in (("artifacts","test \"$(cat /state/fmonitor2/artifacts/rehearsal/original.pdf)\" = PDF-known && test \"$(stat -c %a /state/fmonitor2/artifacts/rehearsal/original.pdf)\" = 600"),("sessions","test -s /state/fmonitor2/sessions/rehearsal-session")):
   observed=subprocess.run(["docker","run","--rm","--network","none","--mount",f"type=volume,source={auth['volumes'][volume]['name']},target=/state,readonly",auth["image"],"sh","-c",command],capture_output=True,text=True,timeout=60);self.assertEqual(0,observed.returncode,observed.stderr)
  for smoke in auth["golden"]:
   observed=subprocess.run(["curl","--fail","--silent","--show-error",smoke["url"]],capture_output=True,timeout=30);self.assertEqual(0,observed.returncode,observed.stderr);self.assertEqual(smoke["sha256"],__import__("hashlib").sha256(observed.stdout).hexdigest())
  history=Path(auth["external_evidence_root"])/"candidate-failure.json";self.assertTrue(history.is_file());failed=json.loads(history.read_text());self.assertEqual("FAILED",failed["outcome"]);self.assertEqual(auth["candidate_operation_id"],failed["operation_id"])
if __name__=="__main__":unittest.main(verbosity=2)
