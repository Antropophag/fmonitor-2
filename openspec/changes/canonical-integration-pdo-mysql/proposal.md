## Why

Canonical public `integration` profile уже загружает frozen candidate, Composer и Yii, но реальный DB-backed Yii route на актуальном `main` останавливается до application behavior с `PDOException: could not find driver`: focused-check image содержит PDO/pdo_sqlite, но не `pdo_mysql`. Это uncovered infrastructure blocker, обнаруженный при #20, который должен быть устранён отдельным bounded slice без продолжения #20 и без изменения product behavior.

## What Changes

- Добавить `pdo_mysql` в существующий canonical focused-check image, сохранив закреплённые PHP/MariaDB версии и существующие profiles.
- Зафиксировать executable regression, который проверяет наличие `pdo_mysql` внутри реально исполняемого image и запускает DB-backed Yii user-access test через public `integration` seam с disposable MariaDB.
- Сохранить bootstrap/source-isolation contracts #123-A/#167, включая representative K/L/M regressions и отсутствие host PHP/vendor fallback.
- Оставить test DB port изолируемым и настраиваемым; известный конфликт host port `23306` не является частью этого slice.
- После focused verification, обязательных независимых reviews и одного exact-source CI подготовить отдельный PR-ready candidate; merge не выполнять.

## Capabilities

### New Capabilities

- `delivery/canonical-integration-runtime`: Canonical `integration` profile предоставляет container-owned PHP MySQL PDO runtime, достаточный для реального DB-backed Yii execution через существующий public launcher.

### Modified Capabilities

- Нет. Существующие #123-A/#167 contracts сохраняются как regression invariants, их требования не изменяются.

## Impact

- Actor: разработчик или existing CI consumer, запускающий focused DB-backed Yii verification.
- Source oracle: owner request от 2026-09-16 и воспроизводимый blocker на актуальном `origin/main` `992e32f1`.
- Target public seam: `tools/delivery/run-in-profile integration <command> [args...]`.
- Затрагиваются только existing focused-check image recipe, bounded executable verification, OpenSpec/delivery/review evidence и существующий CI consumer.
- Release value: integration profile доходит до ожидаемого application behavior с disposable MariaDB, а не возвращает infrastructure 503 из-за отсутствующего PDO driver.
- Не затрагиваются application/product behavior, #20, весь #123, новый Docker profile/environment manager, production runtime, версии MariaDB/PHP и unrelated Docker optimization.
