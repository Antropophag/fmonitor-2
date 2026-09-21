# Issue #222 — редактирование реквизитов объекта

## Owner authorization — 2026-09-21

Владелец поручил продолжить существующую ветку `codex/issue-222-object-details` до PR-ready и разово разрешил для #222 начать production implementation до предварительного `APPROVED` Gate 3. Это исключение только из порядка: root сохраняет авторство specification/tests, отдельный `gpt-5.6-sol / low` executor реализует функцию, а один независимый reviewer на готовом кандидате совместно проверяет specification, tests, implementation, evidence и все прежние findings. Gate 3 остаётся `CHANGES_REQUESTED` до фактического нового review; approvals не подразумеваются.

Сохраняются исходный RED и fixture evidence. Локальный полный `make test`/`make verify` запрещён; один штатный exact-source CI выполняется после review. Merge/deploy, реальные imports, внешние отправки и repository settings запрещены.

## Approved scope

- Карточка сохраняет компоновку; только компактная pencil button в header.
- Одна `shlz-ui` modal, группы «Идентификация и размещение» и «Классификация оборудования».
- Editable: address, entrance, registration/factory numbers, floors, capacity, speed, shaft type/material, lift type, paired.
- Кшах и все plan dates запрещены в payload; Кшах вычисляется из effective shaft material.
- Integration/process/system fields запрещены.
- Owner decision 2026-09-21: `fkr_operator`/`manager` имеют глобальный scope по всем пилотным объектам с `objects.read`; персональная actor↔object связь вне scope.
- Override и full diff history атомарны; replay/no-op не дублируют; >8 events доступны.
- Effective values используют card/search/ERP/Bitrix/new calculations; immutable imports/old documents/snapshots/payments сохраняются.
- Общий `shlz-modal` close target 40×40 сохраняется; browser automation только Playwright.

## Authorship and reviews

- Scope/spec/tests: root.
- Production implementation: отдельный executor, ещё не назначен на момент записи.
- Previous independent Gate 3 records retained: `reviews/tests/OBJECT-DETAILS-EDITING-001.md`, `reviews/tests/OBJECT-DETAILS-EDITING-001-v2.md`, оба `CHANGES_REQUESTED`.
- Final joint test/code review: pending; reviewer must receive disposition for every previous finding.

## Current state

OpenSpec: `openspec/changes/edit-object-details-with-history/`. Contract: `specs/OBJECT-DETAILS-EDITING-001.md`. Prototype decisions already approved and are not reopened. Current next action: reconcile scope, prepare executor package, implement minimal complete scenario, run bounded obligations, then independent joint review.
