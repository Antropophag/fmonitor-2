# Exact-SHA make verify после подготовки dependency/PATH

Дата: 2026-09-05. Исполнитель: `/root`.
SHA: `d1a5d0938ac9939f7098d045e4054bdf30e1b343`.
Checkout: `/Users/antropophag/code/fmonitor2-verify-d1a5d09`, detached, tracked
files unchanged. TCPDF exact `fbbaf14cfae8fe646f154f7c530d15ec25764040`
и autoload установлены согласно предыдущей append-only записи.

Команда: `make verify` с обычным PATH, содержащим PHP, Docker и rg.
Полный stdout/stderr вне repository:
`/tmp/fmonitor2-verify-d1a5d09-prepared.log`, SHA-256
`f5ab9e710c1b881d6f6369999c22d52199cc2064a41b8a85ef261f40c73ad623`.
Exit 2. Test processes завершились до этой записи.

```text
VERIFY_STAGE test-db-reset PASS
VERIFY_STAGE migrate PASS
VERIFY_STAGE architecture-check PASS
VERIFY_STAGE lint PASS
VERIFY_STAGE unit-test PASS
VERIFY_STAGE db-test FAIL
VERIFY_STAGE characterization-test FAIL
VERIFY_STAGE e2e-test FAIL
VERIFY_STAGE diff-check PASS
FULL_VERIFICATION_FAILURE count=3 stages=db-test,characterization-test,e2e-test
```

## Точная классификация оставшихся failures

Десять db test prerequisites ожидают старый full terminal/catalog v11:
checklist_template_schema, classification_provenance_schema,
identity_access_schema, inspection_evidence_schema,
inspection_item_complete_001_mariadb, inspection_planning_schema,
installation_completion_schema, pilot_case_import, pilot_http_auth,
workforce_canonical_runner. Настоящий CLI успешно выдаёт v12 согласно ранее
approved schema contract. Pilot demo bootstrap наследует падение своего
pilot_case_import child; отдельная причина в нём этим запуском не доказана.

Два characterization failures имеют ту же причину:
verify-calendar-projections и harness_otiz_canonical_compat ожидают v11.
Например последний получает exact
`{"ok":true,"schemaVersion":12,"appliedVersions":[]}` плюс LF, exit0,
empty stderr и останавливается на assertion expected11/actual12.

Protected pilot_e2e_flow отдельно падает в db и e2e stages на actor18 admission:
expected object link count 1, actual 0. Его test/spec не изменены; это известный
отдельно gated blocker, не разрешение ослабить admission assertion.

TCPDF/PATH setup failures первого запуска устранены: unit stage, renderer,
production composition и artifact store проходят; rg classification работает.
Эта запись не переписывает результат первого запуска.

## Следующие разрешённые действия

Supporting fixture contract `CANONICAL-V12-CONSUMER-FIXTURES-001` проходит
technical Gate 1; exact patch review должен предшествовать применению. Этот
run — сохранённое воспроизведение ранее approved v12 против старых fixtures,
не утверждение нового production TDD cycle. Family-local conflicts и business
assertions должны сохраниться. Full VERIFY_OK по-прежнему отсутствует;
Quality Graph/CI publication/readiness остаются закрытыми.
