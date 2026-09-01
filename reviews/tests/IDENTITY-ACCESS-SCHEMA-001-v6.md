# Test rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_test_rereview_20260901v`
- Test author: separately tasked Gate 2 author; this reviewer authored no tests,
  specification, OpenSpec artifact or production code
- Supersedes: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v5.md` after the
  owner-approved UCA-alias amendment and corrected valid-database oracle
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved version
  `0.1`
- Approved amendments:
  `docs/operations/identity-access-gate1-diagnostic-seam-amendment.md` and
  `docs/operations/identity-access-gate1-uca-alias-amendment.md`
- Claimed evidence: `docs/operations/identity-access-schema-red-evidence-v11.md`
- Verdict: `APPROVED`

## Independence and reviewed scope

I reviewed the amended executable specification, the four coherent artifacts
under `openspec/changes/canonicalize-identity-access-schema/`, superseding RED
evidence v11, the current canonical/application tests and prior rereview v5. I
changed no test, specification, OpenSpec artifact or production code. This
append-only review record is my only repository edit.

## Oracle and fixture review

The valid database oracle independently proves all approved preconditions before
target DDL: database charset is exactly `utf8mb4`, the reported collation obeys
the safe identifier grammar, membership is either one exact utf8mb4 row or the
prefix-less UCA alias with `CHARACTER_SET_NAME IS NULL`, and MariaDB successfully
applies the exact reported name to an `_utf8mb4` literal. On the repository-owned
MariaDB 11.4.7 contour I observed the approved alias form:

```text
SCHEMATA:   utf8mb4 / utf8mb4_uca1400_ai_ci
COLLATIONS: uca1400_ai_ci / NULL
trial:      identity-access-collation-trial
```

The generated-name fixture begins with the test-owned literal nine-table DDL and
changes only presentation tokens: explicit secondary/unique index names and FK
constraint symbols are omitted so MariaDB generates them. Its columns, ordinal
types, nullability/defaults, engine, charset/collation, ordered index columns,
uniqueness, FK endpoints and rules therefore remain the section 4 literals.
Before runner invocation the test independently observes at least one generated
index name and one generated FK symbol outside the canonical presentation-name
sets. Populated sentinel rows and non-default counters are captured before the
public call. The reproduced failure occurs only at the expected compatible
result assertion, after those setup checks.

The non-utf8mb4 fixture is a real separate database created explicitly as
`latin1 / latin1_swedish_ci`; the test reads those exact defaults back from
`information_schema.SCHEMATA` before invoking the public runner. It compares the
complete prefixed identity state before and after and requires exact redacted
stdout, empty stderr and exit 69.

## Independent RED reproduction

Against the disposable repository service started with `make test-env-up`:

```text
FMONITOR_IA_RED_CASE=generated-names \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/identity_access_schema_001_test.php
```

The UCA oracle and generated-name assertions passed. The public canonical runner
then returned `SCHEMA_MIGRATION_CONFLICT`, `schemaVersion=6` where the approved
semantic-compatible populated outcome is success with `appliedVersions=[]`.
This is a qualifying name-sensitivity RED, not fixture/setup failure.

```text
FMONITOR_IA_RED_CASE=collation \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/identity_access_schema_001_test.php
```

The valid UCA oracle passed first. The separate latin1 database reached the
public runner and its identity state remained exactly empty, but the observed
result was exit 70 with exact redacted `MIGRATION_FAILED`; the contract requires
exit 69 with exact redacted `DATABASE_UNAVAILABLE`. This is a qualifying
classification RED with zero identity mutation and no diagnostic leakage.

Both randomized databases were removed by test `finally` blocks. I removed the
disposable Compose service, volume and network after reproduction.

## Static checks and sensitivity

- `php -l tests/InstallationProcess/identity_access_schema_001_test.php` passes.
- Focused `git diff --check` passes.
- Canonical test SHA-256 is
  `1c8e21b0eedf84794349c14fb8bf706b95c616e225a32104ab62b7e21c94dafe`,
  matching evidence v11.
- Immutable first-GREEN helper SHA-256 remains
  `9a255b2d3d1df6e1a4fb56ab7f63aade58f5dc137637c6ce5525f219cc50919b`.
- The assertions retain exact public exit/stdout/stderr contracts, zero mutation,
  literal v6 results, generated-name compatibility and independently observable
  UCA/latin1 metadata; they do not derive expectations from production code.
- No production edit or cleanup was required to obtain either RED.

## Gate decision

Gate 3 is `APPROVED`. OpenSpec task `2.4` is authorized for completion. Minimal
production changes may resume only for the two Gate 5 findings demonstrated
here: ignore presentation-only index/FK names in the compatibility fingerprint,
and classify an invalid database default as redacted `DATABASE_UNAVAILABLE`
exit 69 before identity mutation. All other Gate 1 constraints remain in force,
including exact semantic fingerprints, safe-name plus alias-membership plus
utf8mb4 trial checks, redacted CLI output and no RBAC/authorization behavior
change.
