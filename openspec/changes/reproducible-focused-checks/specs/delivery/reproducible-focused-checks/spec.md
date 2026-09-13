## ADDED Requirements

### Requirement: Reproducible focused checks

Harness SHALL выполнять закрытый контракт `specs/DELIVERY-EXECUTION-107-I2.md`
через общий container route.

#### Scenario: Registered check executes reproducibly

- **WHEN** delivery agent запускает registered governance, integration или browser check
- **THEN** harness использует observed pinned environment и isolated run-owned resources

#### Scenario: Environment or ownership mismatch is rejected

- **WHEN** runtime/package/platform/assets не совпадают либо ресурс не принадлежит run
- **THEN** launcher возвращает SETUP_FAILURE и не использует host fallback или broad cleanup
