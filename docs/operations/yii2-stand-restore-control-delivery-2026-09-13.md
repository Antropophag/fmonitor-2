# Yii2 stand restore control — delivery evidence

Issue #76; base `11b8587372040ab045d1a69faeeb1432ff85e400`. Этот
append-only record не утверждает live deployment, operational drill или cutover.

## Remaining RuntimeRecovery production responsibility

- `tests/Runtime/runtime_recovery_001_test.php` — old-format restore
  `fmonitor-runtime-backup-v1`, которого новый stand bundle protocol не читает.
- `tests/Runtime/runtime_recovery_forward_update_001_test.php` — historical v22/v23 forward migration в current image.
- `tests/Runtime/runtime_jobs_recovery_001_test.php` — jobs recovery с реальными DB/job facts.
- `specs/PRODUCTION-RUNTIME-RESTORE-001.md` — schema v22-v24 compatibility и legacy recovery contract.
- `specs/PRODUCTION-JOBS-RECOVERY-001.md` — production jobs recovery contract.
- `docs/operations/runtime-recovery-runbook.md` — legacy runbook command, ещё не заменённый live Yii2 driver/drill.

Поэтому `RuntimeRecovery` и `bin/fmonitor2-runtime-recovery.php` сохраняются.
Новый `StandRestoreApplication` не зависит от них; удаление возможно только после
отдельного переноса перечисленных responsibilities и executable evidence.
