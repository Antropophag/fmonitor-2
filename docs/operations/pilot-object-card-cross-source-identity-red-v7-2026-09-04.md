# PILOT-OBJECT-CARD-001 cross-source identity RED v7 — 2026-09-04

## Scope and contract

- Gate: Gate 2 tests/evidence only.
- Starting head: `3c08131578a1d7de002a86549f5949c0770fdd63`.
- Public seam: configured raw HTTP `GET|HEAD /pilot/objects/4512`.
- Production/spec/support changes: none.

`PILOT-OBJECT-CARD-001 v0.2` admits an exact active legacy user with an active
legacy role. Local identity/access rows do not replace that inherited gate and
neither `objects.read` nor a process capability is required to read a card.

The test adds four explicit cross-source actors:

- `26`: local active user/role with zero permissions/capabilities, but inactive
  exact legacy user — exact `403` before card read;
- `27`: local active user/role with zero permissions/capabilities, but exact
  legacy user belongs to inactive legacy role — exact `403` before card read;
- `28`: exact active legacy user/role, corresponding local row inactive — full
  card `200`, because local status is not an additional card admission gate;
- `29`: exact active legacy user/role and no local row — the same full card
  `200` without an upload-first action.

Each outcome uses GET/HEAD parity. Exact `403` plaintext forbids shell/object
leakage. Successful cases require the full independently fixed Example A
content, shared shell, exact breadcrumb/navigation/stylesheets/scripts and
closed href/action allowlist. Actor 25's permissionless success remains
unchanged.

The full before/after database fingerprint and filesystem guards cover these
requests, and every added server is attempt-safely stopped in local and outer
`finally` blocks.

## Read-scope sensitivity

A separate identity-only SQL principal can read only the exact legacy identity
columns and minimal local identity/role-assignment columns. It cannot read
unrelated local phone/source fields, permission rows, object/process tables or
artifacts. Actor 26 must still receive exact `403`. Thus an exact identity
lookup can deny before card read without a broad user-directory scan or an
object read. No new production privilege or product decision is introduced.

## Genuine RED

At `2026-09-04T12:37:05+03:00`:

```text
$ php -l tests/InstallationProcess/pilot_object_card_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_card_001_test.php
$ git diff --check
PASS (no output)
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/pilot_object_card_001_test.php
TestFailure: legacy inactive despite active local identity denied before card read status
Expected: 403
Actual: 200
... pilot_object_card_001_test.php(599): pocError()
exit 255
```

All prior fixture, full Example A, shared-shell, exact link, CSP/script and
read-only checks pass before this new public boundary assertion. Current
production therefore demonstrably admits an inactive legacy identity solely
because its local row is active. This is the intended authorization RED, not a
setup failure.

Post-failure inspection found no `t_poc_*` database, `poc_*`/`pocu_*`/`poci_*`
SQL user, `.test-artifacts/poc-*` entry or owned PHP server process. The host
does not provide a `mariadb` CLI, so the database/user inspection used an
independent PHP `mysqli` query and returned no rows.

## Exact candidate bytes

```text
3d0d01da364cb9575793bf43e15389371ac73ffb633b34b79963c1191e2d065d  tests/InstallationProcess/pilot_object_card_001_test.php
a3fd80de9e9d4fda16b04f9ca545a7ede33ca1b85390120a3d977790dafc3d68  tests/InstallationProcess/pilot_ui_shell_001_test.php
f18b804204c838965daf70c3aa81b3e2b609db67c5df58e658302aaf321c88d8  reviews/tests/PILOT-OBJECT-CARD-001-spec-axis-integration-v6.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
065d4bddd974cf8acec1becff5e85fda23adef728ef44b3b6f6f9a45a7ae647d  app/PilotHttp/ObjectCardView.php
39a27d0d686b0c548592d33acf9ce539d1b0a5909b38d059c9a93faba7ba816f  app/PilotHttp/PilotHttp.php
```

Fresh independent Gate 3 review is required before Gate 4. No GREEN or Gate 5
claim is made.
