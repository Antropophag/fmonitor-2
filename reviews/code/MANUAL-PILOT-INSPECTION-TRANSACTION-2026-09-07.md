# Manual-pilot inspection transaction — independent verification

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the reviewed production or test changes.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `8b9b2d7fca669b9e47efb3ac1e097ba978ebe224`.
- Production scope: `app/InspectionEvidence/MariaDbInspectionTransaction.php` and `app/InspectionEvidence/MariaDbInspectionEvidenceEnvironment.php` working-tree diffs against that base.
- Verification extension inspected: `tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php` working-tree diff against that base.
- Verdict: `APPROVED` for the exact bounded production correction below.

## Exact reviewed identities

Working-file SHA-256:

```text
84c726c489a681971caa6f0e714e92f2bb7e5bab73ad0a1476deaa229367ae39  app/InspectionEvidence/MariaDbInspectionTransaction.php
566c75815ea1ad8e9b44915e3b46ee26f3f4f1c49d10e1a0a4e5983bef188aa0  app/InspectionEvidence/MariaDbInspectionEvidenceEnvironment.php
8f115e716291a731f79165fb378260a3fbde34b0b63bc993ccf309cccf44f5d8  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
```

SHA-256 of each exact `git diff --binary 8b9b2d7fca669b9e47efb3ac1e097ba978ebe224 -- <path>` byte stream:

```text
1bb4822d5d1964e0b025019fbe641a77230b823fc6e9288fb653549cbdc7605d  app/InspectionEvidence/MariaDbInspectionTransaction.php.diff
f2888294b9042f56e0776451b72cf5492bd43089a8a0656cda09c97380faa7dc  app/InspectionEvidence/MariaDbInspectionEvidenceEnvironment.php.diff
110a921a00ca8ae7a181b7b8e327c010f20c63503b2b9a0c3b5e9573b5e7a9cd  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php.diff
```

Combined production two-file `git diff --binary` SHA-256:

```text
79d124ea6fbfbca83e94e18df5e40733bb8bc56d52aca230de0fe4dc20ec03f6
```

Combined production-plus-test `git diff --binary` SHA-256:

```text
ddfd6cbcd258f8983a7245b1c373a49f0716036cf2445d74e64ef8077f86ffe4
```

## Findings

No blocking findings remain in the exact reviewed correction.

The originally presented lock/read-first implementation corrected the existing-row PROCESSLIST regression and made the receipt clock lazy, but it left a material missing-row race: two first writers could both acquire compatible missing-key gap locks and then deadlock while attempting `INSERT IGNORE`. I raised that as blocking before approval. The final reviewed source closes it.

The final transaction performs a nonlocking revision existence probe while the connection is still in autocommit mode. That probe selects only the lock strategy; it is not treated as authoritative state. If initialization may be needed, the transaction locks the always-existing installation-case row before touching the absent revision key. Competing first writers therefore serialize at the case row instead of acquiring competing revision gap locks. This lock order also agrees with the existing legacy checklist path, which locks the case before initializing/locking its revision.

After any conditional case lock, the transaction obtains the authoritative revision with `SELECT ... FOR UPDATE`. It initializes revision zero only if that locking read still finds no row, then repeats the same locking read. A follower waiting on first initialization sees the committed revision and returns `STALE_REVISION(1)` rather than attempting a competing insert. Existing-row writers skip the case lock and continue to queue directly on the revision row, preserving the original concurrency behavior and exact PROCESSLIST sensitivity.

The pre-transaction probe is necessary for replay visibility under MariaDB's default repeatable-read isolation. It does not establish a transaction snapshot before a lock wait. The later plain operation lookup therefore observes a preceding committed operation after the authoritative lock is acquired, preserving exact duplicate/conflict handling.

The environment now passes receipt time as a callable. The transaction invokes it only when it actually creates a missing revision row. An existing-row replay or payload conflict does not consume the application clock; accepted first receipts still obtain their authoritative server time through the established application path. Missing installation cases return with no initialized revision and are rolled back by the existing command flow, so no ghost revision is committed.

The extended public-seam MariaDB test retains the original pre-existing-revision scenario and adds a missing-revision mode. Both modes publish two distinct worker connections, prove a sustained exact lock wait through PROCESSLIST, then require exactly one `ACCEPTED(1)` and one `STALE_REVISION(1)`, one immutable winner, no loser evidence, exact replay/conflict behavior, no replay clock read, catalogue/decoy preservation, bounded worker cleanup, and isolated database/user cleanup. The missing mode locks the case row at the coordinator, so it would fail if workers reverted to locking or inserting the missing revision key before the case lock.

No authorization, append-only evidence shape, revision result, audit payload, application seam, stand data, or protected E2E expectation changes in this patch.

## Independent verification

```text
PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
PASS: INSPECTION-ITEM-COMPLETE-001 existing and missing revision concurrency

PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/InstallationProcess/manual_original_execution_smoke_test.php
PASS manual original application/reapplication/opening/template preservation smoke

php -l app/InspectionEvidence/MariaDbInspectionTransaction.php
No syntax errors detected in app/InspectionEvidence/MariaDbInspectionTransaction.php

php -l app/InspectionEvidence/MariaDbInspectionEvidenceEnvironment.php
No syntax errors detected in app/InspectionEvidence/MariaDbInspectionEvidenceEnvironment.php

php -l tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php

git diff --check -- app/InspectionEvidence/MariaDbInspectionTransaction.php app/InspectionEvidence/MariaDbInspectionEvidenceEnvironment.php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
PASS
```

Both database tests used isolated fixtures and completed cleanup. No global database reset, stand or persistent-data mutation, remote action, or Bitrix action was performed. The manual original/opening smoke is adjacent preservation evidence; it does not itself exercise the inspection transaction. Other working-tree changes are outside this review. This approval does not assert full `VERIFY_OK`, deployment readiness, or completion of the continuing manual-pilot goal.
