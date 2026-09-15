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
    BASE = '25aee5524f790292d350175ba278bc47e282ed4c'
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
                       original + 'unit\tnode\t' + UNIT[0] + '\tunit\n',
                       'unknown\tphp\t' + UNIT[0] + '\tunit\n',
                       'unit\truby\t' + UNIT[0] + '\tunit\n',
                       'unit\tphp\ttests/InstallationProcess/missing_test.php\tunit\n',
                       'unit\tphp\t../outside.php\tunit\n',
                       'unit\tphp\t' + UNIT[0] + '\tunknown\n',
                       'unit\tphp\t' + UNIT[0] + '\tunit\textra\n']:
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
        e2e = 'tests/Verification/example_e2e_test.php'
        for name in [py, ch, e2e]:
            (self.root / name).write_text('fixture')
        rows = [line.split('\t') for line in catalog.read_text().splitlines()]
        rows += [('characterization', 'python3', py, 'governance'),
                 ('characterization', 'php', ch, 'governance'),
                 ('e2e', 'php', e2e, 'e2e')]
        catalog.write_text(''.join('\t'.join(row) + '\n'
                                   for row in sorted(rows, key=lambda row: (row[0], row[2], row[1], row[3]))))
        for suite, expected in [('characterization', [f'php\t{ch}', f'python3\t{py}']),
                                ('e2e', [f'php\t{e2e}'])]:
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
        self.assertEqual([f'php\t{ch}', f'python3\t{py}'], self.calls(),
                         'retain Python failure after canonical predecessors')

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
            suite, runtime, path, _category = raw.split('\t')
            expected[suite].append(f'{runtime}\t{path}')
        for suite, lines in expected.items():
            result = subprocess.run(['/bin/bash', str(native.ROOT / 'tools/verification/run.sh'),
                                     'list', suite], cwd=native.ROOT,
                                    capture_output=True, text=True)
            self.assertEqual(0, result.returncode, result.stderr)
            self.assertEqual(lines, result.stdout.splitlines())
            self.assertEqual(len(lines), len(set(lines)), suite + ' duplicates')
        self.assertEqual(sum(map(len, expected.values())), summary['tests'])

    def test_migration_preserves_exact_base_inventory_without_embedded_roster(self):
        old_catalog = subprocess.check_output(
            ['git', 'show', self.BASE + ':tools/verification/suites.tsv'], cwd=native.ROOT,
            text=True).splitlines()
        old_categories = json.loads(subprocess.check_output(
            ['git', 'show', self.BASE + ':tools/verification/categories.json'], cwd=native.ROOT,
            text=True))
        expected = sorted((suite, runtime, path, old_categories[path])
                          for suite, runtime, path in (line.split('\t') for line in old_catalog
                                                       if line and not line.startswith('#')))
        current = sorted(tuple(line.split('\t')) for line in
                         (native.ROOT / 'tools/verification/suites.tsv').read_text().splitlines())
        self.assertEqual(427, len(expected))
        self.assertTrue(set(expected).issubset(current),
                        'INTENDED_RED migration changed base suite/runtime/path/category membership')

    def test_categories_json_and_active_consumer_references_are_removed(self):
        self.assertFalse((native.ROOT / 'tools/verification/categories.json').exists(),
                         'INTENDED_RED duplicate category registry remains')
        active = [native.ROOT / path for path in [
            'tools/verification/ci.py', 'tools/verification/run.sh',
            'tools/delivery/change-verification.py', '.quality-graph/verification-policy.json',
            '.github/workflows/quality-graph.yml', 'Makefile', 'tools/verification/README.md']]
        offenders = [str(path.relative_to(native.ROOT)) for path in active
                     if path.is_file() and 'categories.json' in path.read_text()]
        self.assertEqual([], offenders, 'active duplicate inventory references')

    def test_public_registration_is_atomic_and_canonical(self):
        cli = self.root / 'tools/verification/inventory.py'
        self.assertTrue(cli.is_file(), 'INTENDED_RED canonical inventory CLI missing')
        path = self.root / 'tests/InstallationProcess/registered_test.php'
        path.write_text('fixture\n')
        catalog = self.root / 'tools/verification/suites.tsv'
        before = catalog.read_bytes()
        result = subprocess.run([sys.executable, str(cli), 'register', '--file',
                                 'tests/InstallationProcess/registered_test.php', '--category', 'unit',
                                 '--runtime', 'php', '--suite', 'unit'],
                                cwd=self.root, capture_output=True, text=True)
        self.assertEqual(0, result.returncode, 'INTENDED_RED registration unavailable: ' + result.stderr)
        self.assertEqual(1, catalog.read_text().count('tests/InstallationProcess/registered_test.php'))
        self.assertEqual(0, subprocess.run([sys.executable, str(cli), 'validate'], cwd=self.root).returncode)
        registered = catalog.read_bytes()
        self.assertNotEqual(before, registered)
        for arguments in [
            ['--file', 'tests/InstallationProcess/registered_test.php', '--category', 'unit', '--runtime', 'php', '--suite', 'unit'],
            ['--file', 'tests/InstallationProcess/missing_test.php', '--category', 'unit', '--runtime', 'php', '--suite', 'unit'],
            ['--file', 'tests/InstallationProcess/registered_test.php', '--category', 'unknown', '--runtime', 'php', '--suite', 'unit'],
            ['--file', 'tests/InstallationProcess/registered_test.php', '--category', 'unit', '--runtime', 'ruby', '--suite', 'unit'],
            ['--file', 'tests/InstallationProcess/registered_test.php', '--category', 'unit', '--runtime', 'php', '--suite', 'unknown'],
        ]:
            with self.subTest(arguments=arguments):
                result = subprocess.run([sys.executable, str(cli), 'register', *arguments], cwd=self.root,
                                        capture_output=True, text=True)
                self.assertNotEqual(0, result.returncode)
                self.assertEqual(registered, catalog.read_bytes(), 'failed registration changed manifest')

    def test_make_registration_requires_all_fields_and_changes_only_manifest(self):
        shutil.copy2(native.ROOT / 'Makefile', self.root / 'Makefile')
        path = self.root / 'tests/Verification/make_registered_test.py'
        path.write_text('fixture\n')
        before = {p.relative_to(self.root).as_posix(): p.read_bytes() for p in self.root.rglob('*') if p.is_file()}
        result = subprocess.run(['make', '--no-print-directory', 'register-test',
                                 'FILE=tests/Verification/make_registered_test.py',
                                 'CATEGORY=governance', 'RUNTIME=python3', 'SUITE=characterization'],
                                cwd=self.root, capture_output=True, text=True)
        self.assertEqual(0, result.returncode, 'INTENDED_RED Make registration unavailable: ' + result.stderr)
        after = {p.relative_to(self.root).as_posix(): p.read_bytes() for p in self.root.rglob('*') if p.is_file()}
        changed = sorted(path for path in set(before) | set(after) if before.get(path) != after.get(path))
        self.assertEqual(['tools/verification/suites.tsv'], changed)
        registered = (self.root / 'tools/verification/suites.tsv').read_bytes()
        for missing in ['FILE', 'CATEGORY', 'RUNTIME', 'SUITE']:
            args = ['FILE=tests/Verification/make_registered_test.py', 'CATEGORY=governance',
                    'RUNTIME=python3', 'SUITE=characterization']
            args = [arg for arg in args if not arg.startswith(missing + '=')]
            result = subprocess.run(['make', '--no-print-directory', 'register-test', *args], cwd=self.root,
                                    capture_output=True, text=True)
            self.assertNotEqual(0, result.returncode, missing)
            self.assertEqual(registered, (self.root / 'tools/verification/suites.tsv').read_bytes())
        sentinel = self.root / 'SHELL_INJECTION'
        result = subprocess.run(['make', '--no-print-directory', 'register-test',
                                 'FILE=tests/Verification/nope;touch SHELL_INJECTION',
                                 'CATEGORY=governance', 'RUNTIME=python3', 'SUITE=characterization'],
                                cwd=self.root, capture_output=True, text=True)
        self.assertNotEqual(0, result.returncode)
        self.assertFalse(sentinel.exists())

    def test_canonicalize_is_independent_of_input_order(self):
        cli = self.root / 'tools/verification/inventory.py'
        catalog = self.root / 'tools/verification/suites.tsv'
        first = subprocess.run([sys.executable, str(cli), 'canonicalize'], cwd=self.root,
                               capture_output=True, text=True)
        self.assertEqual(0, first.returncode, 'INTENDED_RED canonicalize unavailable: ' + first.stderr)
        catalog.write_text(''.join(reversed(catalog.read_text().splitlines(keepends=True))))
        for command in [['validate'], ['list', '--category', 'unit']]:
            with self.subTest(command=command):
                rejected = subprocess.run([sys.executable, str(cli), *command], cwd=self.root,
                                          capture_output=True, text=True)
                self.assertNotEqual(0, rejected.returncode,
                                    'INTENDED_RED noncanonical manifest order was accepted')
        second = subprocess.run([sys.executable, str(cli), 'canonicalize'], cwd=self.root,
                                capture_output=True, text=True)
        self.assertEqual(first.stdout, second.stdout)

if __name__ == '__main__':
    unittest.main(verbosity=2)
