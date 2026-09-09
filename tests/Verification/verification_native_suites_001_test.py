"""VERIFICATION-NATIVE-SUITES-001: public runner CLI in an isolated scheduler tree."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
PURE = [f'tests/AssignmentOrderComposition/selection_command_{name}_001_test.php'
        for name in ['outcomes', 'recovery', 'tracer']]
UNIT = sorted(['tests/InstallationProcess/u_test.php'] + PURE)
DB = sorted(['tests/InstallationProcess/d_test.php',
             'tests/AssignmentOrderComposition/selection_native_extra_test.php'])
CLIENT = 'tests/Verification/original_upload_client_001_test.mjs'


class NativeSuites(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='fmonitor-runner-contract-')
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        shutil.copytree(ROOT / 'tools/verification', self.root / 'tools/verification')
        for name in UNIT + DB + [CLIENT]:
            path = self.root / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text('// FMONITOR_TEST_DB\n' if name.endswith('/d_test.php') else '// scheduler fixture\n')
        (self.root / "tests/Otiz").mkdir(parents=True, exist_ok=True)
        (self.root / "tests/Runtime").mkdir(parents=True, exist_ok=True)
        self.write_catalog()
        self.bin = self.root / 'trace-bin'
        self.bin.mkdir()
        for name in ['dirname', 'find', 'sort']:
            executable = shutil.which(name)
            self.assertIsNotNone(executable, f'SETUP_OK {name}')
            (self.bin / name).symlink_to(executable)
        self.trace = self.root / 'calls.log'
        self.invocations = self.root / 'interpreter-invocations.log'
        for name in ['php', 'node', 'python3']:
            script = self.bin / name
            script.write_text('#!/bin/sh\n'
                              f'printf "{name}\\t%s\\n" "$1" >> "$INVOCATIONS"\n'
                              'if [ "$1" = "-r" ]; then exit 0; fi\n'
                              f'printf "{name}\\t%s\\n" "$1" >> "$TRACE"\n'
                              'case "$FAIL_PATHS" in *"|$1|"*) exit 7;; esac\n'
                              'exit 0\n')
            script.chmod(0o700)
        self.env = dict(os.environ, PATH=str(self.bin), TRACE=str(self.trace), INVOCATIONS=str(self.invocations), FAIL_PATHS='')

    def write_catalog(self, unit=UNIT, db=DB, clients=(CLIENT,)):
        rows = [('unit', 'php', p) for p in unit]
        rows += [('unit', 'node', p) for p in clients]
        rows += [('db', 'php', p) for p in db]
        (self.root / 'tools/verification/suites.tsv').write_text(
            ''.join('\t'.join(row) + '\n' for row in rows))

    def run_cli(self, *arguments, failures=()):
        env = dict(self.env, FAIL_PATHS=''.join(f'|{p}|' for p in failures))
        return subprocess.run(['/bin/bash', str(self.root / 'tools/verification/run.sh'), *arguments],
                              cwd=self.root, env=env, capture_output=True, text=True, timeout=20)

    def calls(self):
        return self.trace.read_text().splitlines() if self.trace.exists() else []

    def test_list_membership_without_execution(self):
        before = sorted(str(p.relative_to(self.root)) for p in self.root.rglob('*'))
        for suite, expected in [('unit', [f'php\t{p}' for p in UNIT] + [f'node\t{CLIENT}']),
                                ('db', [f'php\t{p}' for p in DB])]:
            result = self.run_cli('list', suite)
            self.assertEqual(0, result.returncode, 'INTENDED_RED list CLI missing: ' + result.stderr)
            self.assertEqual('\n'.join(expected) + '\n', result.stdout)
            self.assertEqual('', result.stderr)
        self.assertFalse(self.invocations.exists(), 'list never invokes any interpreter, including php -r')
        self.assertEqual([], self.calls(), 'list never executes files')
        self.assertEqual(before, sorted(str(p.relative_to(self.root)) for p in self.root.rglob('*')))

    def test_unit_executes_native_php_then_node(self):
        result = self.run_cli('unit')
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual([f'php\t{p}' for p in UNIT] + [f'node\t{CLIENT}'], self.calls(),
                         'INTENDED_RED omitted native unit/client discovery')
        for path in UNIT + [CLIENT]:
            self.assertIn('VERIFY ' + path + '\n', result.stdout)

    def test_db_executes_registered_native_filename(self):
        result = self.run_cli('db')
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual([f'php\t{p}' for p in DB], self.calls(),
                         'INTENDED_RED native dependency through fixture is not discovered')

    def test_php_and_node_failures_both_reported_and_all_run(self):
        failed = [UNIT[0], CLIENT]
        result = self.run_cli('unit', failures=failed)
        self.assertNotEqual(0, result.returncode, 'INTENDED_RED omitted failing verifiers')
        self.assertEqual([f'php\t{p}' for p in UNIT] + [f'node\t{CLIENT}'], self.calls())
        for path in failed:
            self.assertIn('REGRESSION_FAILURE: ' + path, result.stderr)

    def test_db_failure_does_not_drop_remaining_member(self):
        result = self.run_cli('db', failures=[DB[0]])
        self.assertNotEqual(0, result.returncode)
        self.assertEqual([f'php\t{p}' for p in DB], self.calls())
        self.assertIn('REGRESSION_FAILURE: ' + DB[0], result.stderr)

    def test_missing_node_is_failure_after_php_execution(self):
        (self.bin / 'node').unlink()
        result = self.run_cli('unit')
        self.assertNotEqual(0, result.returncode, 'INTENDED_RED Node absence is hidden')
        self.assertEqual([f'php\t{p}' for p in UNIT], self.calls())
        self.assertIn('REGRESSION_FAILURE: ' + CLIENT, result.stderr)

    def test_empty_node_inventory_is_allowed(self):
        (self.root / CLIENT).unlink()
        self.write_catalog(clients=())
        result = self.run_cli('list', 'unit')
        self.assertEqual(0, result.returncode, 'INTENDED_RED Bash3 empty Node array: ' + result.stderr)
        self.assertEqual(''.join(f'php\t{p}\n' for p in UNIT), result.stdout)
        self.assertFalse(self.invocations.exists(), 'empty Node list remains read-only')
        result = self.run_cli('unit')
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual([f'php\t{p}' for p in UNIT], self.calls())

    def test_empty_php_and_node_inventories_are_allowed(self):
        for name in UNIT + DB + [CLIENT]:
            (self.root / name).unlink()
        self.write_catalog(unit=(), db=(), clients=())
        for suite in ['unit', 'db']:
            result = self.run_cli('list', suite)
            self.assertEqual(0, result.returncode, 'INTENDED_RED Bash3 empty PHP array: ' + result.stderr)
            self.assertEqual('', result.stdout)
            result = self.run_cli(suite)
            self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual([], self.calls(), 'empty suite runs no test files')

    def test_missing_directory_and_bad_arguments_fail_closed(self):
        for args in [('list',), ('list', 'other'), ('list', 'unit', 'extra')]:
            result = self.run_cli(*args)
            self.assertNotEqual(0, result.returncode)
            self.assertIn('SETUP_FAILURE', result.stderr)
        shutil.rmtree(self.root / 'tests/AssignmentOrderComposition')
        result = self.run_cli('unit')
        self.assertNotEqual(0, result.returncode, 'INTENDED_RED missing family cannot be empty success')
        self.assertIn('SETUP_FAILURE', result.stderr)


if __name__ == '__main__':
    unittest.main(verbosity=2)
