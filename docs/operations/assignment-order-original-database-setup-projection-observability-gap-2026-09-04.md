# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — setup projection observability gap

Date: `2026-09-04`

RED correction author: separately tasked agent `/root/assignment_original_red2`

Reviewed test commit: `21c1bcb16d9e9ee4c65b881e358521bd6c0b3062`

Gate 3 record commit: `2cc93762c91d1137717ad75bb77d50c122d2bd58`

Outcome: **GATE 1 AMENDMENT REQUIRED BEFORE SETUP RED CORRECTION**

## Простыми словами

Независимый reviewer справедливо требует вычислять checklist и decoy digests
из строк, действительно созданных fixture, а не только повторно хэшировать
константы. Current v10 задаёт ожидаемый JSON, но не говорит, из каких
существующих таблиц и полей должны читаться эти две проекции. Тест не может
угадать это хранение: иначе ожидаемое значение начнёт зависеть от будущей
реализации fixture.

## Exact gap

Executable spec v10 перечисляет exact rows для actors, roles, grants, case,
order, installers и task. Для checklist и decoy он фиксирует только result
projections:

```text
{"items":[{"availability":"blocked_pending_original_and_opening","checklistIdentity":"installation-case-4512"}]}
{"items":[{"caseId":9999,"marker":"fixture-decoy-v1"}]}
```

Он не определяет:

- prerequisite logical table и primary identity для checklist row;
- поля, из которых независимо выводятся `checklistIdentity` и `availability`;
- prerequisite logical table/identity и marker field для decoy row;
- normative row-to-projection mapping и binary ordering;
- является ли checklist availability сохранённой строкой или projection от
  case opening state и отсутствия original;
- является ли decoy отдельной unrelated case row, process event или иным fact.

`AssignmentOrderOriginalVerificationDatabaseFixture` MUST perform no DDL and
MUST be callable only after approved prerequisite migrations. Version-1
original migration owns only seven original-evidence tables, none of which may
receive fixture original facts. Current canonical process schema has no named
checklist-availability or decoy-marker column. Future evidence reader is
read-only and explicitly receives no fixture dependency; using its output to
define setup expected values would also defeat the Gate 3 requirement to derive
them independently from seeded rows.

The repository inventory confirms that the two literals occur only in v10 and
the current disconnected support oracle; no application migration or approved
OpenSpec artifact names their storage mapping:

```text
$ rg -n "checklistIdentity|blocked_pending_original_and_opening|fixture-decoy-v1" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md openspec/changes/replace-pilot-registration-with-original-upload app/InstallationProcess tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:1060:{"items":[{"availability":"blocked_pending_original_and_opening","checklistIdentity":"installation-case-4512"}]}
specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md:1062:{"items":[{"caseId":9999,"marker":"fixture-decoy-v1"}]}
tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php:25: checklist literal only
tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php:26: decoy literal only
```

## Smallest coherent amendment

Gate 1 should map checklist solely from exact target case/order facts already
owned by prerequisite process tables: checklist identity is
`installation-case-4512`, and availability is
`blocked_pending_original_and_opening` exactly when case `4512` is unopened and
no original root exists for order `81`. The mapping must state exact input
columns, null/absence semantics and canonical binary ordering.

Decoy should map from one exact unrelated case row (case `9999`) in an existing
named process table, with an exact existing column/value that canonically emits
marker `fixture-decoy-v1`; if no suitable existing column exists, Gate 1 must
choose an already approved generic evidence carrier or remove the impossible
stored-row claim. The amendment must name the input columns and canonical JSON
mapping, not only repeat the output literal.

After approval the RED author can complete all four Gate 3 corrections in one
coherent batch: exact normalized CHECK/column properties, multi-table
near-equivalent conflict matrix, complete seeded-row projections, cleanup drift
rollback and bounded SERIALIZABLE contention. No partial correction is
committed now because it could encode a projection mapping contradicted by the
amendment.

Task 2.2 remains historically checked to reflect the submitted RED batch, but
Gate 3 is `CHANGES_REQUESTED`; task 3.1 remains unauthorized. No spec, OpenSpec,
production, test, support or prior evidence/review bytes were edited by this
gap record.

## Exact inspected hashes

```text
62b42d5b957dd628d09c13d6864152401c54998a5607b1c1835cc8a93ab9c3dd  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
0208895b4a605381ece9cc0bba4cee49ac79c1b17ffa1939f62601c05144051f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
8e533ff36104d6b1b01e4deaf0a695a938c5ff90e78d4c0d861ac736e1edb06d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
a061abc535528436d3caaadd0f34e7618793fd3ae76f5e7ccb16fd40fbbf43b5  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
72d4252762912d5c26dfb35ec0008f4d74a2e9e477ec89a601cbf8ee077ef400  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
35afcdd180441a2bf3631c6715dac225135df5bdc25011f31abfe319ef5c69ee  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
c5174e5e109bd13104aa1085be1e6b975f2ccaef301ac4006714a644f37e301c  docs/operations/assignment-order-original-database-setup-red-2026-09-04.md
cc65deb0130a24e9f019b0605b78a6707a2671a75f686899dc16ecad8bbe5192  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
```
