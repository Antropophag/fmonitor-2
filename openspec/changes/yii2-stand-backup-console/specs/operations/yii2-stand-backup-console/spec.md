## Purpose

Определяет Yii2/PHP console owner для безопасного создания и независимой проверки backup bundle перед будущим переключением production runtime.

## ADDED Requirements

### Requirement: Backup принадлежит Yii2 console runtime
Production seam SHALL быть `php bin/yii stand-backup/create|verify`; application и filesystem protocol MUST выполняться PHP-кодом общей Yii2 composition. Production entrypoint MUST NOT запускать либо импортировать Python и `rapid-pilot`.

#### Scenario: Запуск команды
- **WHEN** operator вызывает backup через `php bin/yii`
- **THEN** Yii2 console controller вызывает единственный PHP application owner и возвращает safe outcome

### Requirement: Exact verified bundle сохраняет crash-consistent history
PHP owner MUST валидировать exact target, самостоятельно хешировать DB/artifact/session bytes, атомарно публиковать content-addressed bundle и сохранять idempotent operation history. Invalid target, corruption, concurrency и filesystem ambiguity MUST завершаться fail closed без destructive effect и без изменения прежнего verified bundle.

#### Scenario: Повтор после потерянного ответа
- **WHEN** тот же operation id повторён после durable outcome
- **THEN** команда возвращает тот же результат без повторного backup effect

### Requirement: Output и runtime boundary безопасны
Console command SHALL выдавать ровно один JSON object, не раскрывать credentials, paths или payload и использовать non-zero exit для rejected/unknown outcomes. Python MAY использоваться только внешним black-box test/harness процессом.

#### Scenario: Ошибка filesystem
- **WHEN** PHP filesystem port сообщает definite либо unknown failure
- **THEN** Yii2 command возвращает canonical safe JSON без traceback и сохраняет фазово корректное evidence state
