# Gate 3 test review — LEGACY-CONTROL-ENGINEER-MIGRATION-001

- Reviewer: `issue20-gate3-sol`
- Artifact author: `root`
- Verdict: `CHANGES_REQUESTED`
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T164014Z-2e3f6fd0b9/package.json`
- Candidate source: `f76b0dd3655696cf3a769e1625940043b8e363702cb86ec14d4764eb92cf4d9d`
- Snapshot commit: `0b88adf796c51e24fbe46752684e75644e8326e7`
- Contract: `specs/LEGACY-CONTROL-ENGINEER-MIGRATION-001.md`
- Planner lane/reviews: `CRITICAL`; `gate3`, `final`

## RED evidence

The three package-linked integration-profile records are deterministic intended REDs on the missing public behavior, not setup failures:

- `tests/IdentityAccess/legacy_identity_link_001_test.php`: owner absent (`INTENDED_RED`), exit 255.
- `tests/InstallationProcess/legacy_control_engineer_migration_001_test.php`: migration CLI absent (`INTENDED_RED`), exit 255.
- `tests/Yii2/yii2_legacy_identity_link_001_test.php`: link control absent (`INTENDED_RED`), exit 255.

The tests use the declared IdentityAccess owner, real Yii HTTP, and public CLI process seams against isolated MariaDB. No production implementation was reviewed or written at Gate 3.

## Findings

1. **BLOCKER — acceptance C–L is not completely or sensitively covered.** `tests/InstallationProcess/legacy_control_engineer_migration_001_test.php` covers one ready row, deterministic repeat, successful apply/replay/reconcile, and a single source-drift rejection. It does not exercise: explicit-ID bounds and exclusion of legacy-only objects (C); name/email collision (D); any of the required skipped/conflict reason matrix or ambiguous/corrupt inputs (E); equivalent prior migration versus manual/different current assignment (F); canonical JSON bytes/counts/source and process fingerprints (G); multi-row provenance and atomic owner handoff (H); drift of each authority input and whole-batch no-first-mutation behavior (I); reconcile agreement for all terminal states (J); commit failure, unknown outcome, and concurrent commands (K); or error-path redaction and byte identity of historical process documents/facts (L). Correction: add deterministic MariaDB/CLI cases for every C–L branch, including a multi-row batch and fault/concurrency fixtures, and assert no facts for every rejection.

2. **BLOCKER — acceptance A–B is only partial.** `tests/IdentityAccess/legacy_identity_link_001_test.php` starts from already-active local users and covers neither invite→role→link→activate nor creation of a new local credential without legacy credential access (A). It omits inactive local identity, inactive/missing engineer role, invalid request ID, ambiguous persisted identity state, and append-only superseding correction with mandatory reason (B). `tests/Yii2/yii2_legacy_identity_link_001_test.php` covers authorized success, one unauthorized request, CSRF, escaping, and password hiding, but not inherited method/content bounds or the specified 400/409/422/503 mappings, replay, and activation flow. Correction: extend the owner and real HTTP tests through the existing public invitation/role/activation seams and cover the complete rejection/replay/correction/status matrix with no-fact assertions.

3. **MAJOR — several normative outputs lack independent oracles.** The migration test accepts any 64-hex digest instead of independently deriving the SHA-256 from specified canonical bytes, asserts only four assignment fields instead of the complete provenance/fingerprint/actor/time contract, and checks disclosure only across the happy preview/apply outputs. The identity test checks only the legacy name snapshot and detects forbidden words in stored JSON rather than proving legacy credentials were never read. Correction: derive canonical expected JSON and digest independently from fixtures, assert the full immutable fact payload, instrument/deny credential-column reads, and apply redaction assertions to every rejection/fault/unknown response on stdout and stderr.

## Scope assessment

No test scope creep was found: all three files target the declared identity-link, Yii administration, and preview/apply/reconcile seams. The problem is under-coverage and insufficient sensitivity, not added behavior.
