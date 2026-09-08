# Manual-pilot OTiZ current progress — independent review

- Reviewer: Codex agent `/root/auth_review`, independently tasked; did not author the production or test changes.
- Review date: `2026-09-07` (`Europe/Moscow`).
- Review base / current `HEAD`: `c02f1a23121058fb0046e4b8fec859c5754ecb6d`.
- Scope: `app/PilotHttp/MariaDbOtizCurrentProgress.php`, `rapid-pilot/Otiz.php`, and `tests/InstallationProcess/otiz_current_progress_001_test.php`.
- Test verdict: `APPROVED`.
- Code verdict: `APPROVED`.

## Exact reviewed identities

```text
f9d1c783616efa2a480601892552129e9760e83437b3303ad94c411ffa493942  app/PilotHttp/MariaDbOtizCurrentProgress.php
2ee6e7385b609760e90167b2efa98d02e6741bfdfc746a328f9a074ab7abe253  rapid-pilot/Otiz.php
2c29fa7c38437ccccb65b42433eb2e57087ec79b4bc5add13207035d9ab10db9  tests/InstallationProcess/otiz_current_progress_001_test.php
4f493a7be3fa88cfafaafc58775ee758068379ab1b9f93b438d8a756afef28cc  docs/operations/otiz-current-progress-feedback-2026-09-07.md
```

Exact diff SHA-256:

```text
2770cb9b553dd8f794f164c6c626e478510180e484c91ee5573c578d1b53b066  MariaDbOtizCurrentProgress.php new-file diff
f2aedd1da7b2fe0b73d52924d50f4243b8e3db01654de01a5b4a405587cd3458  Otiz.php diff
ecd81be74d2145f54935ea8a7c6cf9d39ca8317940e7a669dd1119365752625b  otiz_current_progress_001_test.php new-file diff
```

The architecture baseline remained byte-identical:

```text
9a67b19242bc1609d00c8a9e923246096b9730a89c988af6390ceb6541b5a6c8  tools/architecture/baseline.json
```

## Findings

No blocking findings.

`MariaDbOtizCurrentProgress` is a read-only batch projection. It normalizes the
requested positive object IDs, resolves their installation cases in one query,
uses the existing native weighted-checklist reader for the whole case set, then
reads completion facts and latest server-side activity in two fixed queries. It
does not issue DDL or DML, and `rapid-pilot/Otiz.php` gains no SQL for this feature.

The progress rule matches the manual-pilot contract: accepted checklist weights
are capped at 85%; both `pto_act` and `declaration` facts raise display progress to
exactly 100%. PTO without declaration remains 85%. Completion is not inferred from
`process_state`, a snapshot calculation state, a payment, or a legacy field. The
display date comes from the latest current server-received checklist activity or
completion-fact recording time; the prior snapshot `progress_fact_date` remains the
fallback only when no current native projection exists.

The OTiZ adapter writes the projection into new `display_progress_bp` and
`display_progress_date` row fields used only by the progress column. It does not
overwrite `current_progress_bp`, `progress_fact_date`, `accrued_cents`, `pool_cents`,
`calculation_state`, `inputs_json`, eligibility, payment closures, penalties, fund,
earned, paid, retained balance, or snapshot/export data. Existing financial totals
and state transitions therefore keep their prior sources and meaning.

The technical state `planned` remains unchanged for filtering and calculations.
Its visible label is now `Расчёт не подготовлен` in the status and state selector,
which truthfully describes the absence of an OTiZ calculation alongside current
progress up to 100%. It no longer produces the contradictory `Работы не начаты`
claim. `ready`, `blocked`, `no_new_amount`, `completed`, and `missing_norm` retain
their existing labels and logic.

The focused test independently fixes expected values for partial 4%, full native
checklist plus PTO at 85%, and checklist plus both documentary facts at 100%, with
dates from current evidence. It also protects the corrected planned-state copy.
The source diff and read model establish that no calculation/payment path consumes
the display-only values.

## Independent verification

```text
PATH=/opt/homebrew/bin:$PATH FMONITOR_TEST_DB_ADMIN_PASSWORD=<configured test secret> php tests/InstallationProcess/otiz_current_progress_001_test.php
otiz_current_progress_001_test: PASS

php -l app/PilotHttp/MariaDbOtizCurrentProgress.php
php -l rapid-pilot/Otiz.php
php -l tests/InstallationProcess/otiz_current_progress_001_test.php
git diff --check -- reviewed files
PASS

PATH=/opt/homebrew/bin:$PATH php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

PATH=/opt/homebrew/bin:$PATH tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

php rapid-pilot/verify-visual-contract.php
php rapid-pilot/verify-focus-contract.php
PASS

/Users/antropophag/.agents/skills/impeccable/scripts/impeccable detect --json rapid-pilot/Otiz.php app/PilotHttp/MariaDbOtizCurrentProgress.php
[]
```

The host invocation of `rapid-pilot/verify-otiz-workflow.php` remains the documented
Compose-DNS setup failure and is not counted as GREEN. This review performed no
calculation, payment, production/stand data mutation, implementation/test edit,
deployment, remote action, or Bitrix action. The verdict is bounded to the exact
artifacts above and does not claim full `VERIFY_OK` or production readiness.
