# PILOT-PREPARE-RBAC-FIXTURES-001 — Gate 2 RED v12 expected-value correction

Дата: 2026-09-04  
Автор: independently tasked agent `/root/prepare_v15_red`  
Base commit: `ebbbed1`  
Verdict: **QUALIFYING RED / fresh independent Gate 3 required**

## Correction rationale

После Gate 3 v11 APPROVED Gate 4 preflight выявил test expected-value bug.
Для literal ASCII query `10` exact six-digit substring contract совпадает не
только с `001042`, но и с fixture `003010`. v11 ошибочно ожидал один result.

V12 запускает этот случай в отдельном fresh DOM execution, требует ровно два
result и независимо кликает их в порядке выдачи. Exact resulting hidden IDs
должны быть `["1042", "3010"]`. Значения и порядок выведены только из literal
base fixtures и owner-approved substring rule, не из production output.

Все остальные v11 assertions сохранены. Production не изменялся. Поскольку
test hash изменён после approval, OpenSpec task 2.2 возвращён в open.

## Reproduction

```text
2026-09-04T09:52:39+03:00

node tests/InstallationProcess/support/pilot_prepare_picker_client.js \
  app/PilotHttp/picker.js

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
# exit 1

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
FMONITOR_TEST_DB_PORT=23306 \
php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1
# exit 255; completed 2026-09-04T09:52:43+03:00
```

Direct и canonical runs снова доходят до genuine first product failure против
неизменённого production asset: отсутствует atomic validated initialization.
Canonical run до этого проходит public asset success/failure checks, retained
v7 matrix и canonical allowed GET/HEAD; cleanup выполнен.

`node --check`, PHP syntax, strict OpenSpec validation и `git diff --check`
прошли.

## Exact hashes

```text
5f8cc0d803302d4469c0775e291a8278c692ec85897c5e8bafda4d830174952a  tests/InstallationProcess/support/pilot_prepare_picker_client.js
aeb10393be84329a8fca8de4a75b9731a2786f6cd61effa3678e0aaaa1ec2c9d  tests/InstallationProcess/pilot_prepare_form_001_test.php
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
5fcca8ca64d443748a26a31adcf962acf8e7ace29c1f03484b2f16ba87420a5c  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
```

Fresh Gate 3 должен выполнить отдельно назначенный reviewer; это не self-review.
