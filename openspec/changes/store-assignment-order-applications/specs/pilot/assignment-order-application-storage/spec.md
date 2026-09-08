## Purpose

Хранить неизменяемые применения состава и попытки команды отдельно от выбора
и оригинала, с безопасным additive deployment без изменения прежних фактов.

## ADDED Requirements

### Requirement: Exact additive application storage
Migration SHALL соблюдать ASSIGNMENT-ORDER-APPLICATION-SCHEMA-001 и не создавать
domain facts, grants или canonical version до соответствующих integration gates.

#### Scenario: Conflicting second table
- **WHEN** первая таблица отсутствует, а вторая имеет неподходящую форму
- **THEN** preflight возвращает conflict без создания первой и без изменения существующей.

#### Scenario: Interrupted exact empty prefix
- **WHEN** applications создана и пуста, attempts отсутствует
- **THEN** создаётся только attempts, прежняя DDL/counter сохраняется.

#### Scenario: Complete populated family
- **WHEN** обе таблицы exact и уже содержат facts
- **THEN** повтор не меняет rows/DDL/counters и возвращает appliedfalse.
