# Code rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_code_rereview_20260901x`
- Independence: reviewer authored neither implementation nor Gate 2 tests
- Supersedes: `reviews/code/IDENTITY-ACCESS-SCHEMA-001.md`
- Reviewed state: authoritative dirty worktree at
  `79658fa1e12e9d5fe4b795b628de3d4f9ccf23af` plus the scoped uncommitted
  identity/access slice
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved `v0.1`
- Amendments: `identity-access-gate1-diagnostic-seam-amendment.md` and
  `identity-access-gate1-uca-alias-amendment.md`
- Gate 3 authority: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v6.md`, verdict
  `APPROVED`
- Gate 4 evidence: `docs/operations/identity-access-schema-green-verification.md`
- Verdict: `CHANGES_REQUESTED`

## Closure of the first Gate 5 findings

The two findings in the first code review are technically closed:

- `IdentityAccessSemanticFingerprintSchemaMigration` removes only index and FK
  presentation names. It still compares primary/unique/secondary category,
  index type, ordered columns, FK source/target columns, target table and
  `DELETE`/`UPDATE` rules. New DDL continues to emit deterministic canonical
  names from `IdentityAccessDefinitionSchemaMigration`.
- database preflight now requires exact `utf8mb4`, a safe collation identifier,
  either exact utf8mb4 membership or the approved prefix-less nullable-charset
  UCA alias, and a successful exact-name `_utf8mb4` trial before target DDL.
  The real latin1 case returns redacted `DATABASE_UNAVAILABLE`, exit `69`, with
  zero identity mutation. Focused generated-name and latin1/UCA cases pass.

No request-path `CREATE`/`ALTER`/`DROP`, identity repair, RBAC meaning change or
authorization-outcome change was found. Block/unblock is behind one public
application seam, checks complete compatibility before mutation and retains the
existing policy/audit behavior. The explicit destructive rebuild remains a
separate bootstrap operation.

## Findings

### 1. HIGH — unexpected initial metadata failure escapes the redacted CLI boundary

`bin/fmonitor2-migrate.php:84-88` calls `databaseCollation()` before
`CanonicalMigrationApplication::run()`, but catches only `DatabaseUnavailable`.
An unexpected `mysqli_sql_exception`, driver error or other `Throwable` from
the SCHEMATA/COLLATIONS queries or trial therefore bypasses the application's
catch at `CanonicalMigrationApplication.php:28-31`, reaches PHP's uncaught-error
path and can produce non-redacted stderr/a non-contract exit code.

The approved contract requires unexpected metadata/DDL/driver failures to be
exactly redacted as `MIGRATION_FAILED`, exit `70`. The early preflight is needed
to protect v1-v5 from DDL on an invalid default, but it must have a complete CLI
error boundary: keep `DatabaseUnavailable` mapped to `69` and map every other
`Throwable` to redacted `MIGRATION_FAILED`/`70` before proceeding.

### 2. MEDIUM — the CLI duplicates the migration owner's nine-table catalogue

`bin/fmonitor2-migrate.php:98-104` repeats all nine logical names already owned
by `IdentityAccessDefinitionSchemaMigration::tables()`. This list controls
`reportFromVersion`; drift would silently change `appliedVersions` behavior for
an existing identity family. Consume the public catalogue method instead of
maintaining a second literal family list.

### 3. MEDIUM — new application owners are physically minified and obscure reviewable ownership

`MariaDbIdentityBootstrapApplication.php:16-38` and
`MariaDbUserStatusApplication.php:12-33` compress policy checks, persistence,
transactions, catalog replacement, credential changes and role grants into
multi-operation one-line methods with names such as `$p`, `$s`, `$q` and `$c`.
This is a maintainability and reviewability defect in newly extracted
application owners (possible Mysterious Name, Divergent Change and Duplicated
Code smells), and physical-line compression also prevents the hotspot ratchet
from measuring their real size meaningfully. Format the new owners normally and
extract/namescope helpers where that clarifies ownership, without changing the
characterized RBAC behavior.

## Verification performed

- Full canonical identity/access DB suite: PASS.
- Isolated runtime DDL observer with explicit repository test DB environment:
  PASS.
- `make architecture-check`: PASS, 7 rules; baseline unchanged.
- strict OpenSpec validation: PASS.
- `git diff --check`: PASS.
- relevant production PHP lint: PASS.
- Gate 2 hashes remain
  `1c8e21b0eedf84794349c14fb8bf706b95c616e225a32104ab62b7e21c94dafe`
  and `9a255b2d3d1df6e1a4fb56ab7f63aade58f5dc137637c6ce5525f219cc50919b`.
- The superseding Gate 4 evidence records a fresh full verification with only
  the known DB/E2E baseline and no new identity regression.

## Gate decision

Gate 5 remains unapproved. OpenSpec tasks `4.3` and `5.1` are **not authorized**
for completion, and the slice is **not authorized** for its dedicated local
commit. Correct the three production-only findings without changing approved
tests or behavior, rerun focused/runtime/architecture/strict/full verification,
then obtain another fresh independent Gate 5 review in a new append-only record.
