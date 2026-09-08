# PILOT-PREPARE-RBAC-FIXTURES-001 — corrected Gate 2 RED v10

Дата: 2026-09-04  
Автор: independently tasked agent `/root/prepare_v15_red`  
Base/review commit: `9867bfac8446ac97f35cfe0d26b73bc45970f170`  
Prior review: `reviews/tests/PILOT-PREPARE-RBAC-FIXTURES-001-v9.md` — **CHANGES_REQUESTED**  
Verdict: **QUALIFYING RED / fresh independent Gate 3 required**

## Corrections

Вся v7 authority/admission/revoke/renderer/read-only/cleanup matrix и v8/v9
assertions сохранены. Исполняемый client oracle теперь дополнительно требует:

- zero-result как ровно один direct `p` с exact approved copy и `Найдено: 0`,
  отдельно от доказательства ASCII-only tab search;
- atomic rejection post-delivery mutations: seventh attribute, nested element,
  span text, forbidden interstitial text, missing field, invalid/boundary ID/tab/
  selected/busy/name/position, duplicate/out-of-order rows, 301/161 code points
  и 501 records;
- acceptance exact ID/name/position bounds и каждого разрешённого interstitial
  whitespace character;
- both outside/modal selection summaries, removal accessible name, chip metadata
  exclusion, exact hidden-ID add/remove, live count, rerender focus, chip-removal
  focus return, Escape and non-prevented native Tab order.

Asset failure проверяется без изменения repository source: verifier копирует
`app/` в task-owned mutable root, удаляет только скопированный `picker.js`,
запускает тот же canonical factory/router против копии и требует public
GET/HEAD redacted `503`, exact retry/HEAD parity и guarded zero mutation. Test
router принимает этот explicit test-only copied root; normal path неизменён.

Production и OpenSpec tasks не менялись; task 2.2 остаётся open.

## Canonical RED

```text
$ date --iso-8601=seconds
2026-09-04T09:41:46+03:00

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_TEST_DB_PORT=23306 \
    php tests/InstallationProcess/pilot_prepare_form_001_test.php

Error: successful initialization atomically enables picker and hides fallback:
expected [false,true,true], actual [true,true,false]
Expected: 0
Actual: 1

# non-zero; completed 2026-09-04T09:41:49+03:00
```

Это intended client product RED, не setup/predecessor failure. До него реально
прошли exact asset GET/HEAD/repeat/method admission, новый missing-source 503,
полная retained v7 matrix и canonical allowed GET/HEAD renderer path. MariaDB,
PHP и Node были доступны; task-owned копия и остальные fixtures удалены в
`finally`.

## Checks and exact hashes

`node --check`, три `php -l`, strict OpenSpec validation и `git diff --check`
завершились exit 0.

```text
aeb10393be84329a8fca8de4a75b9731a2786f6cd61effa3678e0aaaa1ec2c9d  tests/InstallationProcess/pilot_prepare_form_001_test.php
56305f4d12d7ffc2f3707d283a22f4143c9bca15a7f5a8ffb0eace18968d9bb4  tests/InstallationProcess/support/pilot_prepare_picker_client.js
046e0ccac03b9ccfd94bf1c41fb476d5cc65db5914eb7024838ba1c1b26d3d70  tests/Support/PrepareRendererInvocationSpy.php
365e6fe5a622bfcb4aeae1f0409b4ce624110c63f70850be0544f49c3ecebdd5  tests/Support/pilot_prepare_renderer_spy_router.php
```

Любое следующее изменение verifier/harness/support возвращает Gate 2. Gate 3
должен выполнить новый независимо назначенный reviewer; это не self-review.
