"""VERIFICATION-INVENTORY-001: isolated public CLI; no Docker or live DB."""
import re
import json
import subprocess
import shutil
import sys
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
                     'tests/Verification/unregistered_test.mjs',
                     'tests/Runtime/unregistered_test.php',
                     'tests/Jobs/unregistered_test.php']:
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
        roster = subprocess.run([sys.executable, str(native.ROOT / 'tools/verification/ci.py'),
                                 'verify-roster'], cwd=native.ROOT,
                                capture_output=True, text=True)
        self.assertEqual(0, roster.returncode, 'INTENDED_RED verify-roster unavailable: ' + roster.stderr)
        summary = json.loads(roster.stdout)
        self.assertEqual('GREEN', summary['status'])

        expected = {suite: [] for suite in ['unit', 'db', 'characterization', 'e2e']}
        catalog = native.ROOT / 'tools/verification/suites.tsv'
        for raw in catalog.read_text().splitlines():
            if not raw or raw.startswith('#'):
                continue
            suite, runtime, path = raw.split('\t')
            expected[suite].append(f'{runtime}\t{path}')
        for suite, lines in expected.items():
            result = subprocess.run(['/bin/bash', str(native.ROOT / 'tools/verification/run.sh'),
                                     'list', suite], cwd=native.ROOT,
                                    capture_output=True, text=True)
            self.assertEqual(0, result.returncode, result.stderr)
            self.assertEqual(lines, result.stdout.splitlines())
            self.assertEqual(len(lines), len(set(lines)), suite + ' duplicates')
        self.assertEqual(sum(map(len, expected.values())), summary['tests'])

if __name__ == '__main__':
    unittest.main(verbosity=2)
