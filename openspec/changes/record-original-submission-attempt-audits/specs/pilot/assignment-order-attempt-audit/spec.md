## Purpose

Сохраняет каждую запрещённую попытку загрузки оригинала и безопасный аудит ошибок файла, не переписывая ранее принятые оригиналы или результаты запросов.

## ADDED Requirements

### Requirement: Каждая запрещённая попытка сохраняется отдельно

Система SHALL соблюдать ATTEMPT-AUDIT-001: проверять полномочия до чтения результата и сохранять отдельную audit row каждой valid-shape denied invocation. Существующий terminal result MUST остаться неизменным.

#### Scenario: Повтор отказа
- **WHEN** один request повторён дважды без прав в разные моменты
- **THEN** журнал содержит две строки с соответствующими actor/time, один terminal denial и ни одного original fact

#### Scenario: Отзыв прав после принятия
- **WHEN** принятую операцию повторяет actor после отзыва прав
- **THEN** новый отказ записывается отдельно, accepted evidence не раскрывается и не изменяется

### Requirement: Ошибки файла остаются повторяемыми

STREAM/STORAGE failure SHALL пытаться сохранить только безопасный audit; failure этого аудита MUST NOT заменить исходную ошибку или создать terminal request.

#### Scenario: Хранилище и аудит недоступны
- **WHEN** storage failure сопровождается неуспехом audit write
- **THEN** result остаётся retryable STORAGE_FAILURE, diagnostic безопасен и попытки записи/cleanup не повторяются

### Requirement: Миграция сохраняет историю

Canonical migration SHALL добавить original family v3 на проверенном frontier и сохранить прежние строки. Только описанные ограничения audit table изменяются; runtime DDL запрещён.

#### Scenario: Обновление заполненной базы
- **WHEN** exact v2 audit family обновляется и затем migration повторяется
- **THEN** все прежние audit IDs/values сохранены, повтор не делает DDL, новые repeated-denial и failure rows допустимы

#### Scenario: Неизвестная схема
- **WHEN** metadata отличается от допустимых v2/v3 состояний
- **THEN** migration не изменяет данные или схему и возвращает conflict
