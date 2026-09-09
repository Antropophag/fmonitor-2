# OTIZ-SETTLEMENT-001 — выплаты, удержания и сторно

## Простыми словами

ОТиЗ может добавить подтверждённое удержание, автоматически закрыть остатки принятого среза или сторнировать запись. Произвольную выплату вручную вводить нельзя.

Public operations: `recordDiscipline(actor,snapshotId,objectId,positiveCents,basis,artifact,operationId)`, `completeSnapshotPayments(actor,snapshotId,operationId)`, `reverse(actor,closureId,basis,operationId)`. Active actor требует current `otiz.manage`; UUID lowercase; ids positive; cents1..1000000000000; basis1..500; artifact0..300.

Budget legacy object равен `max(0, accepted accrued - global signed closures)` без второго closed_before. S1 accrued100000 и S2 accrued150000 суммарно закрывают150000: concurrent bulk допускает100000+50000 либо150000+zero. Bulk пропускает blocked/zero, использует fixed basis и all-or-nothing sorted locks.

Success имеет operation fingerprint receipt; exact replay стабилен. Successful zero bulk хранит receipt `no_change` без closure/event; refusal не хранит facts. Reverse копирует exact paid/discipline/historical deadline с обратным знаком, новым basis, пустым artifact и unique link; original неизменен.

Outcomes: `recorded`, `completed`, `no_change`, `reversed`; errors `FORBIDDEN`, `INVALID_COMMAND`, `NOT_FOUND`, `SNAPSHOT_NOT_ACCEPTED`, `OBJECT_BLOCKED`, `AMOUNT_UNAVAILABLE`, `OPERATION_CONFLICT`, `ALREADY_REVERSED`. Формулы/#66/allocation/UI/publication не меняются. Полный normative delta — в OpenSpec change.
