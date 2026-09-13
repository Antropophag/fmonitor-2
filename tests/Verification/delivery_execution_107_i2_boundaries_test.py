"""I2 correction: exact source set, minimal daemon access, observed image readiness."""
import json
import os
from pathlib import Path
import subprocess
import time
import unittest
import uuid

import delivery_execution_107_i2_routes_test as routes


class Boundaries107(routes.Routes107):
    def docker(self, *args, check=True, timeout=1200):
        directory = self.evidence_home/'docker-diagnostics'
        directory.mkdir(exist_ok=True)
        identifier = uuid.uuid4().hex
        started = time.time()
        result = subprocess.run(['docker', *args], env=self.env, capture_output=True,
                                text=True, timeout=timeout)
        (directory/(identifier+'.stdout')).write_text(result.stdout)
        (directory/(identifier+'.stderr')).write_text(result.stderr)
        (directory/(identifier+'.json')).write_text(json.dumps({
            'purpose':'diagnostic','argv':['docker',*args], 'started_at':started,
            'finished_at':time.time(),'raw_exit':result.returncode}))
        if check:
            self.assertEqual(0, result.returncode, result.stdout+result.stderr)
        return result

    def register_php(self, root, path):
        self.register(root, path, 'governance')
        roster = root/'tools/verification/suites.tsv'
        roster.write_text(roster.read_text().replace('\tpython3\t'+path, '\tphp\t'+path))

    def test_snapshot_excludes_ignored_inputs_but_tracks_and_executes_tracked_source(self):
        root = self.repository('source-boundary')
        ignore = root/'.gitignore'
        ignore.write_text(ignore.read_text()+'\nignored107.txt\n__pycache__/\n')
        (root/'ignored107.txt').write_text('CONTROLLED_IGNORED_INPUT107; not a secret\n')
        tracked = 'tests/Verification/__pycache__/tracked107.py'
        (root/tracked).parent.mkdir(parents=True)
        (root/tracked).write_text('print("TRACKED_SOURCE107")\n')
        self.register(root, tracked, 'governance')
        subprocess.run(['git','add','-f',tracked],cwd=root,env=self.env,check=True,capture_output=True)
        subprocess.run(['git','commit','-qm','tracked source witness'],cwd=root,env=self.env,
                       check=True,capture_output=True)
        before = self.json_result(self.cli(root,'state'))['source']
        (root/tracked).write_text((root/tracked).read_text()+'# changed tracked source\n')
        after = self.json_result(self.cli(root,'state'))['source']
        with self.subTest(witness='tracked identity'):
            self.assertNotEqual(before,after,'INTENDED_RED tracked source hidden by a cache-directory name')
        with self.subTest(witness='tracked execution'):
            result, record = self.run_profile(root,'governance',['python3',tracked])
            self.assertEqual(0,result.returncode,
                             'INTENDED_RED tracked source omitted from snapshot: '+str(record))
            self.assertIn('TRACKED_SOURCE107',Path(record['stdout_path']).read_text())
        path = 'tests/Verification/ignored_input107_test.py'
        (root/path).write_text('import json,subprocess\nfrom pathlib import Path\n'
            'assert not Path("ignored107.txt").exists(), "IGNORED_INPUT_EXECUTED107"\n'
            'p=subprocess.run(["python3","tools/delivery/harness.py","state"],capture_output=True,text=True,check=True)\n'
            'print("SNAPSHOT_SOURCE107="+json.loads(p.stdout)["source"])\n')
        self.register(root,path,'governance')
        with self.subTest(witness='ignored input boundary and observed snapshot'):
            result, record = self.run_profile(root,'governance',['python3',path])
            self.assertEqual(0,result.returncode,
                             'INTENDED_RED project-ignored bytes entered execution: '+str(record))
            observed = Path(record['stdout_path']).read_text().strip().split('SNAPSHOT_SOURCE107=')[-1]
            self.assertEqual(record['source'],observed)
            self.assertEqual(record['snapshot']['identity'],observed)

    def test_unrelated_python_methods_and_dead_function_do_not_grant_daemon(self):
        root = self.repository('python-capabilities')
        path = 'tests/Verification/ordinary_client107_test.py'
        self.register(root,path,'governance')
        variants = {
            'cli':'class Client:\n def cli(self): return 107\nassert Client().cli()==107\n',
            'run_profile':'class Client:\n def run_profile(self): return 107\nassert Client().run_profile()==107\n',
            'dead function':'import subprocess\ndef unused():\n subprocess.run(["docker","info"],check=True)\n',
        }
        for name, body in variants.items():
            with self.subTest(variant=name):
                (root/path).write_text(body+'import os\nfrom pathlib import Path\n'
                    'assert not Path("/var/run/docker.sock").exists(), "UNNECESSARY_DAEMON107"\n'
                    'assert not os.environ.get("DOCKER_HOST"), "UNNECESSARY_REMOTE_DAEMON107"\n'
                    'print("NO_DAEMON107")\n')
                result, record = self.run_profile(root,'governance',['python3',path])
                self.assertEqual(0,result.returncode,
                                 'INTENDED_RED syntactic method/function grants daemon: '+str(record))
                self.assertIn('NO_DAEMON107',Path(record['stdout_path']).read_text())

    def test_php_comments_and_strings_are_inert_but_real_helper_keeps_daemon(self):
        root = self.repository('php-capabilities')
        path = 'tests/Verification/php_capability107_test.php'
        self.register_php(root,path)
        for text in ('// Docker is only an explanatory comment.\n',
                     '$description="docker info is documentation, not a call";\n'):
            with self.subTest(witness=text):
                (root/path).write_text('<?php\n'+text+
                    'if(file_exists("/var/run/docker.sock")||getenv("DOCKER_HOST"))'
                    'throw new RuntimeException("UNNECESSARY_PHP_DAEMON107");\n'
                    'echo "PHP_NO_DAEMON107\\n";\n')
                result, record = self.run_profile(root,'governance',['php',path])
                self.assertEqual(0,result.returncode,
                                 'INTENDED_RED PHP inert text grants daemon: '+str(record))
        helper = root/'tests/Verification/daemon107_helper.php'
        helper.write_text('<?php\nfunction observe107():void{'
            'passthru("docker info >/dev/null",$status);'
            'if($status!==0)throw new RuntimeException("REAL_DAEMON_UNAVAILABLE107");'
            'echo "PHP_DAEMON_OBSERVED107\\n";}\n')
        (root/path).write_text('<?php\nrequire __DIR__."/daemon107_helper.php"; observe107();\n')
        with self.subTest(witness='real transitive PHP call'):
            result, record = self.run_profile(root,'governance',['php',path])
            self.assertEqual(0,result.returncode,
                             'INTENDED_RED real PHP helper lost required daemon: '+str(record))
            self.assertIn('PHP_DAEMON_OBSERVED107',Path(record['stdout_path']).read_text())
            self.assertNotIn('tests/Verification/daemon107_helper.php',
                             json.loads((root/'tools/verification/categories.json').read_text()))

    def build_witness(self, root, profile, suffix, label, platform=None):
        recipe = root/'tools/delivery/Dockerfile.execution.in'
        original = recipe.read_text()
        recipe.write_text(original+'\n'+suffix+'\nLABEL fmonitor.boundary107="'+label+'"\n')
        pins = dict(line.split('=',1) for line in (root/'tools/delivery/dependencies.env').read_text().splitlines()
                    if line and not line.startswith('#'))
        tag = 'fmonitor2-boundary107:'+label
        args = ['build','--file',str(recipe),'--tag',tag,'--build-arg','PROFILE='+profile]
        if platform:
            args += ['--platform',platform]
        for key, value in pins.items():
            args += ['--build-arg',key+'='+value]
        self.docker(*args,str(root))
        return recipe, original, tag, json.loads(self.docker('image','inspect',tag).stdout)[0]

    def cleanup_images(self, label):
        ids = self.docker('image','ls','--quiet','--no-trunc','--filter',
                          'label=fmonitor.boundary107='+label).stdout.split()
        for identifier in dict.fromkeys(ids):
            data = json.loads(self.docker('image','inspect',identifier).stdout)[0]
            self.assertEqual(label,data['Config']['Labels']['fmonitor.boundary107'])
            self.docker('image','rm','--force',identifier)

    def test_prepare_rejects_built_recipes_with_missing_required_runtime_or_packages(self):
        root = self.repository('observed-readiness')
        variants = [
            ('python','governance',
             "RUN mkdir -p /opt/boundary107/bin && printf '%s\\n' '#!/bin/sh' 'echo 0.0.0' > /opt/boundary107/bin/python3 && chmod +x /opt/boundary107/bin/python3\nENV PATH=/opt/boundary107/bin:${PATH}",
             ['python3','-c','import platform;print(platform.python_version())'],'0.0.0'),
            ('mysqli','governance',
             'RUN mv /usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini /usr/local/etc/php/conf.d/docker-php-ext-mysqli.ini.disabled',
             ['php','-r','echo extension_loaded("mysqli") ? "present" : "missing";'],'missing'),
            ('locked Python packages','governance',
             'RUN mv /opt/fmonitor-dependencies/python /opt/fmonitor-dependencies/python-disabled',
             ['python3','-c','import importlib.metadata as m\ntry: m.version("quality-graph-cli"); print("present")\nexcept m.PackageNotFoundError: print("missing")'],'missing'),
            ('locked Composer packages','governance',
             'RUN mv /opt/fmonitor-dependencies/vendor /opt/fmonitor-dependencies/vendor-disabled',
             ['php','-r','echo is_file("/opt/fmonitor-dependencies/vendor/autoload.php") ? "present" : "missing";'],'missing'),
            ('browser assets','browser',
             'RUN mv /workspace/shlz-ui /workspace/shlz-ui-disabled',
             ['sh','-c','test ! -d /workspace/shlz-ui && printf missing'],'missing'),
        ]
        for name, profile, suffix, probe, expected in variants:
            with self.subTest(component=name):
                label = uuid.uuid4().hex
                recipe = root/'tools/delivery/Dockerfile.execution.in'
                original = recipe.read_text()
                try:
                    _, _, tag, image = self.build_witness(root,profile,suffix,label)
                    actual = self.docker('run','--rm',tag,*probe).stdout.strip()
                    self.assertEqual(expected,actual,'defective image witness must be reached')
                    print('IMAGE_COMPONENT_WITNESS107',name,image['Id'],actual,flush=True)
                    result = self.cli(root,'environment','--profile',profile,'--prepare')
                    value = self.json_result(result)
                    (self.evidence_home/('prepare-'+label+'.stdout')).write_text(result.stdout)
                    (self.evidence_home/('prepare-'+label+'.stderr')).write_text(result.stderr)
                    self.assertNotEqual(0,result.returncode,
                        'INTENDED_RED prepare certified missing required '+name+': '+str(value))
                    self.assertEqual('SETUP_FAILURE',value['outcome'])
                    reason = str(value.get('reason','')).lower()
                    self.assertTrue(reason)
                    self.assertNotIn('image build failed',reason,'build failure is not observer sensitivity')
                    self.assertNotIn('docker_unavailable',reason)
                finally:
                    recipe.write_text(original)
                    self.cleanup_images(label)

    def test_prepare_checks_explicit_target_before_runtime_probes(self):
        root = self.repository('target-platform')
        native = self.docker('version','--format','{{.Server.Os}}/{{.Server.Arch}}').stdout.strip()
        self.assertIn(native,('linux/amd64','linux/arm64'))
        other = 'linux/arm64' if native=='linux/amd64' else 'linux/amd64'
        self.env['DOCKER_DEFAULT_PLATFORM'] = native
        pins = dict(line.split('=',1) for line in (root/'tools/delivery/dependencies.env').read_text().splitlines()
                    if line and not line.startswith('#'))
        label = uuid.uuid4().hex
        recipe = root/'tools/delivery/Dockerfile.execution.in'
        original = recipe.read_text()
        try:
            _, _, _, image = self.build_witness(root,'governance',
                'FROM --platform='+other+' '+pins['NODE_EXECUTION_IMAGE'],label,other)
            self.assertEqual(other,image['Os']+'/'+image['Architecture'])
            print('PLATFORM_WITNESS107',native,other,image['Id'],flush=True)
            result = self.cli(root,'environment','--profile','governance','--prepare')
            value = self.json_result(result)
            (self.evidence_home/('prepare-'+label+'.stdout')).write_text(result.stdout)
            (self.evidence_home/('prepare-'+label+'.stderr')).write_text(result.stderr)
            self.assertNotEqual(0,result.returncode)
            self.assertEqual('SETUP_FAILURE',value['outcome'])
            self.assertIn('platform',str(value.get('reason','')).lower(),
                          'INTENDED_RED wrong target was not rejected before missing runtime cascade')
        finally:
            recipe.write_text(original)
            self.cleanup_images(label)


def load_tests(loader, tests, pattern):
    return unittest.TestSuite(Boundaries107(name) for name in sorted(Boundaries107.__dict__)
                              if name.startswith('test_'))


if __name__ == '__main__':
    unittest.main()
