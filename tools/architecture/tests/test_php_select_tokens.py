"""ARCHITECTURE-PHP-SELECT-TOKENS-001: public CLI, literal PHP fixtures."""
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

REPO = Path(__file__).resolve().parents[3]


class PhpSelectTokensTest(unittest.TestCase):
    def inspect(self, source, path="app/AssignmentOrderComposition/SelectionCapability.php"):
        with tempfile.TemporaryDirectory(prefix="fm2-php-select-") as directory:
            root = Path(directory)
            tool = root / "tools/architecture"
            tool.mkdir(parents=True)
            for name in ("check.py", "baseline.json"):
                shutil.copyfile(REPO / "tools/architecture" / name, tool / name)
            target = root / path
            target.parent.mkdir(parents=True)
            target.write_text(source, encoding="utf-8")
            result = subprocess.run([sys.executable, str(tool / "check.py"), "--json"], cwd=root, capture_output=True, text=True, timeout=30)
            self.assertEqual("", result.stderr)
            self.assertIn(result.returncode, (0, 1))
            return result.returncode, json.loads(result.stdout)

    def test_01_enum_is_not_sql(self):
        code, result = self.inspect("<?php\nenum Capability:string { case SELECT = 'assignment_order.composition.select'; }\n")
        self.assertEqual((0, True, []), (code, result["ok"], result["errors"]))

    def test_02_constant_reference_is_not_sql(self):
        code, result = self.inspect("<?php\n$authorizer->authorize($actor, Capability::SELECT);\n")
        self.assertEqual((0, True, []), (code, result["ok"], result["errors"]))

    def test_03_capability_atom_is_not_sql(self):
        for atom in ('"assignment_order.composition.select"', "'assignment_order.composition.select'"):
            with self.subTest(atom=atom):
                code, result = self.inspect('<?php\n$capability = ' + atom + ';\n')
                self.assertEqual((0, True, []), (code, result["ok"], result["errors"]))

    def test_04_same_line_sql_still_rejected(self):
        source = "<?php\nenum Capability:string { case SELECT='assignment_order.composition.select'; } $db->query('SELECT id FROM accounts');\n"
        code, result = self.inspect(source)
        self.assertEqual(1, code)
        self.assertEqual(["sql_ownership: new violation (1x): sql|app/AssignmentOrderComposition/SelectionCapability.php|1394e4e45c5841e7"], result["errors"])

    def test_05_actual_sql_and_fragments_still_rejected(self):
        for expression in ("'SELECT id FROM accounts'", "'SELECT'.' id FROM accounts'", "'UPDATE accounts SET flag=1'", "'INSERT INTO accounts VALUES(1)'", "'DELETE FROM accounts'"):
            with self.subTest(expression=expression):
                code, result = self.inspect("<?php\n$db->query(" + expression + ");\n")
                self.assertEqual(1, code)
                self.assertTrue(any("sql_ownership: new violation" in error for error in result["errors"]))

    def test_06_enum_like_text_inside_sql_is_not_erased(self):
        code, result = self.inspect("<?php\n$db->query(\"SELECT field FROM accounts WHERE label='Capability::SELECT'\");\n")
        self.assertEqual(1, code)
        self.assertTrue(any("sql_ownership: new violation" in error for error in result["errors"]))

    def test_07_runtime_ddl_and_dml_still_rejected(self):
        code, result = self.inspect("<?php\n$cap='assignment_order.composition.select'; $db->query('CREATE TABLE forbidden(id INT)');\n$db->query('UPDATE forbidden SET id=2');\n", "rapid-pilot/hostile.php")
        self.assertEqual(1, code)
        self.assertTrue(any("ddl_ownership: new violation" in error for error in result["errors"]))
        self.assertTrue(any("rapid_pilot_boundary: new violation" in error for error in result["errors"]))


if __name__ == "__main__":
    unittest.main(verbosity=2)
