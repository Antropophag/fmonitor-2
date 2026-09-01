# Test rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_test_rereview_20260901t`
- Test authors: separately tasked Gate 5 return-path authors; this reviewer authored no tests, specification or production code
- Supersedes: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v4.md` for the changed Gate 5 return-path tests
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved version `0.1`
- Approved amendment: `docs/operations/identity-access-gate1-diagnostic-seam-amendment.md`
- Claimed evidence: `docs/operations/identity-access-schema-red-evidence-v10.md`
- Verdict: `CHANGES_REQUESTED`

## Independence and reviewed scope

I reviewed the executable specification and amendment, all four artifacts under
`openspec/changes/canonicalize-identity-access-schema/`, the Gate 5 finding in
`reviews/code/IDENTITY-ACCESS-SCHEMA-001.md`, superseding RED evidence v10,
the changed canonical/application test and prior approved Gate 3 record v4. I
changed no code, test, specification or OpenSpec task.

## Blocking finding

The two claimed RED cases do not reach their target observations on the
repository-owned MariaDB 11.4.7 test contour. Both stop at the new valid-fixture
precondition:

```text
Valid fixture collation exists for utf8mb4 in information_schema.COLLATIONS.
Expected: 1
Actual: 0
```

`CREATE DATABASE ... DEFAULT CHARSET=utf8mb4` selects
`utf8mb4_uca1400_ai_ci` in this contour. MariaDB reports that exact database
default in `information_schema.SCHEMATA`, but does not expose a row named
`utf8mb4_uca1400_ai_ci` in `information_schema.COLLATIONS`; its UCA 1400
collations are exposed there under unprefixed names such as `uca1400_ai_ci`
with a null character-set field. Consequently the test's exact-name membership
query returns zero even though the database default is a real, working utf8mb4
collation selected by MariaDB itself.

This is a fixture/oracle defect, not a qualifying product RED. It blocks both
the generated-name case and the latin1 case before either public runner call.
Therefore I could not independently verify from the current test that:

- the genuinely generated `INDEX_NAME` and `CONSTRAINT_NAME` values differ
  before the runner while the semantic structure and populated state are exact;
- the current name-sensitive classifier returns the claimed conflict;
- the real latin1 database reaches the public runner, remains unmutated and
  returns the claimed current exit 70 instead of required exit 69.

The v10 impossibility note is sound only for fabricating invalid or nonexistent
server metadata: MariaDB rejects such database defaults and
`information_schema` is not mutable. It does not resolve this separate valid
fixture problem. The valid-path assertion must account for MariaDB's UCA alias
representation, or the fixture must choose a concrete utf8mb4 database default
whose exact row is independently visible in `COLLATIONS`. The production
contract still needs an independently meaningful charset, safe-name grammar and
collation-validity check; the test must not merely restate production logic.

## Reproduction and cleanup

Commands run against the repository-owned disposable service:

```text
make test-env-up
FMONITOR_IA_RED_CASE=generated-names \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/identity_access_schema_001_test.php

FMONITOR_IA_RED_CASE=collation \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/identity_access_schema_001_test.php
```

Both executions fail at line 250 with the setup assertion above. The randomized
database is removed by the test's `finally`; the disposable Compose service was
then removed with volumes and orphans.

## Gate decision

Gate 3 return-path rereview is `CHANGES_REQUESTED`. OpenSpec task `2.4` is not
authorized for completion and Gate 4 production correction must not resume.
Correct the valid-collation fixture/oracle without weakening the approved
preflight contract, reproduce both sensitive RED cases through the public
runner, and obtain another fresh independent append-only Gate 3 review.
