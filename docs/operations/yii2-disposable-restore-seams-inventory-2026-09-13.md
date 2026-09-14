# Yii2 disposable restore — pre-implementation seam inventory

Base: `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a` (merge PR #124), issue #76.

| Boundary | Current test-only seam | Required real boundary | Owner after change |
|---|---|---|---|
| Restore admission | `FMONITOR_STAND_RESTORE_TEST_MODE=1` and `test-*` name | explicit canonical authorization plus exact observed target attestation | `StandRestoreApplication` |
| Restore effects | JSON fixture read from `--fixture-driver` | injected narrow production driver | `StandRestoreApplication` |
| Database | decoded `database.sql` JSON written to `database.json` | MariaDB import, schema/history check and controlled next insert | production driver under application |
| Artifacts | decoded JSON written below temporary sibling | exact persistent volume/root, staging/fsync/rename, bytes and modes | production driver under application |
| Sessions | decoded JSON written to `sessions.json` | canonical session storage and explicit retained/relogin assertion | production driver under application |
| Credentials | absent | private absolute regular-file references, values never persisted/logged | production driver configuration |
| Target identity | fixture booleans/path inode and project prefix | pinned source/image/compose plus observed container/network/DB/volume IDs, repeated before effects | authorization value + driver observer |
| Runtime lifecycle | synthetic `readiness.json` | quiesce, restart pinned services, fresh `/health/live` and `/health/ready` | driver effects; application owns outcome |
| Backup source | `FMONITOR_STAND_BACKUP_TEST_MODE=1` fixture payloads | consistent MariaDB/artifact/session capture under existing backup owner | `StandBackupApplication` adapter |
| Rollback | absent | separate authorized operation and UUID using known-good verified bundle | `StandRestoreApplication` |

`RuntimeRecovery` is deliberately retained. Its current executable responsibilities
are old-format production backup/restore, v22/v23 forward update into v24, schema
v22–v24 compatibility, jobs recovery and the deployed legacy recovery runbook.
This inventory authorizes neither cleanup nor production cutover.

Executable bindings remain: `tests/Runtime/runtime_recovery_001_test.php`,
`tests/Runtime/runtime_recovery_forward_update_001_test.php`,
`tests/Runtime/runtime_jobs_recovery_001_test.php`,
`specs/PRODUCTION-RUNTIME-RESTORE-001.md` and
`docs/operations/runtime-recovery-runbook.md`.
