## Why

Gate 2 сейчас зависит от ручного выбора focused tests. Реальный diff может
разойтись с planned scope, а устаревший список команд не обнаруживается.

## What Changes

- Добавить repository-owned CLI, который до Gate 2 вычисляет обязательства из
  planned paths, acceptance seams и полного локального git delta.
- Связать план с graph/category inventory/policy/spec/input/source digests и
  проверять актуальность непосредственно перед исполнением.
- Не менять штатный Quality Graph publisher/CI и сохранить `make test` как
  обязательную полную интеграционную проверку.

## Capabilities

### New Capabilities
- `verification/change-obligations`: детерминированный focused plan и fail-closed
  validation для одного change.

## Impact

Новый seam `tools/delivery/change-verification.py`, policy в `.quality-graph/`,
нормативная executable spec и isolated contract test. Регистрация теста в
существующем inventory выполняется интегратором отдельно.

