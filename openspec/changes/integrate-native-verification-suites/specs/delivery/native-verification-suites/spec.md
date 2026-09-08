## Purpose

Release engineer получает полный и воспроизводимый список native verification
семейств, которые действительно запускает canonical pipeline перед интеграцией.

## ADDED Requirements

### Requirement: Native suite membership and execution
Runner SHALL соблюдать VERIFICATION-NATIVE-SUITES-001 и включать standalone native
PHP и Node tests в существующие unit/db stages без пропуска прежних семейств.

#### Scenario: Native failure
- **WHEN** один native verifier возвращает failure
- **THEN** pipeline сообщает его путь, исполняет оставшиеся проверки и завершается failure

#### Scenario: Auditable membership
- **WHEN** release engineer вызывает list unit или list db
- **THEN** получает deterministic interpreter/path inventory без запуска tests/DB
