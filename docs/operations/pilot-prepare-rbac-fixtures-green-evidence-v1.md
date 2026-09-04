# PILOT-PREPARE-RBAC-FIXTURES-001 — Gate 4 GREEN evidence v1

Дата: `2026-09-04`  
Исполнитель: separately tasked implementation agent `/root/prepare_v15_green`  
Base: `6137d5e83be6a31b00e801efe6acf00b4ce473ce`  
Gate 3: `reviews/tests/PILOT-PREPARE-RBAC-FIXTURES-001-v12.md`, `APPROVED`  
Verdict: **GREEN / fresh independent Gate 5 required**

## Scope

Minimal production correction подключает exact local `assignment_order.prepare`
admission к уже отдельному process-capability gate, выдаёт upload-first read-only
GET/HEAD representation, валидирует server picker records fail closed, добавляет
atomic client parser/selection и получает eligible engineer radio-group из
отдельного MariaDB read owner. POST/CSRF/process command и tests/support не
изменялись. Все HTTP probes сохранили byte-equivalent database/filesystem
snapshots и redacted denial/error responses.

## Exact Gate 3 inputs

```text
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
aeb10393be84329a8fca8de4a75b9731a2786f6cd61effa3678e0aaaa1ec2c9d  tests/InstallationProcess/pilot_prepare_form_001_test.php
5f8cc0d803302d4469c0775e291a8278c692ec85897c5e8bafda4d830174952a  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```

## Final verification

Executed from `/home/antropophag/code/fmonitor-2-prepare-rbac`, started
`2026-09-04T10:09:57+03:00`, completed `2026-09-04T10:10:41+03:00`:

```text
node tests/InstallationProcess/support/pilot_prepare_picker_client.js app/PilotHttp/picker.js
prepare picker client contract: PASS

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local FMONITOR_TEST_DB_PORT=23306 php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local FMONITOR_TEST_DB_PORT=23306 php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local FMONITOR_TEST_DB_PORT=23306 php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local FMONITOR_TEST_DB_PORT=23306 php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

make lint
# exit 0, no output

openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

git diff --check
# exit 0, no output
```

Production hashes before the evidence/task commit:

```text
649d7e67791fc6c3bd511bea99c5ea633829de3ee79ecd825441dca486cf8b1a  app/PilotHttp/ObjectCardView.php
9e7c10384f185099d8da7768f3c4307d2c59600029256c08fcef1a9013a8e4d4  app/PilotHttp/PilotHttp.php
88fb82e2e632e3d065fbeb47128b17f874f459909e556f5600c665b7e89b87e7  app/PilotHttp/PrepareFormView.php
51868858049e889467d4e8c06ccdd71e496655f7d41cc2ee6638ac8d18411bd1  app/PilotHttp/picker.js
6c02c00bf14e2f9559e9f4fd79e538ce9699dc2b14c004212d59bdd8f2b5b17c  app/PilotHttp/MariaDbPrepareEngineerDirectory.php
```

## Known predecessor result

`tests/InstallationProcess/pilot_object_card_001_test.php` returns existing
`Example A broad reader without capability status: expected 200, actual 503`.
The exact failure was reproduced on a clean detached worktree at base
`6137d5e83be6a31b00e801efe6acf00b4ce473ce`; it is not introduced by this
production diff. The temporary worktree was verified clean, removed and
`git worktree prune` was executed.

Gate 4 does not claim Gate 5, repository-wide GREEN or integration readiness.
