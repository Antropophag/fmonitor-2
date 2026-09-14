import json,sys,unittest
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/'Support'));from stand_restore_reconcile_contract import *
class T(unittest.TestCase):
 def test_valid_fact_and_whole_tree_replay(self):
  f=ReconcileFixture()
  try:
   original=f.ledger.read_bytes();code,result=f.run()
   if code!=0:print('INTENDED_RED: exact UNKNOWN reconciliation public seam missing')
   self.assertEqual((0,'UNKNOWN_RECONCILED_FOR_ROLLBACK'),(code,result.get('outcome')));self.assertEqual(original,f.ledger.read_bytes());self.assertFalse((f.e/'restored.json').exists());facts=f.facts();self.assertEqual(1,len(facts));fact=facts[0]
   expected={'state':'ROLLBACK_ONLY','previous_outcome':'OUTCOME_UNKNOWN','success_confirmed':False,'forward_completion':'ABANDONED','next_action':'ROLLBACK','reconciliation_id':RID,'prior_operation_id':PRIOR,'target_digest':f.target,'bundle_digest':f.f.digest,'rollback_bundle_digest':f.f.digest,'authorization_digest':file_digest(f.auth),'unknown_record_digest':file_digest(f.ledger),'lease_digest':f.value['lease_digest']}
   for key,value in expected.items():self.assertEqual(value,fact[key],key)
   self.assertRegex(fact['reconciled_at'],r'^20[0-9]{2}-');kind,state=f.rollback_state();self.assertIn(kind,('released','transferred'));self.assertEqual('ROLLBACK_ONLY',state['state']);before=f.snapshot();effects=f.effects();self.assertEqual((code,result),f.run());self.assertEqual(before,f.snapshot());self.assertEqual(effects,f.effects());self.assertEqual(1,len(f.facts()))
  finally:f.close()
if __name__=='__main__':unittest.main(verbosity=2)
