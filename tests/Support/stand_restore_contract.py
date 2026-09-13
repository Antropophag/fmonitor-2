"""Independent YII2-STAND-RESTORE-CONTROL-001 fixture."""
import json, os, subprocess, sys, shutil
from pathlib import Path
sys.path.insert(0,str(Path(__file__).parent))
from stand_backup_contract import Fixture as BackupFixture, ROOT, CLI, canonical, tree
DB={"schema_version":24,"rows":[{"id":7,"event":"opened"}],"auto_increment":11}
ART={"signed/original.pdf":{"mode":384,"bytes":"PDF-v1"}}
SESS={"opaque":{"user":41}}
class RestoreFixture:
 def __init__(self):
  self.backup=BackupFixture();self.root=self.backup.root;self.evidence=self.backup.evidence;self.target=self.root/"target";self.driver=self.root/"restore.json";self.effects=self.root/"restore-effects.jsonl"
  self.backup.write_driver(payloads={"database.sql":canonical(DB).decode(),"artifacts.tar":canonical(ART).decode(),"sessions.json":canonical(SESS).decode()})
  c,o,_=self.backup.run("create",operation_id="31313131-3131-4131-8131-313131313131");assert c==0,("SETUP_FAILURE",o);self.digest=o["bundle_digest"];self.configure()
 def configure(self,**kw):
  v={"target_root":str(self.target),"effects_path":str(self.effects),"target_inventory_matches":True,"target_empty":True,"driver_outcome":"success","readiness":"ready"};v.update(kw);self.driver.write_bytes(canonical(v))
 def run(self,operation="41414141-4141-4141-8141-414141414141",manifest=None,digest=None,test_mode=True,driver=None):
  if not (ROOT/"app/YiiRuntime/Commands/StandRestoreController.php").is_file():print("INTENDED_RED: Yii2 stand restore controller is missing");raise AssertionError("restore owner absent")
  a=["php",str(CLI),"stand-restore/run","--manifest="+str(manifest or self.backup.manifest),"--bundle-digest="+(digest or self.digest),"--operation-id="+operation]
  if driver is not False:a.append("--fixture-driver="+str(driver or self.driver))
  a.append("--interactive=0");e={"PATH":os.environ.get("PATH","/usr/bin:/bin"),"LANG":"C","LC_ALL":"C"}
  if test_mode:e["FMONITOR_STAND_RESTORE_TEST_MODE"]="1"
  p=subprocess.run(a,cwd=self.root,env=e,capture_output=True,timeout=15);assert not p.stderr,p.stderr;return p.returncode,json.loads(p.stdout)
 def effect_list(self):return [] if not self.effects.exists() else [json.loads(x) for x in self.effects.read_text().splitlines()]
 def close(self):self.backup.close()
 def ledger(self):
  p=self.evidence/"restore-operations.jsonl";return [] if not p.exists() else [json.loads(x) for x in p.read_text().splitlines()]
