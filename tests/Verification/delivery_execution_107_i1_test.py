"""DELIVERY-EXECUTION-107 / AC01-I1: public CLI admission and retained truth.

API observations are isolated fixtures, never evidence of GitHub enforcement.
Expected jobs come from the owner's full/harness contract, not the evaluator.
"""
import copy
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
JOBS = ['plan', 'fast', 'harness', 'unit', 'Integration (1/2)',
        'Integration (2/2)', 'e2e', 'governance', 'verify']
NATIVE_JOBS = json.loads((ROOT / 'tests/Verification/fixtures/delivery107-ci-jobs.json').read_text())


class Admission107(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='delivery-107-')
        self.addCleanup(self.temp.cleanup)
        self.home = Path(self.temp.name)
        self.env = dict(os.environ, FMONITOR_HARNESS_HOME=str(self.home / 'evidence'),
                        PYTHONDONTWRITEBYTECODE='1')

    def observation(self, mode='full'):
        binding = dict(repository='Antropophag/fmonitor-2', pr=107,
                       head='a' * 40, base='b' * 40, candidate='c' * 64,
                       mode=mode, policy_digest=hashlib.sha256(
                           (ROOT / '.quality-graph/verification-policy.json').read_bytes()).hexdigest(),
                       workflow='.github/workflows/quality-graph.yml', run_id=1007, attempt=2)
        selected = ({'plan', 'harness', 'verify'} if mode == 'harness' else
                    {'plan', 'fast', 'verify'} if mode == 'docs' else set(JOBS) - {'harness'})
        names = (JOBS if mode == 'full' else
                 [name for name in JOBS if not name.startswith('Integration (')] +
                 ['Integration (${{ matrix.shard }}/2)'])
        return dict(binding=binding, current=dict(head=binding['head'], base=binding['base']),
                    ci=dict(binding=copy.deepcopy(binding), jobs=[
                        dict(name=name, status='COMPLETED', conclusion=(
                            'SUCCESS' if name in selected else 'SKIPPED'), binding=copy.deepcopy(binding))
                        for name in names]),
                    preflight=dict(outcome='GREEN', failures=[], binding=copy.deepcopy(binding)),
                    reviews=[dict(gate=gate, verdict='APPROVED', author='root' if gate == 3 else 'executor',
                                  reviewer='independent-reviewer', binding=copy.deepcopy(binding))
                             for gate in (3, 5)], authorization=None,
                    enforcement='ENFORCEMENT_NOT_CONFIGURED')

    def cli(self, *args, root=ROOT, stdin=None):
        return subprocess.run([sys.executable, str(root / 'tools/delivery/harness.py'), *args],
                              cwd=root, env=self.env, input=stdin, capture_output=True, text=True, timeout=30)

    def evaluate(self, observation, command='admission'):
        path = self.home / 'observation.json'
        path.write_text(json.dumps(observation))
        response = self.cli(command, '--observation', str(path))
        self.assertTrue(response.stdout.strip().startswith('{'),
                        'INTENDED_RED admission public CLI is absent: ' + response.stderr)
        value = json.loads(response.stdout)
        fields = ('ci', 'publication_ready', 'merge_ready', 'action_authorized', 'enforcement', 'reasons')
        if command == 'admission':
            for alias in ('state', 'wait', 'prepare-merge'):
                other = self.cli(alias, '--observation', str(path))
                self.assertEqual(response.returncode, other.returncode, alias)
                result = json.loads(other.stdout)
                self.assertEqual({key: value[key] for key in fields},
                                 {key: result[key] for key in fields}, alias)
        if not value['merge_ready']:
            self.assertNotEqual(0, response.returncode)
            self.assertTrue(value['reasons'])
        return response, value

    def test_positive_modes_expected_skips_and_authorization_separation(self):
        for mode in ('full', 'harness', 'docs'):
            with self.subTest(mode=mode):
                response, result = self.evaluate(self.observation(mode))
                self.assertEqual(0, response.returncode, result)
                self.assertEqual('SUCCESS', result['ci']['status'])
                self.assertTrue(result['publication_ready'])
                self.assertTrue(result['merge_ready'])
                self.assertFalse(result['action_authorized'])
                self.assertEqual('ENFORCEMENT_NOT_CONFIGURED', result['enforcement'])

    def test_exact_owner_authorization_exception_and_enforcement(self):
        healthy = self.observation()
        binding = healthy['binding']
        authorization = dict(actor='owner', action='merge', head=binding['head'],
                             policy_digest=binding['policy_digest'], mode='owner-controlled')
        healthy['authorization'] = authorization
        self.assertTrue(self.evaluate(healthy)[1]['action_authorized'])
        automatic = copy.deepcopy(healthy)
        automatic['authorization']['mode'] = 'autonomous'
        self.assertFalse(self.evaluate(automatic)[1]['action_authorized'])
        for field in ('head', 'policy_digest', 'action', 'actor'):
            bad = copy.deepcopy(healthy)
            bad['authorization'][field] = 'wrong'
            self.assertFalse(self.evaluate(bad)[1]['action_authorized'], field)
        blocked = copy.deepcopy(healthy)
        blocked['preflight'].update(outcome='BLOCKED', failures=[{'code': 'DEPENDENCY_UNAVAILABLE'}])
        exception = dict(actor='owner', action='merge', head=binding['head'],
                         policy_digest=binding['policy_digest'], reasons=['DEPENDENCY_UNAVAILABLE'])
        blocked['owner_exception'] = exception
        allowed = self.evaluate(blocked)[1]
        self.assertTrue(allowed['merge_ready'])
        self.assertIn('DEPENDENCY_UNAVAILABLE', json.dumps(allowed['original_failures']))
        for field in ('head', 'policy_digest', 'action', 'actor', 'reasons'):
            bad = copy.deepcopy(blocked)
            bad['owner_exception'][field] = ['wrong'] if field == 'reasons' else 'wrong'
            self.assertFalse(self.evaluate(bad)[1]['merge_ready'], field)

    def test_publisher_extras_do_not_replace_or_block_required_jobs(self):
        value = self.observation()
        value['ci']['jobs'].append(dict(name='quality-results', status='COMPLETED',
                                        conclusion='SUCCESS', binding=copy.deepcopy(value['binding'])))
        response, result = self.evaluate(value)
        self.assertTrue(result['merge_ready'],
                        'INTENDED_RED benign publisher job falsely blocks selected matrix')
        value['ci']['jobs'] = [job for job in value['ci']['jobs'] if job['name'] != 'verify']
        self.assertFalse(self.evaluate(value)[1]['merge_ready'])

    def test_real_github_job_payloads_preserve_selected_matrix_shape(self):
        # Public primary evidence: PR103 full and PR106 harness native job inventories.
        for mode, corpus in NATIVE_JOBS.items():
            with self.subTest(mode=mode):
                value = self.observation(mode)
                value['ci']['jobs'] = [dict(name=job['name'], status=job['status'].upper(),
                                           conclusion=job['conclusion'].upper(),
                                           binding=copy.deepcopy(value['binding'])) for job in corpus['jobs']]
                result = self.evaluate(value)[1]
                self.assertTrue(result['merge_ready'],
                                'INTENDED_RED healthy native GitHub skipped matrix shape rejected')
                value['ci']['jobs'] = [job for job in value['ci']['jobs'] if job['name'] != 'verify']
                self.assertFalse(self.evaluate(value)[1]['merge_ready'])

    def test_unrecognized_enforcement_does_not_authorize_autonomous_action(self):
        value = self.observation()
        value['authorization'] = dict(actor='owner', action='merge', head=value['binding']['head'],
                                       policy_digest=value['binding']['policy_digest'], mode='autonomous')
        for enforcement in ('UNKNOWN', 'unverified', True, {'configured': True}):
            value['enforcement'] = enforcement
            result = self.evaluate(value)[1]
            self.assertFalse(result['action_authorized'],
                             'INTENDED_RED unrecognized enforcement authorizes autonomous action')
            self.assertEqual('ENFORCEMENT_NOT_CONFIGURED', result['enforcement'],
                             'INTENDED_RED unknown enforcement not normalized')

    def test_merge_exception_cannot_waive_publication_failure(self):
        value = self.observation()
        value['preflight'].update(outcome='BLOCKED', failures=[{'code': 'DEPENDENCY_UNAVAILABLE'}])
        value['owner_exception'] = dict(actor='owner', action='merge', head=value['binding']['head'],
                                        policy_digest=value['binding']['policy_digest'],
                                        reasons=['DEPENDENCY_UNAVAILABLE'])
        result = self.evaluate(value)[1]
        self.assertTrue(result['merge_ready'])
        self.assertFalse(result['publication_ready'],
                         'INTENDED_RED merge-only exception authorizes publication readiness')

    def test_every_missing_skipped_failed_cancelled_or_pending_required_job_blocks(self):
        for mode in ('full', 'harness', 'docs'):
            healthy = self.observation(mode)
            for job in healthy['ci']['jobs']:
                defects = (('missing', 'SUCCESS', 'FAILURE', 'CANCELLED', 'PENDING')
                           if job['conclusion'] == 'SKIPPED' else
                           ('missing', 'SKIPPED', 'FAILURE', 'CANCELLED', 'PENDING'))
                for defect in defects:
                    with self.subTest(mode=mode, job=job['name'], defect=defect):
                        bad = copy.deepcopy(healthy)
                        target = next(x for x in bad['ci']['jobs'] if x['name'] == job['name'])
                        if defect == 'missing':
                            bad['ci']['jobs'].remove(target)
                        elif defect == 'PENDING':
                            target.update(status='IN_PROGRESS', conclusion=None)
                        else:
                            target['conclusion'] = defect
                        response, result = self.evaluate(bad)
                        self.assertNotEqual(0, response.returncode)
                        self.assertFalse(result['merge_ready'])
                        self.assertNotEqual('SUCCESS', result['ci']['status'])
                        self.assertTrue(result['reasons'])

    def test_exact_binding_provenance_and_duplicate_names(self):
        for field in ('repository', 'pr', 'head', 'base', 'candidate', 'mode',
                      'policy_digest', 'workflow', 'run_id', 'attempt'):
            for target in ('ci', 'job', 'preflight', 'review'):
                with self.subTest(field=field, target=target):
                    bad = self.observation()
                    binding = (bad['ci']['jobs'][0] if target == 'job' else
                               bad['reviews'][0] if target == 'review' else bad[target])['binding']
                    binding[field] = 'foreign-or-stale'
                    response, result = self.evaluate(bad)
                    self.assertNotEqual(0, response.returncode)
                    self.assertFalse(result['merge_ready'])
        bad = self.observation()
        bad['ci']['jobs'].append(copy.deepcopy(bad['ci']['jobs'][0]))
        self.assertFalse(self.evaluate(bad)[1]['merge_ready'])

    def test_candidate_cannot_choose_empty_policy_or_fake_mode(self):
        for change in ('empty', 'mode', 'policy'):
            bad = self.observation()
            if change == 'empty':
                bad['expected_checks'] = []
                bad['ci']['jobs'] = []
            elif change == 'mode':
                bad['binding']['mode'] = 'trust-me'
            else:
                bad['binding']['policy_digest'] = '0' * 64
            response, result = self.evaluate(bad)
            self.assertNotEqual(0, response.returncode)
            self.assertFalse(result['merge_ready'])

    def test_binding_requires_real_typed_exact_identities(self):
        cases = [('repository', ''), ('repository', []), ('pr', 0), ('pr', True),
                 ('head', 'not-a-sha'), ('base', 123), ('candidate', 'c' * 40),
                 ('run_id', 0), ('attempt', -1), ('attempt', True)]
        for field, invalid in cases:
            with self.subTest(field=field, invalid=invalid):
                value = self.observation()
                value['binding'][field] = invalid
                value['ci']['binding'] = copy.deepcopy(value['binding'])
                for item in value['ci']['jobs'] + value['reviews'] + [value['preflight']]:
                    item['binding'] = copy.deepcopy(value['binding'])
                if field in ('head', 'base'):
                    value['current'][field] = invalid
                result = self.evaluate(value)[1]
                self.assertFalse(result['merge_ready'], 'INTENDED_RED malformed binding admitted')
                self.assertNotEqual('SUCCESS', result['ci']['status'])

    def test_preflight_reviews_and_head_base_race_block_consistently(self):
        for defect in ('preflight', 'missing_review', 'self_review', 'verdict', 'head', 'base'):
            bad = self.observation()
            if defect == 'preflight':
                bad['preflight'].update(outcome='BLOCKED', failures=[{'code': 'SETUP_FAILURE'}])
            elif defect == 'missing_review':
                bad['reviews'].pop()
            elif defect == 'self_review':
                bad['reviews'][0]['reviewer'] = bad['reviews'][0]['author']
            elif defect == 'verdict':
                bad['reviews'][1]['verdict'] = 'CHANGES_REQUESTED'
            else:
                bad['current'][defect] = 'd' * 40
            results = [self.evaluate(bad, command)[1] for command in
                       ('admission', 'state', 'wait', 'prepare-merge')]
            self.assertTrue(all(not x['merge_ready'] for x in results), defect)
            self.assertTrue(all(x['reasons'] == results[0]['reasons'] for x in results), defect)

    def test_unknown_and_unscoped_exception_never_approve(self):
        for change in ('unknown', 'exception'):
            bad = self.observation()
            bad['preflight'].update(outcome='BLOCKED', failures=[{'code': 'DEPENDENCY_UNAVAILABLE'}])
            if change == 'unknown':
                bad['reviews'][1]['verdict'] = 'UNKNOWN'
            else:
                bad['owner_exception'] = {'approved': True}
            result = self.evaluate(bad)[1]
            self.assertFalse(result['publication_ready'])
            self.assertFalse(result['action_authorized'])

    def fixture_repo(self):
        repo = self.home / 'repo'
        shutil.copytree(ROOT / 'tools/delivery', repo / 'tools/delivery')
        (repo / 'case.py').write_text('print("baseline")\n')
        for args in (['init', '-q'], ['config', 'user.email', 'fixture@example.invalid'],
                     ['config', 'user.name', 'Fixture'], ['add', '.'], ['commit', '-qm', 'fixture']):
            subprocess.run(['git', *args], cwd=repo, check=True, capture_output=True)
        return repo

    def test_applicable_truth_and_candidate_scoped_report(self):
        repo = self.fixture_repo()
        records = []
        for index in range(2):
            response = self.cli('run', '--task', '107', '--run-id', 'scope', '--',
                                sys.executable, '-c', 'print("ok")', root=repo)
            self.assertTrue(response.stdout.startswith('{'), 'INTENDED_RED task runner absent')
            record = json.loads(Path(json.loads(response.stdout)['record_path']).read_text())
            records.append(record)
            self.assertEqual('APPLICABLE', record['applicability'])
            self.assertTrue(record['applicability_reason'])
            (repo / 'case.py').write_text('print(' + str(index) + ')\n')
        report = self.cli('report', '--task', '107', '--run-id', 'scope',
                          '--candidate', records[0]['source'], root=repo)
        self.assertEqual(1, json.loads(report.stdout)['checks'])
        response = self.cli('run', '--', sys.executable, '-c', 'print("UNKNOWN: fixture identity")', root=repo)
        record = json.loads(Path(json.loads(response.stdout)['record_path']).read_text())
        self.assertEqual('UNKNOWN', record['command_verdict'])
        self.assertEqual('UNKNOWN', record['applicability'])
        self.assertTrue(record['applicability_reason'])

    def test_raw_verdict_survives_drift_for_both_exit_values(self):
        repo = self.fixture_repo()
        for code, expected in ((255, 'REGRESSION_FAILURE'), (0, 'GREEN')):
            script = ('from pathlib import Path; import sys; '
                      'Path("case.py").write_text("changed-' + str(code) + '\\n"); '
                      'print("retained witness"); sys.exit(' + str(code) + ')')
            response = self.cli('run', '--', sys.executable, '-c', script, root=repo)
            record = json.loads(Path(json.loads(response.stdout)['record_path']).read_text())
            self.assertEqual(expected, record.get('command_verdict'),
                             'INTENDED_RED drift erases command verdict')
            self.assertEqual(code, record['raw_child_returncode'])
            self.assertEqual('STALE', record['applicability'])
            self.assertTrue(record['applicability_reason'])
            self.assertIn('retained witness', Path(record['stdout_path']).read_text())

    def test_diagnostic_is_not_green_check_and_report_is_task_scoped(self):
        repo = self.fixture_repo()
        for task, purpose in (('107', 'diagnostic'), ('other', 'acceptance')):
            response = self.cli('run', '--task', task, '--run-id', 'i1', '--command-id', task,
                                '--purpose', purpose, '--', sys.executable, '-c', 'print("ok")', root=repo)
            self.assertTrue(response.stdout.startswith('{'),
                            'INTENDED_RED scoped diagnostic runner is absent: ' + response.stderr)
            record = json.loads(Path(json.loads(response.stdout)['record_path']).read_text())
            if purpose == 'diagnostic':
                self.assertEqual('DIAGNOSTIC', record['command_verdict'])
                self.assertNotEqual('GREEN', record['outcome'])
        report = self.cli('report', '--task', '107', '--run-id', 'i1', root=repo)
        self.assertEqual(0, report.returncode, report.stderr)
        result = json.loads(report.stdout)
        self.assertEqual(0, result['checks'])
        self.assertEqual(1, result['diagnostics'])
        self.assertEqual('UNKNOWN', result['token_telemetry']['status'])

    def test_live_github_adapter_requires_exact_run_and_complete_jobs(self):
        repo = self.fixture_repo()
        for name in ('.quality-graph', '.github/workflows', 'tools/verification'):
            shutil.copytree(ROOT / name, repo / name, dirs_exist_ok=True)
        subprocess.run(['git', 'add', '.'], cwd=repo, check=True)
        subprocess.run(['git', 'commit', '-qm', 'live fixture'], cwd=repo, check=True)
        head = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=repo, text=True).strip()
        base = subprocess.check_output(['git', 'rev-parse', 'HEAD^'], cwd=repo, text=True).strip()
        bin_dir = self.home / 'bin'
        bin_dir.mkdir()
        path = self.home / 'github.json'
        native = dict(repository={'nameWithOwner': 'fixture/repo'},
                      pr=dict(number=107, state='OPEN', headRefOid=head, baseRefOid=base,
                              url='https://github.com/fixture/repo/pull/107', statusCheckRollup=[
                                  dict(name='verify', status='COMPLETED', conclusion='SUCCESS')]),
                      runs=[dict(databaseId=1007, headSha=head, status='completed',
                                 conclusion='success', attempt=2, workflowName='Quality Graph', event='pull_request')],
                      run=dict(id=1007, run_attempt=2, head_sha=head, status='completed',
                               conclusion='success', path='.github/workflows/quality-graph.yml', event='pull_request',
                               pull_requests=[{'number': 107, 'head': {'sha': head}, 'base': {'sha': base}}]),
                      jobs={'total_count': len(JOBS), 'jobs': [dict(
                          id=i+1, name=name, status='completed',
                          conclusion='skipped' if name == 'harness' else 'success', head_sha=head,
                          run_id=1007, run_attempt=2) for i, name in enumerate(JOBS)]})
        path.write_text(json.dumps(native))
        gh = bin_dir / 'gh'
        gh.write_text('#!' + sys.executable + '\nimport json,sys\n'
                      'd=json.load(open(' + repr(str(path)) + ')); a=" ".join(sys.argv[1:])\n'
                      'key="repository" if a.startswith("repo view") else "pr" if a.startswith("pr view") else '
                      '"runs" if a.startswith("run list") else "jobs" if "/jobs" in a else "run"\n'
                      'print(json.dumps(d[key]))\n')
        gh.chmod(0o700)
        self.env['PATH'] = str(bin_dir) + os.pathsep + os.environ['PATH']
        for defect in ('healthy', 'missing', 'attempt', 'base', 'foreign-head',
                       'wrong-pr', 'missing-pr', 'push-run', 'manual-run',
                       'stale-run-attempt', 'wrong-run-id', 'wrong-workflow'):
            with self.subTest(defect=defect):
                data = copy.deepcopy(native)
                if defect == 'missing':
                    data['jobs']['jobs'].pop()
                    data['jobs']['total_count'] -= 1
                elif defect == 'attempt':
                    data['jobs']['jobs'][0]['run_attempt'] = 1
                elif defect == 'base':
                    data['run']['pull_requests'][0]['base']['sha'] = 'd' * 40
                elif defect == 'foreign-head':
                    data['run']['head_sha'] = 'd' * 40
                elif defect == 'wrong-pr':
                    data['run']['pull_requests'][0]['number'] = 999
                elif defect == 'missing-pr':
                    data['run']['pull_requests'] = []
                elif defect in ('push-run', 'manual-run'):
                    event = 'push' if defect == 'push-run' else 'workflow_dispatch'
                    data['run']['event'] = event
                    data['runs'][0]['event'] = event
                elif defect == 'stale-run-attempt':
                    data['run']['run_attempt'] = 1
                    for job in data['jobs']['jobs']:
                        job['run_attempt'] = 1
                elif defect == 'wrong-run-id':
                    data['run']['id'] = 1008
                    for job in data['jobs']['jobs']:
                        job['run_id'] = 1008
                elif defect == 'wrong-workflow':
                    data['run']['path'] = '.github/workflows/unrelated.yml'
                path.write_text(json.dumps(data))
                for command in ('state', 'wait', 'prepare-merge'):
                    response = self.cli(command, *(['--once'] if command == 'wait' else []), root=repo)
                    self.assertTrue(response.stdout.startswith('{'),
                                    'INTENDED_RED live adapter absent: ' + response.stderr)
                    state = json.loads(response.stdout)
                    if command == 'state':
                        self.assertEqual(0, response.returncode, response.stderr)
                    if defect == 'healthy':
                        self.assertEqual('SUCCESS', state['ci']['status'],
                                         'INTENDED_RED live state lacks exact policy-aware CI adapter')
                    else:
                        self.assertNotEqual('SUCCESS', state['ci']['status'],
                                            'INTENDED_RED unbound native run accepted: ' + defect)
                    self.assertFalse(state['merge_ready'])  # no locally approved review/preflight
                    self.assertFalse(state['action_authorized'])
                    self.assertTrue(state['reasons'])
                hook = self.cli('hook', root=repo, stdin=json.dumps(dict(
                    hook_event_name='SessionStart', session_id='ci-' + defect, cwd=str(repo))))
                self.assertEqual(0, hook.returncode, hook.stderr)
                context = json.loads(hook.stdout)['hookSpecificOutput']['additionalContext']
                if defect == 'healthy':
                    self.assertIn('CI=SUCCESS;', context)
                else:
                    self.assertNotIn('CI=SUCCESS;', context,
                                     'INTENDED_RED hook still uses weak rollup instead of shared admission')

    def test_report_scopes_hook_events_and_does_not_attribute_unknown_events(self):
        repo = self.fixture_repo()
        for task, candidate, payload in (('107', 'a' * 64, 'abc'),
                                         ('107', 'b' * 64, 'longer'), ('other', 'a' * 64, 'foreign')):
            for name in ('PostToolUse', 'SubagentStart', 'SubagentStop'):
                event = dict(hook_event_name=name, session_id=task + candidate + name,
                             cwd=str(repo), task=task, run_id='events', candidate=candidate,
                             tool_use_id='tool', tool_name='Bash', tool_response=payload,
                             turn_id='turn', agent_id='agent', agent_type='reviewer',
                             last_assistant_message='CHANGES_REQUESTED')
                result = self.cli('hook', root=repo, stdin=json.dumps(event))
                self.assertEqual(0, result.returncode, result.stderr)
        unknown = dict(hook_event_name='PostToolUse', session_id='unknown', cwd=str(repo),
                       tool_use_id='unknown', tool_name='Bash', tool_response='unscoped')
        self.cli('hook', root=repo, stdin=json.dumps(unknown))
        result = self.cli('report', '--task', '107', '--run-id', 'events', '--candidate', 'a' * 64, root=repo)
        self.assertEqual(0, result.returncode, result.stderr)
        value = json.loads(result.stdout)
        for key in ('tool_calls', 'agent_tasks', 'review_returns'):
            self.assertEqual(1, value[key], 'INTENDED_RED event aggregates leak scope: ' + key)
        self.assertEqual(3, value['observed_tool_output_bytes'])

    def test_fixture_witnesses_are_independently_valid(self):
        # Runs before production exists: reach every observation shape without a fake evaluator.
        for mode in ('full', 'harness', 'docs'):
            value = self.observation(mode)
            self.assertEqual(9 if mode == 'full' else 8, len({x['name'] for x in value['ci']['jobs']}))
            expected = 8 if mode == 'full' else 3
            self.assertEqual(expected, sum(x['conclusion'] == 'SUCCESS' for x in value['ci']['jobs']))
            damaged = copy.deepcopy(value)
            damaged['ci']['jobs'][0]['binding']['head'] = 'wrong'
            self.assertNotEqual(damaged['ci']['jobs'][0]['binding'], value['binding'])
            self.assertEqual(value['ci']['jobs'][0]['binding'], value['binding'])


if __name__ == '__main__':
    unittest.main()
