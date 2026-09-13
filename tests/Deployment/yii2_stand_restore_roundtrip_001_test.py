#!/usr/bin/env python3
"""YII2-STAND-RESTORE-CONTROL-001 roundtrip RED."""
import json,sys,unittest
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/"Support"))
from stand_restore_contract import RestoreFixture,DB,ART,SESS
from stand_backup_contract import canonical,digest
class T(unittest.TestCase):
 def test_roundtrip_and_replay(self):
  f=RestoreFixture()
  try:
   f.target.mkdir();(f.target/"destroyed-state").write_text("gone");(f.target/"destroyed-state").unlink();f.target.rmdir()
   c,o=f.run();self.assertEqual((0,"RESTORE_VERIFIED"),(c,o.get("outcome")));db=json.loads((f.target/"database.json").read_bytes());self.assertEqual(DB,db);self.assertEqual(["fm2_events"],json.loads((f.target/"schema-inventory.json").read_bytes())["tables"]);self.assertEqual(11,db["auto_increment"]);self.assertEqual(11,json.loads((f.target/"next-insert.json").read_bytes())["generated_id"])
   artifact=f.target/"artifacts/signed/original.pdf";self.assertEqual(b"PDF-v1",artifact.read_bytes());self.assertEqual(0o600,artifact.stat().st_mode&0o777);self.assertEqual(SESS,json.loads((f.target/"sessions.json").read_bytes()));ready=json.loads((f.target/"readiness.json").read_bytes());self.assertEqual({"inventory":"verified","readiness":"ready"},ready)
   target_digest=digest(canonical(json.loads(f.backup.manifest.read_bytes())));argument_digest=digest(canonical({"bundle_digest":f.digest,"command":"restore","target_digest":target_digest}))
   pointer_bytes=(f.evidence/"restored.json").read_bytes();pointer=json.loads(pointer_bytes);self.assertEqual({"version":1,"operation_id":"41414141-4141-4141-8141-414141414141","target_digest":target_digest,"bundle_digest":f.digest},pointer)
   ledger_bytes=(f.evidence/"restore-operations.jsonl").read_bytes();self.assertEqual([{"version":1,"operation_id":"41414141-4141-4141-8141-414141414141","target_digest":target_digest,"argument_digest":argument_digest,"bundle_digest":f.digest,"outcome":"RESTORE_VERIFIED","exit_code":0,"result":o}],f.ledger())
   before=f.effect_list();self.assertEqual((c,o),f.run());self.assertEqual(before,f.effect_list());self.assertEqual(ledger_bytes,(f.evidence/"restore-operations.jsonl").read_bytes());self.assertEqual(pointer_bytes,(f.evidence/"restored.json").read_bytes())
  finally:f.close()
if __name__=="__main__":unittest.main(verbosity=2)
