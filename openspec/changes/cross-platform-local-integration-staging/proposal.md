## Why

После merge #189 действующие `make import-legacy` и `make sync-workforce` передают подготовленный host-файл прямым bind mount. При несовпадающих host/container UID файл `0600` недоступен штатному non-root пользователю контейнера, а exact-mode проверки дополнительно отклоняют корректные доступные файлы и каталоги на POSIX, WSL и Docker Desktop.

## What Changes

- Заменить exact `0600`/`0700` требования локального integration staging проверкой типа, отсутствия symlink, доступности и валидного формата, сохранив атомарную публикацию.
- Для обоих Make-targets доставлять snapshot из host staging в container-owned private regular file, читаемый UID 10001, не меняя owner/mode исходного `.env` или host snapshot.
- Удалять container-side snapshot после успеха и ошибки, не маскируя exit status импортёра; повторная подготовка должна атомарно заменить старые значения.
- Обновить downstream PHP loaders, чтобы корректный доступный snapshot не был повторно отклонён по exact mode.
- Проверить реальные legacy import и workforce sync на синтетической MariaDB и локальном Bitrix endpoint без production-соединений и утечек секретов.
- Не менять production runtime/session file policies, алгоритмы harness/classifier, архитектурные baseline, импортные фильтры или другие срезы #185.

## Capabilities

### New Capabilities

- `local-integration-staging`: Cross-platform подготовка и private container delivery конфигурации для действующих local legacy/workforce Make seams.

### Modified Capabilities

Нет существующей OpenSpec capability; нормативный контракт `LOCAL-INTEGRATION-ENV-001` обновляется адресно.

## Impact

Затронуты `tools/delivery/local-integration-config`, `Makefile`, runtime Compose/template, PHP loaders, связанные deployment/integration tests, verification ownership и операторская документация. Публичные seams и форматы `.env`, legacy env snapshot и Bitrix JSON сохраняются.
