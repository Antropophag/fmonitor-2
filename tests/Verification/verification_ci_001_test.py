"""VERIFICATION-PR-CYCLE-001: real Git, public CLI, isolated traced runtimes."""
import json
import importlib.util
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import sys
import unittest
from unittest import mock

ROOT = Path(__file__).resolve().parents[2]
CATEGORIES = ['unit', 'integration', 'e2e', 'governance']


class VerificationCI(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='fmonitor-ci-contract-')
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        shutil.copytree(ROOT / 'tools/delivery', self.root / 'tools/delivery')
        evidence = tempfile.TemporaryDirectory(prefix='fmonitor-runner-evidence-')
        self.addCleanup(evidence.cleanup)
        self.evidence_home = evidence.name
        (self.root / '.gitignore').write_text('*.log\n__pycache__/\n')
        tool = self.root / 'tools/verification'
        tool.mkdir(parents=True)
        shutil.copy(ROOT / 'tools/verification/run.sh', tool)
        if (ROOT / 'tools/verification/ci.py').exists():
            shutil.copy(ROOT / 'tools/verification/ci.py', tool)
        if (ROOT / 'tools/verification/inventory.py').exists():
            shutil.copy(ROOT / 'tools/verification/inventory.py', tool)
        for directory in ['InstallationProcess', 'AssignmentOrderComposition', 'Verification', 'Otiz', 'Runtime', 'Jobs']:
            (self.root / 'tests' / directory).mkdir(parents=True)
        self.paths = [f'tests/InstallationProcess/{name}_test.php' for name in ['a', 'b', 'c', 'd', 'e']]
        for path in self.paths:
            (self.root / path).write_text('fixture')
        rows = [('unit', self.paths[0]), ('unit', self.paths[1]), ('db', self.paths[2]),
                ('e2e', self.paths[3]), ('characterization', self.paths[4])]
        self.runtimes = dict.fromkeys(self.paths, 'php')
        self.runtimes[self.paths[1]] = 'node'
        self.mapping = dict(zip(self.paths, ['unit', 'unit', 'integration', 'e2e', 'governance']))
        rows = [(group, self.runtimes[path], path, self.mapping[path]) for group, path in rows]
        (tool / 'suites.tsv').write_text(
            ''.join('\t'.join(row) + '\n'
                    for row in sorted(rows, key=lambda row: (row[0], row[2], row[1], row[3]))))
        self.bin = self.root / 'trace-bin'
        self.bin.mkdir()
        self.trace = self.root / 'trace.log'
        self.db_trace = self.root / 'db-trace.log'
        php = self.bin / 'php'
        php.write_text('#!/bin/sh\nif [ "$1" = -r ]; then '
                       'test -z "${DB_TRACE:-}" || printf "probe\\n" >> "$DB_TRACE"; exit 0; fi\n'
                       'printf "php\\t%s\\n" "$1" >> "$TRACE"\n'
                       'printf "child-output:%s\\n" "$1"\n'
                       'test "$1" != "$FAIL_FILE" || exit 7\n')
        php.chmod(0o700)
        node = self.bin / 'node'
        node.write_text(php.read_text().replace('php\\t', 'node\\t'))
        node.chmod(0o700)
        self.env = dict(os.environ, PATH=str(self.bin) + os.pathsep + os.environ['PATH'],
                        TRACE=str(self.trace), DB_TRACE=str(self.db_trace), FAIL_FILE='')
        # Deliberately failing synthetic categories must not pollute the real CI report.
        self.env.pop('GITHUB_STEP_SUMMARY', None)
        self.env.update(FMONITOR_HARNESS_HOME=self.evidence_home, FMONITOR_HARNESS_PYTHON=sys.executable)
        for args in [('init','-q'),('config','user.email','fixture@example.invalid'),('config','user.name','Fixture'),('add','.'),('commit','-qm','runner fixture')]:
            subprocess.run(['git',*args],cwd=self.root,check=True,capture_output=True)


    def add_integration_inventory(self):
        paths = [
            'tests/AssignmentOrderComposition/zeta_test.php',
            'tests/InstallationProcess/alpha_test.php',
            'tests/Verification/middle_test.mjs',
            'tests/InstallationProcess/gamma_test.php',
            'tests/AssignmentOrderComposition/beta_test.php',
        ]
        runtimes = ['php', 'node', 'node', 'php', 'php']
        for path in paths:
            (self.root / path).write_text('fixture')
        catalog = self.root / 'tools/verification/suites.tsv'
        rows = [line.split('\t') for line in catalog.read_text().splitlines()]
        rows += [('db', runtime, path, 'integration') for runtime, path in zip(runtimes, paths)]
        catalog.write_text(''.join('\t'.join(row) + '\n'
                                   for row in sorted(rows, key=lambda row: (row[0], row[2], row[1], row[3]))))
        self.mapping.update(dict.fromkeys(paths, 'integration'))
        self.runtimes.update(zip(paths, runtimes))
        return paths

    def write_integration_weights(self, weights, raw=None):
        target = self.root / 'tools/verification/integration-timings.tsv'
        if raw is not None:
            target.write_text(raw)
        else:
            target.write_text(''.join(f'{path}\t{weight}\n'
                                      for path, weight in sorted(weights.items())))

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

    def test_setup_outcome_with_zero_child_exit_fails_category(self):
        php=self.bin/'php'
        php.write_text(php.read_text().replace('#!/bin/sh\n', '#!/bin/sh\necho "SETUP_FAILURE: synthetic unavailable dependency"\n'))
        result=self.cli('run','unit')
        self.assertNotEqual(0,result.returncode,'INTENDED_RED setup outcome became green category')
        self.assertIn('CATEGORY_RESULT',result.stdout)
        self.assertIn(self.paths[0],result.stdout)
        self.assertIn(self.paths[1],result.stdout)

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
        self.assertFalse(self.db_trace.exists())
        self.assertFalse((self.root / 'tools/verification/__pycache__').exists(),
                         'INTENDED_RED inventory import mutated candidate source')

    def test_integration_shards_use_deterministic_lpt_and_beat_round_robin_skew(self):
        added = self.add_integration_inventory()
        unsharded = self.cli('list', 'integration')
        self.assertEqual(0, unsharded.returncode, unsharded.stderr)
        # The unsharded category preserves canonical catalogue order.
        expected_paths = sorted([self.paths[2],
                                 'tests/AssignmentOrderComposition/zeta_test.php',
                                 'tests/InstallationProcess/alpha_test.php',
                                 'tests/Verification/middle_test.mjs',
                                 'tests/InstallationProcess/gamma_test.php',
                                 'tests/AssignmentOrderComposition/beta_test.php'])
        self.assertEqual([f'{self.runtimes[p]}\t{p}' for p in expected_paths],
                         unsharded.stdout.splitlines())

        full = sorted(unsharded.stdout.splitlines(), key=lambda row: row.split('\t')[1])
        paths = [row.split('\t')[1] for row in full]
        weights = {path: weight for path, weight in zip(paths, [9.0, 1.0, 8.0, 1.0, 7.0, 1.0])}
        self.write_integration_weights(weights)
        first = self.cli('list', 'integration', '--shard', '1/2')
        second = self.cli('list', 'integration', '--shard', '2/2')
        self.assertEqual(0, first.returncode, first.stderr)
        self.assertEqual(0, second.returncode, second.stderr)
        self.assertEqual(set(full), set(first.stdout.splitlines()) | set(second.stdout.splitlines()))
        self.assertFalse(set(first.stdout.splitlines()) & set(second.stdout.splitlines()))
        load = lambda rows: sum(weights[row.split('\t')[1]] for row in rows)
        old_max = max(load(full[::2]), load(full[1::2]))
        new_max = max(load(first.stdout.splitlines()), load(second.stdout.splitlines()))
        self.assertLess(new_max, old_max)

        original = (self.root / 'tools/verification/suites.tsv').read_text()
        (self.root / 'tools/verification/suites.tsv').write_text(
            ''.join(reversed(original.splitlines(keepends=True))))
        shuffled = [self.cli('list', 'integration', '--shard', shard)
                    for shard in ['1/2', '2/2']]
        # Canonical inventory validation rejects order drift rather than allowing
        # input order to influence allocation.
        for result in shuffled:
            self.assertNotEqual(0, result.returncode)
            self.assertIn('not in canonical order', result.stderr)
        (self.root / 'tools/verification/suites.tsv').write_text(original)
        repeated = [self.cli('list', 'integration', '--shard', shard).stdout
                    for shard in ['1/2', '2/2']]
        self.assertEqual([first.stdout, second.stdout], repeated)
        self.assertFalse(self.trace.exists(), 'listing must not probe the DB or invoke runtimes')
        self.assertFalse(self.db_trace.exists(), 'listing must not probe the DB')

    def test_new_test_without_weight_and_stale_weight_preserve_exact_membership(self):
        self.add_integration_inventory()
        full = self.cli('list', 'integration').stdout.splitlines()
        stale = 'tests/InstallationProcess/removed_test.php'
        self.write_integration_weights({stale: 9999.0})
        shards = [self.cli('list', 'integration', '--shard', shard)
                  for shard in ['1/2', '2/2']]
        for result in shards:
            self.assertEqual(0, result.returncode, result.stderr)
        combined = [row for result in shards for row in result.stdout.splitlines()]
        self.assertEqual(set(full), set(combined))
        self.assertEqual(len(full), len(combined))
        self.assertNotIn(stale, '\n'.join(combined))

    def test_missing_and_invalid_weights_schedule_every_test_deterministically(self):
        self.add_integration_inventory()
        full = set(self.cli('list', 'integration').stdout.splitlines())
        cases = [None, 'broken\n', f'{self.paths[2]}\t0\n',
                 f'{self.paths[2]}\tnan\n',
                 f'{self.paths[2]}\t2\n{self.paths[2]}\t3\n']
        for raw in cases:
            target = self.root / 'tools/verification/integration-timings.tsv'
            if raw is None:
                target.unlink(missing_ok=True)
            else:
                self.write_integration_weights({}, raw=raw)
            first = [self.cli('list', 'integration', '--shard', shard)
                     for shard in ['1/2', '2/2']]
            second = [self.cli('list', 'integration', '--shard', shard)
                      for shard in ['1/2', '2/2']]
            for result in first + second:
                self.assertEqual(0, result.returncode, result.stderr)
            self.assertEqual([result.stdout for result in first],
                             [result.stdout for result in second])
            combined = [row for result in first for row in result.stdout.splitlines()]
            self.assertEqual(full, set(combined))
            self.assertEqual(len(full), len(combined))

    def test_real_integration_shards_partition_current_inventory_once(self):
        full = self.cli('list', 'integration', root=ROOT)
        first = self.cli('list', 'integration', '--shard', '1/2', root=ROOT)
        second = self.cli('list', 'integration', '--shard', '2/2', root=ROOT)
        for result in [full, first, second]:
            self.assertEqual(0, result.returncode, result.stderr)
        expected = sorted(full.stdout.splitlines(), key=lambda row: row.split('\t')[1])
        self.assertTrue(first.stdout.splitlines())
        self.assertTrue(second.stdout.splitlines())
        combined = first.stdout.splitlines() + second.stdout.splitlines()
        self.assertEqual(len(expected), len(combined))
        self.assertEqual(len(combined), len(set(combined)))
        self.assertEqual(set(expected), set(combined))

    def test_sharded_run_attempts_every_assigned_file_and_reports_failure(self):
        self.add_integration_inventory()
        selected = self.cli('list', 'integration', '--shard', '1/2')
        self.assertEqual(0, selected.returncode, selected.stderr)
        selected_paths = [row.split('\t')[1] for row in selected.stdout.splitlines()]
        env = dict(self.env, FAIL_FILE=selected_paths[0], GITHUB_ACTIONS='true')
        result = self.cli('run', 'integration', '--shard', '1/2', env=env)
        self.assertNotEqual(0, result.returncode)
        self.assertEqual(selected.stdout.splitlines(), self.trace.read_text().splitlines())
        for path in selected_paths:
            self.assertIn('child-output:' + path, result.stdout)
        self.assertIn('exit=7', result.stdout)
        self.assertIn('REGRESSION_FAILURE', result.stderr)

    def test_invalid_shards_are_rejected_before_any_runtime(self):
        for args in [('list', 'integration', '--shard', '0/2'),
                     ('list', 'integration', '--shard', '2/1'),
                     ('run', 'integration', '--shard', '1/3'),
                     ('run', 'integration', '--shard', 'first'),
                     ('run', 'unit', '--shard', '1/2')]:
            result = self.cli(*args)
            self.assertNotEqual(0, result.returncode, args)
            self.assertFalse(self.trace.exists(), args)
            self.assertFalse(self.db_trace.exists(), args)

    def test_invalid_catalog_fails_before_execution(self):
        catalog = self.root / 'tools/verification/suites.tsv'
        valid = catalog.read_text()
        cases = [valid.replace('\tunit\n', '\tinvalid\n', 1),
                 valid + f'unit\tphp\t{self.paths[0]}\tunit\n',
                 valid + 'unit\tphp\ttests/InstallationProcess/missing_test.php\tunit\n']
        for content in cases:
            catalog.write_text(content)
            result = self.cli('run', 'unit')
            self.assertNotEqual(0, result.returncode)
            self.assertIn('SETUP_FAILURE', result.stderr)
            self.assertFalse(self.trace.exists())
        catalog.write_text(valid)

    def test_category_run_continues_and_reports_real_exit(self):
        py = 'tests/Verification/python_dispatch_test.py'
        (self.root / py).write_text(
            'import os\nfrom pathlib import Path\n'
            f'with Path(os.environ["TRACE"]).open("a") as out: out.write("python3\\t{py}\\n")\n'
            f'print("child-output:{py}")\n')
        catalog = self.root / 'tools/verification/suites.tsv'
        rows = [line.split('\t') for line in catalog.read_text().splitlines()]
        rows.append(('unit', 'python3', py, 'unit'))
        catalog.write_text(''.join('\t'.join(row) + '\n'
                                   for row in sorted(rows, key=lambda row: (row[0], row[2], row[1], row[3]))))
        self.mapping[py] = 'unit'
        env = dict(self.env, FAIL_FILE=self.paths[0], GITHUB_ACTIONS='false')
        result = subprocess.run(['/bin/bash', str(self.root / 'tools/verification/run.sh'), 'category', 'unit'],
                                cwd=self.root, env=env, capture_output=True, text=True, timeout=30)
        self.assertNotEqual(0, result.returncode)
        self.assertTrue(self.trace.exists(), result.stderr)
        self.assertEqual([f'{self.runtimes[p]}\t{p}' for p in self.paths[:2]] + [f'python3\t{py}'], self.trace.read_text().splitlines())
        records=[(p,json.loads(p.read_text())) for p in Path(self.evidence_home).glob('records/*.json')]
        for path in self.paths[:2] + [py]:
            self.assertIn(path,result.stdout)
            matches=[(p,r) for p,r in records if r['argv'][-1]==path]
            self.assertEqual(1,len(matches))
            record_path,record=matches[0]
            self.assertIn('child-output:'+path,Path(record['stdout_path']).read_text())
            self.assertIn(str(record_path.resolve()),result.stdout,'interactive results keep evidence navigation')
        self.assertIn('child-output:'+self.paths[0],result.stdout,'failure diagnostics remain visible')
        self.assertRegex(result.stdout, r'VERIFY_TIMING .*exit=7')
        self.assertRegex(result.stdout, r'VERIFY_TIMING .*runtime=node .*exit=0')
        self.assertRegex(result.stdout, r'VERIFY_TIMING .*runtime=python3 .*exit=0')

    def test_make_category_does_not_leak_into_nested_make(self):
        shutil.copy(ROOT / 'Makefile', self.root / 'Makefile')
        (self.root / 'inner.mk').write_text(
            'ifneq ($(strip $(CATEGORY)),)\n$(error category leaked into nested make)\nendif\n'
            '.PHONY: nested\nnested:\n\t@echo inner-ok\n')
        php = self.bin / 'php'
        php.write_text('#!/bin/sh\n'
                       'test -z "${CATEGORY:-}${MAKEFLAGS:-}${MFLAGS:-}${MAKEOVERRIDES:-}" '
                       '|| { echo "make-control environment leaked" >&2; exit 7; }\n'
                       'make --no-print-directory -f inner.mk nested || exit 7\n')
        php.chmod(0o700)
        result = subprocess.run(['make', '--no-print-directory', 'test', 'CATEGORY=unit'],
                                cwd=self.root, env=dict(self.env, MAKEFLAGS='-k', MFLAGS='-k',
                                    MAKEOVERRIDES='FMONITOR_TEST_OVERRIDE=1'),
                                capture_output=True, text=True, timeout=30)
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        records=[json.loads(p.read_text()) for p in Path(self.evidence_home).glob('records/*.json')]
        nested=[r for r in records if r['argv'][-1]==self.paths[0]]
        self.assertEqual(1,len(nested))
        self.assertIn('inner-ok',Path(nested[0]['stdout_path']).read_text())

    def test_make_forwards_integration_shard_without_leaking_make_controls(self):
        shutil.copy(ROOT / 'Makefile', self.root / 'Makefile')
        added = self.add_integration_inventory()
        full_paths = sorted([self.paths[2]] + added)
        expected_paths = full_paths[::2]
        self.assertGreater(len(full_paths), len(expected_paths))
        php = self.bin / 'php'
        php.write_text('#!/bin/sh\n'
                       'if [ "$1" = -r ]; then '
                       'test -z "${DB_TRACE:-}" || printf "probe\\n" >> "$DB_TRACE"; exit 0; fi\n'
                       'test -z "${CATEGORY:-}${SHARD:-}${MAKEFLAGS:-}${MFLAGS:-}${MAKEOVERRIDES:-}" '
                       '|| { echo "make-control environment leaked" >&2; exit 7; }\n'
                       'printf "php\\t%s\\n" "$1" >> "$TRACE"\n')
        php.chmod(0o700)
        result = subprocess.run(['make', '--no-print-directory', 'test',
                                 'CATEGORY=integration', 'SHARD=1/2'], cwd=self.root,
                                env=dict(self.env, MAKEFLAGS='-k', MFLAGS='-k',
                                         MAKEOVERRIDES='FMONITOR_TEST_OVERRIDE=1'),
                                capture_output=True, text=True, timeout=30)
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        self.assertEqual([f'{self.runtimes[path]}\t{path}' for path in expected_paths],
                         self.trace.read_text().splitlines())

    def test_workflow_runs_two_isolated_integration_shards_and_aggregates_them(self):
        workflow = (ROOT / '.github/workflows/quality-graph.yml').read_text()
        integration = workflow.split('\n  integration:\n', 1)[1].split('\n  e2e:\n', 1)[0]
        self.assertIn('name: Integration (${{ matrix.shard }}/2)', integration)
        self.assertIn('fail-fast: false', integration)
        self.assertIn('shard: [1, 2]', integration)
        self.assertIn('runs-on: ubuntu-latest', integration)
        self.assertIn('run: make test-db-reset migrate', integration)
        self.assertIn('make test CATEGORY=integration SHARD=${{ matrix.shard }}/2', integration)
        self.assertIn('if: always()\n      run: make test-env-down', integration)
        self.assertIn('"integration":"${{ needs.integration.result }}"', workflow)

    def test_fast_validates_inventory_without_running_governance_contract(self):
        spec = importlib.util.spec_from_file_location('verification_ci_contract', ROOT / 'tools/verification/ci.py')
        module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(module)
        observed = []
        def successful(argv, **kwargs):
            observed.append(list(argv))
            return subprocess.CompletedProcess(argv, 0, '', '')
        with mock.patch.object(module, 'reconstructed_plan', return_value=(None, None)), \
             mock.patch.object(module.subprocess, 'run', side_effect=successful):
            module.run_fast_node('HEAD', 'workflow_dispatch')
        self.assertTrue(observed, 'INTENDED_RED fast command list empty')
        self.assertEqual(['python3', 'tools/verification/inventory.py', 'validate'], observed[0],
                         'INTENDED_RED inventory validation must run before other fast checks')
        self.assertNotIn(['python3', 'tests/Verification/verification_ci_001_test.py'], observed,
                         'governance contract must not execute directly in fast')
        failed = []
        def fail_inventory(argv, **kwargs):
            failed.append(list(argv))
            raise subprocess.CalledProcessError(1, argv)
        with mock.patch.object(module, 'reconstructed_plan', return_value=(None, None)), \
             mock.patch.object(module.subprocess, 'run', side_effect=fail_inventory):
            with self.assertRaises(subprocess.CalledProcessError):
                module.run_fast_node('HEAD', 'workflow_dispatch')
        self.assertEqual([['python3', 'tools/verification/inventory.py', 'validate']], failed,
                         'inventory failure must stop fast before later checks')
        governance = self.cli('list', 'governance', root=ROOT)
        self.assertEqual(0, governance.returncode, governance.stderr)
        self.assertEqual(1, [line.split('\t')[1] for line in governance.stdout.splitlines()].count(
            'tests/Verification/verification_ci_001_test.py'))

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

    def test_repository_targets_current_application(self):
        # VERIFICATION-ACTIVE-APPLICATION-001: owner's supported runtime, not failing-test suppression.
        paths = []
        roster = []
        for category in CATEGORIES:
            result = self.cli('list', category, root=ROOT)
            self.assertEqual(0, result.returncode, result.stderr)
            for line in result.stdout.splitlines():
                runtime, path = line.split('\t')
                paths.append(path)
                roster.append([category, runtime, path])
        self.assertEqual([], [p for p in paths if p.startswith('rapid-pilot/')],
                         'INTENDED_RED retired rapid runtime must not be mandatory')
        retired = ['tests/Verification/harness_otiz_canonical_compat_001_test.php', 'tests/InstallationProcess/pilot_demo_bootstrap_001_test.php', 'tests/InstallationProcess/docker_bootstrap_manual_pilot_test.php', 'tests/InstallationProcess/pilot_e2e_flow_001_test.php', 'tests/InstallationProcess/checklist_asset_current_source_manual_test.php', 'tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php', 'tests/InstallationProcess/pilot_http_auth_001_test.php', 'tests/InstallationProcess/pilot_object_card_001_test.php', 'tests/InstallationProcess/pilot_object_list_001_test.php', 'tests/InstallationProcess/pilot_route_csp_login_001_test.php', 'tests/InstallationProcess/pilot_session_storage_local_auth_canonical_001_test.php', 'tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php', 'tests/InstallationProcess/pilot_session_storage_protocol_001_test.php', 'tests/InstallationProcess/pilot_shlz_assets_001_test.php', 'tests/InstallationProcess/pilot_ui_shell_001_test.php', 'tests/Runtime/rapid_router_otiz_dependency_001_test.php']
        self.assertEqual([], sorted(set(paths).intersection(retired)), 'retired launcher and UI tests')
        required = ['tests/Otiz/legacy_premium_calculation_001_test.php', 'tests/Otiz/excel_calculation_001_test.php', 'tests/Otiz/excel_inputs_001_test.php', 'tests/Otiz/excel_publication_001_test.php', 'tests/Otiz/settlement_concurrency_001_test.php', 'tests/Runtime/production_runtime_browser_001_test.php', 'tests/Runtime/runtime_settlement_compatibility_001_test.php', 'tests/Yii2/yii2_otiz_publication_browser_001_test.php', 'tests/Yii2/yii2_otiz_settlement_browser_001_test.php', 'tests/InstallationProcess/production_migration_runner_001_test.php', 'tests/Yii2/yii2_user_access_001_test.php']
        for path in required:
            self.assertEqual(1, paths.count(path), 'current/shared contract retained once: ' + path)

        manifest = []
        for line in (ROOT / 'tools/verification/suites.tsv').read_text().splitlines():
            if not line or line.startswith('#'):
                continue
            _suite, runtime, path, category = line.split('\t')
            manifest.append([category, runtime, path])
        self.assertEqual(sorted(manifest), sorted(roster),
                         'category composition must be reproduced completely from the canonical manifest')
        self.assertEqual(len(roster), len({row[2] for row in roster}),
                         'each canonical test must appear in exactly one category')

    def test_real_composition_keeps_contracts_once(self):
        paths = []
        for group in CATEGORIES:
            result = self.cli('list', group, root=ROOT)
            self.assertEqual(0, result.returncode, result.stderr)
            paths.extend(line.split('\t')[1] for line in result.stdout.splitlines())
        self.assertEqual(len(paths), len(set(paths)))
        children = ['production_migration_runner', 'pilot_case_import', 'artifact_store']
        for name in children:
            self.assertEqual(1, paths.count(f'tests/InstallationProcess/{name}_001_test.php'))
        e2e = self.cli('list', 'e2e', root=ROOT)
        expected = []
        for line in (ROOT / 'tools/verification/suites.tsv').read_text().splitlines():
            if not line or line.startswith('#'):
                continue
            _suite, runtime, path, category = line.split('\t')
            if category == 'e2e':
                expected.append(f'{runtime}\t{path}')
        self.assertEqual(expected, e2e.stdout.splitlines(),
                         'category composition must be reproduced only from canonical manifest')


if __name__ == '__main__':
    unittest.main(verbosity=2)
