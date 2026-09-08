# PILOT-PREPARE-RBAC-FIXTURES-001 — corrected Gate 2 RED v11

Дата: 2026-09-04  
Автор: independently tasked agent `/root/prepare_v15_red`  
Base/review commit: `9d26f3459d5e7a06e0c0a372b0c45b8dbdf34e5d`  
Prior review: `reviews/tests/PILOT-PREPARE-RBAC-FIXTURES-001-v10.md` — **CHANGES_REQUESTED**  
Verdict: **QUALIFYING RED / fresh independent Gate 3 required**

## Exact correction

После удаления selected installer chip client harness теперь независимо для
outside и modal summary требует:

- ровно один `SPAN`, а не оставшийся remove-button;
- exact class `fm2-picker-selection-empty`;
- exact literals `Монтажники ещё не выбраны` и `Пока никого`;
- отсутствие `aria-label="Убрать ИВАНОВ\t  Иван"`, любого button/chip и
  selected remove accessible name.

Предыдущие hidden-ID removal, live count и opener-focus assertions сохранены.
Другие tests/support contracts, production и OpenSpec tasks не менялись;
task 2.2 остаётся open.

## RED reproduction and controls

```text
$ date --iso-8601=seconds
2026-09-04T09:45:48+03:00

$ node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
    app/PilotHttp/picker.js

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
# exit 1

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1
# exit 255; completed 2026-09-04T09:45:51+03:00
```

Canonical RED остаётся intended missing client behavior. До него проходят
public asset success/failure admission, retained v7 matrix и canonical allowed
GET/HEAD. Direct harness и canonical wrapper дают одинаковую причину; cleanup
выполнен.

`node --check`, PHP syntax, strict OpenSpec validation и `git diff --check`
завершились exit 0.

## Exact hashes

```text
aeb10393be84329a8fca8de4a75b9731a2786f6cd61effa3678e0aaaa1ec2c9d  tests/InstallationProcess/pilot_prepare_form_001_test.php
f14603a93467d5a47d0d315a7cfdb43dce001c385e5aa4b7d5a57963eee34bdf  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```

Fresh Gate 3 должен выполнить отдельно назначенный reviewer; этот record не
является self-review.
