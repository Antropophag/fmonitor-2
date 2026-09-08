## Purpose

Canonical consumer verifiers проверяют текущий одобренный frontier и прежние
исторические инварианты, не блокируясь на устаревшей версии migration runner.

## ADDED Requirements

### Requirement: Current frontier keeps strict predecessor checks
Verifier reconciliation SHALL соблюдать VERIFICATION-CANONICAL-FRONTIER-015;
exact positive terminal15 дополняет, а не отменяет прежние metadata/history checks.

#### Scenario: Clean current canonical runner
- **WHEN** consumer вызывает полный runner на пустой synthetic DB
- **THEN** ожидает exact1..15 и все прежние family assertions

#### Scenario: Scoped predecessor composition
- **WHEN** verifier намеренно исполняет composition только до11
- **THEN** expectation остаётся11, а более поздний frontier не подменяет смысл проверки
