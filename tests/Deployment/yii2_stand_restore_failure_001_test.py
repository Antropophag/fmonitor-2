#!/usr/bin/env python3
"""YII2-STAND-RESTORE-CONTROL-001 fail closed RED."""
import json,os,sys,unittest
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/"Support"))
from stand_restore_contract import RestoreFixture,canonical
class T(unittest.TestCase):
 def test_corruption_and_identity_precede_effect(self):
  f=RestoreFixture()
  try:
   p=f.evidence/"bundles"/f.digest/"database.sql";p.write_bytes(p.read_bytes()+b"bad");c,o=f.run();self.assertEqual("BACKUP_INVALID",o.get("reason"));self.assertNotEqual(0,c);self.assertFalse(f.target.exists());self.assertEqual([],f.effect_list())
  finally:f.close()
 def test_manifest_pointer_member_shape_and_identity_matrix(self):
  for case in ("manifest-corrupt","pointer-corrupt","missing","extra","symlink","directory","wrong-source","wrong-image","wrong-database","wrong-volume"):
   f=RestoreFixture()
   try:
    b=f.evidence/"bundles"/f.digest
    if case=="manifest-corrupt":(b/"manifest.json").write_bytes(b"{}\n")
    elif case=="pointer-corrupt":(f.evidence/"verified.json").write_bytes(b"{}\n")
    elif case=="missing":(b/"sessions.json").unlink()
    elif case=="extra":(b/"extra").write_text("x")
    elif case=="symlink":(b/"sessions.json").unlink();(b/"sessions.json").symlink_to(b/"database.sql")
    elif case=="directory":(b/"sessions.json").unlink();(b/"sessions.json").mkdir()
    else:
     m={"wrong-source":{"source":"9"*40},"wrong-image":{"image":"fmonitor2-runtime@sha256:"+"9"*64},"wrong-database":{"database":{"name":"test_fmonitor2","observed_id":"other"}},"wrong-volume":{"volumes":{"database":{"name":"test-fm2-db","observed_id":"other"},"artifacts":{"name":"test-fm2-artifacts","observed_id":"artifact-volume-id"},"sessions":{"name":"test-fm2-sessions","observed_id":"session-volume-id"}}}}[case];other=f.backup.write_manifest(**m);c,o=f.run(manifest=other);self.assertNotEqual(0,c);self.assertIn(o.get("reason"),("BACKUP_INVALID","TARGET_INVALID"));self.assertEqual([],f.effect_list());continue
    c,o=f.run();self.assertNotEqual(0,c);self.assertEqual("BACKUP_INVALID",o.get("reason"));self.assertFalse(f.target.exists());self.assertEqual([],f.effect_list());self.assertEqual([],f.ledger())
   finally:f.close()
 def test_real_foreign_target_and_production_authorization(self):
  f=RestoreFixture()
  try:
   f.target.mkdir();(f.target/"foreign").write_text("preserve");before=(f.target/"foreign").read_bytes();c,o=f.run();self.assertEqual((66,"TARGET_NOT_EMPTY"),(c,o.get("reason")));self.assertEqual(before,(f.target/"foreign").read_bytes());self.assertEqual([],f.effect_list())
  finally:f.close()
 def test_inventory_mismatch_and_symlink_target_preserve_canary(self):
  f=RestoreFixture()
  try:f.configure(target_inventory_matches=False);c,o=f.run();self.assertEqual((64,"TARGET_INVALID"),(c,o.get("reason")));self.assertEqual([],f.effect_list());self.assertEqual([],f.ledger())
  finally:f.close()
  f=RestoreFixture()
  try:
   external=f.root/"external";external.mkdir();canary=external/"keep";canary.write_text("unchanged");f.target.symlink_to(external);c,o=f.run();self.assertEqual((64,"TARGET_INVALID"),(c,o.get("reason")));self.assertEqual(b"unchanged",canary.read_bytes());self.assertEqual([],f.effect_list());self.assertEqual([],f.ledger())
  finally:f.close()
  f=RestoreFixture()
  try:c,o=f.run(test_mode=False,driver=False);self.assertNotEqual(0,c);self.assertEqual("PRODUCTION_DRIVER_UNAVAILABLE",o.get("reason"));self.assertEqual([],f.effect_list())
  finally:f.close()
 def test_nonempty_conflict_interrupt_and_readiness(self):
  for config,expected in [({"target_empty":False},"TARGET_NOT_EMPTY"),({"driver_outcome":"failure"},"RESTORE_FAILED"),({"driver_outcome":"interrupt"},"OUTCOME_UNKNOWN"),({"readiness":"failed"},"OUTCOME_UNKNOWN"),({"inventory_after_restore":"failed"},"OUTCOME_UNKNOWN")]:
   f=RestoreFixture()
   try:
    f.configure(**config);c,o=f.run();self.assertNotEqual(0,c);self.assertEqual(expected,o.get("reason") or o.get("outcome"));self.assertNotEqual("RESTORE_VERIFIED",o.get("outcome"));self.assertFalse((f.evidence/"restored.json").exists());before=f.effect_list();self.assertEqual((c,o),f.run());self.assertEqual(before,f.effect_list())
    if expected=="OUTCOME_UNKNOWN":
     self.assertTrue((f.evidence/"restore-lease.json").exists());self.assertEqual("OUTCOME_UNKNOWN",f.ledger()[0]["outcome"]);c2,o2=f.run(operation="42424242-4242-4242-8242-424242424242");self.assertEqual((75,"LEASE_HELD"),(c2,o2.get("reason")));self.assertEqual(before,f.effect_list())
    elif expected=="RESTORE_FAILED":self.assertEqual("RESTORE_FAILED",f.ledger()[0]["outcome"])
   finally:f.close()
  f=RestoreFixture()
  try:self.assertEqual(0,f.run()[0]);c,o=f.run(digest="a"*64);self.assertEqual((65,"OPERATION_CONFLICT"),(c,o.get("reason")));self.assertEqual(1,len(f.effect_list()))
  finally:f.close()
 def test_malformed_ledger_and_lease_fail_unknown_before_effect(self):
  for name in ("restore-operations.jsonl","restore-lease.json"):
   f=RestoreFixture()
   try:(f.evidence/name).write_bytes(b'{"truncated":');c,o=f.run();self.assertEqual((70,"OUTCOME_UNKNOWN"),(c,o.get("outcome")));self.assertEqual([],f.effect_list());self.assertFalse(f.target.exists())
   finally:f.close()
 def test_independent_observation_rejects_short_materialization(self):
  f=RestoreFixture()
  try:
   f.configure(materialize_failure="short-artifact-write");c,o=f.run()
   if o.get("outcome")=="RESTORE_VERIFIED":print("INTENDED_RED: success trusted desired fixture state without independent reread")
   self.assertEqual((70,"OUTCOME_UNKNOWN"),(c,o.get("outcome")));self.assertFalse((f.evidence/"restored.json").exists());self.assertEqual("OUTCOME_UNKNOWN",f.ledger()[0]["outcome"])
  finally:f.close()
 def test_target_substitution_after_preflight_preserves_external_canary(self):
  f=RestoreFixture()
  try:
   external=f.root/"substitution-canary";external.mkdir();canary=external/"keep";canary.write_bytes(b"UNCHANGED")
   f.configure(swap_target_after_preflight=str(external));c,o=f.run()
   if c==0:print("INTENDED_RED: target pathname substitution reached success")
   self.assertEqual((70,"OUTCOME_UNKNOWN"),(c,o.get("outcome")));self.assertEqual(b"UNCHANGED",canary.read_bytes());self.assertFalse((f.evidence/"restored.json").exists())
  finally:f.close()
 def test_durable_lease_record_pointer_release_order(self):
  f=RestoreFixture()
  try:
   trace=f.root/"restore-trace.jsonl";f.configure(trace_path=str(trace));self.assertEqual(0,f.run()[0]);
   if not trace.exists():print("INTENDED_RED: restore durability trace is absent")
   self.assertTrue(trace.exists());events=[json.loads(x)["event"] for x in trace.read_text().splitlines()]
   expected=["fsync_restore_lease","append_restore_record","fsync_restore_evidence_record","publish_restored_pointer","fsync_restore_evidence_pointer","release_restore_lease","fsync_restore_evidence_release"]
   if events!=expected:print("INTENDED_RED: restore durability ordering is incomplete")
   self.assertEqual(expected,events)
  finally:f.close()
 def test_canonical_unknown_ledger_outcome_fails_closed(self):
  f=RestoreFixture()
  try:
   self.assertEqual(0,f.run()[0]);effects=f.effect_list();record=f.ledger()[0];record["outcome"]="UNRECOGNIZED_TERMINAL";record["exit_code"]=1;record["result"]={"ok":False,"reason":"UNRECOGNIZED_TERMINAL"};(f.evidence/"restore-operations.jsonl").write_bytes(canonical(record))
   c,o=f.run();
   if o.get("reason")=="UNRECOGNIZED_TERMINAL":print("INTENDED_RED: attacker-controlled canonical ledger outcome was replayed")
   self.assertEqual((70,"OUTCOME_UNKNOWN"),(c,o.get("outcome")));self.assertEqual(effects,f.effect_list())
  finally:f.close()
 def test_substitution_between_check_and_file_open_cannot_touch_external_file(self):
  f=RestoreFixture()
  try:
   external=f.root/"open-race-canary";external.mkdir();canary=external/"database.json";canary.write_bytes(b"EXTERNAL");canary.chmod(0o640);before=(canary.read_bytes(),canary.stat().st_mode&0o777)
   f.configure(swap_target_before_file_open=str(external));c,o=f.run()
   if (canary.read_bytes(),canary.stat().st_mode&0o777)!=before:print("INTENDED_RED: check-to-open race modified external canary")
   self.assertNotEqual(0,c);self.assertIn(o.get("outcome") or o.get("reason"),("OUTCOME_UNKNOWN","TARGET_INVALID"));self.assertEqual(before,(canary.read_bytes(),canary.stat().st_mode&0o777));self.assertFalse((f.evidence/"restored.json").exists())
  finally:f.close()
if __name__=="__main__":unittest.main(verbosity=2)
