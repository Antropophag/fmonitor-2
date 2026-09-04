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
