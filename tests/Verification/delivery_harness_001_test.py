"""DELIVERY-HARNESS-001: observable CLI behavior in disposable repositories."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


class Harness(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix='delivery-contract-')
        self.addCleanup(self.tmp.cleanup)
        self.outer = Path(self.tmp.name)
        self.repo = self.outer / 'repo'
        self.repo.mkdir()
        shutil.copytree(ROOT / 'tools/delivery', self.repo / 'tools/delivery')
        if (ROOT / '.codex').exists():
            shutil.copytree(ROOT / '.codex', self.repo / '.codex')
        for name in ['.quality-graph', 'tools/verification', 'specs', 'docs/operations', 'tests/Verification']:
            (self.repo / name).mkdir(parents=True, exist_ok=True)
        for name in ['.quality-graph/verification-policy.json', 'tools/verification/categories.json', 'quality-graph.yml', 'specs/CHANGE-VERIFICATION-001.md', 'AGENTS.md', 'docs/development-process.md']:
            shutil.copy2(ROOT / name, self.repo / name)
        (self.repo / 'docs/operations/current-delivery-goal.md').write_text('Owner intent: implement issue 99; no deployment.\n')
        (self.repo / 'specs/EXAMPLE.md').write_text('EXAMPLE: print expected values.\n')
        self.test = 'tests/Verification/example_test.py'
        (self.repo / self.test).write_text('print("EXAMPLE_OK")\n')
        (self.repo / '.gitignore').write_text('.local/\n__pycache__/\n')
        self.git('init', '-q'); self.git('config', 'user.email', 'contract@example.invalid'); self.git('config', 'user.name', 'Contract')
        self.input = 'specs/input.json'
        self.write(self.input, {'change': 'example', 'planned_paths': [self.test], 'acceptances': [
            {'spec_id': 'EXAMPLE', 'acceptance_id': 'example', 'spec_path': 'specs/EXAMPLE.md', 'seam': 'CLI', 'tests': [self.test]}]})
        self.git('add', '.'); self.git('commit', '-qm', 'fixture base')
        self.base = self.git('rev-parse', 'HEAD').stdout.strip()
        self.env = dict(os.environ, FMONITOR_HARNESS_HOME=str(self.outer / 'evidence'))

    def git(self, *args):
        return subprocess.run(['git', *args], cwd=self.repo, text=True, capture_output=True, check=True)

    def write(self, name, data):
        (self.repo / name).write_text(json.dumps(data) + '\n')

    def cli(self, *args, env=None, stdin=None):
        return subprocess.run([sys.executable, 'tools/delivery/harness.py', *args], cwd=self.repo,
                              env=env or self.env, input=stdin, text=True, capture_output=True, timeout=30)

    def run_check(self, code, *options, env=None):
        result = self.cli('run', *options, '--', sys.executable, '-c', code, env=env)
        self.assertTrue(result.stdout.strip().startswith('{'), 'INTENDED_RED structured harness result absent: ' + result.stderr)
        summary = json.loads(result.stdout)
        record = json.loads(Path(summary['record_path']).read_text())
        return result, {**record, **summary}

    def test_capture_full_bytes_and_compact_summary(self):
        result, summary = self.run_check('import sys; print("x"*100000); sys.stderr.write("stderr-evidence\\n")')
        self.assertEqual(0, result.returncode)
        self.assertEqual('GREEN', summary['outcome'])
        delivered=json.loads(result.stdout)
        self.assertEqual({'id','outcome','record_path'},set(delivered),'GREEN delivery contains only identity/outcome/evidence navigation')
        self.assertLess(len(result.stdout.encode()),len(delivered['record_path'].encode())+180)
        self.assertEqual('x'*100000+'\n', Path(summary['stdout_path']).read_text())
        self.assertEqual('stderr-evidence\n', Path(summary['stderr_path']).read_text())
        self.assertLess(len(result.stdout.encode()), 10000)
        self.assertGreaterEqual(summary['output_bytes'], 100000)
        record = json.loads(Path(summary['record_path']).read_text())
        self.assertEqual(len(result.stdout.encode()),record['summary_bytes'])
        for field in ['argv', 'cwd', 'source', 'environment', 'started_at', 'duration_seconds', 'exit_code', 'reason']:
            self.assertIn(field, record)
        self.assertFalse(Path(summary['record_path']).is_relative_to(self.repo))
        self.assertEqual('', self.git('status', '--porcelain').stdout)

    def test_outcome_precedence_and_actual_exit(self):
        cases = [
            ('print("expected assertion"); raise SystemExit(7)', 'REGRESSION_FAILURE', 7, []),
            ('print("expected assertion"); raise SystemExit(7)', 'INTENDED_RED', 7, ['--intended-red', 'expected assertion']),
            ('print("SETUP_FAILURE: expected assertion"); raise SystemExit(7)', 'SETUP_FAILURE', 7, ['--intended-red', 'expected assertion']),
            ('import os,signal; os.kill(os.getpid(), signal.SIGTERM)', 'INTERRUPTED', 143, ['--intended-red', 'expected assertion']),
            ('print("unexpected failure"); raise SystemExit(7)', 'REGRESSION_FAILURE', 7, ['--intended-red', 'expected assertion']),
        ]
        for code, outcome, exit_code, options in cases:
            with self.subTest(outcome=outcome):
                result, data = self.run_check(code, *options)
                self.assertEqual(exit_code, result.returncode)
                self.assertEqual(outcome, data['outcome'])
        result = self.cli('run', '--intended-red', 'missing', '--', '/no/such/executable')
        self.assertNotEqual(0, result.returncode)
        self.assertTrue(result.stdout.strip().startswith('{'), 'INTENDED_RED structured setup outcome absent: '+result.stderr)
        self.assertEqual('SETUP_FAILURE', json.loads(result.stdout)['outcome'])

    def test_public_cli_rejects_zero_exit_control_markers_without_rewriting_child(self):
        for marker in ['SETUP_FAILURE','UNKNOWN']:
            with self.subTest(marker=marker):
                result,data=self.run_check('print('+repr(marker+': unavailable')+')')
                self.assertEqual(marker,data['outcome'])
                self.assertNotEqual(0,result.returncode,'INTENDED_RED public runner returned success for '+marker)
                record=json.loads(Path(data['record_path']).read_text())
                self.assertEqual(0,record['exit_code'],'actual child exit must not become synthetic CLI exit')
                self.assertEqual(0,record['raw_child_returncode'])
                self.assertEqual(result.returncode,record['cli_exit_code'])

    def test_parent_interruption_and_zero_exit_setup_are_not_green(self):
        import signal
        import time
        result, value = self.run_check('print("SETUP_FAILURE: unavailable")')
        self.assertEqual('SETUP_FAILURE', value['outcome'])
        ready = self.outer/'child-ready'
        code = 'import pathlib,time; pathlib.Path('+repr(str(ready))+').write_text("ready"); print("partial",flush=True); time.sleep(20)'
        process = subprocess.Popen([sys.executable,'tools/delivery/harness.py','run','--',sys.executable,'-c',code],cwd=self.repo,env=self.env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,start_new_session=True)
        try:
            deadline=time.monotonic()+5
            while not ready.exists() and time.monotonic()<deadline and process.poll() is None:
                time.sleep(0.02)
            self.assertTrue(ready.exists(),'INTENDED_RED runner never started child')
            process.send_signal(signal.SIGTERM)
            stdout,stderr=process.communicate(timeout=5)
            self.assertNotEqual(0,process.returncode)
            self.assertTrue(stdout.strip().startswith('{'),'INTENDED_RED interruption record absent: '+stderr)
            data=json.loads(stdout)
            self.assertEqual('INTERRUPTED',data['outcome'])
            self.assertIn('partial',Path(data['stdout_path']).read_text())
        finally:
            try: os.killpg(process.pid,signal.SIGKILL)
            except ProcessLookupError: pass
            process.communicate()

    def test_ci_domain_unknown_words_are_not_outcome_markers(self):
        logs = [
            'ASSIGNMENT_ORDER_UNKNOWN_EMPLOYMENT_SCHEMA_COLLATION_001_OK\n',
            'OBJECT_DETAIL_IMPORT source-rejections metadata=SOURCE_METADATA_INCOMPLETE dictionary=SOURCE_DICTIONARY_VALUE_UNKNOWN mutations=0\nCHARACTERIZATION_OK CHARACTERIZE-OBJECT-DETAIL-IMPORT-001\n',
            'Domain reason SETUP_FAILURE is data; UNKNOWN is an allowed domain value.\n',
        ]
        for log in logs:
            with self.subTest(log=log):
                result, data=self.run_check('import sys; sys.stdout.write('+repr(log)+')')
                self.assertEqual(0,result.returncode)
                self.assertEqual('GREEN',data['outcome'],'INTENDED_RED domain output misclassified as control marker')
                self.assertEqual(log,Path(data['stdout_path']).read_text())

    def test_explicit_marker_wins_after_domain_words_and_remains_visible(self):
        for marker in ['UNKNOWN','SETUP_FAILURE']:
            with self.subTest(marker=marker):
                log='DOMAIN_UNKNOWN_SETUP_FAILURE_OK\n'+'x'*20000+'\n  '+marker+': actual unavailable dependency\n'+'y'*20000+'\n'
                result,data=self.run_check('import sys; sys.stderr.write('+repr(log)+'); raise SystemExit(7)','--intended-red','actual unavailable')
                self.assertEqual(7,result.returncode)
                self.assertEqual(marker,data['outcome'])
                self.assertIn(marker+': actual unavailable',data['excerpt'])
                self.assertEqual(log,Path(data['stderr_path']).read_text())

    def test_pipeline_and_redirect_preserve_child_failure(self):
        output = self.outer / 'redirect.json'
        with output.open('w') as stream:
            result = subprocess.run([sys.executable, 'tools/delivery/harness.py', 'run', '--', 'bash', '-o', 'pipefail', '-c', 'exit 7 | cat'], cwd=self.repo, env=self.env, stdout=stream, timeout=30)
        self.assertEqual(7, result.returncode)
        self.assertEqual(7, json.loads(output.read_text())['exit_code'])

    def test_no_green_reuse_and_programmatic_report(self):
        fixture = self.outer / 'fixture'; fixture.write_text('one')
        code = 'print("executed")'
        _, a = self.run_check(code, '--fixture', str(fixture))
        fixture.write_text('two')
        _, b = self.run_check(code, '--fixture', str(fixture))
        (self.repo / self.test).write_text('print("changed")\n')
        _, c = self.run_check(code, '--fixture', str(fixture))
        env = dict(self.env, FMONITOR_TEST_DB_NAME='changed_fixture_identity')
        _, d = self.run_check(code, '--fixture', str(fixture), env=env)
        records = [json.loads(Path(x['record_path']).read_text()) for x in [a,b,c,d]]
        self.assertEqual(4, len({x['id'] for x in records}))
        self.assertEqual(['initial','fixture_changed','source_changed','environment_changed'], [r['reason'] for r in records])
        self.assertNotEqual(records[0]['fixture'], records[1]['fixture'])
        self.assertNotEqual(records[1]['source'], records[2]['source'])
        self.assertNotEqual(records[2]['environment'], records[3]['environment'])
        report = self.cli('report'); self.assertEqual(0, report.returncode, report.stderr)
        data = json.loads(report.stdout)
        self.assertEqual(4, data['checks'])
        self.assertIn('token_telemetry', data)
        self.assertIn('coverage', data)
        self.assertEqual(4, json.loads(self.cli('report').stdout)['checks'])

    def test_late_failure_timeout_unknown_and_repeat_reasons(self):
        result, data = self.run_check('print("x"*20000); print("ASSERTION_EXPECTED: late failure"); raise SystemExit(9)', '--intended-red', 'ASSERTION_EXPECTED')
        self.assertEqual('INTENDED_RED', data['outcome'])
        self.assertIn('ASSERTION_EXPECTED', data['excerpt'])
        self.assertLessEqual(len(data['excerpt'].encode()), 4096)
        self.assertIn('x'*20000, Path(data['stdout_path']).read_text())
        _, data = self.run_check('print("UNKNOWN: ASSERTION_EXPECTED"); raise SystemExit(9)', '--intended-red', 'ASSERTION_EXPECTED')
        self.assertEqual('UNKNOWN', data['outcome'])
        result, data = self.run_check('import time; time.sleep(10)', '--timeout', '0.1')
        self.assertNotEqual(0, result.returncode)
        self.assertEqual('INTERRUPTED', data['outcome'])
        _, first = self.run_check('print("same")')
        _, second = self.run_check('print("same")', '--reason', 'independent_reproduction')
        a,b = [json.loads(Path(x['record_path']).read_text()) for x in [first,second]]
        self.assertEqual('initial', a['reason'])
        self.assertEqual('independent_reproduction', b['reason'])

    def test_measurement_scope_review_returns_and_unsupported_usage(self):
        def event(session, event, **values):
            payload = dict(hook_event_name=event, session_id=session, cwd=str(self.repo), **values)
            result = self.cli('hook', stdin=json.dumps(payload))
            self.assertEqual(0, result.returncode, 'INTENDED_RED measurement hook absent: '+result.stderr)
        for _ in range(2):
            event('root1', 'PostToolUse', tool_use_id='same-id', tool_name='Bash', tool_response='abc', usage={'input_tokens':100,'cached_input_tokens':80,'output_tokens':20})
            event('root2', 'PostToolUse', tool_use_id='same-id', tool_name='Bash', tool_response='12345')
            event('root1', 'SubagentStart', turn_id='t', agent_id='a', agent_type='reviewer')
            event('root1', 'SubagentStop', turn_id='t', agent_id='a', agent_type='reviewer', last_assistant_message='CHANGES_REQUESTED: fix F1')
            event('root2', 'SubagentStart', turn_id='t', agent_id='a', agent_type='executor')
            event('root2', 'SubagentStop', turn_id='t', agent_id='a', agent_type='executor', last_assistant_message='done')
        report = json.loads(self.cli('report').stdout)
        self.assertEqual(2, report['tool_calls'])
        self.assertEqual(2, report['agent_tasks'])
        self.assertEqual(1, report['review_returns'])
        self.assertEqual(8, report['observed_tool_output_bytes'])
        telemetry = report['token_telemetry']
        self.assertEqual('UNKNOWN', telemetry['status'])
        self.assertEqual('UNKNOWN', telemetry['total_tokens'])
        self.assertEqual('UNKNOWN', telemetry['delta_tokens'])
        for field in ['checks','agent_tasks','review_returns','model_output','token_telemetry']:
            self.assertIn(field, report['coverage'])

    def test_hook_protocol_config_receipts_and_no_checks_for_readonly(self):
        hook = self.cli('hook', stdin=json.dumps({'hook_event_name':'SessionStart','session_id':'protocol','cwd':str(self.repo),'source':'startup'}))
        self.assertEqual(0, hook.returncode, 'INTENDED_RED hook missing: '+hook.stderr)
        data = json.loads(hook.stdout)['hookSpecificOutput']
        self.assertEqual('SessionStart', data['hookEventName'])
        self.assertIn('current-delivery-goal', data['additionalContext'])
        task = self.cli('hook', stdin=json.dumps({'hook_event_name':'UserPromptSubmit','session_id':'protocol','cwd':str(self.repo),'prompt':'продолжай текущую задачу'}))
        self.assertEqual('UserPromptSubmit', json.loads(task.stdout)['hookSpecificOutput']['hookEventName'])
        before = json.loads(self.cli('report').stdout)['checks']
        question = self.cli('hook', stdin=json.dumps({'hook_event_name':'UserPromptSubmit','session_id':'protocol','cwd':str(self.repo),'prompt':'Что делает этот проект?'}))
        self.assertEqual('', question.stdout.strip())
        self.assertEqual(before, json.loads(self.cli('report').stdout)['checks'])
        config = ROOT / '.codex/hooks.json'
        self.assertTrue(config.exists(), 'INTENDED_RED repo hook config missing')
        values = json.loads(config.read_text())['hooks']
        for name in ['SessionStart','UserPromptSubmit','PostToolUse','SubagentStart','SubagentStop']:
            self.assertIn(name, values)
            self.assertTrue(all(h['type']=='command' for group in values[name] for h in group['hooks']))
        doctor = json.loads(self.cli('doctor').stdout)
        self.assertIn('observed_events', doctor)
        self.assertIn('SessionStart', doctor['observed_events'])
        self.assertEqual('', self.git('status','--porcelain').stdout)

    def fake_gh(self, success):
        directory = self.outer / 'bin'; directory.mkdir(exist_ok=True)
        gh = directory / 'gh'
        data = {'state':'MERGED','headRefOid':self.base,'mergeCommit':{'oid':self.base},'url':'https://example.invalid/pr/88','number':88,'statusCheckRollup':[]}
        gh.write_text('#!'+sys.executable+'\nimport json,sys\n'+('print('+repr(json.dumps(data))+')\n' if success else 'sys.exit(1)\n'))
        gh.chmod(0o700)
        return dict(self.env, PATH=str(directory)+os.pathsep+os.environ['PATH'])

    def test_merged_state_and_unavailable_github(self):
        result = self.cli('state', env=self.fake_gh(True))
        self.assertEqual(0, result.returncode, 'INTENDED_RED state absent: '+result.stderr)
        data = json.loads(result.stdout)
        self.assertEqual('MERGED', data['github']['state'])
        self.assertNotIn('publish', data['next_action'].lower())
        result = self.cli('state', env=self.fake_gh(False)); data = json.loads(result.stdout)
        self.assertEqual('UNKNOWN', data['github']['state'])
        self.assertIn('checked_at', data['github'])
        self.assertIn('last_success_at', data['github'])
        self.assertEqual('UNKNOWN', data['deployment'])
        self.assertEqual('', self.git('status', '--porcelain').stdout)

    def test_state_ci_is_bound_to_clean_exact_head(self):
        env = self.fake_gh(True)
        gh = self.outer/'bin/gh'
        def response(head):
            data = {'state':'OPEN','headRefOid':head,'mergeCommit':None,'url':'https://example.invalid/pr/99','number':99,'statusCheckRollup':[{'name':'verify','status':'COMPLETED','conclusion':'SUCCESS'}]}
            gh.write_text('#!'+sys.executable+'\nprint('+repr(json.dumps(data))+')\n')
        response(self.base)
        result = self.cli('state',env=env)
        self.assertEqual(0,result.returncode,'INTENDED_RED state missing: '+result.stderr)
        clean=json.loads(result.stdout)
        self.assertEqual('UNKNOWN',clean['ci']['status'],
                         'INTENDED_RED AC01 verify-only CI has no bound expected matrix')
        self.assertFalse(clean['dirty'])
        (self.repo/self.test).write_text('print("dirty")\n')
        dirty=json.loads(self.cli('state',env=env).stdout)
        self.assertTrue(dirty['dirty']); self.assertNotEqual(clean['source'],dirty['source'])
        self.assertEqual('UNKNOWN',dirty['ci']['status'])
        self.git('checkout','--',self.test)
        response('a'*40)
        mismatch=json.loads(self.cli('state',env=env).stdout)
        self.assertEqual('a'*40,mismatch['github']['headRefOid'])
        self.assertEqual('UNKNOWN',mismatch['ci']['status'])
        unavailable=json.loads(self.cli('state',env=self.fake_gh(False)).stdout)
        self.assertEqual('UNKNOWN',unavailable['ci']['status'])
        self.assertEqual('UNKNOWN',unavailable['github']['state'])
        self.assertTrue(unavailable['github']['last_success_at'])

    def test_additive_install_is_idempotent_and_dispatch_is_repo_scoped(self):
        config=self.outer/'user-hooks.json'
        foreign={'description':'owner settings','hooks':{'Stop':[{'hooks':[{'type':'command','command':'echo owner-hook'}]}],'UserPromptSubmit':[{'hooks':[{'type':'command','command':'echo foreign-prompt'}]}]}}
        config.write_text(json.dumps(foreign))
        before_source=self.git('status','--porcelain').stdout
        first=self.cli('install','--config',str(config))
        self.assertEqual(0,first.returncode,'INTENDED_RED installer absent: '+first.stderr)
        installed=json.loads(config.read_text())
        self.assertEqual(foreign['hooks']['Stop'],installed['hooks']['Stop'])
        self.assertEqual('owner settings',installed['description'])
        self.assertEqual(foreign['hooks']['UserPromptSubmit'][0],installed['hooks']['UserPromptSubmit'][0])
        payload={'hook_event_name':'UserPromptSubmit','session_id':'native-dispatch','cwd':str(self.repo),'prompt':'реализуй issue 99'}
        command=installed['hooks']['UserPromptSubmit'][-1]['hooks'][0]['command']
        result=subprocess.run(command,shell=True,cwd=self.repo,env=self.env,input=json.dumps(payload),text=True,capture_output=True,timeout=20)
        self.assertEqual(0,result.returncode,result.stderr)
        self.assertEqual('UserPromptSubmit',json.loads(result.stdout)['hookSpecificOutput']['hookEventName'])
        linked=self.outer/'linked'
        self.git('worktree','add','--detach',str(linked),'HEAD')
        try:
            payload['cwd']=str(linked)
            linked_result=subprocess.run(command,shell=True,cwd=linked,env=self.env,input=json.dumps(payload),text=True,capture_output=True,timeout=20)
            self.assertEqual(0,linked_result.returncode,linked_result.stderr)
            self.assertEqual('UserPromptSubmit',json.loads(linked_result.stdout)['hookSpecificOutput']['hookEventName'])
        finally:
            self.git('worktree','remove','--force',str(linked))
        unrelated=self.outer/'unrelated';unrelated.mkdir()
        subprocess.run(['git','init','-q'],cwd=unrelated,check=True)
        payload['cwd']=str(unrelated)
        other=subprocess.run(command,shell=True,cwd=unrelated,env=self.env,input=json.dumps(payload),text=True,capture_output=True,timeout=20)
        self.assertEqual(0,other.returncode);self.assertEqual('',other.stdout.strip())
        first_bytes=config.read_bytes()
        self.assertEqual(0,self.cli('install','--config',str(config)).returncode)
        self.assertEqual(first_bytes,config.read_bytes(),'install must not duplicate hooks or rewrite foreign values')
        self.assertEqual(before_source,self.git('status','--porcelain').stdout)
        config.write_text('{invalid-json')
        bad=self.cli('install','--config',str(config))
        self.assertNotEqual(0,bad.returncode)
        self.assertEqual('{invalid-json',config.read_text())

    def test_doctor_does_not_claim_unobserved_hooks(self):
        result = self.cli('doctor')
        self.assertTrue(result.stdout.strip().startswith('{'), 'INTENDED_RED doctor absent: '+result.stderr)
        data = json.loads(result.stdout)
        self.assertNotEqual('OBSERVED', data['integration'])
        self.assertIn('limitations', data)

    def test_hooks_route_task_resume_readonly_and_deduplicate_events(self):
        def hook(event, **extra):
            result = self.cli('hook', stdin=json.dumps(dict(hook_event_name=event, session_id='fixture-session', cwd=str(self.repo), **extra)))
            self.assertEqual(0, result.returncode, 'INTENDED_RED hook absent: '+result.stderr)
            return result.stdout
        readonly = hook('UserPromptSubmit', prompt='Что делает этот проект?')
        self.assertNotIn('Gate 2', readonly)
        task = hook('UserPromptSubmit', prompt='реализуй issue 99')
        self.assertIn('Gate', task)
        resume = hook('SessionStart', source='resume')
        self.assertIn('current-delivery-goal', resume)
        for _ in range(2):
            hook('PostToolUse', tool_name='Bash', tool_use_id='tool1', tool_response='bounded output')
            hook('SubagentStart', turn_id='t1', agent_id='agent1', agent_type='executor')
            hook('SubagentStop', turn_id='t1', agent_id='agent1', agent_type='executor')
        report = json.loads(self.cli('report').stdout)
        self.assertEqual(1, report['agent_tasks'])
        self.assertEqual(1, report['tool_calls'])
        self.assertEqual(len('bounded output'.encode()), report['observed_tool_output_bytes'])
        self.assertEqual('UNKNOWN', report['token_telemetry']['status'])

    def prepare(self, *extra):
        return self.cli('prepare', '--input', self.input, '--base', self.base, '--role', 'root', *extra)

    def test_prepare_snapshot_delta_whitespace_and_no_approval(self):
        (self.repo / self.test).write_text('print("first")\n')
        first = self.prepare(); self.assertEqual(0, first.returncode, 'INTENDED_RED prepare absent: '+first.stderr)
        a = json.loads(first.stdout)
        self.assertTrue(Path(a['snapshot'], 'manifest.json').exists())
        self.assertTrue(Path(a['plan']).exists())
        self.assertNotEqual('APPROVED', a['approval'])
        restored = self.outer / 'restored'
        result = subprocess.run([sys.executable, 'tools/delivery/review-source.py', 'restore', '--snapshot', a['snapshot'], '--output', str(restored)], cwd=self.repo, capture_output=True, text=True)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual('print("first")\n', (restored / self.test).read_text())
        self.git('worktree', 'remove', '--force', str(restored))
        findings = self.outer / 'findings.md'; findings.write_text('F1: correct first to second.\n')
        (self.repo / self.test).write_text('print("second")\n')
        second = self.prepare('--previous', a['snapshot'], '--findings', str(findings))
        self.assertEqual(0, second.returncode, second.stderr)
        b = json.loads(second.stdout)
        delta = Path(b['delta']).read_text()
        self.assertIn('-print("first")', delta)
        self.assertIn('+print("second")', delta)
        self.assertNotIn('APPROVED', b['approval'])
        self.assertTrue(b['previous'])
        self.assertTrue(b['findings'])
        (self.repo / self.test).write_text('print("bad")   \n')
        bad = self.prepare(); self.assertNotEqual(0, bad.returncode)
        self.assertIn('whitespace', (bad.stderr+bad.stdout).lower())

    def test_role_packages_current_evidence_and_future_tests(self):
        import hashlib
        _, unrelated = self.run_check('print("EXAMPLE_OK")')
        rejected = self.cli('prepare','--input',self.input,'--base',self.base,'--role','reviewer','--evidence',unrelated['record_path'])
        self.assertNotEqual(0,rejected.returncode)
        self.assertIn('evidence',(rejected.stdout+rejected.stderr).lower())
        result = self.cli('run','--','python3',self.test)
        self.assertEqual(0,result.returncode,'INTENDED_RED mapped run missing: '+result.stderr)
        run = json.loads(result.stdout)
        prepared = self.cli('prepare','--input',self.input,'--base',self.base,'--role','reviewer','--evidence',run['record_path'])
        self.assertEqual(0,prepared.returncode,'INTENDED_RED reviewer package absent: '+prepared.stderr)
        data=json.loads(prepared.stdout)
        self.assertEqual('reviewer',data['role']); self.assertEqual('NOT_REVIEWED',data['approval'])
        self.assertEqual(hashlib.sha256(Path(data['plan']).read_bytes()).hexdigest(),data['plan_sha256'])
        self.assertTrue(data['contracts']);self.assertTrue(data['rules']);self.assertTrue(data['sources']);self.assertTrue(data['evidence'])
        self.assertTrue(Path(data['package_path']).exists())
        (self.repo/self.test).write_text('print("changed")\n')
        stale=self.cli('prepare','--input',self.input,'--base',self.base,'--role','reviewer','--evidence',run['record_path'])
        self.assertNotEqual(0,stale.returncode); self.assertIn('source',(stale.stdout+stale.stderr).lower())
        executor=self.cli('prepare','--input',self.input,'--base',self.base,'--role','executor')
        self.assertEqual(0,executor.returncode,executor.stderr)
        e=json.loads(executor.stdout);self.assertEqual('executor',e['role']);self.assertFalse(e['evidence'])
        previous_plan=json.loads(Path(e['plan']).read_text())
        (self.repo/'specs/EXAMPLE.md').write_text('EXAMPLE changed contract\n')
        root=self.prepare();self.assertEqual(0,root.returncode,root.stderr)
        new=json.loads(root.stdout)
        self.assertNotEqual(previous_plan['bindings'],json.loads(Path(new['plan']).read_text())['bindings'])
        (self.repo/self.test).unlink()
        future=self.prepare();self.assertEqual(0,future.returncode,future.stderr)
        self.assertIn(self.test,json.loads(future.stdout)['missing_tests'])

    def test_gate3_red_evidence_and_complete_current_obligations(self):
        (self.repo/self.test).write_text('print("EXPECTED_ASSERT"); raise SystemExit(7)\n')
        run=self.cli('run','--intended-red','EXPECTED_ASSERT','--','python3',self.test)
        self.assertEqual(7,run.returncode,'INTENDED_RED mapped RED run missing: '+run.stderr)
        record=json.loads(run.stdout)['record_path']
        gate3=self.cli('prepare','--input',self.input,'--base',self.base,'--role','reviewer','--gate','3','--evidence',record)
        self.assertEqual(0,gate3.returncode,'INTENDED_RED Gate3 package rejected: '+gate3.stderr)
        self.assertEqual('NOT_REVIEWED',json.loads(gate3.stdout)['approval'])
        gate5=self.cli('prepare','--input',self.input,'--base',self.base,'--role','reviewer','--gate','5','--evidence',record)
        self.assertNotEqual(0,gate5.returncode)
        self.assertIn('evidence',(gate5.stdout+gate5.stderr).lower())
        (self.repo/self.test).write_text('print("GREEN")\n')
        second='tests/Verification/second_test.py';(self.repo/second).write_text('print("SECOND")\n')
        value=json.loads((self.repo/self.input).read_text());value['acceptances'][0]['tests'].append(second);self.write(self.input,value)
        run=self.cli('run','--','python3',self.test);record=json.loads(run.stdout)['record_path']
        incomplete=self.cli('prepare','--input',self.input,'--base',self.base,'--role','reviewer','--evidence',record)
        self.assertNotEqual(0,incomplete.returncode)
        self.assertIn('evidence',(incomplete.stdout+incomplete.stderr).lower())

    def test_review_evidence_environment_and_untracked_whitespace(self):
        run=self.cli('run','--','python3',self.test)
        self.assertEqual(0,run.returncode,'INTENDED_RED mapped run absent: '+run.stderr)
        record=json.loads(run.stdout)['record_path']
        env=dict(self.env,FMONITOR_TEST_DB_NAME='other-fixture')
        stale=self.cli('prepare','--input',self.input,'--base',self.base,'--role','reviewer','--evidence',record,env=env)
        self.assertNotEqual(0,stale.returncode)
        self.assertIn('environment',(stale.stdout+stale.stderr).lower())
        (self.repo/'tests/Verification/untracked.py').write_text('print("bad")   \n')
        bad=self.prepare();self.assertNotEqual(0,bad.returncode)
        self.assertIn('whitespace',(bad.stdout+bad.stderr).lower())

    def mapped_run(self, test, red=False):
        options=['--intended-red','NEW_BEHAVIOR_RED'] if red else []
        result=self.cli('run',*options,'--','python3',test)
        self.assertEqual(7 if red else 0,result.returncode,result.stderr)
        return json.loads(result.stdout)['record_path']

    def mixed_mapping(self, new_tests):
        value=json.loads((self.repo/self.input).read_text())
        value['acceptances'][0]['tests']=[self.test,*new_tests]
        value['acceptances'][0]['gate3_expected']={self.test:'GREEN',**{t:'INTENDED_RED' for t in new_tests}}
        self.write(self.input,value)

    def reviewer_with(self, records, gate='3'):
        args=['prepare','--input',self.input,'--base',self.base,'--role','reviewer','--gate',gate]
        for path in records:args+=['--evidence',path]
        return self.cli(*args)

    def test_mixed_gate3_new_red_and_existing_green_regression(self):
        new='tests/Verification/new_behavior_test.py'
        (self.repo/new).write_text('print("NEW_BEHAVIOR_RED"); raise SystemExit(7)\n')
        self.mixed_mapping([new])
        records=[self.mapped_run(new,red=True),self.mapped_run(self.test)]
        prepared=self.reviewer_with(records)
        self.assertEqual(0,prepared.returncode,'INTENDED_RED valid mixed Gate3 refused: '+prepared.stderr)
        package=json.loads(prepared.stdout)
        self.assertEqual({'GREEN','INTENDED_RED'},{e['outcome'] for e in package['evidence']})
        self.assertEqual('NOT_REVIEWED',package['approval'])
        self.assertNotEqual(0,self.reviewer_with(records[:1]).returncode,'all mapped commands remain required')
        self.assertNotEqual(0,self.reviewer_with(records,gate='5').returncode,'Gate5 still requires all GREEN')

    def test_gate3_cannot_replace_new_behavior_red_with_arbitrary_green(self):
        new='tests/Verification/new_behavior_test.py';other='tests/Verification/other_behavior_test.py'
        (self.repo/new).write_text('print("unexpected green")\n')
        (self.repo/other).write_text('print("NEW_BEHAVIOR_RED"); raise SystemExit(7)\n')
        self.mixed_mapping([new,other])
        records=[self.mapped_run(self.test),self.mapped_run(new),self.mapped_run(other,red=True)]
        rejected=self.reviewer_with(records)
        self.assertNotEqual(0,rejected.returncode,'one RED does not excuse GREEN for another RED obligation')
        self.assertIn('evidence',(rejected.stderr+rejected.stdout).lower())
        value=json.loads((self.repo/self.input).read_text());value['acceptances'][0]['gate3_expected']={t:'GREEN' for t in [self.test,new,other]};self.write(self.input,value)
        (self.repo/other).write_text('print("green regression")\n')
        records=[self.mapped_run(t) for t in [self.test,new,other]]
        self.assertNotEqual(0,self.reviewer_with(records).returncode,'all GREEN cannot satisfy Gate3 RED proof')
        self.assertEqual(0,self.reviewer_with(records,gate='5').returncode,'same all-GREEN package can satisfy Gate5 completeness')

    def verification(self,*args):
        return subprocess.run([sys.executable,'tools/delivery/change-verification.py',*args],cwd=self.repo,env=self.env,text=True,capture_output=True,timeout=30)

    def test_prepare_returned_external_plan_works_with_downstream_commands(self):
        (self.repo/'tests/Verification/change_verification_001_test.py').write_text('print("governance fixture OK")\n')
        result=self.prepare();self.assertEqual(0,result.returncode,result.stderr)
        returned=json.loads(result.stdout)['plan']
        self.assertTrue(Path(returned).is_absolute())
        self.assertFalse(Path(returned).is_relative_to(self.repo))
        checked=self.verification('check','--plan',returned)
        self.assertEqual(0,checked.returncode,'INTENDED_RED prepare plan unusable downstream: '+checked.stderr)
        (self.repo/'specs/EXAMPLE.md').write_text('updated acceptance contract\n')
        self.assertNotEqual(0,self.verification('check','--plan',returned).returncode)
        refreshed=self.verification('refresh','--plan',returned)
        self.assertEqual(0,refreshed.returncode,refreshed.stderr)
        self.assertEqual(0,self.verification('check','--plan',returned).returncode)
        executed=self.verification('run','--plan',returned,'--phase','focused')
        self.assertEqual(0,executed.returncode,executed.stderr)
        self.assertTrue(all(x['outcome']=='GREEN' for x in json.loads(executed.stdout)['results']))
        untrusted=self.outer/'untrusted-plan.json';untrusted.write_bytes(Path(returned).read_bytes())
        self.assertNotEqual(0,self.verification('check','--plan',str(untrusted)).returncode)
        traversal=str(Path(returned).parent/'..'/Path(returned).parent.name/'verification-plan.json')
        self.assertNotEqual(0,self.verification('check','--plan',traversal).returncode)
        escape=Path(returned).parent/'escape.json';escape.symlink_to(untrusted)
        self.assertNotEqual(0,self.verification('check','--plan',str(escape)).returncode)
        tampered=json.loads(Path(returned).read_text());tampered['input']='../private-input.json'
        (self.outer/'private-input.json').write_text('PRIVATE_SENTINEL')
        Path(returned).write_text(json.dumps(tampered))
        rejected=self.verification('refresh','--plan',returned)
        self.assertNotEqual(0,rejected.returncode)
        self.assertNotIn('PRIVATE_SENTINEL',rejected.stdout+rejected.stderr)

    def test_active_bindings_survive_interleaved_worktrees(self):
        linked=self.outer/'worktree-b'
        self.git('worktree','add','--detach',str(linked),self.base)
        env=self.fake_gh(False)
        def call(root,*args,payload=None):
            return subprocess.run([sys.executable,str(root/'tools/delivery/harness.py'),*args],cwd=root,env=env,input=json.dumps(payload) if payload else None,text=True,capture_output=True,timeout=30)
        try:
            packages=[]
            for root,name in [(self.repo,'task-a'),(linked,'task-b')]:
                value=json.loads((root/self.input).read_text());value['change']=name;(root/self.input).write_text(json.dumps(value)+'\n')
                prepared=call(root,'prepare','--input',self.input,'--base',self.base,'--role','root')
                self.assertEqual(0,prepared.returncode,prepared.stderr);packages.append(json.loads(prepared.stdout))
            for index,root in enumerate([self.repo,linked]):
                result=call(root,'hook',payload={'hook_event_name':'SessionStart','source':'resume','session_id':'worktree-'+str(index),'cwd':str(root)})
                self.assertEqual(0,result.returncode,result.stderr)
                context=json.loads(result.stdout)['hookSpecificOutput']['additionalContext']
                self.assertIn(packages[index]['package_path'],context,'INTENDED_RED other worktree overwrote binding')
                self.assertNotIn(packages[1-index]['package_path'],context)
            states=[]
            for root in [self.repo,linked]:
                result=call(root,'state');self.assertEqual(0,result.returncode,result.stderr)
                states.append(json.loads(result.stdout)['active_binding'])
            self.assertNotEqual(states[0]['plan'],states[1]['plan'])
            for index,name in enumerate(['task-a','task-b']):
                self.assertEqual(name,json.loads(Path(states[index]['plan']).read_text())['change'])
                self.assertEqual(packages[index]['package_path'],states[index]['package_path'])
                worktree=[self.repo,linked][index]
                checked=subprocess.run([sys.executable,str(worktree/'tools/delivery/change-verification.py'),'check','--plan',states[index]['plan']],cwd=worktree,env=env,text=True,capture_output=True,timeout=30)
                self.assertEqual(0,checked.returncode,'INTENDED_RED namespaced active plan unusable downstream: '+checked.stderr)
        finally:
            self.git('worktree','remove','--force',str(linked))

    def test_reviewer_cannot_dispatch_without_evidence(self):
        result = self.cli('prepare', '--input', self.input, '--base', self.base, '--role', 'reviewer')
        self.assertNotEqual(0, result.returncode)
        self.assertIn('evidence', (result.stdout+result.stderr).lower())


if __name__ == '__main__':
    unittest.main(verbosity=2)
