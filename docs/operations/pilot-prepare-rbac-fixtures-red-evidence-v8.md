# PILOT-PREPARE-RBAC-FIXTURES-001 — replacement Gate 2 RED v8

Дата: 2026-09-04  
Автор RED: independently tasked agent `/root/prepare_v15_red`  
Base commit: `2bc17cc7dcb31984ebecd13765a81f32b69022d5`  
Verdict: **QUALIFYING RED / fresh Gate 3 required**

## Approved basis

Владелец разрешил Gate 2 для exact prepare upload-first v15 package в
`docs/operations/pilot-prepare-rbac-v15-exact-hash-owner-approval-2026-09-04.md`.
Повторная проверка перед test edit подтвердила approved hashes, включая:

```text
7d78aa830265dff3eb933b7fe3fc790c99be3ccbb2cce4cd2134f7bc803e9039  specs/PILOT-PREPARE-FORM-001.md
a7bfd245506e84afbfcd3b0fa5e0b35217349854ba85b583f5a0087f3ca9f226  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d5dc4f998ccc6d3c241eb45f7d481f261a33c16ffd671c7dafa927eafc3d7977  specs/PILOT-UI-SHELL-001.md
7ee7b9b6cff70f4a92e8a36bed029853ef4954868e4c370541c4e898658358bd  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
fd69197956244097fa6acbe64dc2f5a14ab01a14e8f4e80aaa9d02ab710f8c9b  openspec/changes/pilot-prepare-rbac-fixtures/design.md
0c87ed39e3454e87339e606b3c1d4202538cd0d46534a590e69739cf8d19087a  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
c70299b78cc2a8698e7ca4d1eca381967ab0b11e949f2e2b8cb99ea7dcdb8576  docs/operations/pilot-prepare-rbac-fixtures-gate1-rereview-v15.md
```

Current tasks hash differs from the approved pre-approval hash only because
task 1.6 was durably checked by the owner-approval commit. Task 2.1 was already
historically checked and was not edited or used as proof of this replacement
RED. Task 2.2 remains open.

## Replacement verifier

The v7 public HTTP verifier retains the complete local/process one-sided grant,
inactive/near-match chain, committed revoke, unsupported-method, identity,
object/state, DB/local-fault, renderer-spy, read-only snapshot and cleanup
matrix. The replacement changes only the superseded v0.1 presentation oracle:

- exact upload-first heading, intro, breadcrumb and card-link copy;
- exact normalized inert six-attribute installer records in deterministic order,
  with six-digit display tab IDs and zero initial hidden command inputs;
- fail-closed hidden picker controls, labelled search/live regions and visible
  no-JS fallback;
- engineer radio/explicit confirmation inheritance, external same-origin
  `picker.js`, exact prepare CSP and absence of upload command controls;
- v0.2 empty-installer and neutral cancel text.

No production or test-support file was changed.

## Reproduction

Executed at `2026-09-04T09:23:40+03:00` through the canonical raw HTTP seam:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

PHP Fatal error: Uncaught TestFailure: two inert installer records
Expected: 2
Actual: 3
```

The process exited non-zero at `2026-09-04T09:23:43+03:00`. Before this
assertion the complete retained v7 authorization/admission matrix ran, the
canonical factory decorator counters proved expected routing, every guarded
request preserved database/filesystem snapshots, and cleanup completed. MariaDB
and the PHP HTTP process were healthy.

This is intended missing product behavior, not setup or predecessor failure:
the owner-approved v0.2 oracle permits exactly the two eligible normalized
records (`1042`, `2088`), while the current response also exposes the
not-yet-employed-at-business-date fixture `3099`. The failing assertion is at
the public successful GET representation after both admission gates. Later
upload-first assertions remain intentionally unweakened for minimal GREEN.

Syntax and hygiene checks at the same working tree were GREEN:

```text
php -l tests/InstallationProcess/pilot_prepare_form_001_test.php
php -l tests/Support/PrepareRendererInvocationSpy.php
php -l tests/Support/pilot_prepare_renderer_spy_router.php
git diff --check -- tests/InstallationProcess/pilot_prepare_form_001_test.php \
  tests/Support/PrepareRendererInvocationSpy.php \
  tests/Support/pilot_prepare_renderer_spy_router.php
```

## Exact Gate 2 hashes

```text
1fa9dc9bd7a46ccaf9380745bfae1b420e0eff5195cbc603d4a4252ca687e792  tests/InstallationProcess/pilot_prepare_form_001_test.php
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
7bef82320c08b1f21f3316a3f5872f2c74e7cfc8471a0fa9ff95b5329f9521c6  tests/Support/pilot_prepare_renderer_spy_router.php
```

Any verifier/support edit after these hashes restarts Gate 2. A separately
tasked independent agent must perform Gate 3; this record is not self-review.
