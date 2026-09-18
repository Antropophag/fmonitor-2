## Why

После добавления migration v30 предметные PHP-тесты, которым нужна лишь корректно подготовленная актуальная БД, потребовали механического обновления собственных literal-ожиданий версии. Это создаёт повторяемую стоимость и риск пропущенных consumers, хотя независимый oracle актуального каталога уже принадлежит профильному migration contract.

## What Changes

- Выделить минимальный test-support контракт актуального schema frontier с независимым literal-ожиданием.
- Перевести выбранные setup/replay assertions PHP-тестов, затронутых переходом 29 → 30 в #192, на общий контракт, сохранив предметные assertions, пустой `appliedVersions` при повторе, изоляцию, cleanup и concurrency.
- Сохранить literal historical upgrade/recovery и профильные catalog assertions; доказать чувствительность frontier-проверки к отсутствующей последней и промежуточной migration адресными дефектными вариантами.
- При необходимости адресно зарегистрировать helper/проверки в существующей verification policy без изменения classifier, lane или admission rules.

## Capabilities

### New Capabilities

Нет. Это test-only refactoring и усиление regression oracle без изменения поведения продукта; change помечен `skip_specs: true`.

### Modified Capabilities

Нет.

## Impact

Изменения ограничены `tests/**/*.php`, `tests/Support`, OpenSpec/verification artifacts и, только если потребуется для существующего маршрута, адресной policy-регистрацией. `app/**`, DDL, production migration/recovery, Python/Compose fixtures и рабочий стенд не меняются. Источник oracle — независимый тестовый контракт; production catalogue и результат runner не определяют expected frontier.
