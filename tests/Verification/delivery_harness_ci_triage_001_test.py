"""DETERMINISTIC-KNOWN-CI-TRIAGE-001: public state/wait classification A-N."""
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
HEAD = 'b964901b73d567cf2a98efd41bcb31ef9b7b2a86'
CANDIDATE = 'c' * 64
TRANSIENT_ID = 'pr144-inspection-partial-result-json-v1'
SETUP_ID = 'verification-mariadb-precondition-v1'
SIGNATURE_IDS = [TRANSIENT_ID, SETUP_ID]
TRIAGE_KEYS = {
    'classification', 'known_signature_ids', 'exact', 'signature_id',
    'matched_evidence', 'confidence_basis', 'recommended_action',
    'retry_allowed', 'retry_budget', 'retry_remaining',
    'diagnostic_references', 'measurement'}
EXACT_KEYS = {'repository', 'pr', 'run_id', 'attempt', 'job_id', 'job', 'check',
              'candidate_source', 'head'}
MEASUREMENT_KEYS = {'mandatory_log_payloads_materialized', 'model_triage_steps',
                    'automatic_retry_count', 'token_usage'}


class PublicCiTriage(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix='ci-triage-001-')
        self.addCleanup(self.temp.cleanup)
        self.home = Path(self.temp.name)
        self.env = dict(os.environ, FMONITOR_HARNESS_HOME=str(self.home / 'evidence'),
                        PYTHONDONTWRITEBYTECODE='1')
        self.before = subprocess.check_output(
            ['git', 'status', '--porcelain=v1'], cwd=ROOT, text=True)

    def binding(self, attempt=1, head=HEAD, candidate=CANDIDATE, mode='full'):
        return dict(repository='Antropophag/fmonitor-2', pr=144, head=head,
                    base='0' * 40, candidate=candidate, mode=mode,
                    policy_digest=hashlib.sha256(
                        (ROOT / '.quality-graph/verification-policy.json').read_bytes()).hexdigest(),
                    workflow='.github/workflows/quality-graph.yml',
                    run_id=34933440293, attempt=attempt)

    def observation(self, attempt=1, head=HEAD, candidate=CANDIDATE, mode='full'):
        binding = self.binding(attempt, head, candidate, mode)
        names = ['plan', 'fast', 'harness', 'unit', 'Integration (1/2)',
                 'Integration (2/2)', 'e2e', 'governance', 'verify']
        jobs = []
        for index, name in enumerate(names, 1):
            conclusion = 'SKIPPED' if name == 'harness' else 'SUCCESS'
            if name in {'Integration (2/2)', 'verify'}:
                conclusion = 'FAILURE'
            jobs.append(dict(id=104266277493 + index, name=name, check=name,
                             status='COMPLETED', conclusion=conclusion,
                             binding=copy.deepcopy(binding)))
        target = next(job for job in jobs if job['name'] == 'Integration (2/2)')
        target['id'] = 104266277494
        ci = dict(binding=copy.deepcopy(binding), jobs=jobs,
                  failure_inventory=[{
                      'kind': 'REGRESSION_FAILURE', 'primary': True,
                      'job_id': 104266277494, 'job': 'Integration (2/2)',
                      'check': 'Integration (2/2)',
                      'path': 'tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php',
                      'mode': '--missing-revision'}],
                  diagnostics=[{
                      'source': 'bounded_job_diagnostic', 'job_id': 104266277494,
                      'job': 'Integration (2/2)', 'check': 'Integration (2/2)',
                      'test': 'tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php',
                      'mode': '--missing-revision', 'phase': 'product_verifier',
                      'run_id': binding['run_id'], 'attempt': binding['attempt'],
                      'head': binding['head'], 'candidate_source': binding['candidate'],
                      'exception': 'JsonException', 'message': 'Syntax error',
                      'site': 'worker_result_json_decode', 'line': 21,
                      'parent_failure': 'Mode --missing-revision exit',
                      'materialized_log_payloads': 1}],
                  history=[])
        return dict(binding=binding, current=dict(head=head, base=binding['base']), ci=ci,
                    preflight=dict(outcome='GREEN', failures=[], binding=copy.deepcopy(binding)),
                    reviews=[], authorization=None, enforcement='ENFORCEMENT_NOT_CONFIGURED')

    def evaluate(self, observation, command='state'):
        path = self.home / (command + '-observation.json')
        path.write_text(json.dumps(observation))
        argv = [sys.executable, 'tools/delivery/harness.py', command]
        if command == 'wait':
            argv.append('--once')
        argv += ['--observation', str(path)]
        result = subprocess.run(argv, cwd=ROOT, env=self.env, capture_output=True,
                                text=True, timeout=30)
        self.assertTrue(result.stdout.strip().startswith('{'), result.stderr)
        self.assertEqual('', result.stderr)
        value = json.loads(result.stdout)
        self.assertIn('triage', value['ci'],
                      'INTENDED_RED public state/wait has no deterministic triage result')
        self.assertEqual(self.before, subprocess.check_output(
            ['git', 'status', '--porcelain=v1'], cwd=ROOT, text=True))
        self.assertFalse((self.home / 'evidence').exists(),
                         'observation triage must not persist or rewrite evidence')
        self.assertEqual(TRIAGE_KEYS, set(value['ci']['triage']))
        self.assertEqual(EXACT_KEYS, set(value['ci']['triage']['exact']))
        self.assertEqual(MEASUREMENT_KEYS, set(value['ci']['triage']['measurement']))
        self.assertEqual(SIGNATURE_IDS, value['ci']['triage']['known_signature_ids'])
        self.assertEqual(0 if value['merge_ready'] else 1, result.returncode,
                         'triage must preserve existing admission alias exit semantics')
        return result, value, value['ci']['triage']

    def assert_unknown(self, observation):
        _, _, triage = self.evaluate(observation)
        self.assertEqual('UNKNOWN', triage['classification'])
        self.assertFalse(triage['retry_allowed'])
        self.assertIsNone(triage['signature_id'])
        self.assertEqual([], triage['matched_evidence'])
        self.assertEqual('insufficient_evidence', triage['confidence_basis'])
        self.assertEqual('NORMAL_TRIAGE', triage['recommended_action'])
        self.assertEqual(1, triage['retry_budget'])
        self.assertEqual(0, triage['retry_remaining'])
        self.assertEqual(0, triage['measurement']['model_triage_steps'])
        self.assertEqual(observation['binding']['repository'], triage['exact']['repository'])
        self.assertEqual(observation['binding']['run_id'], triage['exact']['run_id'])
        self.assertEqual(observation['binding']['attempt'], triage['exact']['attempt'])
        self.assertEqual(observation['binding']['head'], triage['exact']['head'])
        self.assertEqual(observation['binding']['candidate'], triage['exact']['candidate_source'])

    def test_a_g_exact_historical_transient_and_public_route_parity(self):
        observation = self.observation()
        state = self.evaluate(observation, 'state')[2]
        wait = self.evaluate(observation, 'wait')[2]
        self.assertEqual(state, wait)
        self.assertEqual(('INFRA_TRANSIENT', TRANSIENT_ID, 'deterministic_signature',
                          'SAME_SOURCE_RETRY', True, 1, 1),
                         (state['classification'], state['signature_id'],
                          state['confidence_basis'], state['recommended_action'],
                          state['retry_allowed'], state['retry_budget'],
                          state['retry_remaining']))
        exact = state['exact']
        self.assertEqual(('Antropophag/fmonitor-2', 144, 34933440293, 1,
                          104266277494, 'Integration (2/2)', 'Integration (2/2)',
                          CANDIDATE, HEAD),
                         tuple(exact[key] for key in ('repository', 'pr', 'run_id',
                               'attempt', 'job_id', 'job', 'check',
                               'candidate_source', 'head')))
        self.assertEqual('DIAGNOSTIC', state['matched_evidence']['evidence_role'])
        self.assertEqual(104266277494, state['diagnostic_references']['job_id'])
        self.assertEqual((1, 0, 0, 'UNKNOWN'),
                         (state['measurement']['mandatory_log_payloads_materialized'],
                          state['measurement']['model_triage_steps'],
                          state['measurement']['automatic_retry_count'],
                          state['measurement']['token_usage']))

    def test_b_c_e_f_similar_and_unknown_neighbors_fail_closed(self):
        other_job = self.observation()
        other_job['ci']['diagnostics'][0].update(job='unit', check='unit')
        self.assert_unknown(other_job)
        product_json = self.observation()
        product_json['ci']['failure_inventory'][0].update(
            path='tests/Product/export_json_test.php', mode='--export')
        product_json['ci']['diagnostics'][0].update(
            test='tests/Product/export_json_test.php', mode='--export',
            site='product_output_decode', parent_failure='Export failed')
        self.assert_unknown(product_json)
        another_product_failure = self.observation()
        another_product_failure['ci']['failure_inventory'].append({
            'kind': 'REGRESSION_FAILURE', 'primary': True,
            'job_id': 104266277494, 'job': 'Integration (2/2)',
            'check': 'Integration (2/2)', 'path': 'tests/Product/other_test.php'})
        self.assert_unknown(another_product_failure)
        similar_setup = self.observation()
        similar_setup['ci']['diagnostics'] = [{
            'source': 'bounded_job_diagnostic', 'job_id': 104266277494,
            'job': 'Integration (2/2)', 'check': 'Integration (2/2)',
            'phase': 'product_verifier', 'before_first_test': False,
            'message': 'test MariaDB unavailable; run make test-db-reset migrate',
            'materialized_log_payloads': 1}]
        self.assert_unknown(similar_setup)
        for label, mutate in {
                'generic-json': lambda value: value['ci']['diagnostics'][0].update(
                    exception='JsonException', site='another_decode'),
                'timeout': lambda value: value['ci']['diagnostics'][0].update(message='timeout'),
                'connection-reset': lambda value: value['ci']['diagnostics'][0].update(message='connection reset'),
                'exit-one': lambda value: value['ci']['diagnostics'][0].update(message='exit 1'),
                'flaky': lambda value: value['ci']['diagnostics'][0].update(message='flaky test'),
                'generic-setup': lambda value: value['ci']['diagnostics'][0].update(
                    message='SETUP_FAILURE', phase='product_verifier'),
                'product-mariadb': lambda value: value['ci']['diagnostics'][0].update(
                    message='MariaDB unavailable', phase='product_verifier'),
                'invented-third-signature': lambda value: value['ci']['diagnostics'][0].update(
                    signature_id='invented-third-signature-v1'),
                'missing-diagnostics': lambda value: value['ci'].pop('diagnostics'),
                'missing-inventory': lambda value: value['ci'].pop('failure_inventory'),
                'malformed-diagnostics': lambda value: value['ci'].update(diagnostics='bad'),
                'malformed-inventory': lambda value: value['ci'].update(failure_inventory={}),
                'contradictory-job': lambda value: value['ci']['failure_inventory'][0].update(
                    job_id=999),
                'unavailable-diagnostic': lambda value: value['ci'].update(
                    diagnostics_unavailable=True, diagnostics=[]),
                'stale-diagnostic': lambda value: value['ci']['diagnostics'][0].update(
                    head='f' * 40)}.items():
            with self.subTest(label=label):
                neighbor = self.observation()
                mutate(neighbor)
                self.assert_unknown(neighbor)

    def test_d_exact_setup_precondition(self):
        for job_name in ('Integration (2/2)', 'e2e'):
            with self.subTest(job=job_name):
                observation = self.observation()
                target = next(job for job in observation['ci']['jobs']
                              if job['name'] == job_name)
                for job in observation['ci']['jobs']:
                    if job['name'] == 'verify':
                        job['conclusion'] = 'FAILURE'
                    elif job['name'] == 'harness':
                        job['conclusion'] = 'SKIPPED'
                    else:
                        job['conclusion'] = 'SUCCESS'
                target['conclusion'] = 'FAILURE'
                observation['ci']['failure_inventory'] = []
                observation['ci']['diagnostics'] = [{
                    'source': 'bounded_job_diagnostic', 'job_id': target['id'],
                    'job': job_name, 'check': job_name,
                    'phase': 'category_preflight', 'before_first_test': True,
                    'run_id': observation['binding']['run_id'],
                    'attempt': observation['binding']['attempt'],
                    'head': observation['binding']['head'],
                    'candidate_source': observation['binding']['candidate'],
                    'message': 'test MariaDB unavailable; run make test-db-reset migrate',
                    'materialized_log_payloads': 1}]
                triage = self.evaluate(observation)[2]
                self.assertEqual(('SETUP_FAILURE', SETUP_ID,
                                  'RUN_EXISTING_DB_PREFLIGHT', False),
                                 (triage['classification'], triage['signature_id'],
                                  triage['recommended_action'], triage['retry_allowed']))
                self.assertEqual(1, triage['measurement']['mandatory_log_payloads_materialized'])
                self.assertEqual('DIAGNOSTIC', triage['matched_evidence']['evidence_role'])
                self.assertEqual((target['id'], job_name, job_name),
                                 tuple(triage['exact'][key]
                                       for key in ('job_id', 'job', 'check')))

    def test_h_i_second_attempt_and_source_drift_cannot_retry(self):
        second = self.observation(attempt=2)
        self.assert_unknown(second)
        drift = self.observation()
        drift['current']['head'] = 'd' * 40
        self.assert_unknown(drift)
        candidate_drift = self.observation()
        candidate_drift['ci']['binding']['candidate'] = 'e' * 64
        self.assert_unknown(candidate_drift)

    def test_j_k_history_survives_successful_same_source_retry(self):
        observation = self.observation(attempt=2)
        for job in observation['ci']['jobs']:
            if job['name'] == 'harness':
                job['conclusion'] = 'SKIPPED'
            else:
                job['conclusion'] = 'SUCCESS'
        observation['ci']['failure_inventory'] = []
        observation['ci']['diagnostics'] = []
        observation['ci']['history'] = [{
            'run_id': 34933440293, 'attempt': 1, 'job_id': 104266277494,
            'check': 'Integration (2/2)', 'head': HEAD,
            'classification': 'INFRA_TRANSIENT', 'signature_id': TRANSIENT_ID}]
        _, value, triage = self.evaluate(observation)
        self.assertEqual('SUCCESS', value['ci']['status'])
        self.assertFalse(triage['retry_allowed'])
        expected_history = observation['ci']['history']
        self.assertEqual(expected_history, triage['diagnostic_references']['history'])
        hostile = copy.deepcopy(observation)
        hostile['ci']['history'][0]['head'] = 'f' * 40
        _, hostile_value, hostile_triage = self.evaluate(hostile)
        self.assertEqual('SUCCESS', hostile_value['ci']['status'])
        self.assertEqual([], hostile_triage['diagnostic_references']['history'])
        self.assertFalse(hostile_triage['retry_allowed'])

    def test_l_m_diagnostic_is_not_green_and_binding_mismatch_fails_closed(self):
        observation = self.observation()
        observation['ci']['diagnostics'][0]['outcome'] = 'GREEN'
        _, value, triage = self.evaluate(observation)
        self.assertEqual('FAILURE', value['ci']['status'])
        self.assertEqual('DIAGNOSTIC', triage['matched_evidence']['evidence_role'])
        mismatch = self.observation()
        mismatch['ci']['jobs'][-1]['binding']['run_id'] = 9
        self.assert_unknown(mismatch)

    def test_repeat_is_deterministic_and_read_only(self):
        observation = self.observation()
        first = self.evaluate(observation)[2]
        second = self.evaluate(copy.deepcopy(observation))[2]
        self.assertEqual(first, second)

    def test_n_fast_and_standard_use_same_public_schema(self):
        keys = None
        for mode in ('full', 'fast'):
            observation = self.observation(mode=mode)
            triage = self.evaluate(observation)[2]
            keys = set(triage) if keys is None else keys
            self.assertEqual(keys, set(triage))
            self.assertEqual(TRANSIENT_ID, triage['signature_id'])

    def test_native_collector_retains_every_regression_before_classification(self):
        repo = self.home / 'native-repo'
        repo.mkdir()
        for name in ('tools', '.quality-graph'):
            shutil.copytree(ROOT / name, repo / name)
        (repo / '.github/workflows').mkdir(parents=True)
        shutil.copy2(ROOT / '.github/workflows/quality-graph.yml',
                     repo / '.github/workflows/quality-graph.yml')
        subprocess.run(['git', 'init', '-q'], cwd=repo, check=True)
        subprocess.run(['git', 'config', 'user.email', 'fixture@example.invalid'],
                       cwd=repo, check=True)
        subprocess.run(['git', 'config', 'user.name', 'Fixture'], cwd=repo, check=True)
        subprocess.run(['git', 'add', '.'], cwd=repo, check=True)
        subprocess.run(['git', 'commit', '-qm', 'fixture'], cwd=repo, check=True)
        head = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=repo, text=True).strip()
        jobs = []
        for index, name in enumerate(('plan', 'fast', 'harness', 'unit',
                                      'Integration (1/2)', 'Integration (2/2)',
                                      'e2e', 'governance', 'verify'), 1):
            conclusion = 'skipped' if name == 'harness' else 'success'
            if name in ('Integration (2/2)', 'verify'):
                conclusion = 'failure'
            jobs.append({'id': 7000 + index, 'name': name, 'status': 'completed',
                         'conclusion': conclusion, 'head_sha': head,
                         'run_id': 34933440293, 'run_attempt': 1})
        log = '\n'.join((
            'Mode --missing-revision exit; PHP Fatal error: Uncaught JsonException: Syntax error',
            '#0 /repo/tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php(21): json_decode()',
            'thrown in /repo/tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php on line 21',
            'Integration (2/2)\tUNKNOWN STEP\t2026-09-15T05:43:44.0000000Z REGRESSION_FAILURE: tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php',
            'Integration (2/2)\tUNKNOWN STEP\t2026-09-15T05:43:44.1000000Z REGRESSION_FAILURE: tests/Product/second_regression_test.php'))
        bindir = self.home / 'bin'
        bindir.mkdir()
        gh = bindir / 'gh'
        gh.write_text('#!/usr/bin/env python3\n' +
            'import json,sys\n'
            f'head={head!r}; jobs={jobs!r}; log={log!r}\n'
            'a=sys.argv[1:]\n'
            'if a[:2]==["repo","view"]: print(json.dumps({"nameWithOwner":"Antropophag/fmonitor-2"}))\n'
            'elif a[:2]==["pr","view"]: print(json.dumps({"number":144,"state":"OPEN","headRefOid":head,"baseRefOid":head,"url":"fixture","statusCheckRollup":[]}))\n'
            'elif a[:2]==["run","list"]: print(json.dumps([{"databaseId":34933440293,"headSha":head,"status":"completed","conclusion":"failure","attempt":1,"workflowName":"Quality Graph","event":"pull_request"}]))\n'
            'elif a[:2]==["run","view"]: print(log)\n'
            'elif a and a[0]=="api" and a[1].endswith("/attempts/1/jobs"): print(json.dumps({"jobs":jobs}))\n'
            'elif a and a[0]=="api": print(json.dumps({"id":34933440293,"run_attempt":1,"head_sha":head,"path":".github/workflows/quality-graph.yml","event":"pull_request","pull_requests":[{"number":144,"head":{"sha":head},"base":{"sha":head}}]}))\n'
            'else: raise SystemExit("unexpected gh args: "+repr(a))\n')
        gh.chmod(0o700)
        env = dict(self.env, PATH=str(bindir) + os.pathsep + os.environ['PATH'])
        result = subprocess.run([sys.executable, 'tools/delivery/harness.py', 'state'],
                                cwd=repo, env=env, capture_output=True, text=True, timeout=30)
        self.assertEqual(0, result.returncode, result.stderr)
        value = json.loads(result.stdout)
        triage = value['ci']['triage']
        self.assertEqual(('UNKNOWN', None, False),
                         (triage['classification'], triage['signature_id'],
                          triage['retry_allowed']))
        references = triage['diagnostic_references']
        self.assertEqual([
            {'job_id': 7006, 'job': 'Integration (2/2)',
             'check': 'Integration (2/2)', 'conclusion': 'FAILURE'},
            {'job_id': 7009, 'job': 'verify',
             'check': 'verify', 'conclusion': 'FAILURE'},
        ], references['failed_job_inventory'])
        self.assertEqual([
            {'kind': 'REGRESSION_FAILURE', 'primary': True,
             'job_id': 7006, 'job': 'Integration (2/2)',
             'check': 'Integration (2/2)',
             'path': 'tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php',
             'mode': '--missing-revision'},
            {'kind': 'REGRESSION_FAILURE', 'primary': True,
             'job_id': 7006, 'job': 'Integration (2/2)',
             'check': 'Integration (2/2)',
             'path': 'tests/Product/second_regression_test.php',
             'mode': None},
        ], references['regression_failure_inventory'])


if __name__ == '__main__':
    unittest.main()
