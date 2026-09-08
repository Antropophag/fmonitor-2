# PILOT-PREPARE-RBAC-FIXTURES-001 — Gate 2 correction evidence v14

Дата: 2026-09-04  
Автор: independently tasked agent `/root/prepare_v15_red`  
Base review: `671eee594fd9b93e75108f8d6f7b792451b2d2a3`  
Verdict: **TEST CORRECTED / PRE-GATE4 RED / CURRENT-HEAD GREEN / fresh Gate 3 required**

## Corrections

Homogeneous provenance теперь структурно требует ровно один direct group-level
`p`: exact source text, один `br`, exact timestamp text и отсутствие
`ul.fm2-picker-provenance`. Mixed success независимо требует ровно один direct
provenance `ul`, ровно два direct `li`, exact literal row association/order и
отсутствие group-level source/timestamp `p`. Режимы взаимно исключены.

Equal-code-point-name tie проверяется двумя способами:

- public GET/HEAD временно даёт двум eligible installers exact одинаковое ФИО
  и IDs `99`, `100`, затем требует numeric order `99,100` и display tabs
  `000099,000100`;
- client harness принимает literal ascending equal-name pair `99,100` и
  fail-closed отклоняет deliberately reversed `100,99`.

Сохранены independent U+E000/U+10000 primary comparator, v13 exclusions и вся
предыдущая matrix. Production не менялся; task 2.2 остаётся open.

## Current-head verification

```text
2026-09-04T10:36:58+03:00
node tests/InstallationProcess/support/pilot_prepare_picker_client.js app/PilotHttp/picker.js
prepare picker client contract: PASS

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
# both exit 0; completed 2026-09-04T10:37:08+03:00
```

## Pre-Gate4 RED

В detached `~/code/fmonitor-2-prepare-v14-red` на exact baseline
`6137d5e83be6a31b00e801efe6acf00b4ce473ce` применялся только cumulative exact
test/task diff до v14. Canonical run:

```text
2026-09-04T10:37:45+03:00
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1
# exit 255; completed 2026-09-04T10:37:47+03:00
```

Это intended earliest product RED, не setup failure. Assertions не
переставлялись для искусственного позднего failure. Current-head full PASS
подтверждает исполнение новых provenance/tie cases. После baseline run diff был
reverse-applied, clean status проверен, worktree удалён, `git worktree prune`
выполнен и temporary patch удалён.

PHP/Node syntax, strict OpenSpec и `git diff --check` прошли.

## Exact hashes

```text
526f7b7259bcb239f884453a484c4c76438d0e6ce8342d33216d1297e135c4f2  tests/InstallationProcess/pilot_prepare_form_001_test.php
5955b599e04b4f389e8a88cf50b02c106f9c757a0b33567b7a77b3161e5cb040  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
00e7265ea0d1d16dd50b4590cccf1358d8c99c5ce4b9d0448f108ba0c8ad5546  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
```

Fresh Gate 3 выполняет отдельно назначенный reviewer; это не self-review.
