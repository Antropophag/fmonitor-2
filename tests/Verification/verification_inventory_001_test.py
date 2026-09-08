"""VERIFICATION-INVENTORY-001: isolated public CLI; no Docker or live DB."""
import re
import hashlib
import subprocess
import shutil
import unittest
import verification_native_suites_001_test as native
from verification_native_suites_001_test import UNIT, DB, CLIENT


class Inventory(native.NativeSuites):
    def test_explicit_membership_ignores_source_content(self):
        (self.root / UNIT[0]).write_text('FMONITOR_TEST_DB new mysqli')
        (self.root / DB[0]).write_text('no dependency hint')
        self.test_list_membership_without_execution()

    def test_unknown_file_fails_before_execution(self):
        (self.bin / 'rg').symlink_to(shutil.which('rg'))
        for name in ['tests/InstallationProcess/unregistered_test.php',
                     'tests/Otiz/unregistered_test.php',
                     'tests/AssignmentOrderComposition/unregistered_test.php',
                     'tests/Verification/unregistered_test.mjs']:
            with self.subTest(path=name):
                path = self.root / name
                path.write_text('unregistered fixture')
                result = self.run_cli('unit')
                self.assertNotEqual(0, result.returncode)
                self.assertIn('SETUP_FAILURE', result.stderr)
                self.assertIn(name, result.stderr)
                self.assertFalse(self.invocations.exists())
                path.unlink()

    def test_invalid_catalog_fails_before_execution(self):
        (self.bin / 'rg').symlink_to(shutil.which('rg'))
        catalog = self.root / 'tools/verification/suites.tsv'
        original = catalog.read_text()
        for broken in [original + original.splitlines()[0] + '\n',
                       original + 'unit\tnode\t' + UNIT[0] + '\n',
                       'unknown\tphp\t' + UNIT[0] + '\n',
                       'unit\truby\t' + UNIT[0] + '\n',
                       'unit\tphp\ttests/InstallationProcess/missing_test.php\n',
                       'unit\tphp\t../outside.php\n',
                       'unit\tphp\t' + UNIT[0] + '\textra\n']:
            with self.subTest(catalog=broken[:80]):
                catalog.write_text(broken)
                result = self.run_cli('list', 'unit')
                self.assertNotEqual(0, result.returncode)
                self.assertIn('SETUP_FAILURE', result.stderr)
                self.assertEqual('', result.stdout)
                self.assertFalse(self.invocations.exists())
        catalog.unlink()
        result = self.run_cli('unit')
        self.assertNotEqual(0, result.returncode)
        self.assertIn('SETUP_FAILURE', result.stderr)
        self.assertFalse(self.invocations.exists())

    def test_timing_preserves_failure_and_continuation(self):
        (self.bin / 'rg').symlink_to(shutil.which('rg'))
        result = self.run_cli('unit', failures=[UNIT[0]])
        self.assertNotEqual(0, result.returncode)
        records = re.findall(r'^VERIFY_TIMING suite=(\S+) runtime=(\S+) file=(\S+) seconds=(\d+) exit=(\d+)$', result.stdout, re.M)
        self.assertEqual(len(UNIT) + 1, len(records), result.stdout)
        self.assertEqual([('unit', 'php', p) for p in UNIT] + [('unit', 'node', CLIENT)],
                         [r[:3] for r in records])
        self.assertEqual(['7'] + ['0'] * len(UNIT), [r[4] for r in records])
        self.assertEqual([f'php\t{p}' for p in UNIT] + [f'node\t{CLIENT}'], self.calls())


    def test_characterization_and_e2e_listing_and_execution(self):
        catalog = self.root / 'tools/verification/suites.tsv'
        py = 'tests/Verification/example_test.py'
        ch = 'tests/Verification/example_test.php'
        for name in [py, ch]:
            (self.root / name).write_text('fixture')
        with catalog.open('a') as stream:
            stream.write(f'characterization\tpython3\t{py}\ncharacterization\tphp\t{ch}\n')
            stream.write(f'e2e\tphp\t{DB[0]}\n')
        for suite, expected in [('characterization', [f'python3\t{py}', f'php\t{ch}']),
                                ('e2e', [f'php\t{DB[0]}'])]:
            listed = self.run_cli('list', suite)
            self.assertEqual(0, listed.returncode, listed.stderr)
            self.assertEqual('\n'.join(expected) + '\n', listed.stdout)
            self.assertFalse(self.invocations.exists())
            result = self.run_cli(suite)
            self.assertEqual(0, result.returncode, result.stderr)
            self.assertEqual(expected, self.calls())
            self.assertEqual(len(expected), result.stdout.count('VERIFY_TIMING '))
            self.trace.unlink()
            self.invocations.unlink()
        result = self.run_cli('characterization', failures=[py])
        self.assertNotEqual(0, result.returncode)
        self.assertEqual([f'python3\t{py}'], self.calls(), 'retain Python fail-fast')

    def test_repository_baseline_membership(self):
        # SHA256 of public list output on d5f8f2d; characterization/e2e
        # transcribed from its fixed command list, before implementation.
        expected = {'unit': 'ae1c98c70c549d1ba5f4600a0ed7b77d929eab5e0212f5ee6323e0438f0cef2c', 'db': 'ecb69ca1c8c3b80db2656a661adafb74e53b64dd86c05c7ca396362617c95f5f', 'characterization': 'ce1532ec45715e0d73244798d71d67f2b023397a8d0ef58b6bd9c043120625f3', 'e2e': '3e71f81948cc72b01a99713489fea493a4db50542356967099d9953569718abe'}
        added = [
            'python3\ttests/Verification/verification_inventory_001_test.py\n',
            'python3\ttests/Verification/verification_ci_001_test.py\n',
            'php\ttests/Verification/harness_full_aggregation_001_test.php\n',
            'php\ttests/Verification/harness_fresh_test_lifecycle_001_test.php\n',
            'php\ttests/Verification/quality_graph_ci_setup_001_test.php\n',
        ]
        added_by_suite = {
            'unit': [
                'python3\ttests/Verification/development_setup_001_test.py\n',
                'php\ttests/Deployment/bitrix_startup_config_001_test.php\n',
            ],
            'db': [
                'php\ttests/Otiz/snapshot_publication_001_test.php\n',
                'php\ttests/Otiz/snapshot_publication_http_001_test.php\n',
                'php\ttests/Otiz/runtime_schema_001_test.php\n',
                'php\ttests/InstallationProcess/invitation_reissue_http_001_test.php\n',
                'php\ttests/Verification/batched_schema_snapshot_001_test.php\n',
            ],
            'characterization': added,
        }
        for suite, digest in expected.items():
            result = subprocess.run(['/bin/bash', str(native.ROOT / 'tools/verification/run.sh'),
                                     'list', suite], cwd=native.ROOT, capture_output=True, text=True)
            self.assertEqual(0, result.returncode, result.stderr)
            output = result.stdout
            if suite in added_by_suite:
                for line in added_by_suite[suite]:
                    self.assertEqual(1, output.splitlines().count(line.strip()), 'new contract runs in full harness')
                    output = output.replace(line, '')
            if suite == 'db':
                e2e = 'php\ttests/InstallationProcess/pilot_e2e_flow_001_test.php'
                self.assertNotIn(e2e, output.splitlines(), 'E2E has only its own stage')
                output = '\n'.join(sorted(output.splitlines() + [e2e])) + '\n'
            self.assertEqual(digest, hashlib.sha256(output.encode()).hexdigest(), suite + ' baseline drift')

if __name__ == '__main__':
    unittest.main(verbosity=2)
