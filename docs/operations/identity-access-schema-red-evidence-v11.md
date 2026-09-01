# IDENTITY-ACCESS-SCHEMA-001 superseding RED evidence v11

- Date: `2026-09-01`
- Specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`, Gate 1 approved
- Supersedes: `identity-access-schema-red-evidence-v10.md` for the UCA-alias setup oracle
- Production changes in this iteration: none
- Result: `QUALIFYING RED / READY FOR FRESH INDEPENDENT TEST REVIEW`

## Owner-approved UCA-alias oracle

The valid database fixture now accepts only an exact `utf8mb4` collation row or
the owner-approved MariaDB UCA alias formed by removing the `utf8mb4_` prefix.
The alias row must have nullable character-set metadata. Before any target DDL,
the test also applies the exact reported default to an `_utf8mb4` literal in a
read-only `SELECT`. The safe identifier grammar remains mandatory.

MariaDB 11.4.7 independently reported:

```text
database charset/default: utf8mb4  utf8mb4_uca1400_ai_ci
COLLATIONS alias row:     uca1400_ai_ci  NULL
trial result:             identity-access-collation-trial
```

This normalization does not accept a non-utf8mb4 database and does not relax
the exact collation fingerprint of an existing identity/access table.

## Reproduced qualifying RED

With disposable `mariadb:11.4.7-noble` healthy:

```text
$ FMONITOR_IA_RED_CASE=generated-names \
    FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/identity_access_schema_001_test.php
Expected: ok=true, schemaVersion=6, appliedVersions=[]
Actual:   SCHEMA_MIGRATION_CONFLICT, schemaVersion=6
```

The valid UCA oracle and the prior assertions proving generated non-canonical
index/FK symbols passed. Failure therefore reaches the public canonical runner
and remains the intended semantic-name compatibility RED, not setup failure.

```text
$ FMONITOR_IA_RED_CASE=collation \
    FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/identity_access_schema_001_test.php
Expected: exit 69, DATABASE_UNAVAILABLE, unchanged empty identity state
Actual:   exit 70, MIGRATION_FAILED, unchanged empty identity state
```

The valid UCA oracle passed first. The separate real `latin1` database metadata
and exact zero identity mutation assertions also passed, so this remains the
intended missing database-default preflight/classification RED.

## Checks and gate state

- `php -l tests/InstallationProcess/identity_access_schema_001_test.php` passed.
- Focused `git diff --check` passed.
- Canonical test SHA-256:
  `1c8e21b0eedf84794349c14fb8bf706b95c616e225a32104ab62b7e21c94dafe`.
- Immutable first-GREEN helper SHA-256 remains
  `9a255b2d3d1df6e1a4fb56ab7f63aade58f5dc137637c6ce5525f219cc50919b`.

OpenSpec tasks `2.1` and `2.2` remain checked because the amended test scope is
complete and both qualifying RED contours reach the public runner. Task `2.4`
remains unchecked pending a fresh separately tasked independent test review.
Production GREEN must not resume before that review records `APPROVED` in a new
append-only record.
