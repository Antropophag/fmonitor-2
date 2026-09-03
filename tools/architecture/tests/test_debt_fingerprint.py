#!/usr/bin/env python3
"""Executable specification for ARCHITECTURE-DEBT-FINGERPRINT-001."""

import importlib.util
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch


CHECK_PATH = Path(__file__).resolve().parents[1] / "check.py"
SPEC = importlib.util.spec_from_file_location("architecture_check", CHECK_PATH)
assert SPEC is not None and SPEC.loader is not None
architecture_check = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(architecture_check)


class DebtFingerprintTest(unittest.TestCase):
    def assert_collected_fingerprint_relationship(
        self,
        original: str,
        changed: str,
        *,
        equal: bool,
        context: str,
        buckets: tuple[str, ...] = ("sql_ownership", "rapid_pilot_boundary"),
    ) -> None:
        rapid_pilot = architecture_check.ROOT / "rapid-pilot"
        with tempfile.NamedTemporaryFile(
            mode="w+",
            encoding="utf-8",
            suffix=".php",
            prefix="FingerprintFixture",
            dir=rapid_pilot,
        ) as fixture, patch.object(architecture_check, "files", return_value=[Path(fixture.name)]):
            fixture.write(original)
            fixture.flush()
            original_findings = architecture_check.collect()
            fixture.seek(0)
            fixture.truncate()
            fixture.write(changed)
            fixture.flush()
            changed_findings = architecture_check.collect()
        for bucket in buckets:
            with self.subTest(context=context, bucket=bucket):
                self.assertIn(bucket, original_findings, "fixture must reach the production collection rule")
                self.assertIn(bucket, changed_findings, "changed fixture must reach the production collection rule")
                assertion = self.assertEqual if equal else self.assertNotEqual
                assertion(
                    original_findings[bucket],
                    changed_findings[bucket],
                    (
                        "actual PHP global-call qualification must be fingerprint-neutral"
                        if equal
                        else "non-function-call source identity must remain fingerprint-sensitive"
                    ),
                )

    def test_global_call_qualification_is_ignored_but_sql_ddl_text_is_not(self) -> None:
        path = architecture_check.ROOT / "rapid-pilot" / "FingerprintFixture.php"
        examples = (
            ("sql", '$rows = mysqli_query($db, "SELECT id FROM cases");',
             '$rows = \\mysqli_query($db, "SELECT id FROM cases");',
             '$rows = mysqli_query($db, "SELECT name FROM cases");'),
            ("ddl", '$ok = mysqli_query($db, "CREATE TABLE cases (id INT)");',
             '$ok = \\mysqli_query($db, "CREATE TABLE cases (id INT)");',
             '$ok = mysqli_query($db, "CREATE TABLE orders (id INT)");'),
        )

        for rule, unqualified, qualified, changed_debt in examples:
            with self.subTest(rule=rule):
                original = architecture_check.finding(rule, path, 1, unqualified)
                self.assertNotEqual(
                    original,
                    architecture_check.finding(rule, path, 1, changed_debt),
                    "genuine SQL/DDL text changes must alter the debt fingerprint",
                )
                self.assertEqual(
                    original,
                    architecture_check.finding(rule, path, 1, qualified),
                    "qualifying a PHP direct global call must not alter the debt fingerprint",
                )

    def test_sql_and_ddl_literal_content_remains_fingerprint_sensitive(self) -> None:
        path = architecture_check.ROOT / "rapid-pilot" / "FingerprintFixture.php"
        examples = (
            (
                "sql-backslash",
                "sql",
                '$rows = mysqli_query($db, "SELECT \'mysqli_query(\' AS marker FROM cases");',
                '$rows = mysqli_query($db, "SELECT \'\\mysqli_query(\' AS marker FROM cases");',
            ),
            (
                "sql-text",
                "sql",
                '$rows = mysqli_query($db, "SELECT id FROM cases");',
                '$rows = mysqli_query($db, "SELECT name FROM cases");',
            ),
            (
                "ddl-backslash",
                "ddl",
                '$ok = mysqli_query($db, "CREATE TABLE cases (marker VARCHAR(32) DEFAULT \'mysqli_query(\')");',
                '$ok = mysqli_query($db, "CREATE TABLE cases (marker VARCHAR(32) DEFAULT \'\\mysqli_query(\')");',
            ),
            (
                "ddl-text",
                "ddl",
                '$ok = mysqli_query($db, "CREATE TABLE cases (id INT)");',
                '$ok = mysqli_query($db, "CREATE TABLE orders (id INT)");',
            ),
        )

        for context, rule, original, changed in examples:
            with self.subTest(context=context):
                self.assertNotEqual(
                    architecture_check.finding(rule, path, 1, original),
                    architecture_check.finding(rule, path, 1, changed),
                    "every backslash or text change inside SQL/DDL literals must alter the fingerprint",
                )

    def test_call_like_text_outside_php_code_is_not_normalized(self) -> None:
        path = architecture_check.ROOT / "rapid-pilot" / "FingerprintFixture.php"
        examples = (
            (
                "ordinary-string",
                '$rows = mysqli_query($db, "SELECT id FROM cases"); $label = "mysqli_query(";',
                '$rows = mysqli_query($db, "SELECT id FROM cases"); $label = "\\mysqli_query(";',
            ),
            (
                "line-comment",
                '$rows = mysqli_query($db, "SELECT id FROM cases"); // mysqli_query(',
                '$rows = mysqli_query($db, "SELECT id FROM cases"); // \\mysqli_query(',
            ),
            (
                "block-comment",
                '$rows = mysqli_query($db, "SELECT id FROM cases"); /* mysqli_query( */',
                '$rows = mysqli_query($db, "SELECT id FROM cases"); /* \\mysqli_query( */',
            ),
        )

        for context, original, changed in examples:
            with self.subTest(context=context):
                self.assertNotEqual(
                    architecture_check.finding("sql", path, 1, original),
                    architecture_check.finding("sql", path, 1, changed),
                    "call-like text in strings and comments is evidence, not executable PHP code",
                )

    def test_namespace_separator_is_not_treated_as_global_call_qualification(self) -> None:
        path = architecture_check.ROOT / "rapid-pilot" / "FingerprintFixture.php"
        namespaced = '$rows = Vendor\\mysqli_query($db, "SELECT id FROM cases");'
        differently_named_global = '$rows = Vendormysqli_query($db, "SELECT id FROM cases");'

        self.assertNotEqual(
            architecture_check.finding("sql", path, 1, namespaced),
            architecture_check.finding("sql", path, 1, differently_named_global),
            "a namespace separator is code identity, not an optional global-call qualifier",
        )

    def test_collection_normalizes_actual_global_calls_across_a_multiline_php_file(self) -> None:
        original = """<?php
$label = 'prefix';
$rows = mysqli_query($db, "UPDATE cases SET id = id");
$suffix = "done";
"""
        qualified = original.replace("$rows = mysqli_query(", "$rows = \\mysqli_query(")

        self.assert_collected_fingerprint_relationship(
            original,
            qualified,
            equal=True,
            context="executable-global-call",
        )

    def test_collection_preserves_backslashes_in_multiline_literal_and_comment_state(self) -> None:
        examples = (
            (
                "multiline-single-quoted-string",
                """<?php
$query = 'prefix
UPDATE cases SET marker = mysqli_query(
suffix';
""",
                """<?php
$query = 'prefix
UPDATE cases SET marker = \\mysqli_query(
suffix';
""",
            ),
            (
                "multiline-double-quoted-string",
                """<?php
$query = "prefix
UPDATE cases SET marker = mysqli_query(
suffix";
""",
                """<?php
$query = "prefix
UPDATE cases SET marker = \\mysqli_query(
suffix";
""",
            ),
            (
                "heredoc",
                """<?php
$query = <<<SQL_TEXT
UPDATE cases SET marker = mysqli_query(
SQL_TEXT;
""",
                """<?php
$query = <<<SQL_TEXT
UPDATE cases SET marker = \\mysqli_query(
SQL_TEXT;
""",
            ),
            (
                "nowdoc",
                """<?php
$query = <<<'SQL_TEXT'
UPDATE cases SET marker = mysqli_query(
SQL_TEXT;
""",
                """<?php
$query = <<<'SQL_TEXT'
UPDATE cases SET marker = \\mysqli_query(
SQL_TEXT;
""",
            ),
            (
                "continued-block-comment",
                """<?php
/* commentary begins
UPDATE cases SET marker = mysqli_query(
commentary ends */
""",
                """<?php
/* commentary begins
UPDATE cases SET marker = \\mysqli_query(
commentary ends */
""",
            ),
        )

        for context, original, changed in examples:
            self.assert_collected_fingerprint_relationship(
                original,
                changed,
                equal=False,
                context=context,
            )

    def test_collection_normalizes_calls_only_inside_executable_php_regions(self) -> None:
        fingerprint_sensitive_examples = (
            (
                "before-open-tag",
                "UPDATE cases SET marker = mysqli_query(\n<?php\n$ready = true;\n",
                "UPDATE cases SET marker = \\mysqli_query(\n<?php\n$ready = true;\n",
            ),
            (
                "after-close-tag",
                "<?php\n$ready = true;\n?>\nUPDATE cases SET marker = mysqli_query(\n",
                "<?php\n$ready = true;\n?>\nUPDATE cases SET marker = \\mysqli_query(\n",
            ),
            (
                "inline-html",
                "<?php $ready = true; ?>UPDATE cases SET marker = mysqli_query(<?php $done = true; ?>\n",
                "<?php $ready = true; ?>UPDATE cases SET marker = \\mysqli_query(<?php $done = true; ?>\n",
            ),
            (
                "php-backtick-string",
                "<?php\n$result = `UPDATE cases SET marker = mysqli_query(`;\n",
                "<?php\n$result = `UPDATE cases SET marker = \\mysqli_query(`;\n",
            ),
        )

        for context, original, changed in fingerprint_sensitive_examples:
            self.assert_collected_fingerprint_relationship(
                original,
                changed,
                equal=False,
                context=context,
            )

        original = """inline UPDATE cases SET marker = unchanged
<?php
$first = mysqli_query($db, "UPDATE cases SET id = id");
?>
inline HTML
<?php
$second = mysqli_query($db, "UPDATE cases SET id = id");
?>
"""
        qualified = original.replace("$second = mysqli_query(", "$second = \\mysqli_query(")
        self.assert_collected_fingerprint_relationship(
            original,
            qualified,
            equal=True,
            context="actual-call-inside-reopened-php-block",
        )

    def test_php_open_tag_requires_a_php_valid_boundary(self) -> None:
        invalid_original = "<?phpfoo UPDATE cases SET marker = mysqli_query(\n"
        invalid_qualified = "<?phpfoo UPDATE cases SET marker = \\mysqli_query(\n"
        self.assert_collected_fingerprint_relationship(
            invalid_original,
            invalid_qualified,
            equal=False,
            context="php-prefix-without-whitespace-boundary-is-inline-text",
        )

        valid_original = "<?php $rows = mysqli_query($db, \"UPDATE cases SET id = id\");\n"
        valid_qualified = "<?php $rows = \\mysqli_query($db, \"UPDATE cases SET id = id\");\n"
        self.assert_collected_fingerprint_relationship(
            valid_original,
            valid_qualified,
            equal=True,
            context="php-open-tag-with-whitespace-boundary-enters-code",
        )

    def test_php_close_tag_inside_line_comments_returns_to_inline_text(self) -> None:
        examples = (
            (
                "double-slash-comment-close-tag",
                "<?php\n// commentary ?>\nUPDATE cases SET marker = mysqli_query(\n",
                "<?php\n// commentary ?>\nUPDATE cases SET marker = \\mysqli_query(\n",
            ),
            (
                "hash-comment-close-tag",
                "<?php\n# commentary ?>\nUPDATE cases SET marker = mysqli_query(\n",
                "<?php\n# commentary ?>\nUPDATE cases SET marker = \\mysqli_query(\n",
            ),
        )

        for context, original, changed in examples:
            self.assert_collected_fingerprint_relationship(
                original,
                changed,
                equal=False,
                context=context,
            )

    def test_php_open_tag_is_case_insensitive_as_classified_by_php_tokenization(self) -> None:
        original = '<?PHP $rows = mysqli_query($db, "UPDATE cases SET id = id");\n'
        qualified = '<?PHP $rows = \\mysqli_query($db, "UPDATE cases SET id = id");\n'

        self.assert_collected_fingerprint_relationship(
            original,
            qualified,
            equal=True,
            context="case-insensitive-php-open-tag-enters-code",
        )

    def test_php_attribute_is_code_as_classified_by_php_tokenization(self) -> None:
        original = (
            '<?php #[FingerprintFixture] function fingerprintFixture(): void {} '
            '$rows = mysqli_query($db, "UPDATE cases SET id = id");\n'
        )
        qualified = original.replace("$rows = mysqli_query(", "$rows = \\mysqli_query(")

        self.assert_collected_fingerprint_relationship(
            original,
            qualified,
            equal=True,
            context="php-8-attribute-does-not-start-a-hash-comment",
        )

    def test_only_direct_function_call_qualifiers_are_fingerprint_neutral(self) -> None:
        fingerprint_sensitive_examples = (
            (
                "constructor-class-name",
                '<?php $fixture = new FingerprintFixture(); '
                '$rows = mysqli_query($db, "CREATE TABLE cases (id INT); UPDATE cases SET id = id");\n',
                '<?php $fixture = new \\FingerprintFixture(); '
                '$rows = mysqli_query($db, "CREATE TABLE cases (id INT); UPDATE cases SET id = id");\n',
            ),
            (
                "attribute-class-name",
                '<?php #[FingerprintFixture()] class FingerprintedClass {} '
                '$rows = mysqli_query($db, "CREATE TABLE cases (id INT); UPDATE cases SET id = id");\n',
                '<?php #[\\FingerprintFixture()] class FingerprintedClass {} '
                '$rows = mysqli_query($db, "CREATE TABLE cases (id INT); UPDATE cases SET id = id");\n',
            ),
            (
                "attribute-class-name-with-comma-separated-nested-arguments",
                '<?php #[FingerprintFixture(first: new NestedFixture(), values: [1, 2])] '
                'class FingerprintedClass {} '
                '$rows = mysqli_query($db, "CREATE TABLE cases (id INT); UPDATE cases SET id = id");\n',
                '<?php #[\\FingerprintFixture(first: new NestedFixture(), values: [1, 2])] '
                'class FingerprintedClass {} '
                '$rows = mysqli_query($db, "CREATE TABLE cases (id INT); UPDATE cases SET id = id");\n',
            ),
        )

        for context, original, changed in fingerprint_sensitive_examples:
            self.assert_collected_fingerprint_relationship(
                original,
                changed,
                equal=False,
                context=context,
                buckets=("ddl_ownership", "sql_ownership", "rapid_pilot_boundary"),
            )

        function_call = (
            '<?php $fixture = fingerprintFixture(); '
            '$rows = mysqli_query($db, "CREATE TABLE cases (id INT); UPDATE cases SET id = id");\n'
        )
        qualified_function_call = function_call.replace(
            "$fixture = fingerprintFixture(",
            "$fixture = \\fingerprintFixture(",
        )
        self.assert_collected_fingerprint_relationship(
            function_call,
            qualified_function_call,
            equal=True,
            context="ordinary-php-fully-qualified-function-call",
            buckets=("ddl_ownership", "sql_ownership", "rapid_pilot_boundary"),
        )

    def test_workforce_migration_owner_allowlist_is_exact(self) -> None:
        self.assertTrue(
            architecture_check.workforce_migration_owner(
                architecture_check.ROOT / "bin" / "fmonitor2-migrate.php"
            )
        )
        self.assertTrue(
            architecture_check.workforce_migration_owner(
                architecture_check.ROOT
                / "app"
                / "InstallationProcess"
                / "WorkforceCatalogSchemaMigration.php"
            )
        )
        self.assertFalse(
            architecture_check.workforce_migration_owner(
                architecture_check.ROOT / "rapid-pilot" / "docker-bootstrap.php"
            )
        )
        self.assertTrue(
            architecture_check.workforce_migration_owner(
                architecture_check.ROOT / "rapid-pilot" / "verify-schema.php"
            )
        )
        self.assertFalse(
            architecture_check.workforce_migration_owner(
                architecture_check.ROOT / "bin" / "verify-schema.php"
            )
        )

    def test_workforce_migration_ownership_is_not_baselineable(self) -> None:
        finding = "workforce-migration|rapid-pilot/docker-bootstrap.php|fixture"
        errors = architecture_check.compare(
            {"workforce_migration_ownership": [finding]},
            {"workforce_migration_ownership": [finding]},
        )
        self.assertEqual(
            [f"workforce_migration_ownership: forbidden production owner: {finding}"],
            errors,
        )

    def test_multiline_workforce_ddl_is_forbidden(self) -> None:
        rapid_pilot = architecture_check.ROOT / "rapid-pilot"
        source = """<?php
$db->query(\"CREATE TABLE
    `{$prefix}fm2_workforce_sync_runs`
    (run_id CHAR(36))\");
"""
        with tempfile.NamedTemporaryFile(
            mode="w+",
            encoding="utf-8",
            suffix=".php",
            prefix="WorkforceOwnershipFixture",
            dir=rapid_pilot,
        ) as fixture, patch.object(architecture_check, "files", return_value=[Path(fixture.name)]):
            fixture.write(source)
            fixture.flush()
            findings = architecture_check.collect()
        self.assertEqual(1, len(findings["workforce_migration_ownership"]))
        self.assertIn("workforce-ddl|rapid-pilot/", findings["workforce_migration_ownership"][0])

    def test_workforce_migration_ownership_detection_matrix(self) -> None:
        examples = (
            ("v2-apply", "<?php WorkforceCatalogSchemaMigration::apply($db, $prefix);"),
            ("v5-apply", "<?php BitrixWorkforceHistorySchemaMigration::apply($db, $prefix);"),
            ("create-one-line", '<?php $db->query("CREATE TABLE `{$prefix}fm2_workforce_catalog` (id INT)");'),
            ("create-multiline", '<?php $db->query("CREATE TABLE\n`{$prefix}fm2_workforce_catalog` (id INT)");'),
            ("alter-one-line", '<?php $db->query("ALTER TABLE `{$prefix}fm2_workforce_catalog` ADD marker INT");'),
            ("alter-multiline", '<?php $db->query("ALTER TABLE\n`{$prefix}fm2_workforce_catalog` ADD marker INT");'),
            ("drop-one-line", '<?php $db->query("DROP TABLE `{$prefix}fm2_workforce_catalog`");'),
            ("drop-multiline", '<?php $db->query("DROP TABLE\n`{$prefix}fm2_workforce_catalog`");'),
        )
        rapid_pilot = architecture_check.ROOT / "rapid-pilot"
        for context, source in examples:
            with self.subTest(context=context), tempfile.NamedTemporaryFile(
                mode="w+",
                encoding="utf-8",
                suffix=".php",
                prefix="WorkforceOwnershipMatrixFixture",
                dir=rapid_pilot,
            ) as fixture, patch.object(architecture_check, "files", return_value=[Path(fixture.name)]):
                fixture.write(source + "\n")
                fixture.flush()
                findings = architecture_check.collect()
            self.assertEqual(1, len(findings["workforce_migration_ownership"]))

    def test_workforce_migration_ownership_detects_imported_apply_aliases(self) -> None:
        examples = (
            (
                "v5-alias",
                "BitrixWorkforceHistorySchemaMigration",
                "HistoryUpgradeOwner",
            ),
            (
                "v2-alias",
                "WorkforceCatalogSchemaMigration",
                "ImportedCatalogUpgrade",
            ),
        )
        rapid_pilot = architecture_check.ROOT / "rapid-pilot"
        for context, migration_class, alias in examples:
            source = f"""<?php
use FMonitor2\\InstallationProcess\\{migration_class} as {alias};
{alias}::apply($db, $prefix);
"""
            with self.subTest(context=context), tempfile.NamedTemporaryFile(
                mode="w+",
                encoding="utf-8",
                suffix=".php",
                prefix="WorkforceOwnershipAliasFixture",
                dir=rapid_pilot,
            ) as fixture, patch.object(architecture_check, "files", return_value=[Path(fixture.name)]):
                fixture.write(source)
                fixture.flush()
                findings = architecture_check.collect()
                self.assertEqual(1, len(findings.get("workforce_migration_ownership", [])))

    def test_workforce_migration_ownership_detects_variable_target_ddl(self) -> None:
        examples = (
            (
                "create",
                "catalogRelation",
                "fm2_workforce_catalog",
                "CREATE TABLE",
                " (id INT)",
            ),
            (
                "alter",
                "observationLedger",
                "fm2_workforce_observations",
                "ALTER TABLE",
                " ADD marker INT",
            ),
            (
                "drop",
                "obsoleteRunRegister",
                "fm2_workforce_sync_runs",
                "DROP TABLE",
                "",
            ),
        )
        rapid_pilot = architecture_check.ROOT / "rapid-pilot"
        for context, variable, basename, operation, suffix in examples:
            source = f"""<?php
${variable} = $prefix . '{basename}';
$db->query("{operation} `{{${variable}}}`{suffix}");
"""
            with self.subTest(context=context), tempfile.NamedTemporaryFile(
                mode="w+",
                encoding="utf-8",
                suffix=".php",
                prefix="WorkforceOwnershipVariableDdlFixture",
                dir=rapid_pilot,
            ) as fixture, patch.object(architecture_check, "files", return_value=[Path(fixture.name)]):
                fixture.write(source)
                fixture.flush()
                findings = architecture_check.collect()
                self.assertEqual(1, len(findings.get("workforce_migration_ownership", [])))

    def test_session_storage_ownership_rejects_native_session_and_hardcoded_root(self) -> None:
        rapid_pilot = architecture_check.ROOT / "rapid-pilot"
        source = """<?php
session_save_path('/home/fmonitor/.local/state/fmonitor2/sessions');
session_start();
session_regenerate_id(true);
session_write_close();
session_destroy();
"""
        with tempfile.NamedTemporaryFile(
            mode="w+",
            encoding="utf-8",
            suffix=".php",
            prefix="SessionOwnershipFixture",
            dir=rapid_pilot,
        ) as fixture, patch.object(architecture_check, "files", return_value=[Path(fixture.name)]):
            fixture.write(source)
            fixture.flush()
            findings = architecture_check.collect()
        self.assertEqual(6, len(findings.get("session_storage_ownership", [])))

    def test_session_storage_ownership_rejects_internal_factory_callers(self) -> None:
        identity = architecture_check.ROOT / "app" / "IdentityAccess"
        source = """<?php
PilotSessionOperationResult::ownerStarted('fixture-session-id');
PilotSessionFilesystemEvent::ownerBefore(1, $operation, $artifact, null, 1);
PilotSessionInspectionResult::inspectorOk('{}');
"""
        with tempfile.NamedTemporaryFile(
            mode="w+",
            encoding="utf-8",
            suffix=".php",
            prefix="SessionInternalFactoryFixture",
            dir=identity,
        ) as fixture, patch.object(architecture_check, "files", return_value=[Path(fixture.name)]):
            fixture.write(source)
            fixture.flush()
            findings = architecture_check.collect()
        self.assertEqual(3, len(findings.get("session_storage_ownership", [])))


if __name__ == "__main__":
    unittest.main()
