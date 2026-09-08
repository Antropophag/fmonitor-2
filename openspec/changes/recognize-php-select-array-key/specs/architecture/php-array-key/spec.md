## ADDED Requirements
### Requirement: PHP array keys preserve SQL enforcement
Checker SHALL соблюдать ARCHITECTURE-PHP-SELECT-ARRAY-KEY-001 без изменения baseline.
#### Scenario: Bitrix selected fields key
- **WHEN** PHP request содержит `select` key перед `=>`
- **THEN** имя ключа не даёт SQL violation, но SQL в значении или рядом отклоняется.
