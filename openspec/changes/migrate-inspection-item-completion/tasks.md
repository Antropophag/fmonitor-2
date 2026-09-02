## 1. Gate 1 — executable specification

- [x] 1.1 Зафиксировать `specs/INSPECTION-ITEM-COMPLETE-001.md` с approved command seam `InspectionRecording::completeItem`, evidence query seam `InspectionEvidenceView::getItemCompletion`, explicit production factory/config/clock composition seam, capability `inspection.item.complete`, owner-resolved capability-only object scope, server-receipt reauthorization, replay precedence и exact inputs/results/rejections/audit/idempotency examples; verification: явное решение владельца и acceptance scenarios записаны в spec и durable decision record.
- [x] 1.2 Выполнить focused characterization текущего `item_completed` behavior; verification: offline/current-crew commands и evidence сохранены без изменения production behavior.

## 2. Gates 2–3 — RED и independent test review

- [x] 2.1 Test/RED agent создаёт минимальный public-seam test из approved example; verification: `tools/verification/run.sh red <test>` фиксирует `RED_ASSERTION`, не setup failure.
- [x] 2.2 Отдельный test-review agent проверяет spec/test/RED независимо и записывает `APPROVED` в `reviews/tests/INSPECTION-ITEM-COMPLETE-001.md`; verification: Gate 3 record не имеет unresolved findings.

## 3. Gate 4 — minimal GREEN

- [x] 3.1 Использовать landed canonical inspection-evidence schema v8 без новой
  migration/version; verification: v1–v8 fresh/repeat runner остаётся green,
  missing/incompatible v8 fail closed и runtime DDL для slice отсутствует.
- [x] 3.2 Реализовать application seam и MariaDB adapter без HTTP/UI/rapid dependencies; verification: reviewed focused test проходит без изменения expectation.
- [x] 3.3 Переключить только `item_completed` rapid-pilot branch на public seam; verification: offline replay и current-crew characterization проходят.
- [x] 3.4 Выполнить focused regression, DB/E2E и `make architecture-check`; verification: нет новых baseline debt/hotspot growth и relevant suites green.

## 4. Gate 5 и Done

- [x] 4.1 Отдельный code-review agent проверяет approved spec/tests/diff/evidence и записывает `APPROVED` в `reviews/code/INSPECTION-ITEM-COMPLETE-001.md`; verification: findings закрыты без изменения reviewed expectation.
- [x] 4.2 Интегратор запускает `make verify`, фиксирует friction/cost calibration и обновляет operations backlog; verification: полный verification green либо каждый pre-existing blocker классифицирован и вынесен в отдельный slice.
- [x] 4.3 Done: OpenSpec tasks complete, canonical migration owns schema, one public seam owns mutation, rapid-pilot contains only adapter wiring, characterization/regression/architecture green, independent reviews approved.
