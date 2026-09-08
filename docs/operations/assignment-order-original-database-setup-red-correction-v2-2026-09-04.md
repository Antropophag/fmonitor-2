# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — database setup RED correction v2

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved v12 base: `bdeed9e56aae5ed2fad467aa7ec771cfce310f03`

Prior Gate 3: `2cc93762c91d1137717ad75bb77d50c122d2bd58`,
verdict **CHANGES_REQUESTED**

Outcome: **INTENDED RED — public migration seam absent**

## Corrections

- Every version-1 column now has a literal expected name, normalized type,
  nullability, character set, collation, default and extra property. All opaque
  identities/hashes require `ascii/ascii_bin`; textual columns require the
  database-default `utf8mb4_unicode_ci`.
- Every approved CHECK is independently enumerated and whitespace/backtick/
  redundant-outer-parenthesis normalized before exact comparison. UUID,
  opaque-ID, lower-hex, byte/revision, status/reason/evidence and maintenance
  accounting semantics are no longer represented by counts alone.
- Focused sensitivity replaces one CHECK with a wrong same-count expression and
  separately changes opaque-ID collation/default; both MUST conflict.
- The multi-conflict case stages a near-equivalent roots table with an extra
  semantic CHECK and a gross incompatible audits table in reverse binary name
  order while five manifest members remain missing. It requires every logical
  conflict name in binary order and byte-identical zero-DDL state.
- Fixture assertions now include user-role assignments, credential absence,
  engineer role/capability/position, complete target and decoy cases, full order
  snapshots/audit, installer snapshots and full task row.
- All six canonical JSON values and hashes are constructed from the seeded
  order/composition, case/opening, task and unrelated-case rows. V12 checklist
  availability is derived only from target case opening fields; decoy items are
  derived only from numerically ordered other case rows.
- A drifted target row is passed to both seed and cleanup. Each public seam MUST
  throw the fixed conflict and leave the full database byte-identical.
- Seed and cleanup run once behind an exact-identity lock held by a separate
  `SERIALIZABLE` transaction. A bounded child must remain blocked before release,
  then complete and be reaped; failure cleanup rolls back, closes descriptors
  and terminates/reaps a remaining child.
- Existing clean/repeat/leading-partial/populated preservation, zero-original-
  facts, idempotent cleanup and five-database attempt-always cleanup remain.

No production, executable spec or OpenSpec artifact was edited.

## Reproduced evidence

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

$ independent information_schema query for SCHEMA_NAME LIKE 't_aoou_%'
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_CLEANUP_OK

$ git diff --check
PASS (no output)
```

MariaDB connectivity, five database creations, projection-literal hash checks,
process-control availability and cleanup execute before the intended missing-
class RED. No skip, fallback, leaked database or production data is used.

## Exact hashes

```text
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f4851a33f5bf56c6797c2586791798d8f16d5cfa347162fe84a5b0915cd93a9d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
30476c480d9ca9bdee836934ae70ffa89d075562c0c217382762b518b2ff1a48  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9e2c354069c3b62f5d65eea37dd0417b6bbd8ef78d804f870882beda537dbbc1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
460c1aeb99cb13575e4a8870bee5afd502de75023987bae20ee1600c5361f297  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
0c5ad270bdd8317616d775c9e9b974c05b1ccc00b5fff67348e57a344a267449  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
cc65deb0130a24e9f019b0605b78a6707a2671a75f686899dc16ecad8bbe5192  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
c5174e5e109bd13104aa1085be1e6b975f2ccaef301ac4006714a644f37e301c  docs/operations/assignment-order-original-database-setup-red-2026-09-04.md
```

This record omits its own hash because self-hashing is circular. Corrected test
bytes require a fresh independently tasked Gate 3 review.
