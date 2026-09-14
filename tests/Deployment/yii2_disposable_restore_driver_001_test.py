#!/usr/bin/env python3
"""Application-level effect ordering, ambiguity, replay and secret safety RED."""
import json,os,subprocess,sys,unittest
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/"Support"))
from stand_restore_contract import RestoreFixture
from stand_backup_contract import canonical,digest
ROOT=Path(__file__).resolve().parents[2];PROBE=ROOT/"tests/Support/stand_restore_application_probe.php";SECRET="DRIVER-SECRET-CANARY"
class Driver(unittest.TestCase):
 def setup_case(self,scenario):
  f=RestoreFixture();credential=f.root/"credential";credential.write_text(SECRET);credential.chmod(0o600);source="e5a420e0b52162bde19c7d527c3fb1c57eb9f40a";image="fmonitor2-runtime@sha256:"+"c"*64;volumes={"database":{"name":"d-db","observed_id":"vid-db"},"artifacts":{"name":"d-state","observed_id":"vid-state"},"sessions":{"name":"d-sessions","observed_id":"vid-sessions"}};database={"name":"fm2_disposable_driver","observed_id":"db-observed"};manifest=f.backup.write_manifest(authorization_id="owner-disposable-issue-76-target",source=source,image=image,project="fm2-disposable-driver",database=database,volumes=volumes);target_digest=digest(manifest.read_bytes());old=f.evidence/"bundles"/f.digest;payloads={name:(old/name).read_bytes() for name in ("database.sql","artifacts.tar","sessions.json")};files={name:{"size":len(data),"sha256":digest(data)} for name,data in payloads.items()};bundle_manifest={"version":1,"target_digest":target_digest,"source":source,"image":image,"database":database,"volumes":volumes,"operation_id":"33333333-3333-4333-8333-333333333333","files":files};bundle_digest=digest(canonical(bundle_manifest));bundle=f.evidence/"bundles"/bundle_digest;bundle.mkdir();[(bundle/name).write_bytes(data) for name,data in payloads.items()];(bundle/"manifest.json").write_bytes(canonical(bundle_manifest));(f.evidence/"verified.json").write_bytes(canonical({"version":1,"bundle_digest":bundle_digest,"target_digest":target_digest,"operation_id":bundle_manifest["operation_id"]}));f.backup.manifest=manifest;f.digest=bundle_digest;auth=f.root/"authorization.json";trace=f.root/"production-trace.jsonl";scenario_path=f.root/"scenario.json"
  value={"version":1,"authorization_id":"owner-disposable-issue-76-action-0002","scope":"disposable-stand-restore","expires_at":"2099-01-01T00:00:00Z","operation_id":"61616161-6161-4161-8161-616161616161","bundle_digest":f.digest,"target_digest":target_digest,"disposable":True,"source":source,"image":image,"compose_file":str((ROOT/"deploy/runtime/compose.yaml").resolve()),"project":"fm2-disposable-driver","database":database["name"],"services":{"database":"db","php":"php","web":"web","worker":"jobs-worker","scheduler":"jobs-scheduler"},"volumes":{**volumes,"secrets":{"name":"d-secrets","observed_id":"vid-secret"}},"observed":{"project_id":"pid","network_id":"nid","database_container_id":"cid"},"credential_files":{"database":str(credential)},"health":{"live":"http://127.0.0.1:18099/health/live","ready":"http://127.0.0.1:18099/health/ready"},"evidence_root":str(f.evidence)};auth.write_bytes(canonical(value));scenario_path.write_bytes(canonical({"scenario":scenario,"credential":str(credential)}));return f,auth,scenario_path,trace
 def invoke(self,f,auth,scenario,trace,op="61616161-6161-4161-8161-616161616161",bundle=None):
  p=subprocess.run(["php",str(PROBE),str(f.backup.manifest),bundle or f.digest,op,str(auth),"unused",str(scenario),str(trace)],cwd=ROOT,capture_output=True,text=True);self.assertNotIn(SECRET,p.stdout+p.stderr);return p.returncode,json.loads(p.stdout),([] if not trace.exists() else [json.loads(x)["event"] for x in trace.read_text().splitlines()])
 def rebind(self,path,**changes):
  value=json.loads(path.read_text());value.update(changes);bound=path.with_name("authorization-"+str(len(changes))+"-"+changes.get("operation_id","same")[-4:]+".json");bound.write_bytes(canonical(value));return bound
 def test_repeated_attestation_precedes_credentials_and_each_effect(self):
  f,a,s,t=self.setup_case("success")
  try:
   code,out,events=self.invoke(f,a,s,t)
   if code==78:print("INTENDED_RED: application production driver seam missing")
   self.assertEqual((0,"RESTORE_VERIFIED"),(code,out.get("outcome")));self.assertEqual(["attest:preflight","credential:read","attest:before-db","effect:database","attest:before-artifacts","effect:artifacts","attest:before-sessions","effect:sessions","attest:before-restart","effect:restart","observe:live","observe:ready","observe:integrity"],events);self.assertNotIn(SECRET,(f.evidence/"restore-operations.jsonl").read_text())
  finally:f.close()
 def test_pre_effect_drift_has_no_effect_and_post_effect_drift_is_unknown(self):
  f,a,s,t=self.setup_case("drift-pre")
  try:code,out,events=self.invoke(f,a,s,t);self.assertEqual((64,"TARGET_INVALID"),(code,out.get("reason")));self.assertEqual(["attest:preflight"],events);self.assertFalse((f.evidence/"restore-lease.json").exists())
  finally:f.close()
  f,a,s,t=self.setup_case("drift-after-db")
  try:
   code,out,events=self.invoke(f,a,s,t);self.assertEqual((70,"OUTCOME_UNKNOWN"),(code,out.get("outcome")));self.assertIn("effect:database",events);self.assertTrue((f.evidence/"restore-lease.json").exists());self.assertFalse((f.evidence/"restored.json").exists());record=json.loads((f.evidence/"restore-operations.jsonl").read_text());self.assertEqual(("OUTCOME_UNKNOWN","61616161-6161-4161-8161-616161616161",f.digest),(record["outcome"],record["operation_id"],record["bundle_digest"]));contender="62626262-6262-4262-8262-626262626262";bound=self.rebind(a,operation_id=contender);c2,o2,_=self.invoke(f,bound,s,t,contender);self.assertEqual((75,"LEASE_HELD"),(c2,o2.get("reason")))
  finally:f.close()
 def test_replay_has_no_new_effect_and_conflict_is_rejected(self):
  f,a,s,t=self.setup_case("success")
  try:
   first=self.invoke(f,a,s,t);before_trace=t.read_bytes();before_ledger=(f.evidence/"restore-operations.jsonl").read_bytes();before_pointer=(f.evidence/"restored.json").read_bytes();second=self.invoke(f,a,s,t);self.assertEqual(first[:2],second[:2]);self.assertEqual((before_trace,before_ledger,before_pointer),(t.read_bytes(),(f.evidence/"restore-operations.jsonl").read_bytes(),(f.evidence/"restored.json").read_bytes()));other="a"*64;bound=self.rebind(a,bundle_digest=other);code,out,_=self.invoke(f,bound,s,t,bundle=other);self.assertEqual((65,"OPERATION_CONFLICT"),(code,out.get("reason")))
  finally:f.close()
 def test_application_rejects_incomplete_exact_integrity_evidence(self):
  f,a,s,t=self.setup_case("evidence-mismatch")
  try:
   code,out,events=self.invoke(f,a,s,t)
   if code==0:print("INTENDED_RED: application trusted incomplete exact integrity evidence")
   self.assertEqual((70,"OUTCOME_UNKNOWN"),(code,out.get("outcome")));self.assertIn("observe:integrity",events);self.assertTrue((f.evidence/"restore-lease.json").exists());self.assertFalse((f.evidence/"restored.json").exists());self.assertEqual("OUTCOME_UNKNOWN",json.loads((f.evidence/"restore-operations.jsonl").read_text())["outcome"])
  finally:f.close()
if __name__=="__main__":unittest.main(verbosity=2)
