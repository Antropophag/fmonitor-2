# Native workforce scheduler wiring — focused pilot evidence

The opt-in Compose worker now keeps the existing hourly minute-07 cadence while
calling `bin/fmonitor2-sync-workforce.php`, the native synchronization owner. It
resolves one ready active pilot manifest and passes its validated process prefix.

The existing private webhook JSON remains outside the repository. The native CLI
extracts its HTTPS origin, webhook user, token and sorted department identifiers,
stages only the token in a current-owner single-link `0600` temporary file, and
removes that file after success or failure. Output remains the native fixed safe
summary; credentials and response rows are not printed.

Focused checks are synthetic and do not activate the Compose profile, call Bitrix,
or mutate the pilot database. OpenSpec task 2.5 remains pending until an authorized
successful bounded run and restart are observed on the stand.

Focused results at the working-tree source:

- `php tests/InstallationProcess/workforce_worker_cli_manual_pilot_test.php` — PASS;
  the disposable database endpoint rejects before `fetch()`, so no HTTP request occurs.
- `php tests/InstallationProcess/bitrix_workforce_delivery_001_test.php` — 12/12 PASS
  against task-owned synthetic HTTPS fixtures only.
- PHP lint for the helper, CLI and focused test, plus `sh -n` — PASS.
- `docker compose config --quiet` with a synthetic unused bootstrap interpolation — PASS.
- `make architecture-check` — FAIL on concurrent, unrelated changes in
  `rapid-pilot/ObjectQueue.php` and `app/PilotHttp/PilotE2ECoordinator.php`; no
  scheduler-owned path was reported.
- `openspec validate deliver-manual-pilot-flow --strict` — FAIL on the pre-existing
  placement of task 2.8 under section 3; the scheduler task remains honestly pending.

No live worker, Compose profile, pilot database or Bitrix endpoint was started.
