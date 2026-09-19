#!/usr/bin/env python3
"""VERIFICATION-DELIVERY-DEDUPLICATION-001 public-seam regression contract."""
import importlib.util,json,subprocess,unittest
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2];CANONICAL="tests/Yii2/yii2_preopening_browser_001_test.php";WRAPPER="tests/Yii2/yii2_shlz_operational_ui_001_test.php"
def load(relative,name):
 p=ROOT/relative
 if not p.is_file():p=ROOT/"tests/Verification/fixtures/verification_delivery_deduplication_red.py"
 s=importlib.util.spec_from_file_location(name,p);m=importlib.util.module_from_spec(s);s.loader.exec_module(m);return m
def contract_module(relative,name,attribute):
 m=load(relative,name)
 return m if hasattr(m,attribute) else load("tests/Verification/fixtures/verification_delivery_deduplication_red.py",name+"_red")
class Github:
 def __init__(self,sequences,dispatch_result=None,list_error=None,dispatch_error=None,observed=None,observe_error=None):self.sequences=list(sequences);self.dispatch_result=dispatch_result if dispatch_result is not None else {"id":900,"url":"https://example.invalid/runs/900"};self.list_error=list_error;self.dispatch_error=dispatch_error;self.observed=list(observed or []);self.observe_error=observe_error;self.list_calls=self.observe_calls=self.dispatches=0
 def list_runs(self):
  self.list_calls+=1
  if self.list_error:raise self.list_error
  if not self.sequences:return []
  return self.sequences.pop(0) if len(self.sequences)>1 else self.sequences[0]
 def dispatch(self):
  self.dispatches+=1
  if self.dispatch_error:raise self.dispatch_error
  return self.dispatch_result
 def observe(self,run_id):
  self.observe_calls+=1
  if self.observe_error:raise self.observe_error
  if not self.observed:return {"id":run_id,"status":"queued"}
  return self.observed.pop(0) if len(self.observed)>1 else self.observed[0]
class Admission:
 def __init__(self,result):self.result=result;self.run_ids=[]
 def evaluate(self,run):self.run_ids.append(run.get("id"));return dict(self.result)
def binding(**changes):
 v={"repository":"Antropophag/fmonitor-2","workflow":".github/workflows/quality-graph.yml","head":"a"*40,"base":"b"*40,"mode":"full"};v.update(changes);return v
def run(**changes):
 v={**binding(),"id":77,"url":"https://example.invalid/runs/77","event":"pull_request","status":"in_progress","conclusion":None,"jobs":[{"run_id":77,"name":"verification","status":"completed","conclusion":"success"}]};v.update(changes);return v
class Contract(unittest.TestCase):
 def decide(self,sequences,admission=None,polls=3,**kwargs):
  m=load("tools/delivery/ci_launch.py","ci_launch");g=Github(sequences,**kwargs);a=admission or Admission({"status":"SUCCESS","required_results_complete":True});return m.select_or_dispatch(g,a,binding(),polls=polls,poll_interval=0),g,a
 def test_a1_all_active_mappings_and_inventory_select_canonical_once(self):
  inventory=(ROOT/"tools/verification/suites.tsv").read_text();self.assertEqual(1,inventory.count("\t"+CANONICAL+"\t"));self.assertNotIn("\t"+WRAPPER+"\t",inventory);self.assertFalse((ROOT/WRAPPER).exists());refs=[]
  for p in (ROOT/"openspec/changes").glob("*/verification-input.json"):
   for a in json.loads(p.read_text()).get("acceptances",[]):
    refs.extend((p,t) for t in a.get("tests",[]));refs.extend((p,t) for t in a.get("gate3_expected",{}))
  self.assertEqual([],[(p,t) for p,t in refs if t==WRAPPER]);self.assertTrue(any(t==CANONICAL for _,t in refs));base=subprocess.check_output(["git","show","62d027d54af7a01a1da300eda2901ba68b2bab20:"+CANONICAL],cwd=ROOT);self.assertEqual(base,(ROOT/CANONICAL).read_bytes())
 def test_a2_pr_run_appearing_within_bound_is_reused(self):
  r,g,_=self.decide([[],[run()]]);self.assertEqual(("REUSE",77,2,0),(r["decision"],r["run"]["id"],g.list_calls,g.dispatches))
 def test_a2_completed_requires_admission_and_single_run_jobs(self):
  a=Admission({"status":"UNKNOWN","required_results_complete":False});r,g,a=self.decide([[run(status="completed",conclusion="success")]],admission=a);self.assertEqual(("UNKNOWN",[77],0),(r["decision"],a.run_ids,g.dispatches))
 def test_a2_completed_rejects_jobs_from_another_run(self):
  mixed=run(status="completed",conclusion="success",jobs=[{"run_id":88,"name":"verification","status":"completed","conclusion":"success"}]);r,g,_=self.decide([[mixed]]);self.assertEqual(("UNKNOWN",0),(r["decision"],g.dispatches))
 def test_a2_completed_positive_admission_reuses_same_run(self):
  good=Admission({"status":"SUCCESS","required_results_complete":True});r,g,good=self.decide([[run(status="completed",conclusion="success")]],admission=good);self.assertEqual(("REUSE",77,[77],0),(r["decision"],r["run"]["id"],good.run_ids,g.dispatches))
 def test_a2_confirmed_absence_dispatches_once_and_tracks_identity(self):
  r,g,a=self.decide([[],[],[]],observed=[{"id":900,"status":"queued"},{"id":900,"status":"completed","conclusion":"success","jobs":[{"run_id":900}]}]);self.assertEqual(("DISPATCHED",900,3,1,2,[900]),(r["decision"],r["run"]["id"],g.list_calls,g.dispatches,g.observe_calls,a.run_ids))
 def test_a2_mismatch_failed_cancelled_never_dispatch(self):
  for x in ({"repository":"other/repo"},{"workflow":"other.yml"},{"head":"c"*40},{"base":"d"*40},{"mode":"fast"},{"event":"workflow_dispatch"}):
   with self.subTest(mismatch=x):r,g,_=self.decide([[run(**x)]]);self.assertEqual(("BLOCKED_MISMATCH",0),(r["decision"],g.dispatches))
  for c in ("failure","cancelled"):
   with self.subTest(conclusion=c):r,g,_=self.decide([[run(status="completed",conclusion=c)]]);self.assertEqual(("OBSERVE_FAILURE",0),(r["decision"],g.dispatches))
 def test_a2_unknown_incomplete_transport_never_blind_dispatch(self):
  cases=[([{"id":77}],{}),([],{"list_error":RuntimeError("API")}),([[],[],[]],{"dispatch_error":RuntimeError("dispatch")}),([[],[],[]],{"dispatch_result":{"url":"missing-id"}}),([[],[],[]],{"observe_error":RuntimeError("observe")})]
  for seq,kw in cases:
   with self.subTest(case=repr(kw)):r,g,_=self.decide(seq,**kw);self.assertEqual("UNKNOWN",r["decision"]);self.assertLessEqual(g.dispatches,1);self.assertNotEqual("SUCCESS",r.get("status"))
 def test_a4_actual_correction_package_retains_delta_dispositions(self):
  m=load("tools/delivery/harness_context.py","contract198");self.assertTrue(hasattr(m,"correction_review_context"),"INTENDED_RED real correction package seam absent");findings=[{"id":"F1","status":"fixed","evidence":"tests green"},{"id":"F2","status":"open","blocker":"still broken"},{"id":"F3","status":"not-applicable","reason":"outside contract"}];p=m.correction_review_context(full_candidate="candidate-2",last_reviewed_source="candidate-1",delta="delta.patch",findings=findings,suggestions=["future improvement"],new_risks=["changed transport"],return_count=1);self.assertEqual(("candidate-2","candidate-1","delta.patch",findings,["future improvement"],["changed transport"],"BLOCKED"),(p["full_candidate"],p["last_reviewed_source"],p["candidate_delta"],p["finding_dispositions"],p["suggestions"],p["new_risks"],p["status"]));p=m.correction_review_context(full_candidate="candidate-2",last_reviewed_source="candidate-1",delta="delta.patch",findings=[{"id":"F2","status":"open","blocker":"still broken"}],return_count=2);self.assertEqual("RECONSIDER_OR_BLOCK",p["status"]);self.assertRaises(ValueError,m.correction_review_context,full_candidate="c",last_reviewed_source="p",delta="d",findings=[{"id":"F3","status":"not-applicable"}],return_count=1)
 def test_a5_cosmetic_classifier_is_narrow(self):
  m=contract_module("tools/delivery/harness_context.py","contract198b","requires_repeat_code_review");self.assertFalse(m.requires_repeat_code_review({"openspec/x/tasks.md":"- [ ] done\n","external-pr-record":"PR #19\n"},{"openspec/x/tasks.md":"- [x] done\n","external-pr-record":"PR #198\n"}));cases=[("specs/x.md","MUST reject","MUST accept"),("tools/x.py","return False","return True"),("record.json",'"source":"a"','"source":"b"'),("authority.md","owner only","any agent"),("status.md","CI UNKNOWN","CI GREEN")]
  for path,before,after in cases:
   with self.subTest(path=path):self.assertTrue(m.requires_repeat_code_review({path:before},{path:after}),path)
if __name__=="__main__":
 result=unittest.TextTestRunner(verbosity=2).run(unittest.defaultTestLoader.loadTestsFromTestCase(Contract))
 if not result.wasSuccessful():print("INTENDED_RED VERIFICATION-DELIVERY-DEDUPLICATION-001 missing behavior");raise SystemExit(1)
