## Context

См. `proposal.md`. Текущая policy перечисляет три stateful assets внутри `bounded-ui`; lane вычисляется по именам boundary до независимого применения `semantic_surfaces` #153A. Canonical inventory уже принадлежит `tools/verification/suites.tsv`.

## Goals / Non-Goals

**Goals:** выделить минимальный closed set в существующей boundary policy, связать его с существующим direct oracle и доказать A–L через public planner seam.

**Non-Goals:** анализ функций/AST, общий аудит JS/FAST, новый registry/planner/Gate, runtime изменения, изменение #153A или inventory architecture.

## Decisions

1. Добавить boundary `sensitive-offline-ui` с exact path patterns для `checklist-sw.js`, `checklist.js`, `control-queue.js`; удалить эти paths из `bounded-ui`. Альтернатива «весь Assets/** sensitive» отвергнута как потеря presentation FAST.
2. Включить boundary в существующий `verification_lanes.CRITICAL`. Это использует текущую монотонную lane precedence и автоматически даёт Gate 3 + final review. Новый classifier не нужен.
3. Boundary требует категорию `e2e` и зарегистрированный `tests/Yii2/yii2_inspection_browser_001_test.php`, исполняющий `inspection_browser.mjs`: это текущий direct oracle для IndexedDB operations/photo blobs, replay и cross-user offline cache isolation. `suites.tsv` не дублируется.
4. #153A не меняется: semantic surfaces по-прежнему независимо добавляют integration closure; regression проверяет совместную выдачу.
5. Владельцем изменения является verification policy; persistence owner и runtime adapters не меняются. `rapid-pilot` не читается и не изменяется. Architecture checks получают только обычный policy/spec/test diff.

## Risks / Trade-offs

- [В `checklist.js` есть presentation код] → консервативно классифицируется весь смешанный файл, как решил owner.
- [Closed set может потребовать будущего расширения] → новые state-owning assets добавляются отдельным review policy change; #132 не создаёт эвристик.
- [E2E oracle дороже unit] → это существующий подходящий direct oracle и выполняется через уже существующий CRITICAL/full CI route.

## Migration Plan

Policy-only change применяется атомарно; rollback — возврат policy/spec/test commit. Storage, service worker generation и пользовательские данные не мигрируются.
