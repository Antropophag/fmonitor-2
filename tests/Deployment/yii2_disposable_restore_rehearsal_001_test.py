#!/usr/bin/env python3
"""Guarded real disposable rehearsal entrypoint; never synthesizes success."""
import json,os,subprocess,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];RUNNER=ROOT/"tools/delivery/yii2-disposable-restore-rehearsal.php"
class Rehearsal(unittest.TestCase):
 def test_without_action_authorization_stops_before_docker(self):
  marker=ROOT/".local/forbidden-disposable-effect";marker.unlink(missing_ok=True);env={"PATH":os.environ.get("PATH","/usr/bin:/bin"),"FMONITOR_FORBIDDEN_EFFECT_MARKER":str(marker)};p=subprocess.run(["php",str(RUNNER),"roundtrip","--interactive=0"],cwd=ROOT,env=env,capture_output=True,text=True)
  if p.returncode!=77:print("INTENDED_RED: guarded real rehearsal entrypoint is missing")
  self.assertEqual(77,p.returncode);self.assertEqual("",p.stderr);self.assertEqual({"ok":False,"reason":"ACTION_NOT_AUTHORIZED"},json.loads(p.stdout));self.assertFalse(marker.exists())
 @unittest.skipUnless(os.environ.get("FMONITOR_DISPOSABLE_REHEARSAL_AUTHORIZATION"),"real destructive disposable action is not authorized")
 def test_authorized_real_roundtrip(self):
  auth_path=os.environ["FMONITOR_DISPOSABLE_REHEARSAL_AUTHORIZATION"];outer=json.loads(Path(auth_path).read_text());auth=json.loads(Path(outer["restore_authorization"]).read_text());p=subprocess.run(["php",str(RUNNER),"roundtrip","--authorization="+auth_path,"--interactive=0"],cwd=ROOT,capture_output=True,text=True,timeout=900);self.assertEqual(0,p.returncode,p.stderr);e=json.loads(p.stdout);self.assertEqual("ROUNDTRIP_VERIFIED",e["outcome"]);self.assertEqual((outer["backup_operation_id"],outer["restore_operation_id"],outer["expected_bundle_digest"]),(e["backup_operation_id"],e["restore_operation_id"],e["bundle_digest"]));self.assertNotEqual(e["backup_operation_id"],e["restore_operation_id"])
  self.assertNotIn("database.json",p.stdout);self.assertNotIn("readiness.json",p.stdout)
  password=Path(auth["credential_files"]["database"]).read_text().strip();compose=["docker","compose","--project-name",auth["project"],"--file",auth["compose_file"]];query="SELECT id,fact FROM fm2_restore_rehearsal_probe ORDER BY id; INSERT INTO fm2_restore_rehearsal_probe(fact) VALUES ('post-restore'); SELECT LAST_INSERT_ID();";db=subprocess.run([*compose,"exec","-T","-e","MYSQL_PWD","db","mariadb","-N","-B","-u",auth["database_user"],auth["database"],"-e",query],env={**os.environ,"MYSQL_PWD":password},capture_output=True,text=True,timeout=60);self.assertEqual(0,db.returncode,db.stderr);self.assertEqual("7\tknown-history\n11",db.stdout.strip())
  for name in ("live","ready"):
   health=subprocess.run(["curl","--fail","--silent","--show-error",auth["health"][name]],capture_output=True,text=True,timeout=30);self.assertEqual(0,health.returncode,health.stderr)
  artifact_path=auth["runtime"]["artifact_volume_path"];session_path=auth["runtime"]["session_volume_path"];self.assertEqual(("artifacts","yii-sessions"),(artifact_path,session_path))
  artifact=subprocess.run(["docker","run","--rm","--network","none","--mount",f"type=volume,source={auth['volumes']['artifacts']['name']},target=/state,readonly",auth["image"],"sh","-c",f"test \"$(cat /state/{artifact_path}/rehearsal/original.pdf)\" = PDF-known && test \"$(stat -c %a /state/{artifact_path}/rehearsal/original.pdf)\" = 600"],capture_output=True,text=True,timeout=60);self.assertEqual(0,artifact.returncode,artifact.stderr)
  session=subprocess.run(["docker","run","--rm","--network","none","--mount",f"type=volume,source={auth['volumes']['sessions']['name']},target=/state,readonly",auth["image"],"sh","-c",f"test -s /state/{session_path}/rehearsal-session"],capture_output=True,text=True,timeout=60);self.assertEqual(0,session.returncode,session.stderr)
  prefix=auth["runtime"]["process_table_prefix"];jobs=subprocess.run([*compose,"exec","-T","-e","MYSQL_PWD","db","mariadb","-N","-B","-u",auth["database_user"],auth["database"],"-e",f"SELECT status,idempotency_key FROM {prefix}fm2_jobs WHERE idempotency_key='restore-rehearsal-job'; SELECT status FROM {prefix}fm2_outbox_intents WHERE domain_event_id='restore-rehearsal-outbox' AND channel='rehearsal'"],env={**os.environ,"MYSQL_PWD":password},capture_output=True,text=True,timeout=60);self.assertEqual((0,"ready\trestore-rehearsal-job\npending"),(jobs.returncode,jobs.stdout.strip()))
  for smoke in auth["golden"]:
   observed=subprocess.run(["curl","--fail","--silent","--show-error",smoke["url"]],capture_output=True,timeout=30);self.assertEqual(0,observed.returncode,observed.stderr);self.assertEqual(smoke["sha256"],__import__("hashlib").sha256(observed.stdout).hexdigest())
if __name__=="__main__":unittest.main(verbosity=2)
