# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v10

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

GREEN-attempt gap base: `95c1718e85d6590eb2c5d6fe790066da320d18a2`

Outcome: **INTENDED RED — public migration seam absent**

Expression-specific parenthesis rewrites are replaced by a bounded boolean
tokenizer/parser. It strips only balanced whole wrappers, scans quoted tokens
and nesting, parses top-level `OR` before `AND`, treats the `AND` belonging to
`BETWEEN` as atomic, produces a small `atom|and|or` AST and serializes one
stable precedence-preserving form. Unsupported XOR/semicolon/malformed
parentheses fail closed with fixed `CHECK_NORMALIZATION_FAILURE`.

Before the missing migration guard, real MariaDB tables instantiate every
approved CHECK across roots, revisions, requests, events, audits and both
maintenance tables. Every round trip must equal the complete Contract oracle.
Near mutations cover revision comparison operator, request/audit/maintenance
operands and literals, AND/OR grouping/precedence, regex pattern and unsupported
grammar; none canonicalizes to an approved expression.

Task 2.2 is rechecked. No production or specification artifact changed.

## Reproduced evidence

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema query for SCHEMA_NAME LIKE 't_aoou_%'
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_CLEANUP_OK

$ git diff --check
PASS (no output)
```

## Exact hashes

```text
d23456ea763d4a584ad45d266f3ac37a609af07f541e87285b815c333f0bbb0e  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
768ce3071d52870145e9b0fad3bc048f5bf94f70da1dfe1d7c0e9f73366f3403  docs/operations/assignment-order-original-database-setup-green-attempt-boolean-ast-gap-2026-09-05.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

This append-only record omits its own circular hash. Fresh independent Gate 3
review is required before implementation resumes.
