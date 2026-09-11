"""DELIVERY-HARNESS-HARDENING-001: adversarial contracts at public CLI seams."""
import json
import os
from pathlib import Path
import shutil
import signal
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
DIMENSIONS = {
    'stdout', 'stderr', 'exit_status', 'retained_evidence', 'filesystem_effects',
    'idempotence', 'failure_semantics', 'caller_interoperability',
    'worktree_concurrency_isolation', 'verification_registry_synchronization',
}


class DeliveryHarnessHardening(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix='delivery-hardening-')
        self.addCleanup(self.tmp.cleanup)
        self.outer = Path(self.tmp.name)
        self.repo = self.outer / 'repo'
        self.repo.mkdir()
        for name in ['tools/delivery', 'tools/verification', '.quality-graph', 'specs', 'tests', 'rapid-pilot', 'app']:
            shutil.copytree(ROOT / name, self.repo / name)
        for name in ['docs/operations']:
            (self.repo / name).mkdir(parents=True)
        (self.repo / 'openspec/changes/hardening').mkdir(parents=True)
        for name in ['quality-graph.yml', 'AGENTS.md', 'docs/development-process.md']:
            shutil.copy2(ROOT / name, self.repo / name)
        (self.repo / 'docs/operations/current-delivery-goal.md').write_text('Owner: harden harness; no deployment.\n')
        (self.repo / 'specs/HARDENING.md').write_text('HARDENING observable contract.\n')
        self.acceptance_test = 'tests/Verification/hardening_fixture_test.py'
        (self.repo / self.acceptance_test).write_text('print("HARDENING_OK")\n')
        self.git('init', '-q')
        self.git('config', 'user.email', 'hardening@example.invalid')
        self.git('config', 'user.name', 'Hardening Contract')
        self.git('add', '.')
        self.git('commit', '-qm', 'fixture base')
        self.base = self.git('rev-parse', 'HEAD').stdout.strip()
        self.env = dict(os.environ, FMONITOR_HARNESS_HOME=str(self.outer / 'evidence'))

    def git(self, *args, cwd=None):
        return subprocess.run(['git', *args], cwd=cwd or self.repo, text=True,
                              capture_output=True, check=True, timeout=20)

    def command(self, *argv, cwd=None, stdin=None, timeout=30, env=None):
        return subprocess.run(list(argv), cwd=cwd or self.repo, env=env or self.env, input=stdin,
                              text=True, capture_output=True, timeout=timeout)

    def harness(self, *argv, cwd=None, stdin=None, timeout=30):
        return self.command(sys.executable, 'tools/delivery/harness.py', *argv,
                            cwd=cwd, stdin=stdin, timeout=timeout)

    def retained(self, result):
        self.assertTrue(result.stdout.strip().startswith('{'), result.stderr)
        summary = json.loads(result.stdout)
        return summary, json.loads(Path(summary['record_path']).read_text())

    def test_public_runner_table_is_one_complete_contract(self):
        cases = [
            ('green', 'import sys;print("out");sys.stderr.write("err\\n")', [], 'GREEN', 0, 'out\n', 'err\n'),
            ('failure', 'import sys;print("bad");sys.stderr.write("why\\n");raise SystemExit(7)', [], 'REGRESSION_FAILURE', 7, 'bad\n', 'why\n'),
            ('red', 'print("EXPECTED_RED");raise SystemExit(9)', ['--intended-red', 'EXPECTED_RED'], 'INTENDED_RED', 9, 'EXPECTED_RED\n', ''),
            ('setup', 'print("SETUP_FAILURE: fixture")', [], 'SETUP_FAILURE', 0, 'SETUP_FAILURE: fixture\n', ''),
            ('unknown', 'import sys;sys.stderr.write(" UNKNOWN: source\\n")', [], 'UNKNOWN', 0, '', ' UNKNOWN: source\n'),
            ('signal', 'import os,signal;os.kill(os.getpid(),signal.SIGTERM)', [], 'INTERRUPTED', 143, '', ''),
            ('domain', 'print("SOURCE_DICTIONARY_VALUE_UNKNOWN")', [], 'GREEN', 0, 'SOURCE_DICTIONARY_VALUE_UNKNOWN\n', ''),
        ]
        before = self.git('status', '--porcelain').stdout
        record_paths = []
        for label, code, options, outcome, child_exit, expected_stdout, expected_stderr in cases:
            with self.subTest(label=label):
                result = self.harness('run', *options, '--', sys.executable, '-c', code)
                summary, record = self.retained(result)
                record_paths.append(summary['record_path'])
                self.assertEqual(result.returncode == 0, outcome == 'GREEN')
                self.assertEqual(outcome, summary['outcome'])
                self.assertEqual(child_exit, record['exit_code'])
                self.assertEqual(result.returncode, record['cli_exit_code'])
                self.assertEqual(0 if child_exit == 0 else (-15 if label == 'signal' else child_exit),
                                 record['raw_child_returncode'])
                self.assertEqual(expected_stdout, Path(record['stdout_path']).read_text())
                self.assertEqual(expected_stderr, Path(record['stderr_path']).read_text())
        timeout = self.harness('run', '--timeout', '0.05', '--', sys.executable,
                               '-c', 'import time;time.sleep(10)')
        summary, record = self.retained(timeout)
        self.assertEqual('INTERRUPTED', summary['outcome'])
        self.assertNotEqual(0, timeout.returncode)
        self.assertEqual(124, record['exit_code'])
        self.assertEqual(timeout.returncode, record['cli_exit_code'])
        self.assertEqual(-signal.SIGTERM, record['raw_child_returncode'])
        self.assertEqual('', Path(record['stdout_path']).read_text())
        self.assertEqual('', Path(record['stderr_path']).read_text())
        self.assertEqual(before, self.git('status', '--porcelain').stdout)
        self.assertEqual(len(record_paths), len(set(record_paths)))
        self.assertTrue(all(not Path(path).is_relative_to(self.repo) for path in record_paths))

    def input_value(self, dimensions=None):
        return {'change': 'hardening', 'planned_paths': [self.acceptance_test], 'acceptances': [{
            'spec_id': 'HARDENING', 'acceptance_id': 'R1-R5',
            'spec_path': 'specs/HARDENING.md', 'seam': 'delivery CLI',
            'seam_kind': 'infrastructure', 'tests': [self.acceptance_test],
            'observable_dimensions': dimensions or {
                name: {'status': 'covered', 'tests': [self.acceptance_test]}
                for name in sorted(DIMENSIONS)
            },
        }]}

    def write_input(self, value, name='input.json'):
        if '/' not in name:
            name = 'openspec/changes/hardening/' + name
        path = self.repo / name
        path.write_text(json.dumps(value) + '\n')
        return name

    def plan(self, input_name='openspec/changes/hardening/input.json', output='openspec/changes/hardening/plan.json'):
        return self.command(sys.executable, 'tools/delivery/change-verification.py', 'plan',
                            '--base', self.base, '--input', input_name, '--output', output)

    def test_infrastructure_dimensions_are_complete_and_mapped(self):
        self.write_input(self.input_value())
        valid = self.plan()
        self.assertEqual(0, valid.returncode, 'INTENDED_RED observable dimensions unsupported: ' + valid.stderr)
        plan = json.loads((self.repo / 'openspec/changes/hardening/plan.json').read_text())
        self.assertEqual(DIMENSIONS, set(plan['acceptances'][0]['observable_dimensions']))
        malformed = []
        missing = self.input_value(); missing['acceptances'][0]['observable_dimensions'].pop('stderr')
        malformed.append(missing)
        unmapped = self.input_value(); unmapped['acceptances'][0]['observable_dimensions']['stdout']['tests'] = ['tests/Verification/unmapped.py']
        malformed.append(unmapped)
        no_reason = self.input_value(); no_reason['acceptances'][0]['observable_dimensions']['stderr'] = {'status': 'not_applicable', 'reason': ''}
        malformed.append(no_reason)
        for index, value in enumerate(malformed):
            with self.subTest(index=index):
                name = self.write_input(value, f'bad-{index}.json')
                result = self.plan(name, f'bad-{index}-plan.json')
                self.assertNotEqual(0, result.returncode)
                self.assertIn('observable dimensions', result.stderr)

    def test_prepare_returned_plan_drives_check_refresh_and_hook_context(self):
        input_name = self.write_input(self.input_value())
        prepared = self.harness('prepare', '--input', input_name, '--base', self.base,
                                '--role', 'root', '--gate', '3')
        self.assertEqual(0, prepared.returncode, 'INTENDED_RED prepare lifecycle failed: ' + prepared.stderr)
        package = json.loads(prepared.stdout)
        for action in ['check', 'refresh']:
            result = self.command(sys.executable, 'tools/delivery/change-verification.py', action,
                                  '--plan', package['plan'])
            self.assertEqual(0, result.returncode, f'{action}: {result.stderr}')
        bindir = self.outer / 'bin'; bindir.mkdir(exist_ok=True)
        make = bindir / 'make'; make.write_text('#!/bin/sh\nexit 0\n'); make.chmod(0o700)
        run_env = dict(self.env, PATH=str(bindir) + os.pathsep + os.environ['PATH'])
        executed = self.command(sys.executable, 'tools/delivery/change-verification.py', 'run',
                                '--plan', package['plan'], '--phase', 'focused', env=run_env)
        self.assertEqual(0, executed.returncode, 'returned plan run: ' + executed.stdout + executed.stderr)
        self.assertTrue(all(item['outcome'] == 'GREEN' for item in json.loads(executed.stdout)['results']))
        hook = self.harness('hook', stdin=json.dumps({
            'hook_event_name': 'SessionStart', 'session_id': 'hardening',
            'cwd': str(self.repo), 'source': 'resume'}))
        self.assertEqual(0, hook.returncode, hook.stderr)
        context = json.loads(hook.stdout)['hookSpecificOutput']['additionalContext']
        self.assertIn(input_name, context)
        self.assertIn('obligations=', context)
        external = self.outer / 'external-plan.json'
        external.write_text((Path(package['plan'])).read_text())
        rejected = self.command(sys.executable, 'tools/delivery/change-verification.py', 'check',
                                '--plan', str(external))
        self.assertNotEqual(0, rejected.returncode)

    def test_worktree_bindings_remain_independent(self):
        sibling = self.outer / 'sibling'
        self.git('worktree', 'add', '--detach', str(sibling), 'HEAD')
        self.addCleanup(lambda: subprocess.run(['git', 'worktree', 'remove', '--force', str(sibling)], cwd=self.repo, capture_output=True))
        try:
            for root, suffix in [(self.repo, 'a'), (sibling, 'b')]:
                value = self.input_value()
                value['change'] = suffix
                name = f'openspec/changes/hardening/{suffix}.json'
                (root / name).parent.mkdir(parents=True, exist_ok=True)
                (root / name).write_text(json.dumps(value) + '\n')
                prepared = self.harness('prepare', '--input', name, '--base', self.base,
                                        '--role', 'root', '--gate', '3', cwd=root)
                self.assertEqual(0, prepared.returncode, prepared.stderr)
            state_a = json.loads(self.harness('state', cwd=self.repo).stdout)['active_binding']
            state_b = json.loads(self.harness('state', cwd=sibling).stdout)['active_binding']
            self.assertEqual('openspec/changes/hardening/a.json', state_a['input'])
            self.assertEqual('openspec/changes/hardening/b.json', state_b['input'])
            self.assertNotEqual(state_a['plan'], state_b['plan'])
        finally:
            pass

    def test_roster_consistency_fails_before_ci_and_recovers(self):
        baseline = self.command(sys.executable, 'tools/verification/ci.py', 'verify-roster')
        self.assertEqual(0, baseline.returncode, 'INTENDED_RED public roster check absent: ' + baseline.stderr)
        suite = 'tests/Verification/synthetic_new_e2e_test.py'
        (self.repo / suite).write_text('print("SYNTHETIC_E2E_OK")\n')
        with (self.repo / 'tools/verification/suites.tsv').open('a') as stream:
            stream.write(f'e2e\tpython3\t{suite}\n')
        stale = self.command(sys.executable, 'tools/verification/ci.py', 'verify-roster')
        self.assertNotEqual(0, stale.returncode)
        mapping = json.loads((self.repo / 'tools/verification/categories.json').read_text())
        mapping[suite] = 'e2e'
        (self.repo / 'tools/verification/categories.json').write_text(json.dumps(mapping, sort_keys=True) + '\n')
        fixed = self.command(sys.executable, 'tools/verification/ci.py', 'verify-roster')
        self.assertEqual(0, fixed.returncode, fixed.stderr)
        listed = self.command(sys.executable, 'tools/verification/ci.py', 'list', 'e2e')
        self.assertEqual(1, listed.stdout.splitlines().count(f'python3\t{suite}'))

    def test_runner_wrapper_and_ci_aggregate_form_one_public_chain(self):
        suite = self.acceptance_test
        mini = self.outer / 'mini'; mini.mkdir()
        shutil.copytree(self.repo / 'tools', mini / 'tools')
        shutil.copytree(self.repo / '.quality-graph', mini / '.quality-graph')
        for name in ['tests/InstallationProcess', 'tests/AssignmentOrderComposition',
                     'tests/Verification', 'tests/Otiz', 'tests/Runtime', 'tests/Jobs']:
            (mini / name).mkdir(parents=True)
        (mini / suite).write_text('print("HARDENING_OK")\n')
        (mini / 'tools/verification/suites.tsv').write_text(f'unit\tpython3\t{suite}\n')
        (mini / 'tools/verification/categories.json').write_text(json.dumps({suite: 'unit'}) + '\n')
        subprocess.run(['git', 'init', '-q'], cwd=mini, check=True)
        subprocess.run(['git', 'add', '.'], cwd=mini, check=True)
        subprocess.run(['git', '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid',
                        'commit', '-qm', 'fixture'], cwd=mini, check=True)
        wrapper = self.command(sys.executable, 'tools/verification/ci.py', 'run', 'unit', cwd=mini)
        self.assertEqual(0, wrapper.returncode, wrapper.stdout + wrapper.stderr)
        self.assertIn('CATEGORY_RESULT category=unit tests=1 failures=0', wrapper.stdout)
        wrapper_conclusion = 'success' if wrapper.returncode == 0 else 'failure'
        results = {'plan': 'success', 'fast': 'success', 'unit': wrapper_conclusion,
                   'integration': 'success', 'e2e': 'success', 'governance': 'success'}
        aggregate = self.command(sys.executable, 'tools/verification/ci.py', 'aggregate',
                                 '--full', 'true', '--results', json.dumps(results), cwd=mini)
        self.assertEqual(0, aggregate.returncode, aggregate.stderr)
        self.assertEqual('VERIFY_OK', aggregate.stdout.strip())


if __name__ == '__main__':
    unittest.main(verbosity=2)
