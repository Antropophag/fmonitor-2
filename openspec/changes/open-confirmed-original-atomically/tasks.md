## 1. Contract и RED

- [x] 1.1 Зафиксировать owner decision/form contract и public compound command/result; verification: strict OpenSpec valid.
- [x] 1.2 Добавить focused synthetic RED для first apply+open, same application, corrected reapply, invalid date/stale/auth denial/replay; verification: intended missing-seam RED и unchanged full projections.
- [x] 1.3 Получить независимый Gate 3 review либо честно отметить manual-pilot deferred gate.

## 2. Atomic application owner

- [x] 2.1 Извлечь shared transaction helpers из существующих application/opening owners без изменения standalone public behavior; verification: existing suites GREEN.
- [x] 2.2 Реализовать compound owner/factory с stable lock order, one commit и replay/conflict; verification: RED становится GREEN, failure branches leave exact facts unchanged.

## 3. HTTP wiring

- [x] 3.1 Добавить `open_confirmed` shape/CSRF mapping в `ExecutionHttpHandler`; verification: exact fields, trusted actor/object, one seam call, success 303 card.
- [x] 3.2 Выполнить focused HTTP/browser flow без отдельного apply request; verification: upload remains pure, explicit click creates application+opening.

## 4. Verification

- [x] 4.1 Запустить lint, diff-check, existing application/opening tests, global-call и architecture checks; verification: GREEN без baseline/schema/stand changes.
- [x] 4.2 Получить независимый code review; verification: APPROVED exact hashes before bundle/deploy.
