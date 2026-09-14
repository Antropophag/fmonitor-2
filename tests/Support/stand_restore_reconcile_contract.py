"""Isolated public-seam fixture for UNKNOWN restore reconciliation."""
import copy,hashlib,json,os,subprocess,sys
from pathlib import Path
sys.path.insert(0,str(Path(__file__).parent))
from stand_restore_contract import RestoreFixture
from stand_backup_contract import canonical,digest,tree
ROOT=Path(__file__).resolve().parents[2];CLI=ROOT/'bin/yii'
PRIOR='41414141-4141-4141-8141-414141414141';RID='73737373-7373-4373-8373-737373737373'
def file_digest(p):return digest(p.read_bytes())
class ReconcileFixture:
 def __init__(self):
  self.f=RestoreFixture();self.f.configure(driver_outcome='interrupt');assert self.f.run()[0]==70;self.e=self.f.evidence;self.ledger=self.e/'restore-operations.jsonl';self.lease=self.e/'restore-lease.json';self.auth=self.f.root/'reconcile-authorization.json';self.driver=self.f.root/'reconcile-driver.json';self.trace=self.f.root/'reconcile-effects.jsonl';self.target=digest(canonical(json.loads(self.f.backup.manifest.read_bytes())));m=json.loads(self.f.backup.manifest.read_bytes());self.source=m['source'];self.image=m['image'];self.runtime={'process_table_prefix':'test_','artifact_volume_path':'artifacts','session_volume_path':'yii-sessions'};self.observed={'project_id':m['project'],'database_id':m['database']['observed_id'],'network_id':'test-network-id','volumes':m['volumes']};self.value={'version':1,'scope':'disposable-stand-restore-reconcile-unknown','authorization_id':'owner-test-reconcile-exact','reconciliation_id':RID,'prior_operation_id':PRIOR,'target_digest':self.target,'bundle_digest':self.f.digest,'rollback_bundle_digest':self.f.digest,'lease_digest':file_digest(self.lease),'unknown_record_digest':file_digest(self.ledger),'source':self.source,'image':self.image,'runtime':self.runtime,'observed':self.observed,'expires_at':'2099-01-01T00:00:00Z','disposable':True};self.write_auth();self.configure()
 def write_auth(self,value=None,path=None):
  p=path or self.auth;p.write_bytes(canonical(value or self.value));return p
 def configure(self,**changes):
  v={'trace_path':str(self.trace),'observed':self.observed,'runtime':self.runtime,'source':self.source,'image':self.image,'production_overlap':False,'interrupt_at':None};v.update(changes);self.driver.write_bytes(canonical(v))
 def run(self,auth=None,rid=RID,prior=PRIOR,extra=()):
  argv=['php',str(CLI),'stand-restore/reconcile-unknown','--manifest='+str(self.f.backup.manifest),'--operation-id='+prior,'--reconciliation-id='+rid,'--authorization='+str(auth or self.auth),'--fixture-driver='+str(self.driver),*extra,'--interactive=0'];env={'PATH':os.environ.get('PATH','/usr/bin:/bin'),'FMONITOR_STAND_RECONCILE_TEST_MODE':'1'};p=subprocess.run(argv,cwd=ROOT,env=env,capture_output=True,text=True);assert p.stderr=='',p.stderr;return p.returncode,json.loads(p.stdout)
 def snapshot(self):return tree(self.e)
 def effects(self):return [] if not self.trace.exists() else [json.loads(x) for x in self.trace.read_text().splitlines()]
 def facts(self):
  p=self.e/'restore-reconciliations.jsonl';return [] if not p.exists() else [json.loads(x) for x in p.read_text().splitlines()]
 def rollback_state(self):
  lease=self.e/'restore-lease.json';pointer=self.e/'rollback-ready.json'
  if not lease.exists() and pointer.exists():return 'released',json.loads(pointer.read_bytes())
  if lease.exists() and not pointer.exists():
   value=json.loads(lease.read_bytes());return ('transferred',value) if value.get('state')=='ROLLBACK_ONLY' else ('invalid',value)
  return 'invalid',None
 def variant_auth(self,**changes):
  value=copy.deepcopy(self.value);value.update(changes);path=self.f.root/('auth-'+hashlib.sha256(canonical(value)).hexdigest()[:12]+'.json');return self.write_auth(value,path)
 def close(self):self.f.close()
