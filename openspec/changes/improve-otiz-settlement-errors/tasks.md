## 1. Gate 1 и verification plan

- [x] 1.1 Создать canonical executable spec `OTIZ-SETTLEMENT-FORM-RECOVERY-001` с полной HTTP/browser acceptance matrix и проверить traceability к #249/#169/#248.
- [x] 1.2 Создать `verification-input.json`, подготовить root package через delivery harness, прочитать все обязательства planner и устранить unresolved coverage до Gate 2.

## 2. Gate 2 — root-authored RED

- [x] 2.1 Добавить focused parser/HTTP RED на допустимые суммы и strict rejection matrix; доказать exact cents и отсутствие financial facts на отказах.
- [x] 2.2 Добавить isolated HTTP/browser RED на восстановление snapshot/object form context, allowlist/escaping, отсутствие текста в URL, drawer/focus, no-JS и desktop/narrow.
- [x] 2.3 Добавить focused HTTP/browser RED на stale 409 page, authorization/CSRF, double-submit, retained operationId, replay/conflict и неизвестный transport outcome без auto-retry.
- [x] 2.4 Сохранить RED evidence exact source вне checkout и подготовить complete Gate 3 reviewer package.

## 3. Gate 3 — независимый review тестов

- [x] 3.1 Получить planner-required независимый Gate 3 verdict; устранить все blocking findings и повторно проверить исправленный exact source до `APPROVED`.

## 4. Executor implementation

- [x] 4.1 Реализовать локальный strict money/form-state helper и controller mapping, подтвердив GREEN parser/HTTP checks без изменения `OtizSettlement` и persistence.
- [x] 4.2 Реализовать безопасное восстановление формы и error presentation в snapshot view/локальном partial, подтвердив object binding, escaping, focus и no-JS behavior.
- [x] 4.3 Реализовать HTML 409 stale page и local submit lifecycle enhancement без `fetch`/auto-retry/new operationId, подтвердив browser desktop/narrow и transport-uncertainty scenarios.
- [x] 4.4 Выполнить focused regression для #248 object-wide history, settlement replay/conflict/reversal, guest/denied/CSRF и доказать неизменность financial table inventory.
- [x] 4.5 Выполнить обязательный `pilot_http_auth_001_global_calls_test.php`, planner-selected focused commands и `make architecture-check`; полный локальный `make test`/`make verify` не запускать.

## 5. Gate 5 и проверенный PR

- [x] 5.1 Подготовить independent final-review package exact source; устранить все blocking findings и получить planner-required `APPROVED` на исправленный source.
- [x] 5.2 Зафиксировать пользовательский before/after, авторов, review/evidence links и неизменность formulas/writers/ledger/snapshots/rights в delivery record.
- [ ] 5.3 Создать PR #249 без merge/deploy, запустить один exact-source GitHub CI consumer, собрать полный failed-job/`REGRESSION_FAILURE` inventory при сбое и довести head до GREEN либо честно зафиксировать внешний blocker.
