## Why

Агент или оператор в новом worktree сейчас не может выполнить зарегистрированный Yii2 integration-тест одной prepared-командой: host-запуск последовательно упирается в отсутствующий `vendor`, неподнятую MariaDB и соседний checkout `shlz-ui`. Это провоцирует нештатные ссылки и host-fallback вместо проверки точного candidate snapshot.

## What Changes

- Действующий prepared-plan → harness → `run-in-profile` маршрут для зарегистрированного `tests/Yii2/yii2_main_navigation_001_test.php` сам выбирает контейнерный профиль, строит pinned dependencies и запускает только этот тест.
- Профиль владеет изолированным lifecycle тестовой MariaDB: поднимает её, ограниченно ждёт health/readiness и удаляет только ресурсы данного запуска.
- Yii HTTP получает pinned `shlz-ui` assets из container image, не из соседнего checkout и не из host `node_modules`.
- Ошибки Docker, dependency build и DB readiness классифицируются как `SETUP_FAILURE` до assertions и сохраняются обычным harness evidence store без изменения command identity/acceptance mapping.
- Добавляются адресные executable проверки первого/повторного запуска, source isolation, controlled candidate mutation и негативных setup-сценариев.
- Не меняются продуктовая навигация, error handler, схема данных, FAST classifier, review/admission policy и evidence schema.

## Capabilities

### New Capabilities

- `delivery/registered-focused-bootstrap`: автономный, exact-source bootstrap одного зарегистрированного Yii2 integration-теста через существующий delivery runner.

### Modified Capabilities


## Impact

Затрагиваются только existing verification planning/runner registrations, `tools/delivery/run-in-profile`, focused-check image/compose wiring, Yii2 test fixture asset lookup, focused executable tests и delivery documentation. На host остаются только уже допустимые Git, Docker/Compose и launcher prerequisites; host PHP/Composer/Node и соседний `shlz-ui` не являются fallback.
