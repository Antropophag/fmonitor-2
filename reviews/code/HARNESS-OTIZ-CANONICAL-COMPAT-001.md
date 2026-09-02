# Code review: HARNESS-OTIZ-CANONICAL-COMPAT-001

- Gate: 5 — independent code review
- Reviewer: separately tasked agent `/root/otiz_canonical_compat_code_review`
- Independence: this reviewer did not author the specification, test, or implementation
- Specification: `specs/HARNESS-OTIZ-CANONICAL-COMPAT-001.md`, version `0.1`
- Approved test review: `reviews/tests/HARNESS-OTIZ-CANONICAL-COMPAT-001.md`
- Implementation: `tests/Verification/harness_otiz_isolation_001_test.php`, `tools/verification/run.sh`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Standards review

No blocking findings.

The change is confined to the verification harness and its canonical characterization runner. It introduces no product behavior, production DDL, persistence ownership, dependency-direction change, or rapid-pilot domain logic. The implementation preserves the existing public isolation seam and transcript shape while making fixture ownership explicit.

The compact style in the pre-existing isolation harness remains less readable than normal production PHP, but this change does not expand that style into product code and does not create a correctness problem for this bounded harness edit.

## Specification review

No blocking findings.

- Pre-existing canonical tables are detected and left owned by the environment; only absent fixture tables are appended to the owned-table list, immediately after their successful creation.
- If fixture installation fails partway through, `finally` receives the already-recorded owned subset and removes it. Cleanup does not depend on successful completion of `hoiNativeDecoys`.
- The unrelated ambient decoy has a per-run random table name, so a pre-existing fixed name or concurrent harness run cannot be mistaken for harness ownership. It is dropped only after its own `CREATE TABLE` succeeded.
- The random table identity is normalized before the public decoy hash is calculated. Two repeated public-seam runs emitted byte-identical transcripts.
- Canonical compatibility verification establishes v4 through public `make migrate`, installs deterministic sentinel rows, invokes the existing harness twice, and proves definitions and rows unchanged after both normal and injected-failure paths.
- Cleanup is child-to-parent for canonical sentinel rows. Existing canonical tables are never dropped or recreated; owned missing ambient tables and verifier-private tables are the only drop targets.
- The exact harness-only `RuntimeException` sentinel is converted to the required single regression verdict. Any other `RuntimeException` is rethrown, so genuine runtime failures are not swallowed.
- Characterization now runs the compatibility verifier, which in turn preserves the complete existing isolation and OTIZ workflow coverage without running the old harness redundantly in the suite.

The deterministic canonical sentinel identities require exclusive use of the configured disposable test database during this verifier. That is consistent with this specification's disposable-database precondition and the current sequential canonical verification contract; it does not authorize parallel processes to share one database.

## Verification evidence

The following passed against the reviewed bytes:

```text
php -l tests/Verification/harness_otiz_isolation_001_test.php
php -l tests/Verification/harness_otiz_canonical_compat_001_test.php
php tests/Verification/harness_otiz_canonical_compat_001_test.php
php tests/Verification/harness_otiz_isolation_001_test.php
tools/verification/run.sh characterization
make --no-print-directory architecture-check
git diff --check
```

The isolation seam was then run twice more and its complete stdout compared byte-for-byte; the transcripts matched. Catalog checks after those runs returned no `otiz_verify_%` or `harness_otiz_isolation_decoy_%` tables.

Observed stable success transcript:

```text
ok - HARNESS-OTIZ-ISOLATION-001 verifier runs twice with transcript sha256=addd46ec1c19987f6e6e9c966ca79a7f440be4569f010604c1f24876b78c65e8
ok - no leaked private tables and ambient decoys sha256=da9f6a8b3e4a06104fe4f0902836871470bc75c191683da63b43c2874a4dd057
```

## Reviewed hashes

```text
fa623b9ddef906f3d621e58f0b1e0015d62acc25e5b9f70a7adee79a9ab284b8  specs/HARNESS-OTIZ-CANONICAL-COMPAT-001.md
ba08716d2fcf7da828b3477ee2b8db2aec2c7cfeb25bbe3243f4288846dff7f9  reviews/tests/HARNESS-OTIZ-CANONICAL-COMPAT-001.md
93e6e1105f99445556a412e3a1b458d07ffea6616480387dccf63fcf69e76ded  tests/Verification/harness_otiz_canonical_compat_001_test.php
c5ae5aefbe8ce031fdea764b57cb7a60d7d9ce5169caf9b4a14f2bcb176afc83  tests/Verification/harness_otiz_isolation_001_test.php
2f1c2bd78363adae47be5b4014cf6da97da784430da43454b09f4009e16a1546  tools/verification/run.sh
dfd21296c5ca0cfe8cf09c43311384a9716d79f5be73b2060916a8e678f0d556  rapid-pilot/verify-otiz-workflow.php
```

Gate 5 is approved for the pinned bytes above.
