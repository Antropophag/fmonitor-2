# Диагностический make verify — неподготовленный отдельный checkout

Дата: 2026-09-05. Исполнитель: `/root`.
Exact SHA: `d1a5d0938ac9939f7098d045e4054bdf30e1b343`.
Detached worktree: `/Users/antropophag/code/fmonitor2-verify-d1a5d09`.
Tracked tree не изменялся; основной checkout продолжал только planning work.

Команда:

```text
env PATH=/Applications/Docker.app/Contents/Resources/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin make verify
```

Полный stdout/stderr сохранён вне repository:
`/tmp/fmonitor2-verify-d1a5d09.log`, SHA-256
`2dcb1386e7c4cc39e38c6f718e283e4b7ea0f76385af9070b5269d22f59b8f80`.
Process exit 2, final output:

```text
FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
make: *** [verify] Error 1
```

## Классификация

- test-db-reset, migrate, architecture-check, lint и diff-check — PASS.
  Migration JSON: schemaVersion 12, appliedVersions `[1,2,3,4,5,6,7,8,9,10,11,12]`.
- Ошибка подготовки исполнителем: ограниченный PATH потерял установленный
  bundled `rg`. `run.sh` не смог классифицировать unit/db files, все попали в
  unit, затем db stage упал на empty/unbound array. Это SETUP_FAILURE запуска,
  а не качественная оценка db suite. Ошибка не скрыта и не заменена skip.
- Ошибка подготовки исполнителем: новый checkout не имел ignored vendor/TCPDF.
  `artifact_store_001`, `production_composition_001` и
  `production_pdf_assignment_order_renderer` получили renderer unavailable.
  Это отсутствующая dependency, не qualifying product RED.
- Отдельные реальные несовпадения expectation с landed v12:
  checklist_template_schema, classification_provenance_schema,
  identity_access_schema, inspection_evidence_schema,
  inspection_item_complete_001_mariadb, inspection_planning_schema,
  installation_completion_schema, pilot_case_import (и его bootstrap consumer),
  pilot_http_auth, workforce_canonical_runner, verify-calendar-projections и
  harness_otiz_canonical_compat. Они требуют точного scoped fixture review;
  нельзя просто принимать любое schemaVersion или ослаблять assertions.
- Protected `pilot_e2e_flow_001_test.php` по-прежнему падает на actor18 admission.
  Test/spec не менялись; отсутствие owner-approved Gate 1 сохраняется.

Этот запуск не дал literal VERIFY_OK и не разрешает Quality Graph integration,
bootstrap CI PR, публикацию integration branch или readiness claim.

## Исправление только test environment

После завершения failure в этот же detached checkout установлен TCPDF способом
из tracked Dockerfile: `git clone --branch 6.11.4 --depth 1` официального upstream
в `vendor/tecnickcom/tcpdf`, HEAD проверен на exact
`fbbaf14cfae8fe646f154f7c530d15ec25764040`; tracked
`rapid-pilot/tcpdf-autoload.php` скопирован в ignored `vendor/autoload.php`.
Никакие production/tests/expectations не изменены. Новый отдельный run использует
обычный session PATH, где присутствуют `rg`, PHP и Docker, и новый log
`/tmp/fmonitor2-verify-d1a5d09-prepared.log`. Его результат будет записан отдельно.
