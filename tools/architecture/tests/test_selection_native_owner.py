"""ASSIGNMENT-ORDER-SELECTION-NATIVE-001: narrow SQL owner, public CLI."""
import unittest
import test_php_select_tokens as cli


class SelectionNativeOwnerTest(unittest.TestCase):
    def inspect(self, source, filename):
        return cli.PhpSelectTokensTest().inspect(source, 'app/AssignmentOrderComposition/' + filename)

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
