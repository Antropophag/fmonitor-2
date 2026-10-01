"""PR286: DOM select tokens are not SQL; actual statements remain forbidden."""
import importlib.util
import unittest
from pathlib import Path

spec = importlib.util.spec_from_file_location('select_fixture', Path(__file__).resolve().parents[2] / 'tools/architecture/tests/test_php_select_tokens.py')
fixture = importlib.util.module_from_spec(spec)
spec.loader.exec_module(fixture)


class JsSelectTokensTest(unittest.TestCase):
    inspect = fixture.PhpSelectTokensTest.inspect

    def test_dom_selectors_and_variable(self):
        for source in (
            "root.querySelectorAll('input.shlz-input,select.shlz-input,textarea.shlz-input');",
            'event.target.closest("a,button,input,select,textarea,label");',
            "nodes.forEach(select=>select.addEventListener('change',()=>select.closest('dialog')));",
        ):
            with self.subTest(source=source):
                code, result = self.inspect(source+'\n', 'app/YiiRuntime/Assets/example.js')
                self.assertEqual((0, True, []), (code, result['ok'], result['errors']))

    def test_sql_is_still_detected_in_js(self):
        for source in (
            "const query='SELECT id FROM users';",
            "const query='SELECT'+' id FROM users';",
            "root.querySelectorAll('select'); db.query('SELECT id FROM users');",
            "nodes.forEach(select=>db.query('SELECT id FROM users'));",
            "root.querySelectorAll('SELECT id FROM users');",
            "const query='UPDATE users SET active=1';",
            "const query='INSERT INTO users VALUES(1)';",
            "const query='DELETE FROM users';",
        ):
            with self.subTest(source=source):
                code, result = self.inspect(source+'\n', 'app/YiiRuntime/Assets/example.js')
                self.assertEqual((1, False), (code, result['ok']))
                self.assertTrue(any('sql_ownership: new violation' in e for e in result['errors']))

    def test_multiline_sql_literals_remain_detected(self):
        for source in (
            'const query = `\nselect id FROM users\n`;',
            'const query = `start\nselect\n` + " id FROM users";',
            'const query = "start\\\nselect id FROM users";',
            'const query = `start\nselect.col FROM users\n`;',
        ):
            with self.subTest(source=source):
                code, result = self.inspect(source+'\n', 'app/YiiRuntime/Assets/example.js')
                self.assertEqual((1, False), (code, result['ok']))
                self.assertTrue(any('sql_ownership: new violation' in e for e in result['errors']))

    def test_comment_and_regex_quotes_do_not_hide_sql(self):
        for source in (
            "// it's a DOM helper\nconst query = 'select id FROM users';",
            '/* quote " in a comment */\nconst query = "select id FROM users";',
            'const marker = /[\'"]/; const query = \'select id FROM users\';',
        ):
            with self.subTest(source=source):
                code, result = self.inspect(source+'\n', 'app/YiiRuntime/Assets/example.js')
                self.assertEqual((1, False), (code, result['ok']))
                self.assertTrue(any('sql_ownership: new violation' in e for e in result['errors']))

    def test_php_detection_is_unchanged(self):
        code, result = self.inspect("<?php\n$query='SELECT id FROM users';\n", 'app/YiiRuntime/Example.php')
        self.assertEqual((1, False), (code, result['ok']))


if __name__ == '__main__':
    unittest.main(verbosity=2)
