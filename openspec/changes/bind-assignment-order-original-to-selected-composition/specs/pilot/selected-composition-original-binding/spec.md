## Purpose

Принимать подписанный оригинал по native выбранному составу без предварительного
PDF-шаблона, сохраняя согласованность выбора при конкурентной замене.

## ADDED Requirements

### Requirement: Direct original uses the selected composition

Original command SHALL принимать текущий native выбор через прежний публичный
application seam с exact original capability, PDF validation, immutable revision
и audit. Выбор MUST NOT требовать physical order или шаблон.

#### Scenario: Selection then direct original
- **WHEN** authorized ФКР загружает допустимый original для текущего native выбора
- **THEN** original сохраняется с exact composition identity/hash; состав и открытие не применяются

### Requirement: Replaced pending choice cannot acquire an original

Уже заменённый pending выбор без original MUST возвращать conflict/target_not_current
без original acceptance. Preflight и locked recheck SHALL обеспечивать это правило.

#### Scenario: Replacement wins before original commit
- **WHEN** pending selection заменена после preflight, но до original case lock
- **THEN** original не принимается, возвращается target_not_current и сохраняется terminal attempt/audit

### Requirement: Accepted original history retains its own correction workflow

Correction/replay SHALL сохранять approved original semantics, включая correction
прежнего accepted order после появления последующего выбора. История MUST оставаться
append-only; upload сам не открывает работы и не применяет назначения.

#### Scenario: Earlier accepted original is corrected
- **WHEN** current leaf оригинала прежнего accepted order исправляется при наличии новой pending selection
- **THEN** добавляется original revision для прежнего immutable состава; новая selection не изменяется
