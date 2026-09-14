#!/usr/bin/env python3
"""Jobs disposable readiness must receive the canonical private Bitrix config reference."""
import json,os,subprocess,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];COMPOSE=ROOT/"deploy/runtime/compose.yaml"
class JobsReadiness(unittest.TestCase):
 def test_worker_receives_private_config_file_and_scheduler_does_not(self):
  hostile="HOSTILE-BITRIX-CONFIG-CONTENTS";env={"PATH":os.environ["PATH"],"FMONITOR_RUNTIME_IMAGE":"fmonitor2-runtime@sha256:"+"a"*64,"FMONITOR_DB_NAME":"fm2_disposable_jobs","FMONITOR_DB_USER":"root","FMONITOR_DB_PASSWORD":"secret","FMONITOR_MIGRATION_DB_USER":"root","FMONITOR_MIGRATION_DB_PASSWORD":"secret","FMONITOR_PROCESS_TABLE_PREFIX":"fm2_","FMONITOR_LEGACY_TABLE_PREFIX":"fm2_","FMONITOR_SESSION_INSTANCE":"disposable","FMONITOR_YII_COOKIE_VALIDATION_KEY":"cookie","FMONITOR_YII_IDENTITY_KEY":"identity","FMONITOR_TRUSTED_REQUEST_HOST":"127.0.0.1:18176","FMONITOR_TRUSTED_REQUEST_SCHEME":"http","FMONITOR_HTTP_PORT":"18176","FMONITOR_BITRIX_CONFIG":hostile}
  p=subprocess.run(["docker","compose","--project-name","fm2-disposable-jobs","--file",str(COMPOSE),"--profile","jobs","config","--format","json"],cwd=ROOT,env=env,capture_output=True,text=True);self.assertEqual(0,p.returncode,p.stderr);services=json.loads(p.stdout)["services"]
  worker=services["jobs-worker"];scheduler=services["jobs-scheduler"]
  if worker["environment"].get("FMONITOR_BITRIX_CONFIG")!="/run/fmonitor-secrets/bitrix-config.json":print("INTENDED_RED: jobs worker lacks canonical private Bitrix config reference")
  self.assertEqual("/run/fmonitor-secrets/bitrix-config.json",worker["environment"].get("FMONITOR_BITRIX_CONFIG"));self.assertNotIn("FMONITOR_BITRIX_CONFIG",scheduler["environment"])
  self.assertNotIn(hostile,json.dumps(worker,sort_keys=True));mounts={m["target"]:m for m in worker["volumes"]};self.assertIn("/run/fmonitor-secrets",mounts);self.assertEqual("volume",mounts["/run/fmonitor-secrets"]["type"]);self.assertEqual("secrets",mounts["/run/fmonitor-secrets"]["source"])
  for service in (worker,scheduler):self.assertEqual(["CMD","php","bin/yii","jobs/health","--interactive=0"],service["healthcheck"]["test"])
if __name__=="__main__":unittest.main(verbosity=2)
