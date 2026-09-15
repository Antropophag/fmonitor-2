## Why

Issue #132 закрывает safety gap в public verification planner: смешанные Yii assets, владеющие offline persisted state, private cache и replay, сейчас ошибочно проходят как bounded presentation UI и могут получить FAST. После merged #135 и #153 Slice A это должен быть маленький repository-owned policy slice без нового inventory или classifier.

## What Changes

- Ввести deterministic closed-set boundary для трёх существующих sensitive assets: `checklist-sw.js`, `checklist.js`, `control-queue.js`.
- Запретить FAST для этой boundary, направив её в существующий CRITICAL route и связав с зарегистрированным offline/session/cache oracle.
- Сохранить FAST для остальных действительно presentation-only Yii assets.
- Добавить regression matrix через настоящий `change-verification.py plan` с поставляемой policy и проверить композицию с semantic integration closure #153A.
- Не менять checklist/offline product behavior, storage/replay/auth semantics, service worker implementation или canonical inventory model.

## Capabilities

### New Capabilities

- `sensitive-offline-verification`: детерминированная классификация protected offline/state UI paths и объяснимое запрещение FAST.

### Modified Capabilities

- Нет.

## Impact

Изменяются только verification policy, нормативный planner contract, OpenSpec/delivery records и один зарегистрированный governance regression test. Runtime/product assets не изменяются. Canonical inventory остаётся `tools/verification/suites.tsv`; существующий зарегистрированный `tests/Yii2/yii2_inspection_browser_001_test.php`, исполняющий `inspection_browser.mjs`, служит прямым behavioral oracle.
