# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — Gate 4 GREEN verification

- Date: `2026-09-04`
- Approved test review: `561c725eb1c65e94d04f3fa0ee1f86e2ef399dd1`
- Production commit: `3191d19`
- Public seam: raw `GET|HEAD /pilot/objects`

## Minimal implementation

`PilotHttpCoordinator` выполняет exact local authorization before CSS, затем
валидирует configured `shlz.css` и при configured UI `pilot.css`, и только после
этого читает local profile/object projection. `MariaDbObjectListReader::read()`
не читает query state, получает canonical `all` projection с bounded SQL limit
`501`, возвращает полный список до `500` и fail-closed при overflow. Старый
`readPage()` не является public HTTP query contract; его filters не выбираются
collection route.

## Focused GREEN

Disposable PHP 8.5 + MariaDB run:

```text
$ php -l app/PilotHttp/PilotHttp.php
No syntax errors detected in app/PilotHttp/PilotHttp.php

$ php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ php tests/InstallationProcess/pilot_object_list_001_test.php
PASS: PILOT-OBJECT-LIST-001 public HTTP collection

$ tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

$ bash tools/verification/run.sh lint
# exit 0, empty output

$ git diff --check
# exit 0, empty output
```

Focused object-list test includes exact GET/HEAD authorization/CSS/list order,
query byte-equivalence, no pagination/classification controls, integrity faults,
complete 500 success, 501 redacted `503 + Retry-After: 60`, read-only snapshots,
foreign decoys and attempt-all cleanup.

## Broader-run classification

`tools/verification/run.sh unit` and `db` were also invoked in the temporary
runner. The owned tests `local_rbac_auth_contract_001_test.php`,
`local_rbac_objects_route_admission_001_test.php`,
`pilot_object_card_001_test.php` and `pilot_object_list_001_test.php` passed.
The stages as a whole remain non-green. Several failures are runner setup gaps
(Node and TCPDF absent, no canonical reset/migrate, container ownership differs
for root-owner probes); the remaining session/UserAccess, pilot auth/E2E/UI and
rapid visual drift coincide with disclosed repository-wide debt. These failures
are not reclassified as object-list success. OpenSpec task `4.1` remains open
until the repository verification contract is run in its canonical environment
and all results are durably classified.

## Canonical full verification receipt

После появления host PHP полный repository contract выполнен через `make
verify` с Docker CLI из Docker Desktop. Его terminal summary:

```text
VERIFY_STAGE test-db-reset PASS
VERIFY_STAGE migrate PASS
VERIFY_STAGE architecture-check PASS
VERIFY_STAGE lint PASS
VERIFY_STAGE unit-test FAIL
VERIFY_STAGE db-test FAIL
VERIFY_STAGE characterization-test FAIL
VERIFY_STAGE e2e-test FAIL
VERIFY_STAGE diff-check PASS
FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

Внутри canonical DB stage оба owned successor/predecessor tests прошли:

```text
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission
PASS: PILOT-OBJECT-LIST-001 public HTTP collection
```

Оставшиеся четыре failed stages соответствуют переданному checkpoint: checklist
UI/session sequential integration и UserAccess, legacy `PILOT-E2E-FLOW-001`,
original/PDF dependency integration, rapid auth-hot-path/visual drift. В этом
change они не исправлялись и не ослаблялись; blocked legacy E2E target не
amended без owner-approved Gate 1.

Independent Gate 5 одобрил exact production commit
`3191d19cd32385280fd52dac4a04d15d6351906c` в
`reviews/code/PILOT-OBJECT-READ-RBAC-FIXTURES-001-v2.md`; review commit
`d2458d4e60c0d276d9d9c5e8d466264efb791433`. Object-list slice завершает Gates
1–5, хотя repository-wide integration остаётся non-green.
