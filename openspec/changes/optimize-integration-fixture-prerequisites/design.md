## Context

См. proposal.md. Изменение принадлежит tests/Support; production код не меняется.
Старый snapshot читает properties/columns/keys/FK/checks отдельно для каждой таблицы.
Каждый before/after обязан независимо наблюдать текущие metadata и строки.

## Goals / Non-Goals

**Goals:** заменить N*5 metadata запросов пятью запросами на всю текущую БД с точным сохранением формы/значений snapshot.

**Non-Goals:** кеш metadata/data, shared DB, ослабление zero-DML/DDL assertions, новые CI jobs, изменение предметных правил.

## Decisions

- Пять свежих запросов information_schema: TABLES (включая имена), COLUMNS, STATISTICS, KEY_COLUMN_USAGE/REFERENTIAL_CONSTRAINTS, CHECK_CONSTRAINTS. Результаты группируются по TABLE_NAME в PHP.
- Возвращается прежний словарь таблиц в бинарном порядке с ключами table/columns/keys/foreignKeys/checks. Порядок колонок — ORDINAL_POSITION; nullable default SQL NULL нормализуется как прежде; индексы/FK/CHECK сортируются прежними PHP операциями.
- Normalizer CHECK передаётся явным callable из старого теста; текущая boolean normalization не переписывается.
- Каждый вызов читает заново, не изменяет БД; нет static cache. Прежние row reads остаются свежими в stateSnapshot, включая чужие таблицы.
- Миграции, DDL/concurrency/полный перебор полей остаются неизменными. Сначала тест на независимой literal schema и мутациях, затем независимый Gate3, минимальная реализация и Gate5.
- Отдельная проверка эквивалентности старому наблюдателю проводится на реальном полном matrix до удаления временной инструментализации.
- Три последовательных baseline и три after на одной машине/БД; фазовые профили отдельно от benchmark. Production owners/HTTP/rapid-pilot и architecture baseline неизменны.

## Risks / Trade-offs

- [Потеря порядка или различий NULL/CHECK/FK] → literal regression по каждому семейству и сравнение со старым snapshot на реальных схемах.
- [Скрытое кеширование пропустит DDL] → повторные чтения после add/drop/alter и сохранённый полный matrix.
- [Шум VM/сети] → последовательные повторения на одном контейнере; локальные и CI результаты разделяются.

## Migration Plan

Test-support PR; обычный revert без миграции БД или стенда.
