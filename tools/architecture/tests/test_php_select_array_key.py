"""ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001: independent literal CLI fixtures."""
import importlib.util
import unittest
from pathlib import Path

# Reuse only the established public-CLI isolated-repository harness.
spec = importlib.util.spec_from_file_location("select_tokens_fixture", Path(__file__).with_name("test_php_select_tokens.py"))
fixture = importlib.util.module_from_spec(spec)
spec.loader.exec_module(fixture)


class PhpSelectArrayKeyTest(unittest.TestCase):
    inspect = fixture.PhpSelectTokensTest.inspect

    def test_01_quoted_array_keys_are_not_sql(self):
        for key in ("'select'", '"select"', "'SELECT'", '"SeLeCt"'):
            with self.subTest(key=key):
                code, result = self.inspect("<?php\n$request = ['nested' => [" + key + "  => ['ID']]];\n", "app/Workforce/Request.php")
                self.assertEqual((0, True, []), (code, result['ok'], result['errors']))

    # Literal fingerprints: SHA256(original single-space source line)[:16], not scanner output.
    def test_02_actual_sql_in_value_or_same_line_stays_detected(self):
        for source, fingerprint in (
            ("$request=['select'=>'SELECT id FROM users'];", 'aed8be61d25c7b35'),
            ("$request=['select'=>[]]; $db->query('SELECT id FROM users');", '3543c41d58574cf5'),
            ("$request=['select'=>[]]; $db->query('SELECT'.' id FROM users');", '8e476761fb489065'),
            ('$request=[\'select\'=>[]]; $db->query("SELECT id FROM users WHERE label=\'select => value\'");', '48c115cd75dcc689'),
        ):
            with self.subTest(source=source):
                code, result = self.inspect('<?php\n' + source + '\n', 'app/Workforce/Request.php')
                self.assertEqual((1, False), (code, result['ok']))
                self.assertEqual(['sql_ownership: new violation (1x): sql|app/Workforce/Request.php|' + fingerprint], result['errors'])

    def test_03_bare_select_fragment_is_not_a_key(self):
        code, result = self.inspect("<?php\n$query='SELECT'.' id FROM users';\n", 'app/Workforce/Request.php')
        self.assertEqual((1, False), (code, result['ok']))
        self.assertTrue(result['errors'][0].startswith('sql_ownership: new violation'))

    def test_04_newline_before_arrow_is_not_exempt(self):
        code, result = self.inspect("<?php\n$request=['select'\n=> ['ID']];\n", 'app/Workforce/Request.php')
        self.assertEqual((1, False), (code, result['ok']))
        self.assertEqual(['sql_ownership: new violation (1x): sql|app/Workforce/Request.php|763517130677fd92'], result['errors'])

    def test_05_other_rules_still_apply(self):
        code, result = self.inspect("<?php\n$request=['select'=>[]]; $db->query('CREATE TABLE forbidden(id INT)');\n$db->query('UPDATE forbidden SET id=2');\n", 'rapid-pilot/hostile.php')
        self.assertEqual((1, False), (code, result['ok']))
        for rule in ('ddl_ownership:', 'sql_ownership:', 'rapid_pilot_boundary:'):
            self.assertTrue(any(error.startswith(rule) for error in result['errors']))


if __name__ == '__main__':
    unittest.main(verbosity=2)
