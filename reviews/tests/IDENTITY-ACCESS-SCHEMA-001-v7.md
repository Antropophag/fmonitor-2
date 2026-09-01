# Test rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_test_rereview_20260901z`
- Independence: reviewer authored neither the tests, specification, OpenSpec
  artifacts nor production implementation
- Supersedes: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v6.md` only for the
  Gate 5 preflight-boundary restart recorded in RED evidence v12
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved `v0.1`
- Approved amendments:
  `docs/operations/identity-access-gate1-diagnostic-seam-amendment.md` and
  `docs/operations/identity-access-gate1-uca-alias-amendment.md`
- Claimed evidence: `docs/operations/identity-access-schema-red-evidence-v12.md`
- Gate 5 source finding: `reviews/code/IDENTITY-ACCESS-SCHEMA-001-v2.md`
- Verdict: `APPROVED`

## Independence and reviewed scope

I reviewed the approved executable contract and amendments, current OpenSpec
artifacts, immutable earlier Gate 2 contracts, code rereview v2, superseding RED
evidence v12, the new preflight test and the current public
`CanonicalMigrationApplication::run()` seam. I changed no test, specification,
OpenSpec artifact or production code. This append-only review record is my only
repository edit.

## Seam and oracle review

The named `databasePreflight` callback is a coherent addition to the public
canonical application seam. It specifies orchestration, not the implementation
of database inspection: the test does not know which metadata queries, alias
normalization or trial operation production uses. It requires only that the
initial database/default-collation preflight execute inside the same redacted
error boundary as migrations and before the first migration invocation. That is
the observable boundary required by the approved exit-69/exit-70 contract and
the HIGH Gate 5 finding.

The two expectations are independent literals:

- a test-owned unexpected `RuntimeException` returns exit `70`, exact stdout
  `{"ok":false,"reason":"MIGRATION_FAILED"}` plus newline, and empty stderr;
- a test-owned `DatabaseUnavailable` returns exit `69`, exact stdout
  `{"ok":false,"reason":"DATABASE_UNAVAILABLE"}` plus newline, and empty
  stderr.

Each scenario has its own migration invocation counter and mutation-marker
list. The only supplied migration would increment both before returning. Both
must remain exactly zero/empty, so the test is sensitive to preflight ordering
and short-circuiting. The preflight callbacks themselves only throw and perform
no database mutation. No CLI injection, production catalogue, migration
implementation, SQL text or internal metadata format is asserted.

## Independent RED reproduction

With the repository MariaDB test service reachable, I ran:

```text
php tests/InstallationProcess/identity_access_schema_001_preflight_application_red.php
```

Both named-argument calls currently escape as `Error`; migration invocations
remain `0` and mutation markers remain `[]`. PHP rejects the absent
`databasePreflight` parameter before entering the current application. This is
the intended missing public callback boundary, not database setup failure and
not a failure in a migration fixture.

## Static and immutability checks

- PHP syntax check passes for the new RED test.
- Focused `git diff --check` passes.
- Existing canonical test SHA-256 remains
  `1c8e21b0eedf84794349c14fb8bf706b95c616e225a32104ab62b7e21c94dafe`.
- Existing immutable first-GREEN helper SHA-256 remains
  `9a255b2d3d1df6e1a4fb56ab7f63aade58f5dc137637c6ce5525f219cc50919b`.
- New preflight RED SHA-256 is
  `48c74ac4a18b9c8fd71618e79291822c6f7ea7dedcecb16472e12f23b8ce68ea`,
  matching evidence v12.
- The current RED preserves exact redaction, exit classification, empty stderr,
  zero migration invocation and zero test-owned mutation markers.

## Gate decision

Gate 3 is `APPROVED`. OpenSpec task `2.4` is authorized for completion. Minimal
production work may resume to add the named preflight callback to the public
canonical application error boundary, with `DatabaseUnavailable` mapped to
exact redacted exit `69`, every other `Throwable` mapped to exact redacted exit
`70`, and no migration invoked after either failure.

The same production-only iteration is authorized to close the remaining code
rereview v2 findings: eliminate the CLI's duplicate identity-family catalogue
by consuming its public owner, and format/deepen the newly extracted bootstrap
and user-status application owners. These changes must not alter any approved
test expectation, RBAC/authorization behavior, identity data, schema
fingerprint or migration result contract. Fresh verification and a fresh
independent Gate 5 review remain required before commit.
