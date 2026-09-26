## Purpose

Обеспечивает единое read-only представление штатно завершённых новых монтажных дел без миграции или изменения сохранённых фактов.

## ADDED Requirements

### Requirement: Persisted completed имеет единое runtime-представление

Система SHALL показывать дело с `process_state=completed` как «Работы завершены» в shared current status и weekly FKR. Operational dashboard SHALL включать его в completed stage и SHALL исключать из active и unfinished-overdue. Read paths MUST не создавать и не изменять факты.

#### Scenario: Новое дело штатно завершено
- **WHEN** первая допустимая декларация штатно переводит новое дело в `completed`
- **THEN** shared status, dashboard и weekly report показывают завершение согласованно, а повторные reads byte-equivalent

#### Scenario: Паспорт остаётся legacy-read
- **WHEN** завершённое дело отображается в runtime-проекции
- **THEN** идентификатор, адрес, регистрационный номер и сроки продолжают читаться через существующую связь с `fm_maintable`

### Requirement: Writers не расширяются

Checklist mutations SHALL по-прежнему требовать exact `working`; документные corrections SHALL сохранять действующие append-only правила completed-дел.

#### Scenario: Checklist после завершения
- **WHEN** actor пытается изменить checklist дела `completed`
- **THEN** команда отклоняется без новых operations, attribution, revision или state facts
