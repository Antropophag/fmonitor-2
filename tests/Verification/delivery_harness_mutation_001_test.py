"""DELIVERY-HARNESS-HARDENING-001 R4: deterministic orchestration mutants."""
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class DeliveryHarnessMutationSensitivity(unittest.TestCase):
    CASES = [
        ('tools/delivery/harness.py',
         'cli_exit = 0 if outcome == "GREEN" else (child_exit or 1)',
         'cli_exit = 0',
         'tests/Verification/delivery_harness_001_test.py',
         'Harness.test_public_cli_rejects_zero_exit_control_markers_without_rewriting_child'),
        ('tools/verification/ci.py',
         "status = summary['exit_code'] if summary.get('outcome') == 'GREEN' else (summary['exit_code'] or 1)",
         "status = summary['exit_code']",
         'tests/Verification/verification_ci_001_test.py',
         'VerificationCI.test_setup_outcome_with_zero_child_exit_fails_category'),
        ('tools/delivery/change-verification.py',
         'if not allowed or not supported_name:',
         'if False:',
         'tests/Verification/delivery_harness_001_test.py',
         'Harness.test_prepare_returned_external_plan_works_with_downstream_commands'),
        ('tools/delivery/harness_context.py',
         'return hashlib.sha256(_worktree_realpath(helpers).encode()).hexdigest()[:20]',
         'return "global-binding"',
         'tests/Verification/delivery_harness_001_test.py',
         'Harness.test_active_bindings_survive_interleaved_worktrees'),
        ('tools/delivery/harness.py',
         'pattern = re.compile(br"(?m)^[ \\t]*(SETUP_FAILURE|UNKNOWN)(?=[:]|[ \\t]*(?:\\r?$))")',
         'pattern = re.compile(br"(SETUP_FAILURE|UNKNOWN)")',
         'tests/Verification/delivery_harness_001_test.py',
         'Harness.test_ci_domain_unknown_words_are_not_outcome_markers'),
        ('tools/delivery/harness_context.py',
         'expected = expectations.get(argv)',
         'expected = record.get("outcome")',
         'tests/Verification/delivery_harness_001_test.py',
         'Harness.test_gate3_cannot_replace_new_behavior_red_with_arbitrary_green'),
        ('tools/verification/ci.py',
         'items = inventory()',
         'items = []',
         'tests/Verification/delivery_harness_hardening_001_test.py',
         'DeliveryHarnessHardening.test_roster_consistency_fails_before_ci_and_recovers'),
    ]

    def copy_repo(self, destination):
        for name in ['tools', 'tests', 'specs', '.quality-graph', '.codex', 'docs',
                     'rapid-pilot', 'app']:
            source = ROOT / name
            if source.exists():
                shutil.copytree(source, destination / name)
        for name in ['quality-graph.yml', 'AGENTS.md']:
            shutil.copy2(ROOT / name, destination / name)

    def run_test(self, repo, file_name, test_name):
        return subprocess.run([sys.executable, file_name, test_name], cwd=repo,
                              text=True, capture_output=True, timeout=60)

    def test_each_named_fault_is_killed_by_its_public_contract(self):
        for index, (source_name, old, new, test_file, test_name) in enumerate(self.CASES):
            with self.subTest(fault=index):
                with tempfile.TemporaryDirectory(prefix=f'harness-mutant-{index}-') as raw:
                    repo = Path(raw) / 'repo'; repo.mkdir()
                    self.copy_repo(repo)
                    baseline = self.run_test(repo, test_file, test_name)
                    self.assertEqual(0, baseline.returncode,
                                     'INTENDED_RED baseline contract unavailable:\n' + baseline.stdout + baseline.stderr)
                    source = repo / source_name
                    content = source.read_text()
                    self.assertEqual(1, content.count(old), 'fault seam drifted: ' + source_name)
                    source.write_text(content.replace(old, new, 1))
                    mutant = self.run_test(repo, test_file, test_name)
                    self.assertNotEqual(0, mutant.returncode,
                                        'INTENDED_RED mutant survived: ' + source_name + '\n' +
                                        mutant.stdout + mutant.stderr)


if __name__ == '__main__':
    unittest.main(verbosity=2)
