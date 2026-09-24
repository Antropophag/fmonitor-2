## Purpose

Определяет безопасный публичный deployment contract, который преобразует существующую Bitrix webhook-конфигурацию в runtime-параметры document-links worker без раскрытия токена.

## ADDED Requirements

### Requirement: Legacy webhook config upgrades to the document runtime contract
Система SHALL принимать канонический `FMONITOR_BITRIX_WEBHOOK_URL` вида `https://HOST/rest/USER_ID/TOKEN/`, атомарно публиковать один private config и перед запуском worker извлекать из него origin, положительный decimal webhook user ID и короткоживущий private token file. Повторная подготовка того же входа SHALL быть идемпотентной и не изменять product state.

#### Scenario: Existing canonical env is staged
- **WHEN** оператор запускает canonical staging с валидным webhook URL и настроенным document root ID
- **THEN** worker получает exact HTTPS origin, decimal webhook user ID, root ID и читаемый временный private token file из одного staged config, а исходный webhook URL и token не передаются в Compose environment или argv worker

#### Scenario: Quoted legacy value is accepted
- **WHEN** webhook URL целиком заключён в поддерживаемые одинарные или двойные кавычки
- **THEN** staging создаёт тот же runtime contract, что и для эквивалентного unquoted значения

### Requirement: Enabled document integration fails closed before worker startup
Если document-links integration включена настроенным root ID, система MUST завершать staging ненулевым кодом до запуска worker при отсутствующем, пустом, нечитаемом, symlink или не-regular token source. Активная интеграция MUST не подменять отсутствующий secret `/dev/null` или пустым файлом.

#### Scenario: Token source is absent
- **WHEN** document root ID настроен, но token source отсутствует
- **THEN** staging завершается fail-fast стабильным безопасным reason без запуска worker и без изменения ранее опубликованного runtime secret

#### Scenario: Token source is invalid
- **WHEN** token source пуст, нечитаем, является symlink или не является regular file
- **THEN** staging завершается тем же fail-closed классом результата, не публикует частичный contract и не раскрывает путь или содержимое секрета

#### Scenario: Integration is not configured
- **WHEN** document root ID отсутствует
- **THEN** document-links integration остаётся выключенной без создания фиктивного token mount и без влияния на workforce sync contract

### Requirement: Runtime secret publication is private and atomic
Staging SHALL полностью валидировать вход до одной атомарной публикации private config и сохранять прежний валидный config побайтно неизменным при любом отказе. Worker SHALL создавать token file с доступом только runtime identity непосредственно перед использованием и удалять его после остановки execution seam. Диагностика SHALL содержать только стабильный код и имя группы настройки, но MUST не содержать webhook URL, token, document URL или secret payload.

#### Scenario: Successful rotation
- **WHEN** оператор предоставляет новый валидный webhook config
- **THEN** новый complete config становится доступен worker одной атомарной заменой, worker использует только соответствующие ему origin/user/token, временные файлы удаляются, а stdout/stderr не содержит старый или новый marker token

#### Scenario: Failed rotation preserves current secret
- **WHEN** новый config не проходит parsing или validation
- **THEN** ранее опубликованный config остаётся побайтно неизменным и worker не получает частично обновлённые origin/user/token параметры

### Requirement: Document delivery reaches the configured API seam
После успешного staging runtime SHALL передать `BitrixOrderDocumentDelivery` согласованные origin, webhook user ID, root ID и token file. Read-only fetch SHALL использовать настроенный root folder и при полном валидном ответе передавать complete result существующему publication owner; ошибки SHALL сохранять прежнюю projection согласно существующему document-links contract.

#### Scenario: Synthetic configured root fetch succeeds
- **WHEN** валидный staged contract используется с синтетическим read-only Bitrix transport и configured root folder
- **THEN** delivery запрашивает configured root, успешно возвращает complete links result и не выводит credential material

#### Scenario: Production outcome is not inferred
- **WHEN** локальные deterministic checks успешны, но exact production adapter и worker не проверены
- **THEN** production fetch, job status, publication rows и отображение карточки остаются `UNKNOWN`, а не `GREEN`
