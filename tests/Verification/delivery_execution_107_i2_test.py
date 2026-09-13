"""DELIVERY-EXECUTION-107-I2: real Docker cold/warm and setup boundaries.

Registered as e2e: this corpus deliberately needs Docker, MariaDB and Chromium.
No host vendor or packages are installed. All modified sources are disposable.
"""
import hashlib
import json
import os
import py_compile
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time
import unittest
import uuid

ROOT = Path(__file__).resolve().parents[2]
CHAIN = [
    ('integration', ['php', 'tests/Yii2/yii2_user_access_001_test.php']),
    ('governance', ['python3', 'tests/Verification/change_verification_001_test.py']),
    ('browser', ['php', 'tests/Yii2/yii2_user_access_browser_001_test.php']),
]


class Container107(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix='delivery107-container-')
        self.addCleanup(self.temporary.cleanup)
        self.outer = Path(self.temporary.name)
        retained = Path(os.environ.get('FMONITOR_HARNESS_HOME',
                        str(Path.home() / '.local/share/fmonitor-2/delivery-harness')))
        self.evidence_home = retained / 'corpus' / '107-i2' / uuid.uuid4().hex
        self.evidence_home.mkdir(parents=True, mode=0o700)
        print('CORPUS_HOME107_I2 ' + str(self.evidence_home), flush=True)
        self.env = dict(os.environ, FMONITOR_HARNESS_HOME=str(self.evidence_home),
                        PYTHONDONTWRITEBYTECODE='1', GIT_CONFIG_NOSYSTEM='1',
                        GIT_CONFIG_GLOBAL=os.devnull)

    def cli(self, root, *args, timeout=1200):
        return subprocess.run([sys.executable, str(root / 'tools/delivery/harness.py'), *args],
                              cwd=root, env=self.env, capture_output=True, text=True, timeout=timeout)

    def json_result(self, result):
        self.assertTrue(result.stdout.startswith('{'),
                        'INTENDED_RED declared container execution CLI absent: ' + result.stderr[:1000])
        return json.loads(result.stdout)

    def repository(self, name):
        root = self.outer / name
        root.mkdir()
        paths = set()
        for args in (['ls-files', '-z'], ['ls-files', '--others', '--exclude-standard', '-z']):
            paths.update(subprocess.check_output(['git', *args], cwd=ROOT).decode().split('\0'))
        for relative in sorted(paths - {''}):
            source = ROOT / relative
            if not source.exists() and not source.is_symlink():
                continue
            destination = root / relative
            destination.parent.mkdir(parents=True, exist_ok=True)
            if source.is_symlink():
                destination.symlink_to(os.readlink(source))
            elif source.is_file():
                shutil.copy2(source, destination)
        for args in (['init', '-q'], ['config', 'user.email', 'fixture@example.invalid'],
                     ['config', 'user.name', 'Fixture'], ['add', '.'], ['commit', '-qm', 'cold candidate']):
            subprocess.run(['git', *args], cwd=root, env=self.env, check=True, capture_output=True)
        # Governance launchers use origin/main as a stable explicit base.
        subprocess.run(['git', 'update-ref', 'refs/remotes/origin/main', 'HEAD'], cwd=root, env=self.env, check=True)
        for name in ('vendor', 'node_modules', '.venv'):
            self.assertFalse((root / name).exists())
        return root

    def prepare(self, root, profile):
        result = self.cli(root, 'environment', '--profile', profile, '--prepare')
        value = self.json_result(result)
        self.assertEqual(0, result.returncode, value)
        self.assertEqual('GREEN', value['outcome'])
        self.assertTrue(value['image_id'].startswith('sha256:'))
        self.assertIn('linux', value['platform'])
        self.assertEqual(profile, value['profile'])
        return value

    def run_profile(self, root, profile, argv, run_id='cold'):
        result = self.cli(root, 'run', '--profile', profile, '--task', '107',
                          '--run-id', run_id, '--', *argv)
        summary = self.json_result(result)
        record = json.loads(Path(summary['record_path']).read_text())
        return result, record

    def test_cold_warm_actual_registered_chain_and_observed_identity(self):
        root = self.repository('cold')
        first_records = []
        for profile, argv in CHAIN:
            with self.subTest(profile=profile):
                first = self.prepare(root, profile)
                warm = self.prepare(root, profile)
                self.assertEqual(first['image_id'], warm['image_id'])
                self.assertEqual(0, warm['dependency_installs'])
                alias = self.outer / ('alias-' + profile)
                alias.symlink_to(root, target_is_directory=True)
                aliased = self.prepare(alias, profile)
                self.assertEqual(warm['image_id'], aliased['image_id'])
                self.assertEqual(0, aliased['dependency_installs'])
                for run_id in ('cold', 'warm'):
                    result, record = self.run_profile(root, profile, argv, run_id)
                    self.assertEqual(0, result.returncode, record)
                    self.assertEqual('GREEN', record['command_verdict'])
                    self.assertEqual('APPLICABLE', record['applicability'])
                    execution = record['execution']
                    self.assertEqual(first['image_id'], execution['image_id'])
                    self.assertEqual(profile, execution['profile'])
                    self.assertEqual('3.12.11', execution['runtimes']['python'])
                    self.assertEqual('22.22.0', execution['runtimes']['node'])
                    self.assertTrue(execution['runtimes']['php'].startswith('8.5.'))
                    self.assertEqual(hashlib.sha256((root / 'composer.lock').read_bytes()).hexdigest(),
                                     execution['lockfiles']['composer.lock'])
                    self.assertTrue(record['snapshot']['identity'])
                    self.assertNotEqual(str(root), record['snapshot']['path'])
                    self.assertEqual(0, execution['dependency_installs'])
                    first_records.append(record)
        other = self.repository('other')
        result, other_record = self.run_profile(other, *CHAIN[0], run_id='other')
        self.assertEqual(0, result.returncode, other_record)
        self.assertNotEqual(first_records[0]['execution']['service_identity'],
                            other_record['execution']['service_identity'])
        self.assertEqual('', subprocess.check_output(['git', 'status', '--porcelain'], cwd=root, text=True))

    def test_frozen_source_uses_separate_mutable_fixture(self):
        root = self.repository('snapshot')
        self.prepare(root, 'governance')
        fixture = self.outer / 'mutable-fixture'
        fixture.mkdir()
        script = root / 'tests/Verification/corpus_107_snapshot_test.py'
        script.write_text(
            'import os,time\nfrom pathlib import Path\n'
            'root=Path(__file__).resolve().parents[2]\n'
            'fixture=Path(os.environ["FMONITOR_EXECUTION_FIXTURE_ROOT"])\n'
            '(fixture/"ready").write_text("ready")\n'
            'deadline=time.monotonic()+30\n'
            'while not (fixture/"release").exists():\n'
            ' if time.monotonic()>deadline: raise AssertionError("release missing")\n'
            ' time.sleep(.02)\n'
            'assert (root/"source-value.txt").read_text()=="original"\n'
            'assert (root/"executable.sh").stat().st_mode & 0o111\n'
            'assert not (root/"deleted.txt").exists()\n'
            'try: (root/"source-value.txt").write_text("tampered")\n'
            'except OSError: pass\n'
            'else: raise AssertionError("snapshot writable")\n'
            'print("FROZEN_SNAPSHOT_OK")\n')
        (root / 'source-value.txt').write_text('original')
        (root / 'executable.sh').write_text('#!/bin/sh\nexit 0\n')
        (root / 'executable.sh').chmod(0o755)
        (root / 'deleted.txt').write_text('tracked deletion witness')
        for args in (['add', '.'], ['commit', '-qm', 'snapshot file mode baseline']):
            subprocess.run(['git', *args], cwd=root, env=self.env, check=True, capture_output=True)
        (root / 'deleted.txt').unlink()
        process = subprocess.Popen([sys.executable, str(root / 'tools/delivery/harness.py'),
                                    'run', '--profile', 'governance', '--fixture', str(fixture),
                                    '--task', '107', '--run-id', 'snapshot', '--',
                                    'python3', 'tests/Verification/corpus_107_snapshot_test.py'],
                                   cwd=root, env=self.env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        try:
            deadline = time.monotonic() + 90
            while not (fixture / 'ready').exists() and process.poll() is None and time.monotonic() < deadline:
                time.sleep(.02)
            self.assertTrue((fixture / 'ready').exists(), 'INTENDED_RED mutable fixture handshake absent')
            (root / 'source-value.txt').write_text('changed while child runs')
            (fixture / 'release').write_text('release')
            output, error = process.communicate(timeout=40)
            summary = json.loads(output)
            record = json.loads(Path(summary['record_path']).read_text())
            self.assertEqual('GREEN', record['command_verdict'], error)
            self.assertEqual('STALE', record['applicability'])
            self.assertIn('FROZEN_SNAPSHOT_OK', Path(record['stdout_path']).read_text())
            self.assertEqual('changed while child runs', (root / 'source-value.txt').read_text())
        finally:
            if process.poll() is None:
                process.terminate()
                process.communicate(timeout=30)

    def test_cancellation_cleans_only_owned_resources_while_other_worktree_runs(self):
        roots = [self.repository('cancel-one'), self.repository('survive-two')]
        self.prepare(roots[0], 'integration')
        processes = []
        fixtures = []
        try:
            for index, root in enumerate(roots):
                fixture = self.outer / ('barrier-' + str(index))
                fixture.mkdir()
                fixtures.append(fixture)
                path = root / 'tests/Verification/corpus_107_barrier.py'
                path.write_text(
                    'import os,time,subprocess\nfrom pathlib import Path\n'
                    'f=Path(os.environ["FMONITOR_EXECUTION_FIXTURE_ROOT"])\n'
                    '(f/"ready").write_text("ready")\n'
                    'deadline=time.monotonic()+300\n'
                    'while not (f/"release").exists():\n'
                    ' if time.monotonic()>deadline: raise AssertionError("barrier expired")\n'
                    ' time.sleep(.02)\n'
                    'subprocess.run(["php","tests/Yii2/yii2_user_access_001_test.php"],check=True)\n')
                processes.append(subprocess.Popen([
                    sys.executable, str(root / 'tools/delivery/harness.py'), 'run', '--profile', 'integration',
                    '--fixture', str(fixture), '--task', '107', '--run-id', 'parallel-' + str(index),
                    '--', 'python3', 'tests/Verification/corpus_107_barrier.py'],
                    cwd=root, env=self.env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True))
            deadline = time.monotonic() + 300
            while not all((f / 'ready').exists() for f in fixtures) and time.monotonic() < deadline:
                if any(p.poll() is not None for p in processes):
                    break
                time.sleep(.02)
            self.assertTrue(all((f / 'ready').exists() for f in fixtures),
                            'INTENDED_RED isolated concurrent services unavailable')
            processes[0].terminate()
            output, error = processes[0].communicate(timeout=45)
            first = json.loads(Path(json.loads(output)['record_path']).read_text())
            self.assertEqual('INTERRUPTED', first['command_verdict'], error)
            (fixtures[1] / 'release').write_text('release')
            output, error = processes[1].communicate(timeout=120)
            second = json.loads(Path(json.loads(output)['record_path']).read_text())
            self.assertEqual('GREEN', second['command_verdict'], error)
            self.assertNotEqual(first['execution']['service_identity'], second['execution']['service_identity'])
            for record in (first, second):
                resources = record['execution']['resource_ids']
                self.assertTrue(resources['containers'])
                for kind, identifiers in resources.items():
                    for identifier in identifiers:
                        check = subprocess.run(['docker', kind.rstrip('s'), 'inspect', identifier],
                                               capture_output=True, text=True, timeout=10)
                        self.assertNotEqual(0, check.returncode, 'owned resource leaked: ' + identifier)
        finally:
            for process in processes:
                if process.poll() is None:
                    process.terminate()
                    process.communicate(timeout=45)

    def test_foreign_autoload_and_missing_docker_are_setup_failures(self):
        root = self.repository('foreign')
        foreign = self.outer / 'foreign-vendor'
        foreign.mkdir()
        (foreign / 'autoload.php').write_text('<?php throw new Exception("foreign code ran");')
        (root / 'vendor').symlink_to(foreign, target_is_directory=True)
        result, record = self.run_profile(root, *CHAIN[0])
        self.assertNotEqual(0, result.returncode)
        self.assertEqual('SETUP_FAILURE', record['command_verdict'])
        logs = Path(record['stdout_path']).read_text() + Path(record['stderr_path']).read_text()
        self.assertIn('AUTOLOAD_ORIGIN', logs)
        self.assertNotIn('foreign code ran', logs)
        self.assertTrue((root / 'vendor').is_symlink())
        self.assertTrue((foreign / 'autoload.php').exists())
        bin_dir = self.outer / 'bin'
        bin_dir.mkdir()
        docker = bin_dir / 'docker'
        docker.write_text('#!/bin/sh\necho "Docker unavailable witness" >&2\nexit 127\n')
        docker.chmod(0o700)
        self.env['PATH'] = str(bin_dir) + os.pathsep + os.environ['PATH']
        result = self.cli(ROOT, 'environment', '--profile', 'governance', '--prepare')
        value = self.json_result(result)
        self.assertNotEqual(0, result.returncode)
        self.assertEqual('SETUP_FAILURE', value['outcome'])
        self.assertIn('DOCKER_UNAVAILABLE', json.dumps(value))

    def test_lock_change_cannot_reuse_old_dependency_layer(self):
        root = self.repository('locks')
        first = self.prepare(root, 'governance')
        lock = root / 'composer.lock'
        value = json.loads(lock.read_text())
        value['_readme'] = ['AC03 harmless valid lock metadata delta']
        lock.write_text(json.dumps(value))
        second = self.prepare(root, 'governance')
        self.assertNotEqual(first['lockfiles']['composer.lock'], second['lockfiles']['composer.lock'])
        self.assertNotEqual(first['image_id'], second['image_id'])

    def test_fixture_registry_and_profile_witnesses(self):
        inventory = json.loads((ROOT / 'tools/verification/categories.json').read_text())
        roster = (ROOT / 'tools/verification/suites.tsv').read_text()
        own_path = 'tests/Verification/delivery_execution_107_i2_test.py'
        self.assertEqual('e2e', inventory.get(own_path), 'INTENDED_RED real Docker corpus not registered e2e')
        self.assertEqual(1, roster.count('\t' + own_path + '\n'))
        for profile, argv in CHAIN:
            self.assertIn(argv[-1], inventory)
            self.assertIn('\t' + argv[-1] + '\n', roster)
            self.assertTrue((ROOT / argv[-1]).is_file())
        result = self.cli(ROOT, 'environment', '--profile', 'untrusted', '--prepare', timeout=20)
        self.assertNotEqual(0, result.returncode)

    def test_common_make_ci_route_and_conservative_full_selection(self):
        dry = subprocess.run(['make', '-n', 'test', 'CATEGORY=governance'], cwd=ROOT,
                             env=self.env, capture_output=True, text=True, timeout=20)
        self.assertEqual(0, dry.returncode, dry.stderr)
        route = dry.stdout + (ROOT / 'tools/verification/run.sh').read_text()
        self.assertIn('harness.py', route, 'INTENDED_RED Make category bypasses container launcher')
        workflow = (ROOT / '.github/workflows/quality-graph.yml').read_text()
        self.assertIn('harness.py run --profile', workflow,
                      'INTENDED_RED CI does not use shared container execution')
        setup = (ROOT / '.github/actions/setup-runtime/action.yml').read_text()
        self.assertNotIn('shivammathur/setup-php', setup)
        self.assertNotIn('actions/setup-node', setup)

    def test_shared_profile_and_unknown_diff_require_full_ci(self):
        root = self.repository('ci-selection')
        base = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=root, env=self.env, text=True).strip()
        pins = root / 'tools/delivery/dependencies.env'
        pins.write_text(pins.read_text() + '\n# shared profile input changes\n')
        for args in (['add', '.'], ['commit', '-qm', 'shared execution profile delta']):
            subprocess.run(['git', *args], cwd=root, env=self.env, check=True, capture_output=True)
        for reference in (base, 'nonexistent-base'):
            with self.subTest(reference=reference):
                result = subprocess.run([sys.executable, 'tools/verification/ci.py', 'plan',
                                         '--base', reference, '--event', 'pull_request'], cwd=root,
                                        env=self.env, capture_output=True, text=True, timeout=20)
                self.assertEqual(0, result.returncode, result.stderr)
                self.assertEqual('full', json.loads(result.stdout)['mode'],
                                 'INTENDED_RED shared execution/unknown base narrowed to harness mode')

    def test_global_ignore_cannot_hide_relevant_candidate_code(self):
        root = self.repository('governance-fixtures')
        global_ignore = self.outer / 'global-ignore'
        global_ignore.write_text('globally_hidden107.py\n')
        global_config = self.outer / 'gitconfig'
        global_config.write_text('[core]\nexcludesFile = ' + str(global_ignore) + '\n')
        self.env['GIT_CONFIG_GLOBAL'] = str(global_config)
        before = self.json_result(self.cli(root, 'state'))
        self.assertEqual(64, len(before['source']))
        (root / 'tests/Verification/globally_hidden107.py').write_text('VALUE = "must be accounted"\n')
        after = self.json_result(self.cli(root, 'state'))
        self.assertTrue(after.get('source') != before['source'] or
                        'GLOBAL_IGNORE_CONFLICT' in json.dumps(after.get('reasons', [])),
                        'INTENDED_RED global ignore silently hides relevant candidate code')

    def test_untracked_bytecode_without_ignores_is_not_candidate_source(self):
        root = self.repository('bytecode-fixture')
        ignore = root / '.gitignore'
        ignore.write_text('\n'.join(line for line in ignore.read_text().splitlines()
                                    if '__pycache__' not in line and '*.pyc' not in line and '*.pyo' not in line) + '\n')
        source = root / 'tests/Verification/imported107.py'
        source.write_text('VALUE = 107\n')
        before = self.json_result(self.cli(root, 'state'))
        artifact = Path(py_compile.compile(str(source), doraise=True))
        self.assertTrue(artifact.is_file())
        after = self.json_result(self.cli(root, 'state'))
        self.assertEqual(before['source'], after['source'],
                         'INTENDED_RED generated bytecode polluted source identity without global ignore')

    def test_independent_runtime_autoload_observation_and_automatic_profile(self):
        root = self.repository('observed')
        path = root / 'tests/Verification/corpus_107_observe.py'
        php = ('require getcwd()."/vendor/autoload.php";require getcwd()."/vendor/yiisoft/yii2/Yii.php";'
               'echo json_encode(["php"=>PHP_VERSION,"project"=>(new ReflectionClass('
               '"FMonitor2\\\\InstallationProcess\\\\CanonicalMigrationApplication"))->getFileName(),'
               '"yii"=>(new ReflectionClass("yii\\\\base\\\\Application"))->getFileName()]);')
        path.write_text('import json,platform,subprocess\nfrom pathlib import Path\n'
                        'v=json.loads(subprocess.check_output(["php","-r",' + repr(php) + '],text=True))\n'
                        'v.update(python=platform.python_version(),node=subprocess.check_output('
                        '["node","-p","process.versions.node"],text=True).strip(),root=str(Path(__file__).resolve().parents[2]))\n'
                        'print("OBSERVED107 "+json.dumps(v))\n')
        for profile, _ in CHAIN:
            result, record = self.run_profile(root, profile, ['python3', 'tests/Verification/corpus_107_observe.py'])
            self.assertEqual(0, result.returncode, record)
            output = Path(record['stdout_path']).read_text()
            observed = json.loads(next(line[len('OBSERVED107 '):] for line in output.splitlines()
                                       if line.startswith('OBSERVED107 ')))
            for language in ('python', 'node', 'php'):
                self.assertEqual(observed[language], record['execution']['runtimes'][language])
            self.assertEqual(record['snapshot']['container_path'], observed['root'])
            self.assertTrue(observed['project'].startswith(observed['root'] + '/'))
            self.assertTrue(observed['yii'].startswith(record['execution']['dependency_root'] + '/'))
            self.assertFalse(observed['yii'].startswith(observed['root'] + '/app/'))
            image = subprocess.run(['docker', 'image', 'inspect', record['execution']['image_id']],
                                   capture_output=True, text=True, timeout=15, check=True)
            inspected = json.loads(image.stdout)[0]
            self.assertEqual(inspected['Id'], record['execution']['image_id'])
            self.assertEqual(inspected['Os'] + '/' + inspected['Architecture'], record['execution']['platform'])
        result = self.cli(root, 'run', '--task', '107', '--run-id', 'auto-profile', '--', *CHAIN[0][1])
        summary = self.json_result(result)
        record = json.loads(Path(summary['record_path']).read_text())
        self.assertEqual(0, result.returncode, record)
        self.assertEqual('integration', record['execution']['profile'],
                         'INTENDED_RED registered launcher has no automatic container profile')

    def test_shipped_preflight_rejects_defects_and_accepts_local_dependencies(self):
        root = self.repository('preflight')
        driver = 'tests/Verification/corpus_107_preflight.py'
        input_path = 'specs/corpus-107-input.json'
        spec_path = 'specs/CORPUS-107.md'
        test_path = 'tests/Verification/corpus_107_dependency_test.py'
        (root / spec_path).write_text('CORPUS-107: public dependency preflight\n')
        (root / 'tests/Verification/local_107.py').write_text('VALUE = 1\n')
        (root / test_path).write_text('import local_107\nassert local_107.VALUE == 1\n')
        # Exercise the shipped public plan/preflight composition inside its declared profile.
        (root / driver).write_text(
            'import json,subprocess,sys\n'
            'p=subprocess.run([sys.executable,"tools/delivery/harness.py","prepare",'
            '"--input","' + input_path + '","--base","origin/main","--role","root"],'
            'capture_output=True,text=True)\n'
            'if p.returncode:\n'
            ' sys.stdout.write(p.stdout);sys.stderr.write(p.stderr);raise SystemExit(p.returncode)\n'
            'plan=json.loads(p.stdout)["plan"]\n'
            'raise SystemExit(subprocess.call([sys.executable,"tools/delivery/change-verification.py",'
            '"preflight","--plan",plan]))\n')
        base_input = dict(change='reliable-delivery-execution-corpus', planned_paths=[test_path],
                          acceptances=[dict(spec_id='CORPUS-107', acceptance_id='dependency',
                                            spec_path=spec_path, seam='preflight', tests=[test_path])])
        (root / input_path).write_text(json.dumps(base_input))
        categories_path = root / 'tools/verification/categories.json'
        categories = json.loads(categories_path.read_text())
        categories[test_path] = 'governance'
        categories[driver] = 'governance'
        categories_path.write_text(json.dumps(categories))
        roster = root / 'tools/verification/suites.tsv'
        roster.write_text(roster.read_text() + '\nunit\tpython3\t' + test_path + '\nunit\tpython3\t' + driver + '\n')
        for args in (['add', '.'], ['commit', '-qm', 'registered dependency corpus'],
                     ['update-ref', 'refs/remotes/origin/main', 'HEAD']):
            subprocess.run(['git', *args], cwd=root, env=self.env, check=True, capture_output=True)
        policy_path = root / '.quality-graph/verification-policy.json'
        policy = policy_path.read_text()
        original_roster = roster.read_text()
        cases = [
            ('healthy-local', None),
            ('unknown-python', 'UNDECLARED_TEST_DEPENDENCY'),
            ('missing-declared-package', 'DEPENDENCY_UNAVAILABLE'),
            ('healthy-node', None),
            ('healthy-node-subpath', None),
            ('unknown-node', 'UNDECLARED_TEST_DEPENDENCY'),
            ('unknown-node-subpath', 'UNDECLARED_TEST_DEPENDENCY'),
            ('missing-declared-node', 'DEPENDENCY_UNAVAILABLE'),
            ('missing-scoped-node', 'DEPENDENCY_UNAVAILABLE'),
            ('stale-registry', 'STALE_VERIFICATION_INVENTORY'),
            ('generated-drift', 'GENERATED_SOURCE_DRIFT'),
            ('db-in-unit', 'CATEGORY_ENVIRONMENT_MISMATCH'),
            ('db-helper-in-unit', 'CATEGORY_ENVIRONMENT_MISMATCH'),
            ('nested-db-helper-in-unit', 'CATEGORY_ENVIRONMENT_MISMATCH'),
            ('missing-declared-php', 'DEPENDENCY_UNAVAILABLE'),
            ('healthy-db-helper', None),
            ('missing-service', 'SERVICE_UNAVAILABLE'),
            ('unconditional-probe', 'UNTRUSTED_SERVICE_PROBE'),
        ]
        generated = root / 'deploy/runtime/Dockerfile'
        generated_bytes = generated.read_bytes()
        for case, expected in cases:
            with self.subTest(case=case):
                policy_path.write_text(policy)
                categories_path.write_text(json.dumps(categories))
                roster.write_text(original_roster)
                generated.write_bytes(generated_bytes)
                (root / test_path).write_text('import local_107\nassert local_107.VALUE == 1\n')
                value = json.loads(json.dumps(base_input))
                node_path = 'tests/Verification/corpus_107_node_test.mjs'
                php_path = 'tests/Verification/corpus_107_db_test.php'
                helper_path = 'tests/Verification/helper_107_db.php'
                bridge_path = 'tests/Verification/bridge_107_db.php'
                for relative in (node_path, php_path, helper_path, bridge_path):
                    if (root / relative).exists():
                        (root / relative).unlink()
                if case in ('unknown-python', 'missing-declared-package'):
                    (root / test_path).write_text('import nonexistent_delivery107_package\n')
                    if case == 'missing-declared-package':
                        changed = json.loads(policy)
                        changed['declared_python_imports'].append('nonexistent_delivery107_package')
                        policy_path.write_text(json.dumps(changed))
                elif case in ('healthy-node', 'healthy-node-subpath', 'unknown-node',
                              'unknown-node-subpath', 'missing-declared-node', 'missing-scoped-node'):
                    module = {'healthy-node': 'node:fs', 'healthy-node-subpath': 'node:fs/promises',
                              'unknown-node': 'node:nonexistent_delivery107',
                              'unknown-node-subpath': 'node:fs/nonexistent_delivery107',
                              'missing-declared-node': 'nonexistent_delivery107_package',
                              'missing-scoped-node': '@delivery107/nonexistent'}[case]
                    if case.startswith('missing-'):
                        changed = json.loads(policy)
                        changed['declared_node_dependencies'].append(module)
                        policy_path.write_text(json.dumps(changed))
                    (root / node_path).write_text('import x from ' + json.dumps(module) + ';\n')
                    value['planned_paths'] = [node_path]
                    value['acceptances'][0]['tests'] = [node_path]
                    current = dict(categories, **{node_path: 'governance'})
                    categories_path.write_text(json.dumps(current))
                    roster.write_text(original_roster + 'unit\tnode\t' + node_path + '\n')
                elif case == 'stale-registry':
                    current = dict(categories)
                    current.pop(test_path)
                    categories_path.write_text(json.dumps(current))
                elif case == 'generated-drift':
                    generated.write_bytes(generated_bytes + b'\n# drift witness\n')
                    value['planned_paths'].append('deploy/runtime/Dockerfile')
                elif case in ('db-in-unit', 'db-helper-in-unit', 'nested-db-helper-in-unit'):
                    (root / php_path).write_text('<?php $db = new mysqli("127.0.0.1");\n')
                    if case != 'db-in-unit':
                        (root / helper_path).write_text('<?php $db = new mysqli("127.0.0.1");\n')
                        required = 'helper_107_db.php'
                        if case == 'nested-db-helper-in-unit':
                            (root / bridge_path).write_text("<?php require_once __DIR__.'/helper_107_db.php';\n")
                            required = 'bridge_107_db.php'
                        (root / php_path).write_text("<?php require_once __DIR__.'/" + required + "';\n")
                    value['planned_paths'] = [php_path]
                    value['acceptances'][0]['tests'] = [php_path]
                    categories_path.write_text(json.dumps(dict(categories, **{php_path: 'unit'})))
                    roster.write_text(original_roster + 'unit\tphp\t' + php_path + '\n')
                elif case == 'missing-declared-php':
                    dependency = 'nonexistent_delivery107_library.php'
                    (root / php_path).write_text("<?php require '" + dependency + "';\n")
                    changed = json.loads(policy)
                    changed['declared_php_dependencies'].append(dependency)
                    policy_path.write_text(json.dumps(changed))
                    value['planned_paths'] = [php_path]
                    value['acceptances'][0]['tests'] = [php_path]
                    categories_path.write_text(json.dumps(dict(categories, **{php_path: 'governance'})))
                    roster.write_text(original_roster + 'unit\tphp\t' + php_path + '\n')
                elif case == 'unconditional-probe':
                    changed = json.loads(policy)
                    changed['service_probes']['mariadb'] = ['sh', '-c', 'exit 0']
                    policy_path.write_text(json.dumps(changed))
                elif case in ('healthy-db-helper', 'missing-service'):
                    value['planned_paths'] = ['tests/Yii2/Yii2AuthFixture.php']
                    value['acceptances'][0]['tests'] = [CHAIN[0][1][-1]]
                (root / input_path).write_text(json.dumps(value))
                profile = 'integration' if case == 'healthy-db-helper' else 'governance'
                result, record = self.run_profile(root, profile, ['python3', driver], case)
                output = Path(record['stdout_path']).read_text() + Path(record['stderr_path']).read_text()
                if expected is None:
                    self.assertEqual(0, result.returncode, output)
                    self.assertEqual('GREEN', record['command_verdict'])
                else:
                    self.assertNotEqual(0, result.returncode,
                                        'INTENDED_RED preflight accepted ' + case + '\n' + output)
                    self.assertIn(expected, output)


if __name__ == '__main__':
    unittest.main()
