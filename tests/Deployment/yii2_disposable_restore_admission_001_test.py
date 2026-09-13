#!/usr/bin/env python3
"""Behavioral authorization/attestation contract at the PHP value seam."""
import copy,hashlib,json,os,subprocess,tempfile,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];PROBE=ROOT/"tests/Support/stand_restore_authorization_probe.php";OP="51515151-5151-4151-8151-515151515151";BUNDLE="a"*64;TARGET="b"*64;SECRET="HOSTILE-RESTORE-SECRET-DO-NOT-LEAK"
def canonical(v):return json.dumps(v,sort_keys=True,separators=(",",":"))+"\n"
class Admission(unittest.TestCase):
 def setUp(self):
  self.tmp=tempfile.TemporaryDirectory();self.root=Path(self.tmp.name).resolve();self.credential=self.root/"credential";self.credential.write_text(SECRET);self.credential.chmod(0o600);self.trace=self.root/"credential-read";self.canary=self.root/"canary";self.canary.write_text("UNCHANGED")
  self.auth={"version":1,"authorization_id":"owner-disposable-issue-76-action-0001","scope":"disposable-stand-restore","expires_at":"2099-01-01T00:00:00Z","operation_id":OP,"bundle_digest":BUNDLE,"target_digest":TARGET,"disposable":True,"source":"e5a420e0b52162bde19c7d527c3fb1c57eb9f40a","image":"fmonitor2-runtime@sha256:"+"c"*64,"compose_file":str((ROOT/"deploy/runtime/compose.yaml").resolve()),"project":"fm2-disposable-contract","database":"fm2_disposable_contract","services":{"database":"db","php":"php","web":"web","worker":"jobs-worker","scheduler":"jobs-scheduler"},"volumes":{"database":{"name":"d-db","observed_id":"vid-db"},"artifacts":{"name":"d-state","observed_id":"vid-state"},"sessions":{"name":"d-state","observed_id":"vid-state"},"secrets":{"name":"d-secrets","observed_id":"vid-secret"}},"observed":{"project_id":"pid","network_id":"nid","database_container_id":"cid"},"credential_files":{"database":str(self.credential)},"health":{"live":"http://127.0.0.1:18099/health/live","ready":"http://127.0.0.1:18099/health/ready"},"evidence_root":str((self.root/"evidence").resolve())}
 def tearDown(self):self.tmp.cleanup()
 def invoke(self,value):
  path=self.root/"authorization.json";path.write_text(canonical(value));env={"PATH":os.environ.get("PATH","/usr/bin:/bin"),"FMONITOR_CREDENTIAL_READ_TRACE":str(self.trace)};before=self.canary.read_bytes();p=subprocess.run(["php",str(PROBE),str(path),OP,BUNDLE,TARGET],cwd=ROOT,env=env,capture_output=True,text=True);self.assertEqual(before,self.canary.read_bytes());self.assertNotIn(SECRET,p.stdout+p.stderr);return p.returncode,json.loads(p.stdout),p.stderr
 def test_valid_authorization_is_bound_without_reading_secret(self):
  code,out,err=self.invoke(self.auth)
  if code!=0:print("INTENDED_RED: executable authorization seam is missing")
  self.assertEqual(0,code);self.assertEqual("",err);self.assertTrue(out["ok"]);self.assertEqual(hashlib.sha256(canonical(self.auth).encode()).hexdigest(),out["authorization_digest"]);self.assertFalse(self.trace.exists());self.assertNotIn("credential_files",out)
 def test_rejection_matrix_has_zero_effects_and_no_secret_read(self):
  cases=[]
  for key,value in (("disposable",False),("operation_id","52525252-5252-4252-8252-525252525252"),("bundle_digest","d"*64),("target_digest","e"*64),("scope","production"),("compose_file","relative.yml")):
   changed=copy.deepcopy(self.auth);changed[key]=value;cases.append(changed)
  changed=copy.deepcopy(self.auth);changed["observed"]={};cases.append(changed);changed=copy.deepcopy(self.auth);changed["credential_files"]["database"]=str(self.root/"missing");cases.append(changed);link=self.root/"linked-secret";link.symlink_to(self.credential);changed=copy.deepcopy(self.auth);changed["credential_files"]["database"]=str(link);cases.append(changed)
  for value in cases:
   with self.subTest(value=value):code,out,err=self.invoke(value);self.assertEqual((64,"TARGET_INVALID"),(code,out.get("reason")));self.assertEqual("",err);self.assertFalse(self.trace.exists())
if __name__=="__main__":unittest.main(verbosity=2)
