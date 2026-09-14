import unittest
from pathlib import Path
R=Path(__file__).resolve().parents[2]
class T(unittest.TestCase):
 def test_no_generic_unlock_entrypoint(self):
  candidates=[p for root in ('bin','tools','app/YiiRuntime/Commands') for p in (R/root).glob('*') if p.is_file()];text='\n'.join(p.read_text(errors='ignore') for p in candidates);self.assertNotIn('manual-unlock',text);self.assertNotIn('delete-lease',text)
 def test_controller_cannot_mutate_evidence(self):
  c=(R/'app/YiiRuntime/Commands/StandRestoreController.php').read_text();self.assertNotIn('unlink(',c);self.assertNotIn('restore-reconciliations.jsonl',c);self.assertNotIn('rollback-ready.json',c)
if __name__=='__main__':unittest.main(verbosity=2)
