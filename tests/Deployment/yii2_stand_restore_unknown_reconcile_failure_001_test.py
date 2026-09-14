import json,sys,unittest
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/'Support'));from stand_restore_reconcile_contract import *;from stand_backup_contract import canonical
class T(unittest.TestCase):
 def test_independent_rejections_are_zero_effect(self):
  probe=ReconcileFixture()
  try:
   if probe.run()[0]!=0:print('INTENDED_RED: reconciliation admission seam missing')
  finally:probe.close()
  cases=['missing-operation','non-unknown','malformed-ledger','malformed-recovery-ledger','missing-lease','malformed-lease','symlink-lease','lease-digest','operation','target','bundle','rollback-bundle','expired','scope','authorization','source','image','runtime','identity','overlap','pointer']
  reasons={'missing-operation':'OPERATION_NOT_FOUND','non-unknown':'OPERATION_NOT_UNKNOWN','malformed-ledger':'OPERATION_INVALID','malformed-recovery-ledger':'RECOVERY_INVALID','missing-lease':'LEASE_INVALID','malformed-lease':'LEASE_INVALID','symlink-lease':'LEASE_INVALID','lease-digest':'LEASE_INVALID','operation':'AUTHORIZATION_INVALID','target':'AUTHORIZATION_INVALID','bundle':'AUTHORIZATION_INVALID','rollback-bundle':'ROLLBACK_BUNDLE_INVALID','expired':'AUTHORIZATION_INVALID','scope':'AUTHORIZATION_INVALID','authorization':'AUTHORIZATION_INVALID','source':'AUTHORIZATION_INVALID','image':'AUTHORIZATION_INVALID','runtime':'AUTHORIZATION_INVALID','identity':'TARGET_INVALID','overlap':'TARGET_INVALID','pointer':'RESTORED_POINTER_PRESENT'}
  for case in cases:
   f=ReconcileFixture()
   try:
    auth=f.auth;prior=PRIOR
    if case=='missing-operation':prior='74747474-7474-4474-8474-747474747474';auth=f.variant_auth(prior_operation_id=prior)
    elif case=='non-unknown':f.ledger.write_bytes(f.ledger.read_bytes().replace(b'OUTCOME_UNKNOWN',b'RESTORE_FAILED'))
    elif case=='malformed-ledger':f.ledger.write_bytes(b'{')
    elif case=='malformed-recovery-ledger':(f.e/'restore-reconciliations.jsonl').write_bytes(b'{')
    elif case=='missing-lease':f.lease.unlink()
    elif case=='malformed-lease':f.lease.write_bytes(b'{')
    elif case=='symlink-lease':f.lease.unlink();f.lease.symlink_to(f.ledger)
    elif case=='lease-digest':auth=f.variant_auth(lease_digest='a'*64)
    elif case=='operation':auth=f.variant_auth(reconciliation_id='75757575-7575-4575-8575-757575757575')
    elif case=='target':auth=f.variant_auth(target_digest='a'*64)
    elif case=='bundle':auth=f.variant_auth(bundle_digest='a'*64)
    elif case=='rollback-bundle':auth=f.variant_auth(rollback_bundle_digest='a'*64)
    elif case=='expired':auth=f.variant_auth(expires_at='2000-01-01T00:00:00Z')
    elif case=='scope':auth=f.variant_auth(scope='manual-unlock')
    elif case=='authorization':auth=f.variant_auth(authorization_id='other-authority')
    elif case=='source':auth=f.variant_auth(source='a'*40)
    elif case=='image':auth=f.variant_auth(image='fmonitor2-runtime@sha256:'+'a'*64)
    elif case=='runtime':auth=f.variant_auth(runtime={**f.runtime,'artifact_volume_path':'other'})
    elif case=='identity':f.configure(observed={**f.observed,'network_id':'other'})
    elif case=='overlap':f.configure(production_overlap=True)
    elif case=='pointer':(f.e/'restored.json').write_bytes(b'{}\n')
    before=f.snapshot();effects=f.effects();c,o=f.run(auth=auth,prior=prior);self.assertNotEqual(0,c,case);self.assertEqual(reasons[case],o.get('reason'),case);self.assertEqual(before,f.snapshot(),case);self.assertEqual(effects,f.effects(),case)
   finally:f.close()
if __name__=='__main__':unittest.main(verbosity=2)
