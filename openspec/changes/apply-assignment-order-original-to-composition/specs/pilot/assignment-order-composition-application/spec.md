## Purpose

Применение принятого оригинала создаёт подтверждённые назначения через одного
application owner, сохраняя историю и отдельность открытия монтажного дела.

## ADDED Requirements

### Requirement: Explicit immutable composition application
Application SHALL соблюдать ASSIGNMENT-ORDER-COMPOSITION-APPLY-001 после его
Gate1 approval, включая authorization, request replay, case locking и exact failures.

#### Scenario: Original corrected before opening
- **WHEN** новый current original отдельно применяют до открытия с актуальным expected sequence
- **THEN** новый application fact ссылается на прежний, который остаётся неизменным.

#### Scenario: Original corrected after opening
- **WHEN** пытаются повторно применить тот же order после открытия
- **THEN** действие отклоняется, исторические assignments/checklist не переписываются.

#### Scenario: Accepted new sequential order
- **WHEN** допустимое новое распоряжение применяют явно
- **THEN** current crew/engineer происходят из нового application, а не из legacy slots.

### Requirement: Honest employment evidence
Application SHALL оставлять неизвестную дату приёма неизвестной и требовать
подтверждённый полный current workforce snapshot для разрешения такого назначения.

#### Scenario: Unknown start with full current employed snapshot
- **WHEN** текущий employed status подтверждён полным снимком и остальные условия соблюдены
- **THEN** отсутствие даты само по себе не блокирует application и sync day не подставляется.
