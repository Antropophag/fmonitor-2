# OTIZ-SETTLEMENT-001 — выплаты, удержания и сторно

## Простыми словами

ОТиЗ может добавить подтверждённое удержание, автоматически закрыть остатки принятого среза или сторнировать запись. Произвольную выплату вручную вводить нельзя.

Public operations: `recordDiscipline(actor,snapshotId,objectId,positiveCents,basis,artifact,operationId)`, `completeSnapshotPayments(actor,snapshotId,operationId)`, `reverse(actor,closureId,basis,operationId)`. Active actor требует current `otiz.manage`; UUID lowercase; ids positive; cents1..1000000000000; basis1..500; artifact0..300.

Budget legacy object равен `max(0, accepted accrued - global signed closures)` без второго closed_before. S1 accrued100000 и S2 accrued150000 суммарно закрывают150000: concurrent bulk допускает100000+50000 либо150000+zero. Bulk пропускает blocked/zero, использует fixed basis и all-or-nothing sorted locks.

Success имеет operation fingerprint receipt; exact replay стабилен. Successful zero bulk хранит receipt `no_change` без closure/event; refusal не хранит facts. Reverse копирует exact paid/discipline/historical deadline с обратным знаком, новым basis, пустым artifact и unique link; original неизменен.

Outcomes: `recorded`, `completed`, `no_change`, `reversed`; errors `FORBIDDEN`, `INVALID_COMMAND`, `NOT_FOUND`, `SNAPSHOT_NOT_ACCEPTED`, `OBJECT_BLOCKED`, `AMOUNT_UNAVAILABLE`, `OPERATION_CONFLICT`, `ALREADY_REVERSED`. Формулы/#66/allocation/UI/publication не меняются. Полный normative delta — в OpenSpec change.

## Retained browser journey — task 3.1

The accepted snapshot screen retains its existing heading/date/status, object
rows, calculation details/allocations/issues, financial summary, navigation,
export link and closure history. The three existing forms submit POST with their
own valid Yii CSRF token and distinct canonical operation UUIDs. A guest opening
an accepted snapshot signs in through Yii and returns to that snapshot. A
successful browser submission returns to the same snapshot, showing the existing
success message and the appended result. Invalid commands show the retained
error without adding facts. A completed budget hides the complete-payment action;
a reversal releases the appropriate amount and appears as a linked negative row.
No new UI display of the optional artifact is required; it remains persisted.

Executable browser example: one accepted object accrued100000 cents, no earlier
closures. Submit discipline10000 with basis, complete the remaining90000, then
reverse the discipline. Preserve the first two rows and append a third row with
paid0/discipline-10000/deadline0 linked to the first. Exactly three receipts and
four events record these commands; signed closed total is90000. Rendered forms,
not synthetic POST payloads, supply operation IDs and CSRF. Runtime credentials
have SELECT/INSERT/UPDATE/DELETE only; setup/cleanup use a disposable admin fixture.
This supplements the existing A02/concurrency and denial matrices, not replaces
these independently reviewed contracts. The legacy renderer is the parity oracle;
its calculation formulas and accepted snapshot facts remain unchanged.

## Canonical v24 delivery and recovery

The complete current catalogue ends at24. Public full-catalogue migration/setup
checks advance to24; direct historical migrations keep their original versions.
Current recovery uses exact v24/71-table/39-AUTO_INCREMENT inventory, adding only
settlement locks and receipts to v23. Both new tables, closure/event history and
existing domain/Jobs rows survive backup/restore exactly; backup creates no domain
transition, restore starts no worker or delivery. Quiesce/identity/private-storage
rules inherit PRODUCTION-JOBS-RECOVERY-001 unchanged. Historical v22/v23 profiles
and primary evidence remain immutable. Old bundles use their exact source image,
then forward migrations; current tooling rejects a mismatched version/inventory
before target mutation. Existing v22 forward-update rehearsal must reach24 and
retain old rows/AUTO_INCREMENT values/private bytes. This is required adaptation
of the existing recovery boundary, not a new retention/RPO/RTO policy.

## Temporary runtime compatibility dependencies

The retained rapid HTTP adapter calls the same settlement owner while migration
continues. Its runtime image must carry the exact shared Composer lock and real
production dependencies (Yii2 and TCPDF), pass Composer platform validation, and
provide mysqli/pdo_mysql/pcntl. Existing nginx/FPM, shlz assets and private storage
configuration remain supported; no fake autoloader or alternate financial writer
may mask a missing dependency. A disposable image test uses DML-only credentials
through actual legacy login/CSRF and the retained complete-payment URL. With an
older100000 closure for the same object and a150000 accepted snapshot it must append
only50000, one receipt and two events; identical replay adds nothing. The retained
screen must then report completion, using the same global financial basis. No stand
switch is implied by making this compatibility image reproducible.
