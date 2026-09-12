## Why

Поставка PR #98 и корректировка PR #100 показали, что текущий delivery harness может допустить публикацию кандидата, который локально выглядит GREEN, но предсказуемо падает в первом exact-source CI из-за незамкнутых generated-source связей, неполного набора adjacent consumers, undeclared test dependencies или несовпадения suite с CI environment. Нужен bounded pre-publication контракт, который выявляет эти классы ошибок до push, не ослабляя Gates 1–5 и не запуская локально полный product matrix.

## What Changes

- Quality Graph plan транзитивно включает generator/template checks и известных consumers изменённых inventory, entrypoints, bootstrap/error-envelope, Dockerfile/Compose и verification catalogs.
- Verification catalog и evidence фиксируют runtime prerequisites команд и отклоняют suite/category, чья CI-среда уже фактических зависимостей теста.
- Добавляется bounded CI-parity preflight exact commit tree, блокирующий publication package при generator drift, missing inventory expectation и undeclared test dependency.
- Reviewer package различает acceptance evidence и обязательные boundary/category records из generated plan.
- Gate 3 package для post-implementation test delta связывает current GREEN с историческим intended RED и точным test delta без фиктивного нового RED.
- Source identity разделяется на executable candidate и review/lifecycle metadata, чтобы append-only verdicts и task checkboxes не инвалидировали evidence неизменённых executable bytes.
- Dependency workspace для ignored/generated trees становится явной частью package/evidence contract без включения `vendor` или аналогичных деревьев в deliverable source.
- Добавляются deterministic fixtures, воспроизводящие первичные failures PR #98 и category/environment mismatch PR #100.

## Capabilities

### New Capabilities

- `delivery/first-pass-ci-completeness`: Полнота verification plan, CI-equivalent preflight, типизированное reviewer evidence, test-delta review, executable source identity и dependency-workspace contract до публикации.

### Modified Capabilities

Нет.

## Impact

- Затрагиваются `tools/delivery/`, `tools/verification/`, их bounded tests/fixtures и machine-readable verification inputs.
- Публичные seams: `harness.py prepare/state`, generated role packages/plans, evidence records и pre-publication admission.
- Runtime продуктового приложения, Yii2/rapid-pilot domain code, рабочий стенд и deployment не меняются; работа изолирована от параллельного #76.
- Новые неявные Python/PHP/Node зависимости не допускаются; full product matrix остаётся единственным удалённым exact-source CI и локально не запускается.
