"""PR291: native selects and JavaScript identifiers are not SQL statements."""
import importlib.util
import unittest
from pathlib import Path

_spec = importlib.util.spec_from_file_location(
    'select_fixture', Path(__file__).resolve().parents[2] / 'tools/architecture/tests/test_php_select_tokens.py')
_fixture = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(_fixture)


class NativeSelectTokensTest(unittest.TestCase):
    inspect = _fixture.PhpSelectTokensTest.inspect

    def test_javascript_form_and_value_access_are_not_sql(self):
        for source in (
            "nodes.forEach(select=>select.addEventListener('change',()=>select.form?.requestSubmit()));",
            "const select = node; select.form.requestSubmit(); select.value = '25';",
            "nodes.forEach(select=>select?.form?.requestSubmit());",
        ):
            with self.subTest(source=source):
                code, result = self.inspect(source+'\n', 'app/YiiRuntime/Assets/example.js')
                self.assertEqual((0, True, []), (code, result['ok'], result['errors']),
                                 'INTENDED_RED: JavaScript select identifiers are not SQL')

    def test_native_template_select_tags_are_not_sql(self):
        for source in (
            '<select name="pageSize"><?php foreach([10,25]as$size):?><option><?=$size?></option><?php endforeach?></select>',
            '<?php $size=25; ?><SELECT name="pageSize"><option selected><?= $size ?></option></SELECT>',
            '<select\n name="pageSize">\n<option>25</option>\n</select>',
        ):
            with self.subTest(source=source):
                code, result = self.inspect(source+'\n', 'app/YiiRuntime/Views/example.php')
                self.assertEqual((0, True, []), (code, result['ok'], result['errors']),
                                 'INTENDED_RED: native HTML select tags are not SQL')

    def test_real_sql_in_javascript_literals_is_still_rejected(self):
        for sql in ("'SELECT id FROM users'", "'SELECT'+' id FROM users'",
                    "`SELECT.form FROM users`", "`start\nselect id FROM users\n`",
                    "'UPDATE users SET active=1'", "'INSERT INTO users VALUES(1)'",
                    "'DELETE FROM users'"):
            with self.subTest(sql=sql):
                source = "nodes.forEach(select=>select.form?.requestSubmit()); const query="+sql+";\n"
                code, result = self.inspect(source, 'app/YiiRuntime/Assets/example.js')
                self.assertEqual((1, False), (code, result['ok']))
                self.assertTrue(any('sql_ownership: new violation' in e for e in result['errors']))

    def test_markup_does_not_hide_php_sql_or_fragments(self):
        for sql in ("'SELECT id FROM users'", "'SELECT'.' id FROM users'",
                    "'UPDATE users SET active=1'", "'INSERT INTO users VALUES(1)'",
                    "'DELETE FROM users'"):
            with self.subTest(sql=sql):
                source = '<select name="size"></select><?php $query='+sql+'; ?>\n'
                code, result = self.inspect(source, 'app/YiiRuntime/Views/example.php')
                self.assertEqual((1, False), (code, result['ok']))
                self.assertTrue(any('sql_ownership: new violation' in e for e in result['errors']))

    def test_markup_does_not_erase_runtime_ddl_or_foreign_writes(self):
        source = '<select name="size"></select><?php $db->query("CREATE TABLE forbidden(id INT)"); $db->query("UPDATE forbidden SET id=2"); ?>\n'
        code, result = self.inspect(source, 'rapid-pilot/forbidden.php')
        self.assertEqual((1, False), (code, result['ok']))
        for rule in ['ddl_ownership', 'sql_ownership', 'rapid_pilot_boundary']:
            self.assertTrue(any(rule+': new violation' in e for e in result['errors']))


if __name__ == '__main__':
    unittest.main(verbosity=2)
