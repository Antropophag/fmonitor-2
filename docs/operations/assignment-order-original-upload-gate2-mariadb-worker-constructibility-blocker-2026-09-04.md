# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — MariaDB worker constructibility audit

Date: 2026-09-04

RED author: `/root/original_upload_red`

Outcome: **BLOCKED SUBSET; Gate 2 task 2.2 remains open**

## Verified boundary

The approved v4 contract defines the canonical migration runner as the only
permitted schema setup path and defines the exact worker config, command/result
JSON, five descriptors, barrier messages and exit behavior. It correctly
forbids verifier composition selected from runtime input.

The runner alone cannot make the required CAS barrier reachable on a fresh
namespace. Before `AFTER_FINGERPRINT_MISS_BEFORE_CAS`, each worker must read an
active actor, active role assignment, exact
`assignment_order.original.correct` capability, case/order/composition and an
existing current original lineage. `AssignmentOrderOriginalWorkerConfig`
contains no approved fixture injection for those facts, as intended.

Existing public predecessor tables can establish case/order/composition, but
the approved contract does not identify the canonical identity/active-role
tables consumed by `ProductionAssignmentOrderOriginalFactory`, and the current
capability CHECK rejects the new original capabilities until future OpenSpec
task 3.1 advances the schema frontier. There is also no approved public fixture
seed seam for an existing original root/revision.

Consequently a Gate 2 author can either:

1. guess future private table names/columns and seed them directly, violating
   the implementation-independent evidence boundary; or
2. run only canonical migrations and observe workers terminate with an earlier
   authorization/order/lineage result, never emitting both required `READY`
   messages.

Neither is the requested deterministic CAS RED. Manual private schema creation
was not performed. No production, spec, task or existing evidence file was
changed.

## Disposition

The real MariaDB/five-FD acceptance subset remains blocked until task 3.1
provides the canonical additive migration and there is an approved way to seed
the prerequisite public facts through production-owned adapters or an exact
fixture seam. This audit does not weaken or defer the requirement, does not
classify an early authorization/order failure as CAS RED, and is not Gate 2 or
Gate 3 approval.

Independent in-memory maintenance and commit/fault RED work can continue while
this blocker remains explicit.
