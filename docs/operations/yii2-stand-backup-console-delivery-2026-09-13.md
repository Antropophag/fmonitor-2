# Yii2 stand backup console — delivery record

- Scope: #76, harness follow-up #115; stacked on PR #113 head `404aa6858b8fdd61ac0e8151f09a67000a387ead`.
- Public seam: `php bin/yii stand-backup/create|verify`; PHP owner `app/RuntimeRestore`.
- Gate 3: APPROVED, `reviews/tests/YII2-STAND-BACKUP-CONSOLE-001.md`.
- Gate 5: APPROVED, `reviews/code/YII2-STAND-BACKUP-CONSOLE-001.md`.
- Reviewed candidate: `59c95d2bc7935c038ca27d5cecdda3e2490c6b5b8f8c29bb53754304788f9b4a`; executable `c876e11d8633b1ecc1e54d7402942e17dc06f959c77d318db3f289442f8a0944`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T142822Z-a24aaeaed1/package.json`.
- Focused evidence: eight mapped commands GREEN; complete adjacent plan GREEN as recorded by executor. Full local suite was not run per owner policy.
- Python is used only by external black-box tests/harness; no Python production seam exists.
- PR/CI/deployment: UNKNOWN until publication checks below are appended. Live backup and deployment were not performed.

## CI history

- Commit `fbec3d5257c270f57c72f11b7b2d1fc40a1416a5`, workflow_dispatch `34762933379`: FAILED. Direct failure only in e2e because the Python observer attempted `read_bytes()` on a chmod(0) fixture under Linux; both integration shards, unit, fast and governance succeeded, verify failed aggregately, quality-results was skipped.
- Root corrected the observer to compare unreadable regular files by lstat metadata without reading bytes. Independent correction Gate 3 and Gate 5: APPROVED. A new exact-source CI is required; the failed run remains history.
