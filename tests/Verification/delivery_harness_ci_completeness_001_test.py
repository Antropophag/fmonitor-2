"""DELIVERY-HARNESS-CI-COMPLETENESS-001 public contract."""
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]


def load(name, path):
    spec = importlib.util.spec_from_file_location(name, ROOT / path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class DeliveryHarnessCiCompleteness(unittest.TestCase):
    def fixture_repo(self):
        temporary = tempfile.TemporaryDirectory(prefix='fmonitor-ci-complete-repo-')
        self.addCleanup(temporary.cleanup)
        repo = Path(temporary.name) / 'repo'
        repo.mkdir()
        for name in ['tools/delivery', 'tools/verification', '.quality-graph', '.codex',
                     'specs', 'tests/Verification', 'deploy/runtime', 'openspec/changes']:
            shutil.copytree(ROOT / name, repo / name)
        for name in ['AGENTS.md', 'quality-graph.yml']:
            shutil.copy2(ROOT / name, repo / name)
        (repo / 'docs/operations').mkdir(parents=True)
        shutil.copy2(ROOT / 'docs/development-process.md', repo / 'docs/development-process.md')
        shutil.copy2(ROOT / 'docs/operations/current-delivery-goal.md',
                     repo / 'docs/operations/current-delivery-goal.md')
        subprocess.run(['git', 'init', '-q'], cwd=repo, check=True)
        subprocess.run(['git', 'config', 'user.email', 'ci-complete@example.invalid'], cwd=repo, check=True)
        subprocess.run(['git', 'config', 'user.name', 'CI completeness fixture'], cwd=repo, check=True)
        subprocess.run(['git', 'add', '.'], cwd=repo, check=True)
        subprocess.run(['git', 'commit', '-qm', 'fixture base'], cwd=repo, check=True)
        base = subprocess.run(['git', 'rev-parse', 'HEAD'], cwd=repo, check=True,
                              text=True, capture_output=True).stdout.strip()
        evidence = Path(temporary.name) / 'evidence'
        environment = dict(os.environ, FMONITOR_HARNESS_HOME=str(evidence))
        return repo, base, evidence, environment

    def command(self, repo, environment, *argv):
        return subprocess.run(list(argv), cwd=repo, env=environment, text=True,
                              capture_output=True, timeout=90)

    def write_input(self, repo, *, test='tests/Verification/ci_complete_acceptance.py',
                    workspaces=None):
        target_test = repo / test
        target_test.parent.mkdir(parents=True, exist_ok=True)
        if not target_test.exists():
            target_test.write_text('print("CI_COMPLETE_ACCEPTANCE_OK")\n')
        relative = 'openspec/changes/ci-completeness/input.json'
        target = repo / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        value = {
            'change': 'delivery-harness-first-pass-ci-completeness-fixture',
            'planned_paths': [test],
            'acceptances': [{
                'spec_id': 'DELIVERY-HARNESS-CI-COMPLETENESS-001',
                'acceptance_id': 'fixture',
                'spec_path': 'specs/DELIVERY-HARNESS-CI-COMPLETENESS-001.md',
                'seam': 'public preflight', 'tests': [test],
                'gate3_expected': {test: 'GREEN'},
            }],
        }
        if workspaces is not None:
            value['dependency_workspaces'] = workspaces
        target.write_text(json.dumps(value) + '\n')
        return relative

    def plan(self, repo, environment, base, input_name):
        relative = 'openspec/changes/ci-completeness/verification-plan.json'
        output = repo / relative
        result = self.command(repo, environment, sys.executable,
                              'tools/delivery/change-verification.py', 'plan', '--base', base,
                              '--input', input_name, '--output', relative)
        return result, output, relative

    def test_policy_models_transitive_obligations_and_ci_environments(self):
        policy = json.loads((ROOT / '.quality-graph/verification-policy.json').read_text())
        generated = policy.get('generated_sources', [])
        runtime = next((x for x in generated if 'deploy/runtime/Dockerfile' in x.get('artifacts', [])), None)
        self.assertIsNotNone(runtime, 'INTENDED_RED Dockerfile generator relation is absent')
        self.assertEqual(['python3', 'tools/delivery/render-dependencies.py', '--check'], runtime['check'])
        self.assertIn('tools/delivery/Dockerfile.runtime.in', runtime['inputs'])
        profiles = policy.get('environment_profiles', {})
        self.assertIn('unit', profiles, 'INTENDED_RED CI environment profiles are absent')
        self.assertEqual([], profiles['unit']['services'])
        self.assertIn('mariadb', profiles['integration']['services'])
        prerequisites = policy.get('test_prerequisites', {})
        self.assertEqual(['mariadb'], prerequisites['tests/Yii2/yii2_imports_workforce_001_test.php']['services'])
        probes = policy.get('service_probes', {})
        for service in ['mariadb', 'browser', 'container']:
            self.assertEqual(['python3', 'tools/delivery/probe-environment.py', 'service', service],
                             probes.get(service),
                             f'INTENDED_RED shipped {service} probe is not an observation')

    def test_plan_commands_are_typed_and_environment_bound(self):
        planner = load('ci_complete_planner', 'tools/delivery/change-verification.py')
        plan = planner.build('origin/main', 'openspec/changes/delivery-harness-first-pass-ci-completeness/verification-input.json')
        self.assertGreaterEqual(plan['version'], 2, 'INTENDED_RED typed plan schema is absent')
        for command in plan['commands']:
            self.assertIn(command.get('purpose'), {'acceptance', 'boundary', 'category'})
            self.assertIsInstance(command.get('id'), str)
            self.assertIsInstance(command.get('environment'), dict)
        argv = [item['argv'] for item in plan['commands']
                if item['argv'] == ['python3', 'tools/delivery/render-dependencies.py', '--check']]
        self.assertEqual([['python3', 'tools/delivery/render-dependencies.py', '--check']], argv,
                         'INTENDED_RED generated-source check omitted')
        self.assertIn(['python3', 'tests/Verification/verification_ci_001_test.py'],
                      [item['argv'] for item in plan['commands']],
                      'INTENDED_RED exact plan dropped generated-source consumer during dedupe')

    def test_source_identity_separates_executable_and_lifecycle_metadata(self):
        harness = load('ci_complete_harness', 'tools/delivery/harness.py')
        details = harness.source_details()
        self.assertEqual(64, len(details.get('candidate_digest', '')),
                         'INTENDED_RED candidate digest split is absent')
        self.assertEqual(64, len(details.get('executable_digest', '')),
                         'INTENDED_RED executable digest split is absent')
        self.assertIn('lifecycle_paths', details)

    def test_public_cli_exposes_preflight_and_test_delta_contracts(self):
        change_help = subprocess.run([sys.executable, 'tools/delivery/change-verification.py', '--help'],
                                     cwd=ROOT, text=True, capture_output=True, timeout=20)
        self.assertEqual(0, change_help.returncode)
        self.assertIn('preflight', change_help.stdout, 'INTENDED_RED preflight command is absent')
        harness_help = subprocess.run([sys.executable, 'tools/delivery/harness.py', 'prepare', '--help'],
                                      cwd=ROOT, text=True, capture_output=True, timeout=20)
        self.assertEqual(0, harness_help.returncode)
        for option in ['--historical-red', '--test-delta', '--dependency-workspace']:
            self.assertIn(option, harness_help.stdout, f'INTENDED_RED {option} package contract is absent')

    def test_preflight_is_fail_closed_and_records_environment(self):
        planner = load('ci_complete_preflight_planner', 'tools/delivery/change-verification.py')
        plan = planner.build('origin/main', 'openspec/changes/delivery-harness-first-pass-ci-completeness/verification-input.json')
        with tempfile.TemporaryDirectory(prefix='fmonitor-ci-completeness-') as directory:
            home = Path(directory)
            package = home / 'packages' / 'contract'
            package.mkdir(parents=True)
            plan_path = package / 'verification-plan.json'
            plan_path.write_text(planner.canonical(plan))
            environment = dict(os.environ, FMONITOR_HARNESS_HOME=str(home))
            result = subprocess.run([
                sys.executable, 'tools/delivery/change-verification.py', 'preflight', '--plan',
                str(plan_path)], cwd=ROOT, env=environment, text=True,
                capture_output=True, timeout=90)
            self.assertNotEqual('', result.stdout.strip() or result.stderr.strip())
            self.assertEqual(0, result.returncode, result.stdout + result.stderr)
            payload = json.loads(result.stdout)
            self.assertEqual('GREEN', payload['outcome'])
            self.assertEqual(payload['executable_source'], payload['evidence_executable_source'])

    def test_pr98_inventory_is_complete_before_publication(self):
        repo, base, _, environment = self.fixture_repo()
        (repo / 'deploy/runtime/Dockerfile').write_text(
            (repo / 'deploy/runtime/Dockerfile').read_text() + '\n# stale generated byte\n')
        synthetic = 'tests/Verification/synthetic_ci_inventory_test.py'
        (repo / synthetic).write_text('import yaml\nprint("PASS")\n')
        with (repo / 'tools/verification/suites.tsv').open('a') as stream:
            stream.write(f'e2e\tpython3\t{synthetic}\n')
        input_name = self.write_input(repo)
        planned, plan_path, plan_name = self.plan(repo, environment, base, input_name)
        self.assertEqual(0, planned.returncode, 'INTENDED_RED planner cannot inventory all failures: ' + planned.stderr)
        plan = json.loads(plan_path.read_text())
        argv = [item['argv'] for item in plan['commands']]
        self.assertIn(['python3', 'tools/delivery/render-dependencies.py', '--check'], argv)
        self.assertIn(['python3', 'tests/Verification/verification_ci_001_test.py'], argv)
        preflight = self.command(repo, environment, sys.executable,
                                 'tools/delivery/change-verification.py', 'preflight', '--plan', plan_name)
        self.assertNotEqual(0, preflight.returncode, 'INTENDED_RED PR #98 candidate admitted')
        payload = json.loads(preflight.stdout)
        self.assertEqual('BLOCKED', payload['outcome'])
        self.assertEqual({'GENERATED_SOURCE_DRIFT', 'STALE_VERIFICATION_INVENTORY',
                          'UNDECLARED_TEST_DEPENDENCY'},
                         {item['code'] for item in payload['failures']})
        self.assertFalse(payload['publication_ready'])
        self.assertTrue(Path(payload['record_path']).is_file())
        self.command(repo, environment, sys.executable, 'tools/delivery/render-dependencies.py')
        (repo / synthetic).write_text('print("PASS")\n')
        categories = json.loads((repo / 'tools/verification/categories.json').read_text())
        categories[synthetic] = 'e2e'
        (repo / 'tools/verification/categories.json').write_text(json.dumps(categories, sort_keys=True) + '\n')
        corrected, corrected_path, corrected_name = self.plan(repo, environment, base, input_name)
        self.assertEqual(0, corrected.returncode, corrected.stderr)
        before = subprocess.run(['git', 'status', '--porcelain'], cwd=repo, text=True,
                                capture_output=True, check=True).stdout
        first = self.command(repo, environment, sys.executable,
                             'tools/delivery/change-verification.py', 'preflight', '--plan', corrected_name)
        second = self.command(repo, environment, sys.executable,
                              'tools/delivery/change-verification.py', 'preflight', '--plan', corrected_name)
        self.assertEqual([0, 0], [first.returncode, second.returncode], first.stdout + first.stderr)
        first_value, second_value = json.loads(first.stdout), json.loads(second.stdout)
        self.assertEqual('GREEN', first_value['outcome'])
        self.assertTrue(first_value['publication_ready'])
        self.assertEqual('UNKNOWN', first_value['pr'])
        self.assertEqual('UNKNOWN', first_value['ci'])
        self.assertEqual(first_value['result_digest'], second_value['result_digest'])
        self.assertNotEqual(first_value['record_path'], second_value['record_path'])
        self.assertIn('evidence', first_value,
                      'INTENDED_RED publication readiness lacks plan-owned evidence')
        self.assertEqual({item['id'] for item in json.loads(corrected_path.read_text())['commands']},
                         {item['command_id'] for item in first_value['evidence']})
        for item in first_value['evidence']:
            self.assertIn(item['purpose'], {'acceptance', 'boundary', 'category'})
            self.assertIsInstance(item['available_services'], list)
            self.assertIsInstance(item['available_dependencies'], dict)
        after = subprocess.run(['git', 'status', '--porcelain'], cwd=repo, text=True,
                               capture_output=True, check=True).stdout
        self.assertEqual(before, after, 'preflight changed candidate source')

    def test_pr100_db_in_unit_is_rejected_in_unit_environment(self):
        repo, base, _, environment = self.fixture_repo()
        synthetic = 'tests/Verification/synthetic_db_in_unit_test.php'
        (repo / synthetic).write_text('<?php $db = new mysqli("db", "u", "p", "d"); echo "PASS\\n";')
        with (repo / 'tools/verification/suites.tsv').open('a') as stream:
            stream.write(f'unit\tphp\t{synthetic}\n')
        categories = json.loads((repo / 'tools/verification/categories.json').read_text())
        categories[synthetic] = 'unit'
        (repo / 'tools/verification/categories.json').write_text(json.dumps(categories, sort_keys=True) + '\n')
        input_name = self.write_input(repo, test=synthetic)
        planned, plan_path, plan_name = self.plan(repo, environment, base, input_name)
        self.assertEqual(0, planned.returncode, planned.stderr)
        preflight = self.command(repo, environment, sys.executable,
                                 'tools/delivery/change-verification.py', 'preflight', '--plan', plan_name)
        self.assertNotEqual(0, preflight.returncode, 'INTENDED_RED DB-dependent unit admitted')
        self.assertTrue(preflight.stdout.strip(), 'INTENDED_RED preflight emitted no structured mismatch')
        payload = json.loads(preflight.stdout)
        mismatch = [item for item in payload['failures'] if item['code'] == 'CATEGORY_ENVIRONMENT_MISMATCH']
        self.assertEqual(1, len(mismatch))
        self.assertEqual([synthetic, 'unit', 'mariadb'],
                         [mismatch[0]['path'], mismatch[0]['category'], mismatch[0]['dependency']])

    def test_preflight_observes_services_and_dependencies_instead_of_copying_profile(self):
        repo, base, _, environment = self.fixture_repo()
        synthetic = 'tests/Verification/synthetic_observed_environment_test.py'
        (repo / synthetic).write_text('import ci_missing_dependency\nprint("PASS")\n')
        with (repo / 'tools/verification/suites.tsv').open('a') as stream:
            stream.write(f'db\tpython3\t{synthetic}\n')
        categories = json.loads((repo / 'tools/verification/categories.json').read_text())
        categories[synthetic] = 'integration'
        (repo / 'tools/verification/categories.json').write_text(json.dumps(categories, sort_keys=True) + '\n')
        policy_path = repo / '.quality-graph/verification-policy.json'
        policy = json.loads(policy_path.read_text())
        policy['declared_python_imports'] = sorted(set(policy.get('declared_python_imports', [])) |
                                                   {'ci_missing_dependency'})
        policy['service_probes'] = {
            'mariadb': ['python3', '-c', 'raise SystemExit(17)'],
            'docker': ['python3', '-c', 'raise SystemExit(18)'],
            'browser': ['python3', '-c', 'raise SystemExit(19)'],
        }
        policy_path.write_text(json.dumps(policy, sort_keys=True) + '\n')
        input_name = self.write_input(repo, test=synthetic)
        planned, _, plan_name = self.plan(repo, environment, base, input_name)
        self.assertEqual(0, planned.returncode, planned.stderr)
        blocked = self.command(repo, environment, sys.executable,
                               'tools/delivery/change-verification.py', 'preflight', '--plan', plan_name)
        self.assertNotEqual(0, blocked.returncode,
                            'INTENDED_RED required services/dependencies were copied, not observed')
        value = json.loads(blocked.stdout)
        self.assertFalse(value['publication_ready'])
        self.assertEqual({'SERVICE_UNAVAILABLE', 'DEPENDENCY_UNAVAILABLE'},
                         {item['code'] for item in value['failures']})
        command = next(item for item in value['evidence'] if item['argv'][-1] == synthetic)
        self.assertNotIn('mariadb', command['available_services'])
        self.assertFalse(command['profile_compatible'])
        self.assertEqual('probe', command['observation_method'])

        third_party = repo.parent / 'site-packages'; third_party.mkdir()
        (third_party / 'ci_missing_dependency.py').write_text('READY = True\n')
        environment['PYTHONPATH'] = str(third_party)
        policy['service_probes']['mariadb'] = ['python3', '-c', 'raise SystemExit(0)']
        policy_path.write_text(json.dumps(policy, sort_keys=True) + '\n')
        replanned, _, plan_name = self.plan(repo, environment, base, input_name)
        self.assertEqual(0, replanned.returncode, replanned.stderr)
        observed = self.command(repo, environment, sys.executable,
                                'tools/delivery/change-verification.py', 'preflight', '--plan', plan_name)
        self.assertEqual(0, observed.returncode, observed.stdout + observed.stderr)
        observed_value = json.loads(observed.stdout)
        command = next(item for item in observed_value['evidence'] if item['argv'][-1] == synthetic)
        self.assertIn('mariadb', command['available_services'])
        self.assertIn('ci_missing_dependency', command['available_dependencies']['python'])
        self.assertTrue(command['profile_compatible'])

    def test_preflight_accepts_repository_local_sibling_python_import(self):
        repo, base, _, environment = self.fixture_repo()
        helper = repo / 'tests/Verification/ci_local_helper.py'
        helper.write_text('VALUE = "LOCAL_SOURCE"\n')
        synthetic = 'tests/Verification/synthetic_local_import_test.py'
        (repo / synthetic).write_text(
            'import ci_local_helper\n'
            'assert ci_local_helper.VALUE == "LOCAL_SOURCE"\n'
            'print("PASS")\n'
        )
        with (repo / 'tools/verification/suites.tsv').open('a') as stream:
            stream.write(f'unit\tpython3\t{synthetic}\n')
        categories = json.loads((repo / 'tools/verification/categories.json').read_text())
        categories[synthetic] = 'unit'
        (repo / 'tools/verification/categories.json').write_text(json.dumps(categories, sort_keys=True) + '\n')
        input_name = self.write_input(repo, test=synthetic)
        planned, _, plan_name = self.plan(repo, environment, base, input_name)
        self.assertEqual(0, planned.returncode, planned.stderr)
        preflight = self.command(repo, environment, sys.executable,
                                 'tools/delivery/change-verification.py', 'preflight', '--plan', plan_name)
        self.assertEqual(0, preflight.returncode,
                         'INTENDED_RED repository-local sibling import rejected: '
                         + preflight.stdout + preflight.stderr)
        payload = json.loads(preflight.stdout)
        self.assertTrue(payload['publication_ready'])
        self.assertEqual([], payload['failures'])

    def test_shipped_service_probes_do_not_claim_absent_services(self):
        policy = json.loads((ROOT / '.quality-graph/verification-policy.json').read_text())
        tools = {'mariadb': ('mysqladmin', 'ping'), 'container': ('docker', 'info'),
                 'browser': ('node', 'playwright')}
        with tempfile.TemporaryDirectory(prefix='fmonitor-probe-tools-') as directory:
            bindir = Path(directory)
            for tool, _ in tools.values():
                executable = bindir / tool
                executable.write_text('#!/bin/sh\nprintf "%s\\n" "$@" > "$FMONITOR_PROBE_TRACE"\nexit "$FMONITOR_PROBE_EXIT"\n')
                executable.chmod(0o700)
            for service, argv in policy.get('service_probes', {}).items():
                with self.subTest(service=service):
                    self.assertEqual(['python3', 'tools/delivery/probe-environment.py', 'service', service],
                                     argv, f'INTENDED_RED {service} probe bypasses public observer')
                    trace = bindir / f'{service}.trace'
                    environment = dict(os.environ, PATH=str(bindir) + os.pathsep + os.environ['PATH'],
                                       FMONITOR_PROBE_TRACE=str(trace), FMONITOR_PROBE_EXIT='0')
                    available = subprocess.run(argv, cwd=ROOT, env=environment, text=True,
                                               capture_output=True, timeout=10)
                    self.assertEqual(0, available.returncode, available.stdout + available.stderr)
                    self.assertIn(f'{service}=AVAILABLE', available.stdout)
                    self.assertTrue(trace.is_file(), f'{service} probe did not invoke {tools[service][0]}')
                    self.assertIn(tools[service][1], trace.read_text())
                    environment['FMONITOR_PROBE_EXIT'] = '23'
                    unavailable = subprocess.run(argv, cwd=ROOT, env=environment, text=True,
                                                 capture_output=True, timeout=10)
                    self.assertNotEqual(0, unavailable.returncode)
                    self.assertIn(f'{service}=UNAVAILABLE', unavailable.stdout + unavailable.stderr)

    def test_source_identity_mutations_and_repeat_preflight_are_observable(self):
        repo, base, _, environment = self.fixture_repo()
        def state():
            result = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'state')
            self.assertEqual(0, result.returncode, result.stderr)
            return json.loads(result.stdout)
        original = state()
        review = repo / 'reviews/tests/DELIVERY-HARNESS-CI-COMPLETENESS-001.md'
        review.parent.mkdir(parents=True, exist_ok=True); review.write_text('APPROVED\n')
        lifecycle = state()
        self.assertNotEqual(original['source'], lifecycle['source'])
        self.assertIn('executable_source', original, 'INTENDED_RED executable source is absent')
        self.assertIn('executable_source', lifecycle, 'INTENDED_RED executable source is absent')
        self.assertEqual(original['executable_source'], lifecycle['executable_source'])
        (repo / 'tools/delivery/harness.py').write_text(
            (repo / 'tools/delivery/harness.py').read_text() + '\n# executable mutation\n')
        executable = state()
        self.assertNotEqual(lifecycle['executable_source'], executable['executable_source'])
        unknown = repo / 'unexpected-policy-byte.txt'; unknown.write_text('unknown paths are executable\n')
        self.assertNotEqual(executable['executable_source'], state()['executable_source'])

    def test_dependency_workspace_realpath_and_consumer_contract(self):
        repo, base, _, environment = self.fixture_repo()
        outside = repo.parent / 'dependencies'; outside.mkdir()
        vendor = outside / 'vendor'; vendor.mkdir(); (vendor / 'autoload.php').write_text('<?php')
        lock = outside / 'composer.lock'; lock.write_text('{"packages":[]}\n')
        import hashlib
        lock_digest = hashlib.sha256(lock.read_bytes()).hexdigest()
        manifest = outside / 'workspace.json'
        manifest.write_text(json.dumps({'version': 1, 'root': str(vendor.resolve()),
            'allowed_root': str(outside.resolve()), 'lock': str(lock.resolve()),
            'identity': 'sha256:' + lock_digest,
            'consumers': ['tests/Verification/ci_complete_acceptance.py']}) + '\n')
        input_name = self.write_input(repo, workspaces=[str(manifest)])
        accepted = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'prepare',
                                '--input', input_name, '--base', base, '--role', 'root',
                                '--dependency-workspace', str(manifest))
        self.assertEqual(0, accepted.returncode, 'INTENDED_RED declared workspace rejected: ' + accepted.stderr)
        escape = outside / 'escape.json'; escape.symlink_to(manifest)
        rejected = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'prepare',
                                '--input', input_name, '--base', base, '--role', 'root',
                                '--dependency-workspace', str(escape))
        self.assertNotEqual(0, rejected.returncode, 'INTENDED_RED workspace symlink escape admitted')
        cases = {
            'missing-identity.json': {'version': 1, 'root': str(vendor.resolve()),
                                      'allowed_root': str(outside.resolve()), 'lock': str(lock.resolve()),
                                      'consumers': ['tests/Verification/ci_complete_acceptance.py']},
            'unauthorized-consumer.json': {'version': 1, 'root': str(vendor.resolve()),
                'allowed_root': str(outside.resolve()), 'lock': str(lock.resolve()),
                'identity': 'sha256:' + lock_digest, 'consumers': ['tests/Verification/other.py']},
            'missing-root.json': {'version': 1, 'root': str(outside / 'missing'),
                'allowed_root': str(outside.resolve()), 'lock': str(lock.resolve()),
                'identity': 'sha256:' + lock_digest,
                'consumers': ['tests/Verification/ci_complete_acceptance.py']},
            'wrong-lock.json': {'version': 1, 'root': str(vendor.resolve()),
                'allowed_root': str(outside.resolve()), 'lock': str(lock.resolve()),
                'identity': 'sha256:' + ('0' * 64),
                'consumers': ['tests/Verification/ci_complete_acceptance.py']},
            'outside-root.json': {'version': 1, 'root': str(vendor.resolve()),
                'allowed_root': str((outside / 'allowed').resolve()), 'lock': str(lock.resolve()),
                'identity': 'sha256:' + lock_digest,
                'consumers': ['tests/Verification/ci_complete_acceptance.py']},
        }
        for name, value in cases.items():
            with self.subTest(workspace=name):
                path = outside / name; path.write_text(json.dumps(value) + '\n')
                failed = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'prepare',
                                      '--input', input_name, '--base', base, '--role', 'root',
                                      '--dependency-workspace', str(path))
                self.assertNotEqual(0, failed.returncode, f'INTENDED_RED {name} admitted')
        (vendor / 'autoload.php').write_text('<?php // changed')
        changed = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'prepare',
                               '--input', input_name, '--base', base, '--role', 'root',
                               '--dependency-workspace', str(manifest))
        self.assertNotEqual(0, changed.returncode, 'INTENDED_RED mutable workspace identity admitted')

    def test_reviewer_package_accepts_plan_owned_category_evidence_only(self):
        repo, base, _, environment = self.fixture_repo()
        acceptance = 'tests/Verification/ci_complete_acceptance.py'
        category_test = 'tests/Verification/ci_complete_category.py'
        (repo / category_test).write_text('print("CI_COMPLETE_CATEGORY_OK")\n')
        policy = json.loads((repo / '.quality-graph/verification-policy.json').read_text())
        policy['category_argv']['governance'] = [['python3', category_test]]
        (repo / '.quality-graph/verification-policy.json').write_text(json.dumps(policy, sort_keys=True) + '\n')
        categories = json.loads((repo / 'tools/verification/categories.json').read_text())
        categories[acceptance] = 'governance'; categories[category_test] = 'governance'
        (repo / 'tools/verification/categories.json').write_text(json.dumps(categories, sort_keys=True) + '\n')
        with (repo / 'tools/verification/suites.tsv').open('a') as stream:
            stream.write(f'governance\tpython3\t{acceptance}\n')
            stream.write(f'governance\tpython3\t{category_test}\n')
        input_name = self.write_input(repo, test=acceptance)
        prepared = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'prepare',
                                '--input', input_name, '--base', base, '--role', 'root', '--gate', '3')
        self.assertEqual(0, prepared.returncode, prepared.stderr)
        package = json.loads(prepared.stdout)
        run = self.command(repo, environment, sys.executable, 'tools/delivery/change-verification.py',
                           'run', '--plan', package['plan'], '--phase', 'focused', '--diagnostic')
        self.assertEqual(0, run.returncode, run.stdout + run.stderr)
        records = json.loads(run.stdout)['results']
        self.assertTrue(all('purpose' in item for item in records),
                        'INTENDED_RED executed records lose evidence purpose')
        self.assertTrue(any(item['purpose'] == 'category' for item in records))
        for item in records:
            retained = json.loads(Path(item['record_path']).read_text())
            self.assertIn('command_id', retained,
                          'INTENDED_RED retained evidence has no record-owned command identity')
            self.assertEqual(item['command_id'], retained['command_id'])
            self.assertEqual(item['purpose'], retained['purpose'])
            self.assertEqual(item['environment'], retained['command_environment'])
        argv = [sys.executable, 'tools/delivery/harness.py', 'prepare', '--input', input_name,
                '--base', base, '--role', 'reviewer', '--gate', '3']
        for item in records:
            argv.extend(['--evidence', item['record_path']])
        reviewed = self.command(repo, environment, *argv)
        self.assertEqual(0, reviewed.returncode, 'INTENDED_RED plan-owned category evidence rejected: ' + reviewed.stderr)
        unrelated = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'run',
                                 '--', sys.executable, '-c', 'print("unrelated")')
        unrelated_record = json.loads(unrelated.stdout)['record_path']
        rejected = self.command(repo, environment, *argv, '--evidence', unrelated_record)
        self.assertNotEqual(0, rejected.returncode, 'INTENDED_RED unrelated evidence admitted')

    def test_gate3_test_delta_links_historical_red_and_rejects_mismatch(self):
        repo, base, _, environment = self.fixture_repo()
        test = 'tests/Verification/ci_complete_delta.py'
        input_name = self.write_input(repo, test=test)
        (repo / test).write_text('print("EXPECTED_DELTA_RED")\nraise SystemExit(1)\n')
        historical = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'run',
                                  '--command-id', 'acceptance:delta', '--purpose', 'acceptance',
                                  '--acceptance-id', 'fixture', '--intended-red', 'EXPECTED_DELTA_RED',
                                  '--', sys.executable, test)
        self.assertNotEqual(0, historical.returncode)
        self.assertTrue(historical.stdout.strip(),
                        'INTENDED_RED runner rejected command identity instead of retaining typed RED')
        historical_record = json.loads(historical.stdout)['record_path']
        (repo / test).write_text('print("CI_COMPLETE_DELTA_GREEN")\n')
        current = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'run',
                               '--command-id', 'acceptance:delta', '--purpose', 'acceptance',
                               '--acceptance-id', 'fixture', '--', sys.executable, test)
        self.assertEqual(0, current.returncode)
        current_record = json.loads(current.stdout)['record_path']
        accepted = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'prepare',
                                '--input', input_name, '--base', base, '--role', 'reviewer', '--gate', '3',
                                '--evidence', current_record, '--historical-red', historical_record,
                                '--test-delta', test)
        self.assertEqual(0, accepted.returncode, 'INTENDED_RED valid test-delta lineage rejected: ' + accepted.stderr)
        lineage = json.loads(accepted.stdout)['test_delta_lineage']
        self.assertEqual('fixture', lineage['acceptance_id'])
        self.assertEqual(64, len(lineage['base_blob']))
        self.assertEqual(64, len(lineage['current_blob']))
        self.assertNotEqual(lineage['base_blob'], lineage['current_blob'])
        self.assertEqual(64, len(lineage['delta_sha256']))
        unrelated = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'run',
                                 '--command-id', 'acceptance:delta', '--purpose', 'acceptance',
                                 '--acceptance-id', 'other-acceptance', '--intended-red', 'OTHER_RED', '--', sys.executable, '-c',
                                 'print("OTHER_RED");raise SystemExit(1)')
        mismatch = self.command(repo, environment, sys.executable, 'tools/delivery/harness.py', 'prepare',
                                '--input', input_name, '--base', base, '--role', 'reviewer', '--gate', '3',
                                '--evidence', current_record, '--historical-red', json.loads(unrelated.stdout)['record_path'],
                                '--test-delta', test)
        self.assertNotEqual(0, mismatch.returncode, 'INTENDED_RED mismatched historical RED admitted')

    def test_two_worktrees_keep_preflight_binding_and_records_isolated(self):
        repo, base, evidence, environment = self.fixture_repo()
        sibling = repo.parent / 'sibling'
        subprocess.run(['git', 'worktree', 'add', '--detach', str(sibling), 'HEAD'],
                       cwd=repo, check=True, capture_output=True)
        self.addCleanup(lambda: subprocess.run(['git', 'worktree', 'remove', '--force', str(sibling)],
                                                cwd=repo, capture_output=True))
        states = []
        for root, label in [(repo, 'a'), (sibling, 'b')]:
            test = f'tests/Verification/ci_complete_{label}.py'
            input_name = self.write_input(root, test=test)
            prepared = self.command(root, environment, sys.executable, 'tools/delivery/harness.py',
                                    'prepare', '--input', input_name, '--base', base, '--role', 'root')
            self.assertEqual(0, prepared.returncode, prepared.stderr)
            state = self.command(root, environment, sys.executable, 'tools/delivery/harness.py', 'state')
            self.assertEqual(0, state.returncode, state.stderr)
            states.append(json.loads(state.stdout)['active_binding'])
        self.assertEqual([str(repo.resolve()), str(sibling.resolve())],
                         [states[0]['worktree'], states[1]['worktree']])
        self.assertNotEqual(states[0]['plan'], states[1]['plan'])
        self.assertTrue(all(Path(state['plan']).is_relative_to(evidence) for state in states))


if __name__ == '__main__':
    unittest.main(verbosity=2)
