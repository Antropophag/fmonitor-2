## ADDED Requirements

### Requirement: Bounded health session reuse
Docker SHALL следовать PILOT-HEALTHCHECK-SESSION-001: повторять anonymous checks
без роста session files; ошибки HTTP/storage не превращаются в healthy.

#### Scenario: Repeated probe
- **WHEN** после первого success выполняются20 проверок
- **THEN** exit0 и неизменны session/lock counts и bytes

#### Scenario: Unavailable route
- **WHEN** любой проверяемый путь возвращает503
- **THEN** probe возвращает exit1 без секретов в output
