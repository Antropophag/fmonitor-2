## Why

Локальный и корпоративный quickstart сейчас требует вручную создавать два внутренних приватных файла помимо `.env`, поэтому операторский контракт распределён между реализационными деталями и документацией. №149 делает один `.env` единственной точкой ввода настроек legacy import и Bitrix workforce sync, сохраняя существующие безопасные файловые boundaries внутри runtime.

## What Changes

- Добавить полный безопасный шаблон параметров legacy source и Bitrix в `.env.example` без реальных секретов.
- На публичных seam `make import-legacy`, `make sync-workforce` и `make up-with-data` читать операторские настройки только из `.env`, валидировать их до сетевых и DB effects и fail closed с безопасной диагностикой.
- Атомарно формировать необходимые существующим adapters приватные runtime-файлы в исключённом из Git каталоге с mode `0600`; повторный запуск применяет актуальные значения `.env` без reset данных.
- Согласовать quickstart-документацию с единым актуальным путём и удалить требование ручного создания внутренних файлов.
- Сохранить существующие import/sync owners, read-only legacy access, идемпотентность и append-only историю.

## Capabilities

### New Capabilities

- `local-integration-env`: Единый безопасный операторский контракт `.env`, preflight и приватный staging для локальных legacy/Bitrix интеграций.

### Modified Capabilities

Нет.

## Impact

Затрагиваются `.env.example`, Make targets локальных интеграций, `tools/delivery/local-integration-config`, quickstart-документация и focused verification. Публичные application-команды Yii2 и форматы их приватных конфигурационных файлов остаются без изменений; product state, schema, backup/restore и production secret-management не меняются.
