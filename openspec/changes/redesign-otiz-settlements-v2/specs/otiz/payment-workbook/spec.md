# OTIZ payment workbook

## ADDED Requirements

### Requirement: Workbook SHALL reproduce the saved server revision
XLSX MUST use saved calculation inputs, claims, contributions, decisions, deductions, allocations and obligations, independent of UI grouping/filter/page and current object-card values.

#### Scenario: Historical values do not drift
- **WHEN** address, norm, workforce status or composition changes after acceptance
- **THEN** historical workbook source amounts and recipients stay identical while current payment state, if shown, is explicitly timestamped as export-time context

### Requirement: Workbook modes SHALL be distinguishable
Draft export MUST say `Черновик. Не основание выплаты` in filename/header/metadata. Current payment export MUST require live admission. Paid, cancelled and replaced calculations MAY export as history but MUST NOT be presented as a new payment instruction.

#### Scenario: Mismatch blocks payment export but preserves history
- **WHEN** #257 blocks an accepted unpaid snapshot
- **THEN** current payment export fails closed, historical export remains readable, and resolution does not revive the old payment snapshot

### Requirement: Workbook SHALL preserve verified legacy fields and explanations
The primary `Расчёт ОТиЗ` sheet and useful `Объекты`, `Работники`, `Контроль`, `Приложение к приказу`, and `Метаданные` sheets MUST be preserved and extended with `Удержания` and `Решения по выплате`. Every legacy column MUST have a verified source mapping or an explicit unresolved finding.

#### Scenario: One installer across two objects aggregates once
- **WHEN** a fixture contains two objects and one recipient
- **THEN** object funds occur once per object, worker detail has one employee-object row, and the payment appendix has one stable-recipient total equal to the sum of those rows

### Requirement: XLSX SHALL be safe and usable
Money MUST be numeric with two decimals, percentages correctly scaled, dates typed, registration/personnel numbers text-preserved with leading zeroes, headers frozen, flat sheets filtered, and the main sheet printable. User text MUST never become formulas; external links and macros MUST be absent.

#### Scenario: Formula injection is neutralized
- **WHEN** a user-controlled value begins with `=`, `+`, `-` or `@`
- **THEN** an independent XLSX reader observes a text cell, no formula and no external relationship
