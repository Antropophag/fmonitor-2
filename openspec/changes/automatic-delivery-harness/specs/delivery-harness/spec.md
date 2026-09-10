## ADDED Requirements
### Requirement: Automated delivery mechanics
Harness SHALL выполнять нормативный specs/DELIVERY-HARNESS-001.md, сохраняя независимость reviews и авторизацию.
#### Scenario: Ordinary task or resume
- **WHEN** владелец поручает реализацию или продолжение
- **THEN** поддерживаемая интеграция предоставляет актуальный контекст и маршрут существующих gates; UNKNOWN не превращается в подтверждение.
