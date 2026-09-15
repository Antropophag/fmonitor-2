# SENSITIVE-OFFLINE-VERIFICATION-001

Простыми словами: изменения клиентских файлов, которые хранят offline операции/фото, синхронизируют очередь или управляют private checklist cache между пользователями, не являются «только оформлением» и не могут получить FAST. Обычные presentation-only Yii изменения сохраняют прежнюю возможность FAST.

## Актор и публичный seam

Актор — автор delivery change. Публичный seam — `python3 tools/delivery/change-verification.py plan` с repository-owned `.quality-graph/verification-policy.json` и canonical `tools/verification/suites.tsv`; observable output — JSON plan либо fail-closed `SETUP_FAILURE`.

## Нормативные требования

1. Exact paths `app/YiiRuntime/Assets/checklist-sw.js`, `app/YiiRuntime/Assets/checklist.js` и `app/YiiRuntime/Assets/control-queue.js` SHALL принадлежать одной однозначной named sensitive boundary и SHALL NOT принадлежать FAST boundary.
2. Любой effective change с этой boundary SHALL выбрать `CRITICAL`, `required_reviews=["gate3","final"]` и machine-readable escalation с конкретным boundary name. Добавление bounded UI path или GREEN direct oracle SHALL NOT снизить lane.
3. Sensitive boundary SHALL выбрать существующий registered direct oracle `tests/Yii2/yii2_inspection_browser_001_test.php` (исполняющий `inspection_browser.mjs`) и его canonical inventory category. Отдельный inventory/roster SHALL NOT создаваться.
4. Изменение `.quality-graph/verification-policy.json` SHALL оставаться delivery-policy/CRITICAL и не может использовать FAST.
5. Healthy presentation-only bounded UI asset SHALL сохранить FAST при выполнении остальных условий. Server-rendered presentation-only Yii view и docs/lifecycle metadata SHALL NOT получать sensitive escalation только из-за соседства с checklist functionality.
6. Sensitive classification SHALL быть deterministic repository path policy: без runtime LLM, AST/function/line judgement. Смешанный `checklist.js` целиком sensitive. Unknown или ambiguous boundary SHALL fail closed, а не FAST.
7. Semantic integration closure #153A SHALL композироваться независимо и монотонно: совместный sensitive + semantic change сохраняет CRITICAL boundary escalation, semantic escalation и объединённые required categories/checks; ни один механизм SHALL NOT отменять более строгий результат другого.

## Rejected cases и неизменяемые факты

- Unknown/ambiguous protected-area path: `SETUP_FAILURE`, plan отсутствует.
- Missing/wrong-category registered direct oracle: существующая strict policy/inventory validation отклоняет plan.
- Planner не изменяет runtime state, authorization, offline format, queue/replay semantics, cache contents или audit/history.

## Примеры

- Только `checklist-sw.js` → `CRITICAL`, reason `sensitive-offline-ui`, e2e oracle выбран.
- `checklist.js` + `pilot.css` → `CRITICAL`, не FAST.
- Только `pilot.css` → `FAST` при остальных healthy FAST условиях.
- `control-queue.js` + `app/Infrastructure/Persistence/Store.php` → `CRITICAL` плюс `persistence-semantics` integration closure.
