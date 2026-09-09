## ADDED Requirements

### Requirement: Reconstructible local review source

Система SHALL выполнять нормативный контракт
[REVIEW-SOURCE-001](../../../../../specs/REVIEW-SOURCE-001.md).

#### Scenario: Review before grouped commit

- **WHEN** автор сохраняет frozen checkout и reviewer восстанавливает snapshot
- **THEN** reviewer получает точные bytes/modes исходного кандидата, а source
  HEAD/index/worktree остаются неизменными

#### Scenario: Invalid snapshot

- **WHEN** digest не соответствует patch или output уже существует
- **THEN** операция завершается отказом и сохраняет чужие данные
