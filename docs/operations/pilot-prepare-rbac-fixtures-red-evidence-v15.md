# PILOT-PREPARE-RBAC-FIXTURES-001 — Gate 2 correction evidence v15

Дата: 2026-09-04  
Автор: independently tasked agent `/root/prepare_v15_red`  
Base review: `9a55d3afe91cad8285244a798bf0932da4e836ed`  
Verdict: **TEST CORRECTED / PRE-GATE4 RED / CURRENT-HEAD GREEN / fresh Gate 3 required**

## Corrections

Homogeneous provenance container теперь требует exactly one scoped direct
paragraph carrying either provenance prefix total. Existing exact two text
lines, one `br` and no per-row `ul` assertions remain, so an extra stale or
duplicate provenance paragraph is observable.

Inherited engineer tie rule теперь executable через public GET/HEAD. Два
eligible active engineer fixtures получают одинаковое exact ФИО and deliberately
noncanonical source identity mapping `10,9`; output radios обязаны иметь numeric
ID order `9,10`. Fixture independently proves prefill только у `9` and separate
unchecked confirmation. Initial distinct-name `73,74` prefill/confirmation
assertions remain unchanged.

Все v14 provenance/installer tie/U+E000-U+10000 assertions и prior matrix
сохранены. Production не менялся; task 2.2 открыт.

## Current head

```text
2026-09-04T10:43:16+03:00
node tests/InstallationProcess/support/pilot_prepare_picker_client.js app/PilotHttp/picker.js
prepare picker client contract: PASS

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
# both exit 0; completed 2026-09-04T10:43:26+03:00
```

## Pre-Gate4 RED

Detached `~/code/fmonitor-2-prepare-v15-red` used exact baseline
`6137d5e83be6a31b00e801efe6acf00b4ce473ce` plus only cumulative current
test/support/task diff:

```text
2026-09-04T10:43:42+03:00
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1
# exit 255; completed 2026-09-04T10:43:45+03:00
```

Это intended earliest product RED, not setup failure; assertions не
переставлялись. Current full GREEN доказывает исполнение новых поздних cases.
После RED patch reverse-applied, clean status проверен, temporary worktree
удалён, prune выполнен и patch удалён.

PHP/Node syntax, strict OpenSpec and `git diff --check` passed.

## Exact hashes

```text
72f6c7668ba5f45f1da0ee1f8814e6807a0bb302f43e0db8162ade47b88af69c  tests/InstallationProcess/pilot_prepare_form_001_test.php
5955b599e04b4f389e8a88cf50b02c106f9c757a0b33567b7a77b3161e5cb040  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
00e7265ea0d1d16dd50b4590cccf1358d8c99c5ce4b9d0448f108ba0c8ad5546  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
```

Fresh Gate 3 выполняет separately tasked reviewer; это не self-review.
