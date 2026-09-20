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
- [x] 3.4 Прежнее Gate 5 approval считать superseded для correction delta; обновить `verification-input.json`, выполнить `harness.py prepare` и принять planner-selected lane/required reviews без ручного назначения FAST.

## 4. Stand-feedback correction

- [x] 4.1 Root дополняет executable spec и пишет RED HTTP/browser tests: полный
  девятиколоночный Yii register, unknown money без ложного нуля, summary,
  filters/sort/pageSize, страницы 50/50/…, keyboard/mobile и exact
  `shlz-pagination` contract на всех pageable Yii surfaces.
- [x] 4.2 Root пишет RED integration coverage для known/unknown legacy material
  identifier и доказанного reference mapping без schema/history mutation.
- [x] 4.3 Если planner требует Gate 3, независимый gpt-5.6-sol/low reviewer
  проверяет полный correction spec/tests/RED и выдаёт явный verdict.
- [x] 4.4 Отдельный gpt-5.6-sol/low executor исправляет material adapter,
  восстанавливает полный OTIZ register и reusable pagination composition, не
  меняя финансовые owners, formulas, permissions и persistence.
- [x] 4.5 Выполнить один batched desktop/mobile screenshot pass, одну correction
  batch, не более одного confirmation pass и один Impeccable detector run.
- [x] 4.6 Выполнить planner-selected focused checks, OTIZ/import/financial и
  затронутые directory regressions, `make architecture-check`; локальный full
  suite не запускать.
- [x] 4.7 Получить независимый final Gate 5 по exact reconstructible correction
  source и исправить полный findings list.
- [x] 4.8 Создать PR и выполнить один exact-source GitHub CI run; при сбое сначала
  собрать полный failed-job/`REGRESSION_FAILURE` inventory. Merge/deploy оставить
  не выполненными без отдельной авторизации.

## 5. Done definition

- [x] 5.1 Подтвердить, что `/pilot/otiz/**` и все pageable Yii surfaces
  соответствуют обновлённому контракту, material mapping доказан, все
  существующие финансовые HTTP/browser/domain contracts остаются GREEN,
  required reviews APPROVED и exact-source CI GREEN; обновить delivery record и
  OpenSpec task state.
