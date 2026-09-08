## Purpose

Production consumers получают историю immutable originals и подготовленные PDF
bytes через owning-module API без diagnostic inventories или нового владения фактами.

## ADDED Requirements

### Requirement: Original history and prepared revision
Reader SHALL соблюдать ASSIGNMENT-ORDER-ORIGINAL-HISTORY-DOWNLOAD-001,
сохраняя caller transaction, append-only source и actor authorization у consumer.

#### Scenario: Historical revision after correction
- **WHEN** consumer запрашивает прежнюю revision после принятого исправления
- **THEN** API выдаёт exact metadata и проверенные bytes прежней revision

#### Scenario: Unavailable evidence bytes
- **WHEN** accepted revision существует, но private PDF отсутствует или испорчен
- **THEN** download unavailable без partial bytes; metadata history остаётся доступна
