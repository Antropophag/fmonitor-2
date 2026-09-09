## Why

Ускорение #25 поставлено PR43, но Quality Graph из закрытого PR37 не интегрирован.
Владелец требует закрыть #25 перед #66 и разрешил штатные комментарии/метки publisher.

## What Changes

- Срез QUALITY-GRAPH-CURRENT-CI-001: автор PR видит граф результатов уже выполняемых
  plan/fast/unit/integration/e2e/governance/verify без второго запуска тестов.
- Repository-owned публичный CLI формирует native reports из терминальных
  GitHub job outcomes и явно принятой full/docs-only политики.
- Штатный pinned collect создаёт Result artifacts с provenance; штатный trusted
  publisher публикует check, сводку и собственные метки.
- Проверки отрицательных сценариев и фактическая GitHub publisher matrix
  предшествуют закрытию #25. История PR37 сохраняется, old full-runner не переносится.

## Capabilities

### New Capabilities

- `delivery/current-ci-quality-graph`: однократный CI и достоверная сводка его результатов.

### Modified Capabilities

Нет.

## Impact

Oracle — текущий tools/verification/ci.py и Repository verification workflow,
матрица PR43, штатный Quality Graph 0.1.7 и история PR37. Изменяются CI/report
integration, declaration/checker, focused tests и delivery evidence. Ни продукт,
ни БД/стенд, ни protected assertions, ни правила допуска merge не меняются.
Не входит миграция старого machine-lineage подсистемы целиком: существующая
независимая spec/RED/review evidence остаётся обязательной и сохраняется.
