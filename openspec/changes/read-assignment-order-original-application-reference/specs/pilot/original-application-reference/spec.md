## Purpose

Application consumers получают проверенную immutable original reference и
подтверждают её актуальность под общим case lock без владения original persistence.

## ADDED Requirements

### Requirement: Original reference and current guard
Reader SHALL соблюдать ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001,
сохраняя read-only facts и caller-owned transaction boundaries.

#### Scenario: Corrected source
- **WHEN** original correction сменила current revision после snapshot lookup
- **THEN** guard сообщает changed и не пишет никаких фактов

#### Scenario: Guard holds case ownership
- **WHEN** caller подтвердил reference внутри своей write transaction
- **THEN** original writer ждёт release общего case lock, а commit/rollback остаётся у caller
