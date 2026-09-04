# PILOT-PREPARE-RBAC-FIXTURES-001 — Gate 2 correction evidence v13

Дата: 2026-09-04  
Автор: independently tasked agent `/root/prepare_v15_red`  
Current production head: `cc707fe9f42c3daa078c9b54f44154ac0efee476`  
Verdict: **TEST CORRECTED / PRE-GATE4 RED / CURRENT-HEAD GREEN / fresh Gate 3 required**

## Exact correction

V12 raw whole-document exclusions `3099`, `4001`, `75`, `76` были ложными:
обычный shared SVG path содержит несвязанный geometry substring `75`. V13
проверяет business identity только в owning DOM scopes:

- excluded installers — exact `data-id`, six-digit `data-tab` и `data-name`
  внутри inert picker records;
- excluded engineers — exact radio values и candidate label names внутри
  engineer fieldset;
- file/CSRF/multipart/submit/command URL по-прежнему запрещены raw, потому что
  это значимые command bytes, а не короткие числовые substrings.

Дополнительно public GET/HEAD verifier теперь имеет:

- sound mixed-provenance success с двумя independently literal per-row source/
  timestamp outputs;
- exact U+E000 / U+10000 name pair. Code-point order обязан быть `1042,2088`,
  тогда как UTF-16 code-unit order был бы обратным; exact names также
  сохраняются byte-for-byte.

Production не изменялся. Из-за test hash task 2.2 возвращён в open.

## Honest RED and current result

В temporary detached worktree
`~/code/fmonitor-2-prepare-v13-red` на exact pre-Gate4 baseline
`6137d5e83be6a31b00e801efe6acf00b4ce473ce` был применён только v13 test/task
diff. Canonical run:

```text
2026-09-04T10:29:55+03:00
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1
# exit 255; completed 2026-09-04T10:29:58+03:00
```

Это genuine intended product RED, не setup failure. Он возникает раньше новых
mixed-provenance/Unicode assertions на pre-Gate4 production, потому что тот
baseline ещё не реализует обязательную validated client initialization; тест
не обходился и assertions не переставлялись ради более позднего failure.

На неизменённом current head `cc707fe9f42c3daa078c9b54f44154ac0efee476`
тот же exact verifier прошёл полностью:

```text
2026-09-04T10:28:46+03:00
node tests/InstallationProcess/support/pilot_prepare_picker_client.js app/PilotHttp/picker.js
prepare picker client contract: PASS

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
# both exit 0; completed 2026-09-04T10:28:57+03:00
```

Current-head GREEN proves both new focused cases execute, while the baseline
run preserves honest RED ordering. Temporary worktree changes were reverse-
applied, clean status was verified, the worktree was removed, metadata pruned
and the temporary patch deleted.

PHP/Node syntax, strict OpenSpec and `git diff --check` passed.

## Exact hashes

```text
2be50722a62def245ccd78dadd3851dc78e6cd7790531555131a4185511fe04f  tests/InstallationProcess/pilot_prepare_form_001_test.php
5f8cc0d803302d4469c0775e291a8278c692ec85897c5e8bafda4d830174952a  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
00e7265ea0d1d16dd50b4590cccf1358d8c99c5ce4b9d0448f108ba0c8ad5546  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
```

Fresh Gate 3 должен выполнить отдельно назначенный reviewer; это не self-review.
