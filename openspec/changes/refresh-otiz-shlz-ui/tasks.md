## 1. Scope и Gates 1–3

- [x] 1.1 Root создаёт `specs/OTIZ-SHLZ-UI-001.md`, acceptance matrix и `verification-input.json`; выполнить `harness.py prepare`, прочитать все planner obligations и проверить `openspec validate --strict`.
- [x] 1.2 Root пишет bounded HTTP/browser RED tests для workflow header, object/evidence/issue linkage, action semantics, 320/768/1024/1440, 200% zoom, keyboard, coarse pointer, reduced motion, JS-off и domain invariants; сохранить intended RED evidence focused-командами.
- [x] 1.3 Если verification plan требует Gate 3, отдельный gpt-5.6-sol/low reviewer проверяет exact spec/tests/RED и выдаёт явный `APPROVED`; весь findings list исправляется до dispatch executor.

## 2. Yii2 OTIZ presentation

- [x] 2.1 Отдельный gpt-5.6-sol/low executor переводит OTIZ GET presentation на `ViewSupport` и semantic Yii views/partials, не меняя routes, owners и persistence; HTTP structure tests проходят.
- [x] 2.2 Executor реализует единый workflow header, object-scoped evidence/issues/allocations и primary/secondary/danger `shlz-ui` action hierarchy; focused browser flow и exact form-contract tests проходят.
- [x] 2.3 Executor реализует labelled-row register и contained-scroll ledger strategies, keyboard/coarse/reduced-motion/JS-off behavior; responsive browser matrix проходит без page overflow.

## 3. Verification и delivery

- [x] 3.1 Выполнить один batched desktop/mobile screenshot pass, одну correction batch, не более одного confirmation pass и один Impeccable detector run; зафиксировать артефакты и результат.
- [x] 3.2 Выполнить planner-selected focused checks, relevant OTIZ regression и `make architecture-check`, не запуская локально full `make test`/`make verify`; записать source digest и полные результаты.
- [x] 3.3 Получить независимый gpt-5.6-sol/low final Gate 5 `APPROVED` по exact reconstructible source; исправить полный findings list и повторно проверить затронутые boundaries.
- [ ] 3.4 Создать PR и выполнить один exact-source GitHub CI run; проверить полный failed-job/`REGRESSION_FAILURE` inventory при сбое и получить GREEN для reviewed matching source. Merge/deploy оставить не выполненными без отдельной авторизации.

## 4. Done definition

- [ ] 4.1 Подтвердить, что `/pilot/otiz/**` соответствует `OTIZ-SHLZ-UI-001`, все существующие финансовые HTTP/browser/domain contracts остаются GREEN, required reviews APPROVED и exact-source CI GREEN; обновить delivery record и OpenSpec task state.
