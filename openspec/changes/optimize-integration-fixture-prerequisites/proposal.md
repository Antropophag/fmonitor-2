## Why

Integration занимает около14минут. Фазовый профиль template fixtures показал, что неизменяемый PDF probe занимает лишь около5%, а тяжёлый database-setup тест повторяет десятки тысяч запросов метаданных. Issue55 требует измеренной экономии без потери сценариев и изоляции.

## What Changes

- Сохранить фазовый профиль fixtures и сопоставимые повторные прогоны тяжёлого database-setup теста.
- Читать пять семейств метаданных всей текущей БД пакетно для каждого schema snapshot вместо пяти запросов на каждую таблицу.
- Сохранить точные прежние поля, сортировки и нормализацию снимка, все свежие чтения строк и каждый предметный assertion.
- Доказать эквивалентность и чувствительность snapshot к изменению схемы отдельным DB тестом.

## Capabilities

### New Capabilities

Нет. Технический рефакторинг тестового наблюдателя; `skip_specs: true`.

### Modified Capabilities

Нет. Продуктовые контракты и CI-матрица не меняются.

## Impact

Actor: разработчик/CI. Source oracle: старые closures снимка в database-setup тесте и issue55.
Public seam: `BatchedSchemaSnapshot::read(mysqli, callable): array` в tests/Support, наблюдающий реальную MariaDB.
Release value: измеренное сокращение повторных metadata запросов.
Вне среза: кеширование снимков, shared mutable DB, пропуск миграционных assertions, CI sharding, publisher, production runtime.
Остальные направления issue55 остаются открытыми. Изначальная гипотеза о кешировании PDF probes отложена по измеренной малой доле.
