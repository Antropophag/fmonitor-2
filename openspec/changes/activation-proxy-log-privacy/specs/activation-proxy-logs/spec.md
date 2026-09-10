## ADDED Requirements

### Requirement: Private activation proxy diagnostics

Система SHALL выполнять [ACTIVATION-PROXY-LOG-001](../../../../../specs/ACTIVATION-PROXY-LOG-001.md).

#### Scenario: FastCGI unavailable

- **WHEN** activation request содержит секретную ссылку, а FPM недоступен
- **THEN** клиент видит502, logs сохраняют safe operational metadata без token

#### Scenario: Ordinary failure

- **WHEN** обычный nonsensitive route получает upstream failure
- **THEN** safe access log и nginx error diagnostic сохраняются
