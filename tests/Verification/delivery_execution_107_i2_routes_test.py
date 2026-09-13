"""DELIVERY-EXECUTION-107-I2: actual Docker consumers and shared public routes."""
import hashlib
import copy
import json
import os
from pathlib import Path
import re
import shlex
import shutil
import subprocess
import sys
import unittest

from delivery_execution_107_i2_test import Container107, CHAIN, ROOT


class Routes107(Container107):
    def register(self, root, path, category):
        inventory = root / 'tools/verification/categories.json'
        values = json.loads(inventory.read_text())
        values[path] = category
        inventory.write_text(json.dumps(values))
        roster = root / 'tools/verification/suites.tsv'
        group = {'integration': 'db', 'e2e': 'e2e', 'unit': 'unit', 'governance': 'unit'}[category]
        roster.write_text(roster.read_text() + '\n' + group + '\tpython3\t' + path + '\n')

    def ci_script(self, root, job):
        source = (root / '.github/workflows/quality-graph.yml').read_text()
        match = re.search(r'^  ' + job + r':\n(.*?)(?=^  [a-zA-Z][\w-]*:|\Z)',
                          source, re.M | re.S)
        self.assertIsNotNone(match, 'INTENDED_RED supported CI job absent: ' + job)
        block = match.group(1)
        lines = block.splitlines()
        indices = [i for i, line in enumerate(lines) if line.startswith('      run:')]
        self.assertEqual(1, len(indices), 'INTENDED_RED expected one shared CI execution step: ' + job)
        index = indices[0]
        first = lines[index].split('run:', 1)[1].strip()
        continuation = []
        for line in lines[index + 1:]:
            if line and not line.startswith('        '):
                break
            continuation.append(line[8:])
        return '\n'.join(([] if first in ('|', '>') else [first]) + continuation)

    def test_nested_records_survive_temporary_checkout_cleanup(self):
        root = self.repository('retained')
        result, record = self.run_profile(root, 'governance',
            ['python3', '-c', 'import sys;print("RETAINED107");print("STDERR107",file=sys.stderr)'])
        self.assertEqual(0, result.returncode, record)
        record_path = self.evidence_home / 'records' / (record['id'] + '.json')
        before = {path: path.read_bytes() for path in
                  (record_path, Path(record['stdout_path']), Path(record['stderr_path']))}
        self.temporary.cleanup()
        self.assertFalse(root.exists())
        for path, value in before.items():
            self.assertTrue(path.resolve().is_relative_to(self.evidence_home.resolve()))
            self.assertEqual(value, path.read_bytes())

    def test_transitive_daemon_requirement_and_inert_comment_do_not_share_privilege(self):
        root = self.repository('daemon-dependencies')
        helper = root / 'tests/Verification/service107.py'
        helper.write_text('import subprocess\ndef observe():\n'
                          ' subprocess.run(["docker","info"],check=True,stdout=subprocess.DEVNULL)\n'
                          ' print("DAEMON_OBSERVED107")\n')
        path = 'tests/Verification/service107_test.py'
        (root / path).write_text('import service107\nservice107.observe()\n')
        self.register(root, path, 'e2e')
        for arguments in ([], ['--witness']):
            with self.subTest(arguments=arguments):
                result, record = self.run_profile(root, 'browser', ['python3', path, *arguments])
                self.assertEqual(0, result.returncode,
                                 'INTENDED_RED transitive/argument-bearing launcher lost service: ' + str(record))
                self.assertIn('DAEMON_OBSERVED107', Path(record['stdout_path']).read_text())

    def test_inert_comment_does_not_grant_daemon_privilege(self):
        root = self.repository('inert-comment')
        inert = 'tests/Verification/inert107_test.py'
        (root / inert).write_text('import os\nfrom pathlib import Path\n'
                                  '# Docker is an inert comment, not a prerequisite.\n'
                                  'assert not Path("/var/run/docker.sock").exists(), "DAEMON_PRIVILEGE_LEAK107"\n'
                                  'assert not os.environ.get("DOCKER_HOST"), "DAEMON_REMOTE_PRIVILEGE_LEAK107"\n')
        self.register(root, inert, 'e2e')
        result, record = self.run_profile(root, 'browser', ['python3', inert])
        self.assertEqual(0, result.returncode, 'INTENDED_RED comment granted daemon: ' + str(record))

    def test_insufficient_explicit_profile_rejects_registered_launcher_before_execution(self):
        root = self.repository('profile-mismatch')
        for profile, argv in [('governance', CHAIN[0][1]), ('integration', CHAIN[2][1])]:
            with self.subTest(profile=profile, argv=argv):
                result = self.cli(root, 'run', '--profile', profile, '--task', '107',
                                  '--run-id', 'wrong-profile', '--', *argv)
                self.assertNotEqual(0, result.returncode)
                summary = self.json_result(result)
                record = json.loads(Path(summary['record_path']).read_text())
                self.assertEqual('SETUP_FAILURE', record['command_verdict'],
                                 'INTENDED_RED incompatible profile reached project check')
                self.assertIn('CATEGORY_ENVIRONMENT_MISMATCH', Path(record['stderr_path']).read_text())

    def test_local_and_actual_ci_category_execute_one_container_without_host_runtimes(self):
        root = self.repository('common-route')
        path = 'tests/Verification/route107_test.py'
        (root / path).write_text('from pathlib import Path\n'
                                'assert Path("/.dockerenv").exists(), "HOST_EXECUTION107"\n'
                                'print("ONE_EXECUTION107")\n')
        self.register(root, path, 'unit')
        inventory = root / 'tools/verification/categories.json'
        values = {name: 'governance' for name in json.loads(inventory.read_text())}
        values[path] = 'unit'
        inventory.write_text(json.dumps(values))
        roster = root / 'tools/verification/suites.tsv'
        rows = []
        for line in roster.read_text().splitlines():
            if not line or line.startswith('#'):
                rows.append(line)
                continue
            group, runtime, registered = line.split('\t')
            rows.append('\t'.join(('unit' if registered == path else 'characterization', runtime, registered)))
        roster.write_text('\n'.join(rows) + '\n')
        # Keep the complete real roster; only this selected category is bounded.
        sentinel = self.outer / 'host-runtime-used'
        blocked = self.outer / 'host-runtime-guards'
        blocked.mkdir()
        for runtime in ('php', 'node', 'uv', 'composer'):
            guard = blocked / runtime
            guard.write_text('#!/bin/sh\necho HOST_RUNTIME_USED107 >> "' + str(sentinel) + '"\nexit 93\n')
            guard.chmod(0o755)
        environment = dict(self.env, PATH=str(blocked) + os.pathsep + os.environ['PATH'])
        for route in ('make test CATEGORY=unit', 'make unit-test', self.ci_script(root, 'unit')):
            with self.subTest(route=route):
                before = set(self.evidence_home.rglob('records/*.json'))
                result = subprocess.run(['bash', '-c', route], cwd=root, env=environment,
                                        capture_output=True, text=True, timeout=300)
                self.assertEqual(0, result.returncode,
                                 'INTENDED_RED common category route failed\n' + result.stdout + result.stderr)
                self.assertFalse(sentinel.exists(), 'host project runtime was consumed')
                records = [json.loads(p.read_text()) for p in
                           set(self.evidence_home.rglob('records/*.json')) - before]
                leaves = [r for r in records if r.get('argv') == ['python3', path]]
                self.assertEqual(1, len(leaves), 'one leaf execution, no recursive rewrap')
                self.assertEqual(1, Path(leaves[0]['stdout_path']).read_text().count('ONE_EXECUTION107'))
                runners = {name for r in records for name in r.get('execution', {}).get(
                    'resource_ids', {}).get('containers', []) if name.startswith('fm2v-run-')}
                self.assertEqual(1, len(runners), 'one actual container for one selected check')
        focused = subprocess.run(['make', 'lint', 'architecture-check'], cwd=root, env=environment,
                                 capture_output=True, text=True, timeout=300)
        self.assertEqual(0, focused.returncode,
                         'INTENDED_RED legacy fast route consumed host runtime\n' + focused.stdout + focused.stderr)
        self.assertFalse(sentinel.exists(), 'legacy fast consumed host runtime')
        makefile = (root / 'Makefile').read_text()
        self.assertNotRegex(makefile, r'(?m)^(?:db-test|e2e-test):[^\n]*test-env-up',
                            'shared legacy Compose setup bypasses run-owned service isolation')

    def test_real_governance_dependencies_and_pinned_browser_asset_path(self):
        root = self.repository('locked-governance')
        path = 'tests/Verification/locked107_probe.py'
        (root / path).write_text(
            'import importlib.metadata,json,subprocess\n'
            'from pathlib import Path\n'
            'pins=dict(line.split("=",1) for line in Path("tools/delivery/dependencies.env").read_text().splitlines() '
            'if line and not line.startswith("#"))\n'
            'assert subprocess.check_output(["uv","--version"],text=True).split()[1]==pins["UV_VERSION"]\n'
            'for name in ("quality-graph-cli","quality-graph-github"):\n'
            ' assert importlib.metadata.version(name)=="0.1.7"\n'
            'extensions=json.loads(subprocess.check_output(["php","-r","echo json_encode(get_loaded_extensions());"],text=True))\n'
            'assert set(pins["PHP_EXTENSIONS"].split(",")) <= set(extensions), extensions\n'
            'print("LOCKED_GOVERNANCE107")\n')
        result, record = self.run_profile(root, 'governance', ['python3', path])
        self.assertEqual(0, result.returncode, 'INTENDED_RED declared governance closure absent: ' + str(record))

    def test_pinned_browser_asset_path_exists_outside_source(self):
        root = self.repository('pinned-assets')
        assets = 'tests/Verification/assets107_probe.py'
        (root / assets).write_text('import os\nfrom pathlib import Path\n'
            'p=Path(os.environ["FMONITOR_SHLZ_UI_ROOT"])\n'
            'assert p.is_dir(), "PINNED_ASSET_ROOT_MISSING107"\n'
            'assert not p.resolve().is_relative_to(Path.cwd().resolve())\n'
            'assert (p/"packages").is_dir(), "PINNED_ASSETS_MISSING107"\n'
            'print("PINNED_ASSETS107")\n')
        result, record = self.run_profile(root, 'browser', ['python3', assets])
        self.assertEqual(0, result.returncode, 'INTENDED_RED declared browser asset root absent: ' + str(record))
        driver = 'tests/Verification/asset107_http.php'
        (root / driver).write_text('''<?php
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/Yii2/UserAccessFixture.php';
$f=new UserAccessFixture(dirname(__DIR__,2));
try {
    $expected=realpath(getenv('FMONITOR_SHLZ_UI_ROOT').'/packages/styles/dist/shlz.css');
    assertSameValue(true,is_string($expected),'pinned public CSS exists');
    assertSameValue($expected,realpath($f->auth->environment()['FMONITOR_SHLZ_CSS_PATH']),
        'registered fixture consumes pinned CSS origin');
    $f->start();$cookies=[];$response=$f->request('GET','/pilot/assets/shlz.css',[],$cookies);
    assertSameValue(200,$response['status'],'pinned CSS served by actual Yii route');
    assertSameValue(hash_file('sha256',$expected),hash('sha256',$response['body']),
        'served bytes match immutable public CSS');
    echo "PINNED_ASSET_CONSUMED107\\n";
} finally {$f->close();}
''')
        result, record = self.run_profile(root, 'browser', ['php', driver])
        self.assertEqual(0, result.returncode, 'INTENDED_RED actual Yii fixture did not consume pinned asset: ' + str(record))

    def test_shipped_fast_command_list_runs_in_governance_without_source_install(self):
        root = self.repository('actual-fast')
        script = self.ci_script(root, 'fast')
        result = subprocess.run(['bash', '-e', '-c', script], cwd=root, env=self.env,
                                capture_output=True, text=True, timeout=600)
        self.assertEqual(0, result.returncode,
                         'INTENDED_RED shipped fast route cannot execute\n' + result.stdout + result.stderr)
        for name in ('vendor', 'node_modules', '.venv'):
            self.assertFalse((root / name).exists(), 'source dependency installation: ' + name)
        (root / 'tools/delivery/render-dependencies.py').write_text(
            'import sys\nprint("CONTROLLED_FAST_FAILURE107",file=sys.stderr)\nraise SystemExit(79)\n')
        rejected = subprocess.run(['bash', '-e', '-c', self.ci_script(root, 'fast')],
                                  cwd=root, env=self.env, capture_output=True, text=True, timeout=600)
        self.assertNotEqual(0, rejected.returncode,
                            'INTENDED_RED later successful command concealed fast-stage failure')


    def test_all_legacy_suite_aliases_use_owned_profiles_before_host_prerequisites(self):
        root = self.repository('legacy-aliases')
        path = 'tests/Verification/legacy107_test.py'
        (root / path).write_text('from pathlib import Path\n'
                                'assert Path("/.dockerenv").exists(), "HOST_EXECUTION107"\n'
                                'print("LEGACY_EXECUTION107")\n')
        self.register(root, path, 'unit')
        inventory = root / 'tools/verification/categories.json'
        roster = root / 'tools/verification/suites.tsv'
        original_mapping = json.loads(inventory.read_text())
        original_rows = roster.read_text().splitlines()
        docker = shutil.which('docker')
        self.assertIsNotNone(docker, 'SETUP_FAILURE diagnostic Docker prerequisite absent')
        real_docker = str(Path(docker).resolve())
        blocked = self.outer / 'alias-guards'
        blocked.mkdir()
        sentinel = self.outer / 'alias-host-used'
        for runtime in ('php', 'node', 'uv', 'composer'):
            target = blocked / runtime
            target.write_text('#!/bin/sh\necho HOST_RUNTIME_USED107 >> ' + shlex.quote(str(sentinel)) + '\nexit 93\n')
            target.chmod(0o755)
        docker_guard = blocked / 'docker'
        docker_guard.write_text('#!/bin/sh\nif [ "$1" = compose ]; then\n'
            ' echo HOST_COMPOSE_BYPASS107 >> ' + shlex.quote(str(sentinel)) + '\n'
            ' echo HOST_COMPOSE_BYPASS107 >&2\n exit 94\nfi\n'
            'exec ' + shlex.quote(real_docker) + ' "$@"\n')
        docker_guard.chmod(0o755)
        environment = dict(self.env, PATH=str(blocked) + os.pathsep + os.environ['PATH'])
        for alias, suite, category, profile in [
                ('unit-test', 'unit', 'unit', 'governance'),
                ('db-test', 'db', 'integration', 'integration'),
                ('characterization-test', 'characterization', 'governance', 'governance'),
                ('e2e-test', 'e2e', 'e2e', 'browser')]:
            with self.subTest(alias=alias):
                if sentinel.exists():
                    sentinel.unlink()
                other_suite, other_category = (('unit', 'unit') if suite == 'characterization'
                                                else ('characterization', 'governance'))
                inventory.write_text(json.dumps({name: category if name == path else other_category
                                                 for name in original_mapping}))
                rows = []
                for line in original_rows:
                    if not line or line.startswith('#'):
                        rows.append(line)
                        continue
                    _, runtime, registered = line.split('\t')
                    rows.append('\t'.join((suite if registered == path else other_suite,
                                           runtime, registered)))
                roster.write_text('\n'.join(rows) + '\n')
                before = set(self.evidence_home.rglob('records/*.json'))
                result = subprocess.run(['make', alias], cwd=root, env=environment,
                                        capture_output=True, text=True, timeout=300)
                self.assertEqual(0, result.returncode,
                    'INTENDED_RED legacy alias bypasses declared profile\n' + result.stdout + result.stderr)
                self.assertFalse(sentinel.exists(), 'host prerequisite was consumed')
                records = [json.loads(p.read_text()) for p in
                           set(self.evidence_home.rglob('records/*.json')) - before]
                leaves = [r for r in records if r.get('argv') == ['python3', path]]
                self.assertEqual(1, len(leaves))
                environments = [r['execution'] for r in records if r.get('execution')]
                self.assertTrue(environments)
                self.assertEqual({profile}, {r['profile'] for r in environments})
                runners = {name for r in environments for name in
                           r.get('resource_ids', {}).get('containers', []) if name.startswith('fm2v-run-')}
                self.assertEqual(1, len(runners), 'one owned runner, not recursive containers')

    def test_uv_lock_identity_and_hash_integrity_are_consumed_by_image(self):
        root = self.repository('uv-lock-consumption')
        lock = root / 'uv.lock'
        original = lock.read_text()
        first = self.prepare(root, 'governance')
        with self.subTest(variant='valid-lock-delta'):
            lock.write_text(original + '\n# delivery107 valid lock identity delta\n')
            second = self.prepare(root, 'governance')
            self.assertNotEqual(first['image_id'], second['image_id'],
                                'INTENDED_RED uv.lock not part of installed image')
            self.assertEqual(hashlib.sha256(lock.read_bytes()).hexdigest(), second['lockfiles']['uv.lock'])
            self.assertEqual(0, self.prepare(root, 'governance')['dependency_installs'])
        block = re.search(r'(?ms)^\[\[package\]\]\nname = "quality-graph-cli"\n.*?(?=^\[\[package\]\]|\Z)', original)
        self.assertIsNotNone(block, 'required locked package fixture exists')
        hashes = list(re.finditer(r'hash = "sha256:[0-9a-f]{64}"', block.group()))
        self.assertEqual(2, len(hashes), 'one sdist and one universal wheel')
        corrupted = block.group()
        for index, match in reversed(list(enumerate(hashes))):
            corrupted = corrupted[:match.start()] + 'hash = "sha256:' + str(index) * 64 + '"' + corrupted[match.end():]
        lock.write_text(original[:block.start()] + corrupted + original[block.end():])
        rejected = self.cli(root, 'environment', '--profile', 'governance', '--prepare')
        value = self.json_result(rejected)
        self.assertNotEqual(0, rejected.returncode,
                            'INTENDED_RED corrupt frozen lock was ignored by installation')
        self.assertEqual('SETUP_FAILURE', value['outcome'])
        self.assertIn('DEPENDENCY_UNAVAILABLE', json.dumps(value))

    def test_internal_prepare_and_preflight_keep_host_readable_evidence(self):
        root = self.repository('internal-evidence')
        path = 'tests/Verification/internal107_test.py'
        spec = 'specs/INTERNAL107.md'
        input_path = 'specs/internal107-input.json'
        (root / spec).write_text('INTERNAL107: retained public preparation evidence\n')
        (root / path).write_text('''import json,subprocess,sys
p=subprocess.run([sys.executable,"tools/delivery/harness.py","prepare","--input",
    "specs/internal107-input.json","--base","origin/main","--role","root"],capture_output=True,text=True)
assert p.returncode == 0, p.stdout+p.stderr
package=json.loads(p.stdout)
print("INTERNAL_PACKAGE107 "+json.dumps(package),flush=True)
p=subprocess.run([sys.executable,"tools/delivery/change-verification.py","preflight",
    "--plan",package["plan"]],capture_output=True,text=True)
assert p.returncode == 0, p.stdout+p.stderr
print("INTERNAL_PREFLIGHT107 "+p.stdout.strip(),flush=True)
''')
        self.register(root, path, 'governance')
        (root / input_path).write_text(json.dumps(dict(change='internal107', planned_paths=[path],
            acceptances=[dict(spec_id='INTERNAL107', acceptance_id='retention', spec_path=spec,
                              seam='public prepare/preflight', tests=[path])])))
        for args in (['add', '.'], ['commit', '-qm', 'internal fixture baseline'],
                     ['update-ref', 'refs/remotes/origin/main', 'HEAD']):
            subprocess.run(['git', *args], cwd=root, env=self.env, check=True, capture_output=True)
        result, record = self.run_profile(root, 'governance', ['python3', path])
        self.assertEqual(0, result.returncode, 'INTENDED_RED internal public preparation failed: ' + str(record))
        output = Path(record['stdout_path']).read_text()
        package = json.loads(next(line.removeprefix('INTERNAL_PACKAGE107 ') for line in output.splitlines()
                                  if line.startswith('INTERNAL_PACKAGE107 ')))
        preflight = json.loads(next(line.removeprefix('INTERNAL_PREFLIGHT107 ') for line in output.splitlines()
                                    if line.startswith('INTERNAL_PREFLIGHT107 ')))
        for value in (package['package_path'], package['plan'], preflight['record_path']):
            path = Path(value)
            self.assertTrue(path.is_file(), 'INTENDED_RED profile lost its internal evidence: ' + value)
            self.assertTrue(path.resolve().is_relative_to(self.evidence_home.resolve()),
                            'internal evidence is not in the run-owned external home')

    def test_canonical_full_composition_delegates_to_checked_public_stages(self):
        source = (ROOT / 'Makefile').read_text()
        recipes = re.findall(r'(?m)^test:[^\n]*\n((?:\t[^\n]*\n|\n)+)', source)
        full = [recipe for recipe in recipes if 'VERIFY_OK' in recipe]
        self.assertEqual(1, len(full), 'INTENDED_RED canonical full aggregation is explicit')
        # HARNESS-CANONICAL-MIGRATION-STAGE-001 owns this order. This is text
        # inspection only; no make test/-n/-p or product full suite is executed.
        stages = re.findall(r'run_(?:setup_)?stage\s+([\w-]+)\s+', full[0])
        self.assertEqual(['test-db-reset', 'migrate', 'architecture-check', 'lint',
                          'unit-test', 'db-test', 'characterization-test', 'e2e-test', 'diff-check'], stages)
        self.assertNotRegex(full[0], r'(?:^|[;\n])\s*(?:php|node|uv|composer|docker)\s')
        self.assertNotIn('tools/verification/run.sh', full[0])
        for stage in ('migrate', 'db-test', 'e2e-test'):
            self.assertRegex(full[0], r'skip_setup_blocked_stage\s+' + stage + r'\s+')

    def database_fixture(self, neighbor=False):
        # This fixture is created only inside this corpus's owned browser/DB run.
        # It never connects to the user's standalone test-db or working stand.
        self.assertTrue(Path('/.dockerenv').is_file(), 'SETUP_FAILURE fixture needs its declared container')
        root = self.repository('existing-database')
        token = hashlib.sha256(str(self.outer).encode()).hexdigest()[:12]
        database, user, password = 't_delivery107_' + token, 'u107_' + token, 'fixture107_' + token
        php = shutil.which('php')
        self.assertIsNotNone(php, 'SETUP_FAILURE fixture PHP missing')
        fixture_env = dict(self.env, FMONITOR_TEST_DB_NAME=database, FMONITOR_TEST_DB_USER=user,
                           FMONITOR_TEST_DB_PASSWORD=password)
        connect = ('mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);'
                   '$c=new mysqli(getenv("FMONITOR_TEST_DB_HOST")?:"127.0.0.1",'
                   'getenv("FMONITOR_TEST_DB_ADMIN_USER")?:"root",'
                   'getenv("FMONITOR_TEST_DB_ADMIN_PASSWORD")?:"fmonitor2_test_root_local",'
                   'null,(int)(getenv("FMONITOR_TEST_DB_PORT")?:3306));')

        def sql(statement):
            result = subprocess.run([php, '-r', connect + statement], env=fixture_env,
                                    capture_output=True, text=True, timeout=20)
            self.assertEqual(0, result.returncode, 'owned DB fixture query: ' + result.stderr)
            return result.stdout.strip()

        sql('$c->query("CREATE DATABASE `' + database + '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");'
            '$c->query("CREATE USER \'' + user + '\'@\'%\' IDENTIFIED BY \'' + password + '\'");'
            '$c->query("GRANT ALL ON `' + database + '`.* TO \'' + user + '\'@\'%\'");')
        self.addCleanup(sql, '$c->query("DROP DATABASE IF EXISTS `' + database + '`");'
                            '$c->query("DROP USER IF EXISTS \'' + user + '\'@\'%\'");')
        if neighbor:
            other = database + '_neighbor'
            sql('$c->query("CREATE DATABASE `' + other + '`");'
                '$c->query("CREATE TABLE `' + other + '`.keep107 (value INT)");'
                '$c->query("INSERT INTO `' + other + '`.keep107 VALUES(107)");')
            self.addCleanup(sql, '$c->query("DROP DATABASE IF EXISTS `' + other + '`");')
        guards = self.outer / 'database-host-guards'
        guards.mkdir()
        marker = self.outer / 'database-host-used'
        for runtime in ('php', 'node', 'uv', 'composer'):
            guard = guards / runtime
            guard.write_text('#!/bin/sh\necho HOST_RUNTIME_USED107 >> ' + shlex.quote(str(marker)) + '\nexit 93\n')
            guard.chmod(0o755)
        docker = shutil.which('docker')
        self.assertIsNotNone(docker, 'SETUP_FAILURE fixture Docker missing')
        guard = guards / 'docker'
        guard.write_text('#!/bin/sh\nif [ "$1" = compose ]; then\n'
                         ' echo HOST_COMPOSE_BYPASS107 >> ' + shlex.quote(str(marker)) + '\n'
                         ' echo HOST_COMPOSE_BYPASS107 >&2\n exit 94\nfi\n'
                         'exec ' + shlex.quote(str(Path(docker).resolve())) + ' "$@"\n')
        guard.chmod(0o755)
        environment = dict(fixture_env, PATH=str(guards) + os.pathsep + os.environ['PATH'])
        return root, database, sql, environment, marker

    def test_explicit_reset_targets_only_the_existing_owned_fixture_database(self):
        root, database, sql, environment, marker = self.database_fixture(neighbor=True)
        sql('$c->query("CREATE TABLE `' + database + '`.keep107 (value INT)");'
            '$c->query("INSERT INTO `' + database + '`.keep107 VALUES(107)");')
        before = set(self.evidence_home.rglob('records/*.json'))
        result = subprocess.run(['make', 'test-db-reset'], cwd=root, env=environment,
                                capture_output=True, text=True, timeout=300)
        self.assertEqual(0, result.returncode,
                         'INTENDED_RED reset bypasses owned container fixture\n' + result.stdout + result.stderr)
        self.assertFalse(marker.exists(), 'reset used host project runtime/shared Compose')
        self.assert_integrated_operation(root, before)
        count = sql('$r=$c->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\''
                    + database + '\'");echo $r->fetch_row()[0];')
        self.assertEqual('0', count, 'explicit reset must affect the supplied DB, not an unrelated fresh DB')
        self.assertEqual('107', sql('$r=$c->query("SELECT value FROM `' + database + '_neighbor`.keep107");'
                                    'echo $r->fetch_row()[0];'),
                         'reset must preserve the adjacent owned database on the same service')

    def test_public_migrate_reuses_existing_database_and_preserves_marker_on_repeat(self):
        root, database, sql, environment, marker = self.database_fixture()
        before = set(self.evidence_home.rglob('records/*.json'))
        first = subprocess.run(['make', 'migrate'], cwd=root, env=environment,
                               capture_output=True, text=True, timeout=300)
        self.assertEqual(0, first.returncode,
                         'INTENDED_RED migration bypasses supplied container fixture\n' + first.stdout + first.stderr)
        self.assertFalse(marker.exists(), 'migration used host project runtime')
        self.assert_integrated_operation(root, before)
        count = sql('$r=$c->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\''
                    + database + '\'");echo $r->fetch_row()[0];')
        self.assertGreater(int(count), 0, 'migration must use the supplied already-created DB')
        sql('$c->query("CREATE TABLE `' + database + '`.keep107 (value INT)");'
            '$c->query("INSERT INTO `' + database + '`.keep107 VALUES(107)");')
        before = set(self.evidence_home.rglob('records/*.json'))
        second = subprocess.run(['make', 'migrate'], cwd=root, env=environment,
                                capture_output=True, text=True, timeout=300)
        self.assertEqual(0, second.returncode, second.stdout + second.stderr)
        self.assert_integrated_operation(root, before)
        self.assertEqual('107', sql('$r=$c->query("SELECT value FROM `' + database + '`.keep107");'
                                    'echo $r->fetch_row()[0];'),
                         'repeated migrate must not reset, replace or tear down the supplied DB')

    def assert_integrated_operation(self, root, before):
        records = [json.loads(path.read_text()) for path in
                   set(self.evidence_home.rglob('records/*.json')) - before]
        environments = [record['execution'] for record in records if record.get('execution')]
        self.assertTrue(environments, 'public DB operation must retain observed container execution')
        self.assertEqual({'integration'}, {value['profile'] for value in environments})
        runners = {name for value in environments for name in
                   value.get('resource_ids', {}).get('containers', []) if name.startswith('fm2v-run-')}
        self.assertEqual(1, len(runners), 'one scoped integration execution per standalone DB operation')
        for name in ('vendor', 'node_modules', '.venv'):
            self.assertFalse((root / name).exists(), 'host dependency intervention: ' + name)

    def test_bounded_full_make_overlay_keeps_same_database_across_real_reset_and_migrate(self):
        root, database, sql, environment, marker = self.database_fixture()
        driver = 'tests/Verification/full107_marker.php'
        (root / driver).write_text('''<?php
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$c=new mysqli(getenv('FMONITOR_TEST_DB_HOST'),getenv('FMONITOR_TEST_DB_USER'),
    getenv('FMONITOR_TEST_DB_PASSWORD'),getenv('FMONITOR_TEST_DB_NAME'),(int)getenv('FMONITOR_TEST_DB_PORT'));
if($argv[1]==='seed') {
    $c->query('CREATE TABLE keep107 (value INT)');$c->query('INSERT INTO keep107 VALUES(107)');
    echo "FULL_MARKER_SEEDED107\\n";
} else {
    if($c->query('SELECT value FROM keep107')->fetch_row()[0] != 107) throw new RuntimeException('FULL_DB_CHANGED107');
    $c->query('SELECT COUNT(*) FROM fm2_pilot_users');
    echo "FULL_MIGRATION_OBSERVED107\\n";
}
$c->close();
''')
        php = shutil.which('php')
        self.assertIsNotNone(php)
        overlay = 'tests/Verification/full107_overlay.mk'
        stubs = ['architecture-check', 'lint', 'unit-test', 'db-test', 'characterization-test', 'e2e-test']
        body = '.PHONY: test-env-up test-db-reset migrate ' + ' '.join(stubs) + '\n'
        body += 'test-env-up:\n\t@:\n'
        body += ('test-db-reset:\n\t@$(MAKE) --no-print-directory -f Makefile test-db-reset\n'
                 '\t@' + shlex.quote(php) + ' ' + driver + ' seed\n')
        body += ('migrate:\n\t@$(MAKE) --no-print-directory -f Makefile migrate\n'
                 '\t@' + shlex.quote(php) + ' ' + driver + ' check\n')
        for stage in stubs:
            body += stage + ':\n\t@echo FULL_STUB107 ' + stage + '\n'
        (root / overlay).write_text(body)
        # Fail closed if a regression drops the overlay: no real product suite
        # may run from this bounded public-Make composition witness.
        original_runner = root / 'tools/verification/run-original107.sh'
        original_runner.write_bytes((root / 'tools/verification/run.sh').read_bytes())
        (root / 'tools/verification/run.sh').write_text(
            '#!/bin/sh\nif [ "${1:-}" = list ]; then exec bash tools/verification/run-original107.sh "$@"; fi\n'
            'echo UNEXPECTED_PRODUCT_STAGE107 >&2\nexit 88\n')
        (root / 'tools/architecture/check').write_text('#!/bin/sh\necho UNEXPECTED_PRODUCT_STAGE107 >&2\nexit 88\n')
        (root / 'tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php').write_text(
            '<?php fwrite(STDERR,"UNEXPECTED_PRODUCT_STAGE107\\n");exit(88);\n')
        git = shutil.which('git')
        self.assertIsNotNone(git)
        guard = Path(environment['PATH'].split(os.pathsep)[0]) / 'git'
        guard.write_text('#!/bin/sh\nif [ "$#" -eq 2 ] && [ "$1" = diff ] && [ "$2" = --check ]; then\n'
                         ' echo FULL_STUB107 diff-check\n exit 0\nfi\n'
                         'exec ' + shlex.quote(str(Path(git).resolve())) + ' "$@"\n')
        guard.chmod(0o755)
        # Seven stages are explicit stubs; only the two real scoped DB operations
        # run. This is the approved stage-overlay seam, NOT a local full suite.
        result = subprocess.run(['make', '--no-print-directory', '-f', 'Makefile', '-f', overlay, 'verify'],
                                cwd=root, env=environment, capture_output=True, text=True, timeout=300)
        self.assertEqual(0, result.returncode,
                         'INTENDED_RED full composition lost routed borrowed DB\n' + result.stdout + result.stderr)
        self.assertFalse(marker.exists(), 'full composition bypassed owned execution route')
        self.assertNotIn('UNEXPECTED_PRODUCT_STAGE107', result.stdout + result.stderr)
        self.assertEqual(stubs + ['diff-check'], re.findall(r'^FULL_STUB107 ([\w-]+)$', result.stdout, re.M))
        self.assertEqual(1, result.stdout.count('FULL_MARKER_SEEDED107'))
        self.assertEqual(1, result.stdout.count('FULL_MIGRATION_OBSERVED107'))
        self.assertEqual('107', sql('$r=$c->query("SELECT value FROM `' + database + '`.keep107");'
                                    'echo $r->fetch_row()[0];'))
        for name in ('vendor', 'node_modules', '.venv'):
            self.assertFalse((root / name).exists(), 'full fixture installed dependencies into source')

    def test_marker_fixture_is_supported_by_existing_canonical_public_cli(self):
        _, database, sql, environment, _ = self.database_fixture()
        sql('$c->query("CREATE TABLE `' + database + '`.keep107 (value INT)");'
            '$c->query("INSERT INTO `' + database + '`.keep107 VALUES(107)");')
        # Independently reach the fixture's application precondition, not the
        # missing Make route. This PHP is already inside the declared image.
        for field in ('HOST', 'PORT', 'NAME', 'USER', 'PASSWORD'):
            environment['FMONITOR_DB_' + field] = environment['FMONITOR_TEST_DB_' + field]
        environment['FMONITOR_PROCESS_TABLE_PREFIX'] = ''
        php = shutil.which('php')
        self.assertIsNotNone(php)
        result = subprocess.run([php, str(ROOT / 'bin/yii'), 'schema-migrate/run', '--interactive=0'],
                                cwd=ROOT, env=environment, capture_output=True, text=True, timeout=120)
        self.assertEqual(0, result.returncode,
                         'CONTROL_FIXTURE_INVALID107 canonical marker input\n' + result.stdout + result.stderr)
        self.assertEqual('107', sql('$r=$c->query("SELECT value FROM `' + database + '`.keep107");'
                                    'echo $r->fetch_row()[0];'))

    def test_reviewer_accepts_observed_container_envelope_without_host_hash_and_rejects_forgery(self):
        root = self.repository('review-environment')
        path = 'tests/Verification/reviewenv107_test.py'
        spec = 'specs/REVIEWENV107.md'
        input_path = 'specs/reviewenv107-input.json'
        (root / path).write_text('print("REVIEW_ENVIRONMENT107")\n')
        (root / spec).write_text('REVIEWENV107: accept observed execution, reject unproven envelope\n')
        self.register(root, path, 'governance')
        policy_path = root / '.quality-graph/verification-policy.json'
        policy = json.loads(policy_path.read_text())
        # A bounded review-format fixture: the category obligation is the same
        # actual registered acceptance command, so no unrelated corpus is run.
        policy['category_argv']['governance'] = [['python3', path]]
        policy_path.write_text(json.dumps(policy))
        (root / input_path).write_text(json.dumps(dict(change='review-environment107', planned_paths=[path],
            acceptances=[dict(spec_id='REVIEWENV107', acceptance_id='observed-profile', spec_path=spec,
                              seam='public reviewer prepare', tests=[path])])))
        for args in (['add', '.'], ['commit', '-qm', 'review environment fixture'],
                     ['update-ref', 'refs/remotes/origin/main', 'HEAD']):
            subprocess.run(['git', *args], cwd=root, env=self.env, check=True, capture_output=True)
        prepared = self.cli(root, 'prepare', '--input', input_path, '--base', 'origin/main', '--role', 'root')
        self.assertEqual(0, prepared.returncode, prepared.stderr)
        package = self.json_result(prepared)
        plan = json.loads(Path(package['plan']).read_text())
        self.assertEqual([['python3', path]], [item['argv'] for item in plan['commands'] if item['phase'] == 'focused'])
        result, record = self.run_profile(root, 'governance', ['python3', path], 'review-profile')
        self.assertEqual(0, result.returncode, record)
        record_path = self.evidence_home / 'records' / (record['id'] + '.json')
        original = record_path.read_bytes()

        def review(evidence, environment=None):
            return subprocess.run([sys.executable, str(root / 'tools/delivery/harness.py'), 'prepare',
                '--input', input_path, '--base', 'origin/main', '--role', 'reviewer', '--gate', '5',
                '--evidence', str(evidence)], cwd=root, env=environment or self.env,
                capture_output=True, text=True, timeout=120)

        # Changing only the reviewer's launcher PATH does not change the image
        # that really executed the check. Legacy host records retain their old
        # exact keyed binding (covered by delivery_harness_001_test.py).
        unused = self.outer / 'unused-reviewer-bin'
        unused.mkdir()
        for environment in (self.env, dict(self.env, PATH=str(unused) + os.pathsep + self.env['PATH'])):
            with self.subTest(valid_environment=environment['PATH']):
                accepted = review(record_path, environment)
                self.assertEqual(0, accepted.returncode,
                                 'INTENDED_RED observed container evidence rejected by host-only binding: ' + accepted.stderr)
                value = self.json_result(accepted)
                self.assertEqual('NOT_REVIEWED', value['approval'], 'preparation is never independent approval')
                self.assertEqual(str(record_path), value['evidence'][0]['record'])

        mutations = [
            ('missing-envelope', ('execution',), None),
            ('declared-only', ('execution',), {'profile': 'governance', 'runtimes': record['execution']['runtimes']}),
            ('unknown-image', ('execution', 'image_id'), 'sha256:' + '0' * 64),
            ('wrong-platform', ('execution', 'platform'), 'windows/amd64'),
            ('missing-platform', ('execution', 'platform'), '__REMOVE107__'),
            ('wrong-runtime', ('execution', 'runtimes', 'python'), '0.0.0'),
            ('wrong-node', ('execution', 'runtimes', 'node'), '0.0.0'),
            ('missing-node', ('execution', 'runtimes', 'node'), '__REMOVE107__'),
            ('wrong-php', ('execution', 'runtimes', 'php'), '0.0.0'),
            ('missing-php', ('execution', 'runtimes', 'php'), '__REMOVE107__'),
            ('wrong-lock', ('execution', 'lockfiles', 'composer.lock'), '0' * 64),
            ('wrong-uv-lock', ('execution', 'lockfiles', 'uv.lock'), '0' * 64),
            ('missing-uv-lock', ('execution', 'lockfiles', 'uv.lock'), '__REMOVE107__'),
            ('unknown-profile', ('execution', 'profile'), 'unregistered107'),
            ('wrong-supported-profile', ('execution', 'profile'), 'integration'),
            ('stale', ('applicability',), 'STALE'),
            ('unknown-applicability', ('applicability',), 'UNKNOWN'),
        ]
        for name, keys, value in mutations:
            with self.subTest(forgery=name):
                forged = copy.deepcopy(record)
                owner = forged
                for key in keys[:-1]:
                    owner = owner[key]
                if value == '__REMOVE107__':
                    owner.pop(keys[-1])
                else:
                    owner[keys[-1]] = value
                forged['id'] = 'forged107-' + name
                invalid = self.evidence_home / 'records' / (forged['id'] + '.json')
                invalid.write_text(json.dumps(forged))
                rejected = review(invalid)
                self.assertNotEqual(0, rejected.returncode, 'unproven execution envelope was admitted: ' + name)
                self.assertIn('SETUP_FAILURE', rejected.stderr)
        self.assertEqual(original, record_path.read_bytes(), 'bootstrap must not rewrite or augment actual evidence')


def load_tests(loader, tests, pattern):
    # Imported helpers are not a second execution of the cold/warm corpus.
    return unittest.TestSuite(Routes107(name) for name in sorted(Routes107.__dict__)
                              if name.startswith('test_'))


if __name__ == '__main__':
    unittest.main()
