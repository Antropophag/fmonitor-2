# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — maintenance/upload lease barrier Gate 1 gap

Date: `2026-09-05`

RED author: `/root/assignment_original_red2`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR TASK 4.1**

Task 4.1 requires the real maintenance/content-lease exclusion race. The
lifecycle contract already has the only causal pause needed:
`AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT`, when finalized content and its digest
lease exist but no DB reference does. Maintenance must observe LOCKED/retained
and must not delete that content.

The real worker bootstrap hard-wires its sole READY/RELEASE barrier to
`AFTER_FINGERPRINT_MISS_BEFORE_CAS`, which occurs before private finalize and
lease acquisition. Worker config has no lifecycle-event selector. Pausing there
cannot test lease exclusion. A direct storage finalize can prove adapter lock
sharing but not that the application retains the lease through commit/unknown/
conflict resolution. The generic application verification factory can inject a
lifecycle observer, but no public factory supplies its real MariaDB repository/
composition dependencies; in-memory persistence is explicitly insufficient for
the persistence/failure matrix.

Thus the contract simultaneously requires a real application-maintenance lease
race and provides no real composition that can pause while that application
lease is held.

Smallest amendment: add worker config `barrierEvent` with exact allowed values
for fingerprint-CAS and private-finalize-pre-commit, plus exact READY/RELEASE
fixture and invalid config rules; or expose a verification-only real command
factory injecting lifecycle/fault ports while binding production MariaDB
repository/composition/storage. Production remains no-op.

Task 4.1 remains unchecked. Existing worker/maintenance/parser/evidence RED
remains valid; no production, test, specification or OpenSpec artifact changed.

```text
b12f3c88762ae59b298fca26b0942f223e95a7b2914398e7e0959d804a2eb845  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
605bd0255e0488b08b39657a05896da200d26e5ea801054276f2eb3dca8701f2  openspec/changes/replace-pilot-registration-with-original-upload/design.md
9e96582344226df950df4f5f4c7937bc176ee2469ff7969a9790dfbcbe766e7f  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
60989e3186508b3cbf33cce0f97abfbbe72cb873d01271c78aa263392f02c12f  docs/operations/assignment-order-original-maintenance-red-part2-2026-09-05.md
```
