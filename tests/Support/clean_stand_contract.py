from __future__ import annotations
import copy, hashlib, json, os, pathlib, subprocess, tempfile, uuid

ROOT=pathlib.Path(__file__).resolve().parents[2]
SEAM=ROOT/"tests/Support/yii2_clean_stand_acceptance.php"
SOURCE="a"*40; IMAGE="fmonitor2-runtime@sha256:"+"b"*64
PROJECT="fm2-clean-76-test-"+"d"*8; DATABASE="fm2_clean_76_test_"+"d"*8
NAMES={"project":PROJECT,"database":DATABASE,"containers":[PROJECT+"-db-1",PROJECT+"-php-1",PROJECT+"-web-1",PROJECT+"-jobs-worker-1",PROJECT+"-jobs-scheduler-1"],"network":PROJECT+"_default","volumes":[PROJECT+"_database",PROJECT+"_state",PROJECT+"_secrets"]}
IDS={"containers":{"db":"1"*64,"php":"2"*64,"web":"3"*64,"jobs-worker":"4"*64,"jobs-scheduler":"5"*64},"network":"6"*64,"volumes":{"database":"7"*64,"state":"8"*64,"secrets":"9"*64}}
CALLS=["precreate.inspect","compose.create","postcreate.inspect","database.create","runtime.prepare","schema.migrate","users.provision","users.provision.replay","runtime.start","health.live","health.ready","jobs.health","golden.login-access","golden.fkr","golden.construction-control","golden.checklist","golden.otiz","jobs.enqueue","jobs.observe","runtime.image-inventory","runtime.process-inspect","runtime.include-traces","evidence.accept"]

def canonical(v): return (json.dumps(v,sort_keys=True,separators=(",",":"))+"\n").encode()
def write(p,v,mode=0o600): p.write_bytes(canonical(v)); p.chmod(mode)
def sha(p): return hashlib.sha256(p.read_bytes()).hexdigest()

class CleanStandFixture:
 def __init__(self):
  self.temp=tempfile.TemporaryDirectory(prefix="fm2-clean-cutover-"); self.root=pathlib.Path(self.temp.name)
  self.evidence=self.root/"evidence"; self.evidence.mkdir(mode=0o700); self.target=self.root/"target-sentinel"; self.target.mkdir(); (self.target/"untouched").write_text("sentinel\n")
  self.credential=self.root/"database-password"; self.credential.write_text("synthetic-secret\n"); self.credential.chmod(0o600)
  self.compose=self.root/"compose.yaml"; self.compose.write_text("name: ${COMPOSE_PROJECT_NAME}\n"); self.compose.chmod(0o600)
  self.manifest_path=self.root/"manifest.json"; self.authorization_path=self.root/"authorization.json"; self.fixture_path=self.root/"driver.json"; self.operation=str(uuid.uuid4())
  self.manifest={"version":"fmonitor-clean-stand-target-v2","cleanDisposable":True,"expectedAbsent":True,"source":SOURCE,"image":IMAGE,"compose":{"path":str(self.compose),"sha256":sha(self.compose)},"names":copy.deepcopy(NAMES),"forbiddenResourceIds":[]}
  self.authorization={"version":"fmonitor-clean-stand-authorization-v2","authorizationId":str(uuid.uuid4()),"operationId":self.operation,"intent":"CLEAN_STAND_PROVISION_AND_ACCEPT","expiresAtUtc":"2099-01-01T00:00:00Z","source":SOURCE,"image":IMAGE,"compose":{"path":str(self.compose),"sha256":sha(self.compose)},"names":copy.deepcopy(NAMES),"credentialFiles":{"databasePassword":str(self.credential)},"effects":["CREATE_DISPOSABLE_PROJECT","CREATE_FRESH_DATABASE","PREPARE_RUNTIME","RUN_CANONICAL_MIGRATIONS","PROVISION_INITIAL_USERS","START_RUNTIME","RUN_ACCEPTANCE"]}
  prefix="fm2_"; job="job-111"; outbox="outbox-222"
  routes={"login-access":["POST","/pilot/login"],"fkr":["POST","/pilot/objects/501/open"],"construction-control":["POST","/pilot/objects/501/inspections"],"checklist":["POST","/pilot/objects/501/checklist/items/1/complete"],"otiz":["POST","/pilot/otiz/calculate"]}
  golden={n:{"method":routes[n][0],"path":routes[n][1],"requestId":n+"-request","status":200,"factId":n+"-fact","factCountDelta":1,"unauthorizedStatus":403,"unauthorizedProjectionBefore":"p"*64,"unauthorizedProjectionAfter":"p"*64} for n in routes}
  traces=[{"invocation":n,"source":SOURCE,"image":IMAGE,"operationId":self.operation,"artifactSha256":hashlib.sha256(n.encode()).hexdigest(),"containerId":IDS["containers"]["jobs-worker"] if n=="jobs" else IDS["containers"]["php"],"files":["/workspace/fmonitor-2/vendor/yiisoft/yii2/Yii.php","/workspace/fmonitor-2/app/YiiRuntime/"+n.replace(".","-")+".php"]} for n in ("http.health","http.login","http.golden","migration","jobs")]
  tables={k:prefix+v for k,v in {"jobs":"fm2_jobs","jobHistory":"fm2_job_events","outbox":"fm2_outbox_intents","outboxHistory":"fm2_outbox_attempt_events","heartbeats":"fm2_worker_heartbeats"}.items()}
  self.driver={"version":"fmonitor-clean-stand-recording-driver-v2","failAt":None,"interruptAt":None,"precreate":{"projectExists":False,"conflicts":[],"names":copy.deepcopy(NAMES)},"postcreate":{"labels":{"project":PROJECT,"disposable":"true"},"image":IMAGE,"ids":copy.deepcopy(IDS),"networkAttachments":IDS["network"],"volumeMounts":copy.deepcopy(IDS["volumes"])},"observations":{"database":{"schemaBefore":[],"schemaVersion":25,"migrationLedger":list(range(1,26)),"tablePrefix":prefix},"prepare":{"paths":{"artifacts":{"mode":"0700","writable":True},"yii-sessions":{"mode":"0700","writable":True}}},"provision":{"first":{"createdIds":[101,102,103,104]},"replay":{"createdIds":[],"userFactDigest":"u"*64},"firstFactDigest":"u"*64},"containers":{n:{"id":IDS["containers"][n],"running":True,"healthy":True} for n in ("php","web","jobs-worker","jobs-scheduler")},"http":{"live":{"status":200,"body":{"ok":True}},"ready":{"status":200,"body":{"ok":True}}},"jobsHealth":{"exit":0,"body":{"ok":True,"counters":{"deadJobs":0,"expiredLeases":0,"overdueReady":0}}},"heartbeats":[{"workerId":"worker:pilot","ageSeconds":1},{"workerId":"scheduler:pilot","ageSeconds":1}],"golden":golden,"jobs":{"prefix":prefix,"tables":tables,"jobId":job,"leaseJobId":job,"historyJobId":job,"outboxId":outbox,"attemptOutboxId":outbox,"historyOutboxId":outbox,"statuses":["ready","claimed","completed"],"stableDigest":"j"*64,"blockingRecovery":[]},"imageInventory":{"image":IMAGE,"paths":["/workspace/fmonitor-2/bin/yii","/workspace/fmonitor-2/public/runtime.php"]},"processes":[{"containerId":IDS["containers"]["php"],"argv":["php-fpm","-F"]},{"containerId":IDS["containers"]["web"],"argv":["nginx","-g","daemon off;"]},{"containerId":IDS["containers"]["jobs-worker"],"argv":["php","bin/yii","jobs/worker","--interactive=0"]},{"containerId":IDS["containers"]["jobs-scheduler"],"argv":["php","bin/yii","jobs/scheduler","--interactive=0"]}],"includeTraces":traces}}
  self.sync()
 def sync(self): write(self.manifest_path,self.manifest); write(self.authorization_path,self.authorization); write(self.fixture_path,self.driver)
 def run(self,action,*extra,authorization_digest=None):
  assert SEAM.is_file(),"missing-clean-stand-acceptance-public-seam"; env=os.environ.copy(); env.update({"FMONITOR_CLEAN_STAND_TEST_MODE":"1","FMONITOR_CLEAN_STAND_RECORDING_DRIVER":str(self.fixture_path)})
  digest=authorization_digest if authorization_digest is not None else sha(self.authorization_path)
  return subprocess.run(["php",str(SEAM),action,"--manifest="+str(self.manifest_path),"--authorization="+str(self.authorization_path),"--authorization-digest="+digest,"--evidence-root="+str(self.evidence),*extra],cwd=ROOT,env=env,text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=20)
 def records(self):
  p=self.evidence/"clean-stand-operations.jsonl"; return [] if not p.exists() else [json.loads(x) for x in p.read_text().splitlines() if x]
 def trace(self):
  p=self.evidence/"external-effects.jsonl"; return [] if not p.exists() else [json.loads(x) for x in p.read_text().splitlines() if x]
 def tree(self,path=None):
  base=path or self.evidence; return {str(p.relative_to(base)):hashlib.sha256(p.read_bytes()).hexdigest() for p in base.rglob("*") if p.is_file()}
 def close(self): self.temp.cleanup()
def result(p): assert p.stderr=="",p.stderr; return json.loads(p.stdout)
