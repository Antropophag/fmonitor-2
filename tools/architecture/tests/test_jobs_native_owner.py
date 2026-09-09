"""DURABLE-BACKGROUND-JOBS-001: Jobs owns SQL only in its native adapters."""
import unittest
import json
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

REPO = Path(__file__).resolve().parents[3]


class JobsNativeOwnerTest(unittest.TestCase):
    def inspect(self, source, filename):
        with tempfile.TemporaryDirectory(prefix='fm2-jobs-owner-') as directory:
            root = Path(directory)
            tool = root / 'tools/architecture'
            tool.mkdir(parents=True)
            shutil.copyfile(REPO / 'tools/architecture/check.py', tool / 'check.py')
            # Independent empty debt: repository baseline entries cannot satisfy this test.
            baseline = {'ddl_ownership': [], 'sql_ownership': [], 'dependency_direction': [],
                        'rapid_pilot_boundary': [], 'hotspots': {}, 'public_seams': []}
            (tool / 'baseline.json').write_text(json.dumps(baseline), encoding='utf-8')
            target = root / 'app/Jobs' / filename
            target.parent.mkdir(parents=True)
            target.write_text(source, encoding='utf-8')
            result = subprocess.run([sys.executable, str(tool / 'check.py'), '--json'],
                                    cwd=root, capture_output=True, text=True, timeout=30)
            self.assertEqual('', result.stderr)
            self.assertIn(result.returncode, (0, 1))
            return result.returncode, json.loads(result.stdout)

    def test_mariadb_binding_owns_dml(self):
        for table in ('fm2_jobs', 'fm2_job_events', 'fm2_outbox_intents',
                      'fm2_outbox_attempt_events', 'fm2_scheduler_slots', 'fm2_worker_heartbeats'):
            for sql in (f'SELECT id FROM {table}', f'INSERT INTO {table} VALUES(1)', f'UPDATE {table} SET id=2'):
                with self.subTest(sql=sql):
                    code, result = self.inspect("<?php\n$db->query('" + sql + "');\n", 'MariaDbJobsExample.php')
                    self.assertEqual((0, []), (code, result['errors']))

    def test_application_still_cannot_write_sql(self):
        code, result = self.inspect("<?php\n$db->query('INSERT INTO fm2_jobs VALUES(1)');\n", 'JobsExample.php')
        self.assertEqual(1, code)
        self.assertTrue(any('sql_ownership: new violation' in e for e in result['errors']))

    def test_native_adapter_still_cannot_own_ddl(self):
        code, result = self.inspect("<?php\n$db->query('CREATE TABLE facts(id INT)');\n", 'MariaDbJobsExample.php')
        self.assertEqual(1, code)
        self.assertTrue(any('ddl_ownership: new violation' in e for e in result['errors']))

    def test_jobs_cannot_reference_another_owners_fact_family(self):
        for source in (
            "<?php\n$db->query('INSERT INTO fm2_installation_cases VALUES(1)');\n",
            "<?php\n$table = $prefix . 'fm2_workforce_catalog';\n",
            "<?php\n$sql->table('fm2_assignment_order_applications');\n",
        ):
            with self.subTest(source=source):
                code, result = self.inspect(source, 'MariaDbJobsExample.php')
                self.assertEqual(1, code)
                self.assertTrue(any('sql_ownership: new violation' in e for e in result['errors']))

    def test_jobs_does_not_depend_on_pilot_adapter(self):
        for source in ("<?php\nuse FMonitor2\\RapidPilot\\Workforce;\n",
                       "<?php\nuse FMonitor2\\PilotHttp\\Workforce;\n",
                       "<?php\nrequire 'rapid-pilot/workforce-worker.php';\n"):
            with self.subTest(source=source):
                code, result = self.inspect(source, 'JobsExample.php')
                self.assertEqual(1, code)
                self.assertTrue(any('dependency_direction: new violation' in e for e in result['errors']))

    def test_runtime_cannot_call_deployment_migration_owner(self):
        for source in (
            "<?php\nuse FMonitor2\\InstallationProcess\\JobsSchemaMigration;\nJobsSchemaMigration::apply($db, '');\n",
            "<?php\nuse FMonitor2\\InstallationProcess\\JobsSchemaMigration as Deployment;\nDeployment::apply($db, '');\n",
            "<?php\n\\FMonitor2\\InstallationProcess\\CanonicalMigrationApplication::run($db, '', $catalogue);\n",
        ):
            with self.subTest(source=source):
                code, result = self.inspect(source, 'MariaDbJobsExample.php')
                self.assertEqual(1, code)
                self.assertTrue(any('ddl_ownership: new violation' in e for e in result['errors']))

    def test_readonly_schema_readiness_remains_allowed(self):
        code, result = self.inspect("<?php\nuse FMonitor2\\InstallationProcess\\JobsSchemaMigration;\nJobsSchemaMigration::isReady($db, '');\n", 'MariaDbJobsExample.php')
        self.assertEqual((0, []), (code, result['errors']))


if __name__ == '__main__':
    unittest.main()
