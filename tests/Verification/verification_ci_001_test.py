"""VERIFICATION-PR-CYCLE-001: real Git, public CLI, isolated traced runtimes."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
CATEGORIES = ['unit', 'integration', 'e2e', 'governance']


class VerificationCI(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='fmonitor-ci-contract-')
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        tool = self.root / 'tools/verification'
        tool.mkdir(parents=True)
        shutil.copy(ROOT / 'tools/verification/run.sh', tool)
        if (ROOT / 'tools/verification/ci.py').exists():
            shutil.copy(ROOT / 'tools/verification/ci.py', tool)
        for directory in ['InstallationProcess', 'AssignmentOrderComposition', 'Verification']:
            (self.root / 'tests' / directory).mkdir(parents=True)
        self.paths = [f'tests/InstallationProcess/{name}_test.php' for name in ['a', 'b', 'c', 'd', 'e']]
        for path in self.paths:
            (self.root / path).write_text('fixture')
        rows = [('unit', self.paths[0]), ('unit', self.paths[1]), ('db', self.paths[2]),
                ('e2e', self.paths[3]), ('characterization', self.paths[4])]
        self.runtimes = dict.fromkeys(self.paths, 'php')
        self.runtimes[self.paths[1]] = 'node'
        (tool / 'suites.tsv').write_text(''.join(f'{g}\t{self.runtimes[p]}\t{p}\n' for g, p in rows))
        self.mapping = dict(zip(self.paths, ['unit', 'unit', 'integration', 'e2e', 'governance']))
        self.write_mapping()
        self.bin = self.root / 'trace-bin'
        self.bin.mkdir()
        self.trace = self.root / 'trace.log'
        php = self.bin / 'php'
        php.write_text('#!/bin/sh\nif [ "$1" = -r ]; then exit 0; fi\n'
                       'printf "php\\t%s\\n" "$1" >> "$TRACE"\n'
                       'printf "child-output:%s\\n" "$1"\n'
                       'test "$1" != "$FAIL_FILE" || exit 7\n')
        php.chmod(0o700)
        node = self.bin / 'node'
        node.write_text(php.read_text().replace('php\\t', 'node\\t'))
        node.chmod(0o700)
        self.env = dict(os.environ, PATH=str(self.bin) + os.pathsep + os.environ['PATH'],
                        TRACE=str(self.trace), FAIL_FILE='')

    def write_mapping(self):
        (self.root / 'tools/verification/categories.json').write_text(json.dumps(self.mapping))

    def cli(self, *args, root=None, env=None):
        target = root or self.root
        return subprocess.run(['python3', str(target / 'tools/verification/ci.py'), *args],
                              cwd=target, env=env or self.env, capture_output=True, text=True, timeout=30)

    def git(self, *args):
        return subprocess.check_output(['git', *args], cwd=self.root, stderr=subprocess.DEVNULL).decode().strip()

    def history(self, path):
        self.git('init', '-q')
        self.git('config', 'user.email', 'fixture@example.invalid')
        self.git('config', 'user.name', 'Fixture')
        (self.root / 'baseline').write_text('baseline')
        self.git('add', '.')
        self.git('commit', '-qm', 'baseline')
        base = self.git('rev-parse', 'HEAD')
        changed = self.root / path
        changed.parent.mkdir(parents=True, exist_ok=True)
        changed.write_text('changed')
        self.git('add', '.')
        self.git('commit', '-qm', 'change')
        return base

    def test_docs_plan_is_explicit_and_code_unknown_are_full(self):
        base = self.history('docs/operations/note.md')
        result = self.cli('plan', '--base', base, '--event', 'pull_request')
        self.assertEqual(0, result.returncode, result.stderr)
        plan = json.loads(result.stdout)
        self.assertEqual((False, 'docs-only', []), (plan['full'], plan['reason'], plan['categories']))
        self.assertEqual(['docs/operations/note.md'], plan['files'])
        for path in ['app/change.php', 'tests/change.py', '.github/x.yml', 'openspec/x.md',
                     'docs/architecture/x.md', 'docs/development-process.md', 'unknown.file']:
            file = self.root / path
            file.parent.mkdir(parents=True, exist_ok=True)
            file.write_text('unknown influence')
            previous = self.git('rev-parse', 'HEAD')
            self.git('add', '.')
            self.git('commit', '-qm', 'code')
            result = self.cli('plan', '--base', previous, '--event', 'pull_request')
            self.assertEqual(0, result.returncode, result.stderr)
            plan = json.loads(result.stdout)
            self.assertTrue(plan['full'], path)
            self.assertEqual(CATEGORIES, plan['categories'])

    def test_invalid_base_empty_diff_and_non_pr_force_full(self):
        base = self.history('README.md')
        for ref, event in [('not-a-ref', 'pull_request'), ('HEAD', 'pull_request'),
                           (base, 'schedule'), (base, 'workflow_dispatch'), (base, 'release')]:
            result = self.cli('plan', '--base', ref, '--event', event)
            self.assertEqual(0, result.returncode, result.stderr)
            self.assertTrue(json.loads(result.stdout)['full'], (ref, event))
        result = self.cli('plan', '--event', 'pull_request')
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertTrue(json.loads(result.stdout)['full'])

    def test_renamed_code_to_docs_is_not_docs_only(self):
        self.history('app/file.php')
        base = self.git('rev-parse', 'HEAD')
        (self.root / 'docs').mkdir(exist_ok=True)
        self.git('mv', 'app/file.php', 'docs/file.md')
        self.git('commit', '-qm', 'rename')
        plan = self.cli('plan', '--base', base, '--event', 'pull_request')
        self.assertEqual(0, plan.returncode, plan.stderr)
        self.assertTrue(json.loads(plan.stdout)['full'])
        self.assertIn('app/file.php', json.loads(plan.stdout)['files'])

    def test_category_lists_partition_inventory_without_execution(self):
        observed = []
        for group in CATEGORIES:
            result = self.cli('list', group)
            self.assertEqual(0, result.returncode, result.stderr)
            expected = [f'{self.runtimes[p]}\t{p}' for p in self.paths if self.mapping[p] == group]
            self.assertEqual(expected, result.stdout.splitlines())
            observed += result.stdout.splitlines()
        self.assertEqual(5, len(set(observed)))
        self.assertFalse(self.trace.exists())

    def test_invalid_mapping_or_catalog_fails_before_execution(self):
        valid = dict(self.mapping)
        cases = [dict(list(valid.items())[1:]), dict(valid, **{'unknown.php': 'unit'}),
                 dict(valid, **{self.paths[0]: 'invalid'})]
        for mapping in cases:
            self.mapping = mapping
            self.write_mapping()
            result = self.cli('run', 'unit')
            self.assertNotEqual(0, result.returncode)
            self.assertIn('SETUP_FAILURE', result.stderr)
            self.assertFalse(self.trace.exists())
        self.mapping = valid
        self.write_mapping()
        with (self.root / 'tools/verification/suites.tsv').open('a') as out:
            out.write(f'e2e\tphp\t{self.paths[0]}\n')
        result = self.cli('run', 'unit')
        self.assertNotEqual(0, result.returncode)
        self.assertIn('SETUP_FAILURE', result.stderr)
        self.assertFalse(self.trace.exists())

    def test_category_run_continues_and_reports_real_exit(self):
        py = 'tests/Verification/python_dispatch_test.py'
        (self.root / py).write_text(
            'import os\nfrom pathlib import Path\n'
            f'with Path(os.environ["TRACE"]).open("a") as out: out.write("python3\\t{py}\\n")\n'
            f'print("child-output:{py}")\n')
        with (self.root / 'tools/verification/suites.tsv').open('a') as out:
            out.write(f'unit\tpython3\t{py}\n')
        self.mapping[py] = 'unit'
        self.write_mapping()
        env = dict(self.env, FAIL_FILE=self.paths[0])
        result = subprocess.run(['/bin/bash', str(self.root / 'tools/verification/run.sh'), 'category', 'unit'],
                                cwd=self.root, env=env, capture_output=True, text=True, timeout=30)
        self.assertNotEqual(0, result.returncode)
        self.assertTrue(self.trace.exists(), result.stderr)
        self.assertEqual([f'{self.runtimes[p]}\t{p}' for p in self.paths[:2]] + [f'python3\t{py}'], self.trace.read_text().splitlines())
        for path in self.paths[:2] + [py]:
            self.assertIn('child-output:' + path, result.stdout)
        self.assertRegex(result.stdout, r'VERIFY_TIMING .*exit=7')
        self.assertRegex(result.stdout, r'VERIFY_TIMING .*runtime=node .*exit=0')
        self.assertRegex(result.stdout, r'VERIFY_TIMING .*runtime=python3 .*exit=0')

    def test_aggregate_requires_exact_expected_evidence(self):
        good = dict.fromkeys(['plan', 'fast'] + CATEGORIES, 'success')
        result = self.cli('aggregate', '--full', 'true', '--results', json.dumps(good))
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertIn('VERIFY_OK\n', result.stdout)
        docs = dict(good, **dict.fromkeys(CATEGORIES, 'skipped'))
        result = self.cli('aggregate', '--full', 'false', '--results', json.dumps(docs))
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual('DOCS_VERIFY_OK\n', result.stdout)
        for name in docs:
            invalid = ['failure', 'cancelled', None]
            invalid += ['skipped'] if name in ['plan', 'fast'] else ['success']
            for value in invalid:
                bad = dict(docs)
                if value is None:
                    bad.pop(name)
                else:
                    bad[name] = value
                result = self.cli('aggregate', '--full', 'false', '--results', json.dumps(bad))
                self.assertNotEqual(0, result.returncode, (name, value))
                self.assertNotIn('VERIFY_OK', result.stdout)
        for name in good:
            for value in ['failure', 'cancelled', 'skipped', None]:
                bad = dict(good)
                if value is None:
                    bad.pop(name)
                else:
                    bad[name] = value
                result = self.cli('aggregate', '--full', 'true', '--results', json.dumps(bad))
                self.assertNotEqual(0, result.returncode, (name, value))
                self.assertNotIn('VERIFY_OK\n', result.stdout)
        for value in ['null', 'invalid', '[]']:
            result = self.cli('aggregate', '--full', 'true', '--results', value)
            self.assertNotEqual(0, result.returncode)
        result = self.cli('aggregate', '--full', 'invalid', '--results', json.dumps(good))
        self.assertNotEqual(0, result.returncode)

    def test_real_composition_keeps_contracts_once(self):
        paths = []
        for group in CATEGORIES:
            result = self.cli('list', group, root=ROOT)
            self.assertEqual(0, result.returncode, result.stderr)
            paths.extend(line.split('\t')[1] for line in result.stdout.splitlines())
        self.assertEqual(len(paths), len(set(paths)))
        children = ['production_migration_runner', 'pilot_case_import', 'artifact_store',
                    'pilot_shlz_assets', 'pilot_e2e_flow', 'pilot_demo_bootstrap']
        for name in children:
            self.assertEqual(1, paths.count(f'tests/InstallationProcess/{name}_001_test.php'))
        bootstrap = (ROOT / 'tests/InstallationProcess/pilot_demo_bootstrap_001_test.php').read_text()
        for name in children[:-1]:
            self.assertNotIn(name + '_001_test.php', bootstrap, 'independent children must not rerun')
        e2e = self.cli('list', 'e2e', root=ROOT)
        self.assertEqual(['php\ttests/InstallationProcess/pilot_e2e_flow_001_test.php'], e2e.stdout.splitlines())


if __name__ == '__main__':
    unittest.main(verbosity=2)
