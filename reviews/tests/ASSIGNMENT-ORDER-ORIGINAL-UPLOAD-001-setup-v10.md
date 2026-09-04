# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v10

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v10`
- Test author: separately tasked agent `/root/assignment_original_red2`
- Reviewed commit: `445578994659dfa49fd44f3247b8dd507fb7b292`
- GREEN-attempt boolean-AST-gap base: `95c1718e85d6590eb2c5d6fe790066da320d18a2`
- Prior Gate 3 v9: approved before the newly observed boolean-expression
  canonicalization gap
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v12, database setup only
- Public seams: `AssignmentOrderOriginalSchemaMigration::apply(mysqli, prefix)`,
  `AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA(mysqli,
  prefix)`, and `cleanupExampleA(mysqli, prefix)`
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author or edit the specification, OpenSpec artifacts,
approvals, tests, support oracle, production code or RED evidence. This new
append-only review record is the only artifact added.

## Blocking finding

### The claimed malformed-expression fail-closed boundary is not implemented or tested

The new parser validates balance only inside `stripBooleanOuter`, and that scan
runs only while the complete expression both starts with `(` and ends with
`)`. `splitBoolean` tracks quote and parenthesis state but never rejects a
negative depth, non-zero final depth, or an open quote. The atom branch then
rejects only an empty atom, semicolon, or substring `xor`.

Consequently malformed inputs such as `status='accepted')`,
`(status='accepted'`, and `status='accepted` reach the atom serializer instead
of the fixed `CHECK_NORMALIZATION_FAILURE`. The only executable unsupported-
grammar control is XOR. There is no assertion for malformed quotes,
unbalanced opening parentheses, or unbalanced closing parentheses, despite the
RED evidence explicitly stating that malformed parentheses fail closed.

This is material to Gate 3 sensitivity: a malformed or unexpectedly emitted
server clause must not be silently accepted and compared as an ordinary atom.
Add one centralized whole-input lexical/balance validation used on every parse
path and executable near controls for at least unmatched opening parenthesis,
unmatched closing parenthesis, and unterminated quote. The controls must demand
the fixed fail-closed outcome before the missing-production-seam guard. A fresh
independent Gate 3 rereview is required after correction.

## Coverage that otherwise passes review

The quote/parenthesis-aware top-level splitting, `BETWEEN ... AND ...` handling,
OR-before-AND AST construction, and precedence-preserving serialization are
appropriately bounded for the approved CHECK oracle once malformed-input
validation is made total. Real MariaDB probes instantiate the complete CHECK
sets for all seven owned tables: roots in the existing roots round trip and
revisions, requests, events, audits, maintenance requests, and maintenance
audits in the new complete probes.

Mutation sensitivity is present for revision comparison, request literal,
audit grouping/precedence, maintenance accounting operands, regex content, and
XOR. Exact sorted set comparison retains count and content sensitivity.

No previously reviewed coverage was removed. The test still retains exact
seven-table names and properties; ordered columns including nullable/default,
charset, collation and extra; exact keys and ordered columns; every FK target
and action plus leading support-index checks; clean/repeat/compatible-leading-
partial/populated outcomes; gross and near conflict rollback with schema/row
snapshots; opaque identity default/collation sensitivity; and same-count wrong
CHECK sensitivity.

The fixture still derives independent projection hashes for exact fictional,
credential-free user/role/grant/capability, workforce, case, order,
composition, opening, task, checklist, and decoy facts. It retains zero
original facts, repeat idempotency, drift-conflict rollback, reverse
byte-validated cleanup, unrelated-state preservation, and server-observed
`SERIALIZABLE` InnoDB contention for both seed and cleanup with bounded child,
socket, transaction and observer cleanup. All five databases and every probe
remain under the independently bounded `t_aoou_<axis>_<12 hex>` namespace.

## Reproduced real-MariaDB RED and cleanup

Against MariaDB at `127.0.0.1:23306`:

```text
$ php -l tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigration production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ independent information_schema query for SCHEMA_NAME LIKE 't\\_aoou\\_%'
0

$ git diff --check
PASS (no output)
```

The live seven-table CHECK probes and other preflight assertions reach the
intended absent-seam RED, and cleanup succeeds. That valid RED reproduction does
not cure the missing malformed-input sensitivity described above, so Gate 3
does not authorize task 3.1.

## Exact reviewed SHA-256 inputs

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f4851a33f5bf56c6797c2586791798d8f16d5cfa347162fe84a5b0915cd93a9d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9e2c354069c3b62f5d65eea37dd0417b6bbd8ef78d804f870882beda537dbbc1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
d23456ea763d4a584ad45d266f3ac37a609af07f541e87285b815c333f0bbb0e  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
c3e120c50c79c90e92324b773b4bc188717711e07bafc4b07c1c65134ac90844  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v9.md
768ce3071d52870145e9b0fad3bc048f5bf94f70da1dfe1d7c0e9f73366f3403  docs/operations/assignment-order-original-database-setup-green-attempt-boolean-ast-gap-2026-09-05.md
5bd32471ea529fcd05cc5c6c1113681d0741217d7cba8513f47b1c113fbae4ec  docs/operations/assignment-order-original-database-setup-red-correction-v10-2026-09-05.md
```

The review path is metadata because a self-hash is circular.
