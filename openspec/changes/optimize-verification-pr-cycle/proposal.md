## Why

Issue25 доводится в одном готовом к merge PR. Явный каталог уже создан, но полный verify всё ещё повторяет E2E и bootstrap children, а GitHub показывает один длинный job.

## What Changes

- Убрать повторные самостоятельные contract calls из bootstrap; сохранить эти же тесты в полном каталоге. E2E только в e2e-группе.
- Добавить явную карту категорий unit/integration/e2e/governance и публичные plan/list/run/aggregate команды; make verify остаётся совместимым полным harness.
- Матрица принята владельцем в этой сессии: локально focused; быстрый CI всегда, полный один раз для кода/тестов/CI/неизвестного влияния и schedule/manual/release; документация без полного CI.
- Раздельные jobs используют изолированные GitHub runners, БД и артефакты. Итоговый verify проверяет все ожидаемые результаты, не скрывает failure/cancel/skip.

## Capabilities

### New Capabilities
- `verification/pr-cycle`: выбор, классификация, однократное исполнение и агрегация проверок.

## Impact

Публичный seam tools/verification/run.sh и ci.py, Makefile, GitHub workflow, проверочная композиция bootstrap. Runtime, business assertions, branch protection и publisher PR37 не меняются. Oracle — suites.tsv c7b7406 и явное owner решение о матрице. Это продолжение #25; отдельный architecture migration не запускается.
