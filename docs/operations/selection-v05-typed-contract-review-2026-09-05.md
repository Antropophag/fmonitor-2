# ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.5 — независимый review typed contract

Дата: 2026-09-05.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed commit: `9233f25a312099017b59f5ab1c8bdac55ed0d73e`.  
Bounded verdict: **CHANGES_REQUESTED для typed contract; full Gate 1 остаётся NOT READY FOR RED**.

Это независимый ограниченный rereview изменений v0.5 против P1 findings review
v0.4. Он не повторяет подробный разбор уже известных P0 dependencies, не
утверждает Gate 1, не разрешает RED и не меняет code/tests/spec/OpenSpec.
Product policy `replace_pending` считается окончательно утверждённой; повторный
owner question не нужен.

## Exact reviewed hashes

- `specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md`:
  `bf616e0dc68b1b8c94722bd6e6c1565fb46f0b8509fc30a1b9dad819c2936c66`.
- OpenSpec design:
  `a84538ffb11d27ea2991de83cf3c83359a2c9c07ab39fd49a85a2b475eb2a080`.
- OpenSpec proposal:
  `d9aee7bd2621e324e853093d8e72f471186ddb2328d68dfece94e6eee7cb1823`.
- OpenSpec delta spec:
  `36e27bd6fd8efb4ab2d99e63bd1706e30b8ca636776ad7728ada1863d80a672b`.
- OpenSpec tasks:
  `2605f922d63da3882085444528bf9948365f8ef0fc34909c682fc0fb92341713`.
- Owner decision record:
  `915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c`.
- Prior independent v0.4 review:
  `7df4a3e6df3b688330de08a4be30b98c8f3b069f51c2f27d7b2915378f721acb`.
- Writer/reader inventory:
  `86896f3f16faf5a9f33276b8dc975cbb0d04c013044c80d7b444262927d1f299`.

## Исправленные findings v0.4

### AUTO_INCREMENT IDs и pre-insert DTO — RESOLVED

`SelectionSelectedEvent` и `SelectionSafeAttemptAudit` больше не требуют
несуществующие eventId/auditId до insert. Storage назначает IDs, а typed
`SelectionStageResult`/`SelectionAuditWriteResult` возвращают ограниченные
receipts; stored observer использует отдельные envelopes. Stage receipt явно не
является commit acknowledgement, rollback допускает только allocator gap.

### Result/lookup closure — RESOLVED по construction surface

`SelectionResult` имеет private constructor и единственные factories для пяти
status families; reason, retryable и success проверяются factory. Serializer
отклоняет foreign implementation. Все lookup types имеют private constructor и
`found/notFound/unavailable` factories. Passive snapshot carriers отделены от
application validation, поэтому malformed dependency value не маскируется как
constructor/setup failure.

### Employment negative branch — RESOLVED

Closed structural payload допускает `employed` и `dismissed`; missing catalog,
dismissed/out-of-period и malformed dependency теперь имеют разные exact
outcomes. Batch completeness, ordering, duplicate/extra/wrong identity и date
period checks принадлежат application owner до allocation.

### Legacy physical status normalization — RESOLVED

`SelectionLegacyPhysicalStatus` закрывает raw status до `prepared/registered`.
Truth table задаёт четыре допустимые status × accepted-root формы и переводит
orphan/dual/mismatch/unknown/invalid-root в dependency unavailable без fallback
или allocation. Transaction state/locked-case reads теперь typed lookups.

### Prefix-25 generated identifiers — не относится к этому bounded delta

v0.5 не заявляет исправление этого finding и корректно оставляет exact generated
names/fingerprints будущему compatibility contract. Он остаётся P0 dependency в
Gate summary, а не новым противоречием typed contract.

## Оставшиеся typed-contract findings

### P1 — persisted terminal outcomes требуют времени раньше единственного clock read

Раздел 8 требует audit для каждого authorization denial, fresh business
rejection/conflict и changed-tuple request conflict. `SelectionSafeAttemptAudit`
обязательно содержит `SelectionInstant attemptedAt`; terminal request также
имеет обязательный `terminal_at_utc`. Однако exact precedence раздела 10 читает
clock только на шаге 6. До него завершаются:

- authorization denial на шаге 2;
- outer stored-request tuple mismatch на шаге 3, для которого раздел 8 требует
  новый conflict audit;
- object_not_found/completed/PTO на шаге 4;
- installer_required/control_engineer_required на шаге 5.

`SelectionAttemptAuditWriter` принимает уже построенный audit и не имеет clock
port. `stageTerminalAttempt` также получает уже построенный payload. Поэтому
application не может сформировать предписанные persisted facts для этих ветвей,
не выполнив незадокументированный дополнительный clock read или не нарушив
precedence «read one clock».

Required disposition: точно поставить единственный clock read перед первым
outcome, которому нужен persisted timestamp, и закрепить precedence при clock
unavailable для denial/conflict/business rejection; либо определить отдельный
storage-owned timestamp contract и согласовать audit/request equality. Нельзя
оставлять timestamp acquisition на усмотрение adapter.

### P1 — rollback result не переносит выбранную application-причину

Раздел 6.1 требует, чтобы transaction `selectionState()`/`lockedCase()`
NOT_FOUND, UNAVAILABLE или malformed payload дали rollback и exact
`failed/dependency_unavailable`, причём это не persistence error. Раздел 9 даёт
`SelectionTransactionDecision::rollback()` без payload и
`SelectionUnitOfWorkResult::rolledBack()` без payload. Тот же `rolledBack()`
нужен для storage/persistence rollback, который acceptance matrix отображает в
`failed/persistence_failure`.

После возврата UoW application не может типизированно различить два обязательных
результата. Захват причины во внешней mutable переменной callback или exception
channel не определён публичным port contract и разрушает заявленную closure.

Required disposition: добавить closed rollback reason/result payload с exact
mapping как минимум для dependency-unavailable и persistence-failure, либо
вернуть terminal `AssignmentOrderCompositionResult` через rollback decision/UoW
result. Request-race и observed-terminal должны остаться отдельными branches.

### P1 — stage persistence error и receipt validation не имеют полного decision mapping

`stageAccepted`/`stageTerminalAttempt` могут вернуть `PERSISTENCE_ERROR`, а
receipt factory может обнаружить out-of-bounds DB-generated ID. Текст говорит,
что adapter возвращает `persistenceError` после доказанного rollback, хотя stage
выполняется внутри UoW и commit/rollback принадлежит UoW, а session не exposes
commit. Не закреплено, какой `SelectionTransactionDecision` возвращает callback
на `PERSISTENCE_ERROR`, и как итог отличается от dependency rollback выше.

Required disposition: задать исчерпывающую таблицу
`SelectionStageResult → SelectionTransactionDecision → SelectionUnitOfWorkResult
→ public result`, включая invalid generated ID, accepted/terminal stage,
request-race, confirmed rollback и unknown outcome. Исправление может быть
объединено с предыдущим finding через один closed rollback-reason model.

## Gate summary

Typed DTO/result/lookup/status changes v0.5 существенно закрывают четыре P1
дефекта v0.4, но три control-flow findings выше не позволяют считать typed
contract executable без implementation-specific side channel. Verdict этого
bounded rereview — `CHANGES_REQUESTED`.

Full Gate 1 независимо остаётся `NOT READY FOR RED`, поскольку exact
migration/backfill/receipt and prefix contract, all-writer/N-1 cutover,
original-reader amendment и same-identity optional-render contract отсутствуют.
Это summary существующих P0 blockers; их подробный rereview здесь не выполнялся.
После исправления typed flow и появления compatibility artifacts нужен fresh
independent Gate 1 review exact hashes. Deadline не отменяет ни один gate.
