import json,sys,unittest
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/'Support'));from stand_restore_reconcile_contract import *
class T(unittest.TestCase):
 def test_interruptions_and_repair(self):
  for point in ('before_fact_append','during_fact_append','after_fact_fsync','after_fact_directory_fsync','after_lease_transition','before_ready_publish'):
   f=ReconcileFixture()
   try:
    lease=f.lease.read_bytes();ledger=f.ledger.read_bytes();f.configure(interrupt_at=point);c,o=f.run()
    if c==64:print('INTENDED_RED: reconciliation durability seam missing')
    self.assertNotEqual(0,c);self.assertEqual(ledger,f.ledger.read_bytes());self.assertFalse((f.e/'restored.json').exists())
    if point in ('before_fact_append','during_fact_append'):
     self.assertEqual(lease,f.lease.read_bytes());self.assertEqual([],f.facts())
    else:
     self.assertEqual(1,len(f.facts()))
     if point in ('after_fact_fsync','after_fact_directory_fsync'):self.assertEqual(lease,f.lease.read_bytes())
     else:self.assertIn(f.rollback_state()[0],('released','transferred','invalid'))
     f.configure(interrupt_at=None);repair=f.run();self.assertEqual((0,'UNKNOWN_RECONCILED_FOR_ROLLBACK'),(repair[0],repair[1].get('outcome')));before=f.snapshot();effects=f.effects();again=f.run();self.assertEqual(0,again[0]);self.assertEqual(before,f.snapshot());self.assertEqual(effects,f.effects())
   finally:f.close()
 def test_conflict_after_partial_fact_has_no_effect(self):
  f=ReconcileFixture()
  try:
   f.configure(interrupt_at='after_fact_fsync');f.run();before=f.snapshot();effects=f.effects();bad=f.variant_auth(bundle_digest='a'*64);c,o=f.run(auth=bad);self.assertNotEqual(0,c);self.assertEqual('OPERATION_CONFLICT',o.get('reason'));self.assertEqual(before,f.snapshot());self.assertEqual(effects,f.effects())
  finally:f.close()
 def test_partial_fact_replay_rechecks_fresh_identity(self):
  f=ReconcileFixture()
  try:
   f.configure(interrupt_at='after_fact_fsync');self.assertNotEqual(0,f.run()[0]);self.assertEqual(1,len(f.facts()));before=f.snapshot();effects=f.effects();f.configure(interrupt_at=None,observed={**f.observed,'network_id':'drifted'});c,o=f.run();
   if c==0:print('INTENDED_RED: post-fact replay skipped fresh target attestation')
   self.assertEqual((64,'TARGET_INVALID'),(c,o.get('reason')));self.assertEqual(before,f.snapshot());self.assertEqual(effects,f.effects())
  finally:f.close()
if __name__=='__main__':unittest.main(verbosity=2)
