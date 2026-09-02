# INSPECTION-ITEM-COMPLETE-001 GREEN verification

Date: 2026-09-01

Срез завершён через обязательные SSD/TDD gates. Canonical inspection-evidence
schema v8 остаётся единственным владельцем persistence; behavior slice не
добавляет migration/version или runtime DDL. `InspectionRecording::completeItem`
остаётся единственным public mutation seam, а rapid-pilot переводит только
HTTP/protocol inputs и result classes.

Последний UI gap закрыт двумя независимыми presentation flags. Пользователь с
exact capability `inspection.item.complete` может отметить отдельный пункт, но
не получает photo upload/revoke, installer correction, bulk или automatic
section completion. Старые queued legacy operations для такого пользователя не
синхронизируются. Existing assigned-engineer/legacy-role access сохранён.

Focused verification:

```text
PASS: INSPECTION-ITEM-COMPLETE-001 raw HTTP endpoint admission
PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring
PASS: INSPECTION-ITEM-COMPLETE-001 receipt-time authorization
PASS: INSPECTION-ITEM-COMPLETE-001 authorization before replay all
ARCHITECTURE CHECK PASSED (7 rules)
```

Новый executable UI/browser harness исполняет реально отданный
`app/PilotHttp/checklist.js`, наблюдает persisted и outgoing `item_completed` и
проверяет недоступность photo/installer/bulk controls. Gate 3 rereview:
`reviews/tests/INSPECTION-ITEM-COMPLETE-001-ui-v2.md` — `APPROVED`. Gate 5 UI
review: `reviews/code/INSPECTION-ITEM-COMPLETE-001-ui.md` — `APPROVED`.

Полный `make verify` завершился установленным baseline:

```text
VERIFY_STAGE architecture-check PASS
VERIFY_STAGE lint PASS
VERIFY_STAGE unit-test PASS
VERIFY_STAGE db-test FAIL
VERIFY_STAGE characterization-test PASS
VERIFY_STAGE e2e-test FAIL
VERIFY_STAGE diff-check PASS
FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test
```

В `db-test` новый UI verifier и все item-completion verifiers прошли. Остались
ровно восемь ранее классифицированных regressions: три shared CSP, четыре
local-RBAC fixture и combined-PDF artifact. Отдельный E2E stage повторяет тот же
combined-PDF artifact fault. Они уже вынесены в отдельные slices
`LOCAL-RBAC-AUTH-CONTRACT-001`, `PILOT-OBJECT-READ-RBAC-FIXTURES-001`,
`PILOT-PREPARE-RBAC-FIXTURES-001`, `PILOT-E2E-RBAC-FIXTURES-001`,
`PILOT-ROUTE-CSP-001`, `PILOT-E2E-COMBINED-PDF-001` в
`docs/operations/migration-backlog-and-grill.md`; expectations не ослаблялись.

Friction calibration: последний пользовательский gap потребовал отдельного UI
RED и двух test-review итераций. Первая строковая JS-проверка была отвергнута;
принят только dependency-free executable DOM/IndexedDB/fetch harness. Временное
pretty-formatting production JS вызвало hotspot ratchet и было устранено
minification без изменения поведения до финального architecture GREEN.
