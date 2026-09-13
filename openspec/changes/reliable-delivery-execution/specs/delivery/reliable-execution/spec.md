## ADDED Requirements

### Requirement: Reliable admission

Harness SHALL выполнять `specs/DELIVERY-EXECUTION-107-I1.md` через существующий
public CLI и fail-closed связывать состояние с exact candidate.

#### Scenario: Exact candidate accepted

- **WHEN** все обязательные evidence и authorization относятся к exact candidate
- **THEN** evaluator сообщает раздельные publication, merge и action verdicts

#### Scenario: Missing or foreign evidence rejected

- **WHEN** evidence отсутствует, устарело, неполно или относится к другому binding
- **THEN** admission отклоняется, а прежний успех не подменяет текущее состояние
