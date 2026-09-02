# INSPECTION-ITEM-COMPLETE-001 authorization decision

Date: 2026-09-01  
Status: `OWNER_APPROVED`  
Mission: `TEST-USER-READY`  
Decision scope: GRILL-003 authorization calibration for item completion

## Owner decision

1. Any active engineer with exact capability `inspection.item.complete` may
   complete checklist items on any installation object. Current assignment as
   the object's control engineer is not an authorization condition. This broad
   object scope is intentional because engineers sometimes record completion
   for one another.
2. Every command, including an offline command, is authorized against current
   active-user status and current exact capability at server receipt.
   `deviceTime` is audit evidence, not authority time.
3. Reassignment alone does not reject an offline command: the former assigned
   engineer remains authorized if active and still holding the capability.
   Blocking the user or revoking the capability causes deterministic rejection,
   even when `deviceTime` predates that change.
4. Audit keeps the actual actor, currently assigned engineer, client device
   time and server receipt time as distinct facts.

## Rejected alternative

Requiring both `inspection.item.complete` and current object assignment was
rejected because it prevents the required practice of engineers recording
completion for colleagues. Trusting device time to preserve old authority was
also rejected because client time is not an authoritative authorization clock.

## Consequence for Gate 1

GRILL-003 is resolved. The target executable spec and OpenSpec acceptance
scenarios must encode capability-only object scope and server-receipt
reauthorization before RED is authored. Current pilot admission behavior remains
characterization evidence and is not promoted implicitly.
