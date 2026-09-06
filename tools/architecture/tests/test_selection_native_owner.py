"""ASSIGNMENT-ORDER-SELECTION-NATIVE-001: narrow SQL owner, public CLI."""
import unittest
import json
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

REPO = Path(__file__).resolve().parents[3]


class SelectionNativeOwnerTest(unittest.TestCase):
    def inspect(self, source, filename):
        with tempfile.TemporaryDirectory(prefix='fm2-selection-owner-') as directory:
            root = Path(directory)
            tool = root / 'tools/architecture'
            tool.mkdir(parents=True)
            shutil.copyfile(REPO / 'tools/architecture/check.py', tool / 'check.py')
            # Independent empty debt: repository baseline entries cannot satisfy this test.
            baseline = {'ddl_ownership': [], 'sql_ownership': [], 'dependency_direction': [],
                        'rapid_pilot_boundary': [], 'hotspots': {}, 'public_seams': []}
            (tool / 'baseline.json').write_text(json.dumps(baseline), encoding='utf-8')
            target = root / 'app/AssignmentOrderComposition' / filename
            target.parent.mkdir(parents=True)
            target.write_text(source, encoding='utf-8')
            result = subprocess.run([sys.executable, str(tool / 'check.py'), '--json'],
                                    cwd=root, capture_output=True, text=True, timeout=30)
            self.assertEqual('', result.stderr)
            self.assertIn(result.returncode, (0, 1))
            return result.returncode, json.loads(result.stdout)

    def test_mariadb_binding_owns_dml(self):
        for sql in ('SELECT id FROM facts', 'INSERT INTO facts VALUES(1)', 'UPDATE facts SET id=2'):
            with self.subTest(sql=sql):
                code, result = self.inspect("<?php\n$db->query('" + sql + "');\n", 'MariaDbSelectionExample.php')
                self.assertEqual((0, []), (code, result['errors']))

    def test_application_still_cannot_write_sql(self):
        code, result = self.inspect("<?php\n$db->query('INSERT INTO facts VALUES(1)');\n", 'SelectionExample.php')
        self.assertEqual(1, code)
        self.assertTrue(any('sql_ownership: new violation' in e for e in result['errors']))

    def test_native_adapter_still_cannot_own_ddl(self):
        code, result = self.inspect("<?php\n$db->query('CREATE TABLE facts(id INT)');\n", 'MariaDbSelectionExample.php')
        self.assertEqual(1, code)
        self.assertTrue(any('ddl_ownership: new violation' in e for e in result['errors']))


if __name__ == '__main__':
    unittest.main()
