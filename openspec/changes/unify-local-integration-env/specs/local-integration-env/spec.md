## Purpose

Определяет единый безопасный операторский контракт `.env` для локального legacy import и Bitrix workforce sync с проверкой до внешних эффектов и приватным runtime-staging.

## ADDED Requirements

### Requirement: Один операторский файл конфигурации
Система SHALL принимать настройки локальных legacy import и Bitrix workforce sync из корневого `.env`, создаваемого из `.env.example`; оператор MUST NOT вручную создавать `.local/legacy-source.env` или `.local/bitrix-workforce.json`.

#### Scenario: Полный безопасный шаблон
- **WHEN** оператор копирует `.env.example` в `.env`
- **THEN** шаблон содержит без реальных секретов `FMONITOR_SOURCE_HOST`, `FMONITOR_SOURCE_PORT`, `FMONITOR_SOURCE_NAME`, `FMONITOR_SOURCE_USER`, `FMONITOR_SOURCE_PASSWORD`, `FMONITOR_MIGRATION_CUTOFF`, `FMONITOR_BITRIX_WEBHOOK_URL` и `FMONITOR_BITRIX_DEPARTMENT_IDS_JSON`

#### Scenario: Legacy import из единого файла
- **WHEN** оператор заполнил корректные legacy-параметры в `.env` и запускает `make import-legacy`
- **THEN** команда использует эти значения без предварительного ручного создания другого конфигурационного файла

#### Scenario: Workforce sync из единого файла
- **WHEN** оператор заполнил корректные Bitrix-параметры в `.env` и запускает `make sync-workforce`
- **THEN** команда использует эти значения без предварительного ручного создания другого конфигурационного файла

#### Scenario: Сквозной quickstart
- **WHEN** на новой машине оператор заполнил все обязательные значения в одном `.env` и запускает `make up-with-data`
- **THEN** система последовательно поднимает runtime, импортирует legacy-данные и синхронизирует workforce без дополнительных ручных конфигурационных файлов

### Requirement: Fail-closed preflight до внешних эффектов
Система SHALL до запуска Docker Compose, сетевого запроса или подключения к source/target DB полностью проверить применимый набор интеграционных параметров и завершиться fail closed при ошибке.

#### Scenario: Корректная legacy-конфигурация
- **WHEN** host, port в диапазоне `1..65535`, database, user и password непусты, а cutoff пуст либо имеет формат `YYYY-MM-DD HH:MM:SS`
- **THEN** legacy preflight допускает запуск существующего read-only import owner

#### Scenario: Некорректная legacy-конфигурация
- **WHEN** отсутствует обязательное legacy-значение, port не является каноническим десятичным числом в диапазоне `1..65535`, cutoff не соответствует разрешённому формату, ввод содержит `$`/backtick shell expression либо password невозможно lossless представить существующему consumer
- **THEN** команда завершается до любых сетевых и DB effects с ненулевым статусом и безопасной причиной без значения секрета

#### Scenario: Корректная Bitrix-конфигурация
- **WHEN** webhook является абсолютным HTTPS URL ожидаемого Bitrix REST webhook и departments является непустым JSON-массивом уникальных положительных целых ID
- **THEN** Bitrix preflight допускает запуск существующего workforce sync owner

#### Scenario: Некорректная Bitrix-конфигурация
- **WHEN** webhook не использует exact lowercase `https`, содержит user-info, token вне `[A-Za-z0-9_-]{1,256}` либо другую неверную структуру, или departments не является непустым JSON-массивом уникальных положительных целых ID
- **THEN** команда завершается до любых сетевых и DB effects с ненулевым статусом и безопасной причиной без webhook, token или иных введённых значений

#### Scenario: Проверяется только вызываемая интеграция
- **WHEN** оператор запускает `make import-legacy` или `make sync-workforce` отдельно
- **THEN** preflight требует полный набор только выбранной интеграции и не требует настройки другой интеграции

### Requirement: Приватный атомарный runtime-staging
Если существующим runtime-boundaries требуются отдельные файлы, система SHALL создавать их автоматически в исключённом из Git приватном каталоге, атомарно заменять завершённым содержимым и устанавливать каталогу mode `0700`, а файлу mode `0600` до передачи consumer.

#### Scenario: Первый запуск
- **WHEN** корректная интеграционная команда запускается без ранее подготовленного runtime-файла
- **THEN** система создаёт приватный каталог и завершённый runtime-файл с требуемыми permissions до запуска consumer

#### Scenario: Изменение конфигурации
- **WHEN** оператор изменяет корректные значения в `.env` и повторяет интеграционную команду
- **THEN** consumer получает новую полную конфигурацию без `make reset`, удаления volumes или потери ранее импортированных данных

#### Scenario: Ошибка обновления
- **WHEN** новая конфигурация неверна или запись нового runtime-файла не может быть безопасно завершена
- **THEN** система не запускает consumer и не выдаёт частично записанный файл за актуальную конфигурацию

#### Scenario: Одновременная подготовка
- **WHEN** два процесса одновременно готовят один и тот же runtime-файл
- **THEN** каждый consumer видит только один полностью сформированный валидный файл, а не смешанное или частичное содержимое

### Requirement: Секреты не покидают приватную границу
Система MUST NOT передавать legacy password или Bitrix webhook/token через argv, stdout/stderr, итоговый `docker compose config`, image layer или Git; runtime consumer SHALL получать секрет через приватный read-only file boundary.

#### Scenario: Успешный запуск
- **WHEN** интеграционная команда успешно проходит preflight и запускает consumer
- **THEN** секретные значения отсутствуют в argv и stdout/stderr, а consumer читает приватный runtime-файл по существующему file-based contract

#### Scenario: Ошибка запуска
- **WHEN** подготовка, Docker Compose или consumer завершается ошибкой
- **THEN** опубликованная диагностика не содержит password, полного webhook/token или содержимого приватного runtime-файла

#### Scenario: Репозиторий и образ
- **WHEN** подготовлены `.env` и внутренние runtime-файлы
- **THEN** они исключены из Git и build context и не оказываются в слоях runtime image

### Requirement: Владельцы данных и история сохраняются
Изменение SHALL оставлять существующие Yii2 import/sync application seams владельцами effects, сохранять read-only доступ к legacy source, существующую идемпотентность и append-only workforce/import history; bootstrap SHALL только валидировать и адаптировать конфигурацию.

#### Scenario: Повтор без изменения настроек
- **WHEN** оператор повторяет import или sync с тем же `.env`
- **THEN** результат следует существующим правилам идемпотентности и не создаёт новый альтернативный owner доменных фактов

#### Scenario: Bootstrap не владеет данными
- **WHEN** bootstrap формирует приватный runtime-файл
- **THEN** он не читает и не изменяет product database, legacy rows или workforce history

### Requirement: Документация описывает один путь
Операторская документация SHALL описывать `.env.example` → `.env` → `make up-with-data` как единственный актуальный локальный путь и SHALL отличать внутренние автоматически создаваемые runtime-файлы от операторских входов.

#### Scenario: Оператор следует quickstart
- **WHEN** оператор читает `.env.example` и документацию Bitrix/local startup
- **THEN** инструкции согласованы, не требуют ручного создания `.local/legacy-source.env` или `.local/bitrix-workforce.json` и объясняют безопасное повторное применение изменённых настроек
