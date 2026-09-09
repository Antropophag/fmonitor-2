"""YII2-AUTH-001: Yii entrypoints cannot restore the retired HTTP/auth runtime."""
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

REPO = Path(__file__).resolve().parents[3]


class YiiRuntimeBoundary(unittest.TestCase):
    def inspect(self, relative, source):
        with tempfile.TemporaryDirectory(prefix='fm2-yii-boundary-') as directory:
            root = Path(directory)
            tool = root / 'tools/architecture'
            tool.mkdir(parents=True)
            shutil.copyfile(REPO / 'tools/architecture/check.py', tool / 'check.py')
            (tool / 'baseline.json').write_text(json.dumps({
                'ddl_ownership': [], 'sql_ownership': [], 'dependency_direction': [],
                'rapid_pilot_boundary': [], 'hotspots': {}, 'public_seams': [],
            }))
            target = root / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_text('<?php\n' + source + '\n')
            result = subprocess.run([sys.executable, str(tool / 'check.py'), '--json'],
                                    cwd=root, capture_output=True, text=True, timeout=15)
            self.assertEqual('', result.stderr)
            return result.returncode, json.loads(result.stdout)['errors']

    def test_every_yii_composition_surface_rejects_legacy_runtime(self):
        for relative in ['app/YiiRuntime/Controllers/Example.php', 'config/yii/web.php',
                         'public/yii.php', 'bin/yii']:
            for source in ["require 'rapid-pilot/LocalAuth.php';",
                           "require 'app/PilotHttp/production-entrypoint.php';"]:
                with self.subTest(path=relative, source=source):
                    code, errors = self.inspect(relative, source)
                    self.assertEqual(1, code, 'INTENDED_RED: Yii composition loaded legacy runtime')
                    self.assertTrue(any('dependency_direction: new violation' in error for error in errors))

    def test_framework_and_application_dependencies_remain_allowed(self):
        for relative in ['app/YiiRuntime/Controllers/Example.php', 'config/yii/web.php',
                         'public/yii.php', 'bin/yii']:
            with self.subTest(path=relative):
                self.assertEqual((0, []), self.inspect(relative,
                    'use yii\\web\\Application; use FMonitor2\\IdentityAccess\\AuthorizeLocalActor;'))

    def test_web_and_configuration_cannot_run_migrations(self):
        for relative in ['app/YiiRuntime/Controllers/Example.php', 'config/yii/web.php', 'public/yii.php']:
            with self.subTest(path=relative):
                code, errors = self.inspect(relative, 'ObjectDetailSnapshotSchemaMigration::apply($db);')
                self.assertEqual(1, code, 'INTENDED_RED: ordinary Yii HTTP invoked migration')
                self.assertTrue(any('ddl_ownership: new violation' in error for error in errors))

    def test_explicit_console_migration_and_readiness_are_allowed(self):
        self.assertEqual((0, []), self.inspect('app/YiiRuntime/Commands/MigrationController.php',
                                              'ObjectDetailSnapshotSchemaMigration::apply($db);'))
        self.assertEqual((0, []), self.inspect('app/YiiRuntime/Controllers/Example.php',
                                              'ObjectDetailSnapshotSchemaMigration::isReady($db);'))

    def test_session_exception_does_not_allow_other_native_session_calls(self):
        code, errors = self.inspect('app/YiiRuntime/ReliableSession.php', 'session_start();')
        self.assertEqual(1, code)
        self.assertTrue(any('session_storage_ownership: forbidden production owner' in error for error in errors))
        self.assertEqual((0, []), self.inspect('app/YiiRuntime/ReliableSession.php', 'session_write_close();'))


if __name__ == '__main__':
    unittest.main()
