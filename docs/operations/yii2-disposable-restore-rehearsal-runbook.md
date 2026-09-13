# Yii2 disposable restore rehearsal runbook

Status: implementation reviewed; destructive execution requires a separate owner
action authorization. This runbook does not authorize production deployment.

## Preconditions

- Exact reviewed source/image and `deploy/runtime/compose.yaml` are pinned.
- The target is newly created and disposable. No production identity, credential,
  database, volume, network or port may be reused.
- Full logs, dumps, cookies and credentials use an external evidence root.
- `python3 tools/delivery/harness.py state` reports the expected worktree/source.
- Gate 3 and Gate 5 have explicit `APPROVED` records.

## Minimum owner action input

The owner supplies two separate canonical outer package paths:

1. Roundtrip package, exported only as
   `FMONITOR_DISPOSABLE_REHEARSAL_AUTHORIZATION`. It contains exact absolute paths
   to distinct backup/restore inner authorizations, distinct UUIDs, manifest and
   expected bundle digest.
2. Rollback package, exported only as
   `FMONITOR_DISPOSABLE_ROLLBACK_AUTHORIZATION`. It contains an external retained
   candidate-failure record, independently verified known-good bundle digest,
   separate rollback authorization and new rollback UUID.

Every inner authorization is canonical, mode 0600, unexpired, explicitly scoped
to `disposable-stand-backup` or `disposable-stand-restore`, and binds source/image,
compose path, project/database, database user, health/golden URLs, external evidence
root, credential-file reference, observed container/network/volume identities and
exact expected DB/history/schema/next-id/jobs/outbox/lease/recovery/artifact/session
facts. Credential values are never embedded in these packages.

The owner must explicitly state that these exact package digests and observed
identities are authorized for destructive use. Project/database names alone are
insufficient.

## Execution after authorization

```sh
php tools/delivery/yii2-disposable-restore-rehearsal.php roundtrip \
  --authorization="$FMONITOR_DISPOSABLE_REHEARSAL_AUTHORIZATION" \
  --interactive=0

php tools/delivery/yii2-disposable-restore-rehearsal.php rollback \
  --authorization="$FMONITOR_DISPOSABLE_ROLLBACK_AUTHORIZATION" \
  --interactive=0
```

The runner only orchestrates public Yii seams. `StandRestoreApplication` and its
validated driver own stop, database recreation/import, volume replacement,
restart, readiness and confirmed outcome. Independent tests then query MariaDB,
mount volumes read-only, call health/golden endpoints and inspect retained evidence.

Any identity drift, non-zero subprocess, incomplete exact evidence, failed health
or golden assertion is failure. Possible partial effects are `OUTCOME_UNKNOWN`;
the lease stays retained and no confirmed pointer is published. Rollback is never
an implicit retry: it uses its separate package and operation UUID.

## Explicit exclusions

- No production credentials or production target.
- No production deployment/cutover.
- No deletion of `RuntimeRecovery`.
- No full local `make test`; exact-source full verification runs once in CI.
