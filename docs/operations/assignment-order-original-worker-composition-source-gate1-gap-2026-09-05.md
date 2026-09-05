# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker composition source Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved cursor base: `2f6dc750ee428505b0d5d3ca0d846174fdc70649`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

The canonical real worker must submit Example A against the seeded MariaDB
order/composition. The command contract requires its composition reader to
return identity `composition-81-v1` and hash `1111...1111`; both enter the
accepted fingerprint and immutable root evidence.

Neither prerequisite process schema nor Example-A fixture has a column/table
for composition identity or that hash. The order/installers rows contain members
and snapshots only. V38 publishes a different process-projection hash
`388c...`, but no algorithm maps those rows to the required `1111...` semantic
hash or `composition-81-v1` identity. Worker construction injects clock, IDs,
inspector, faults and barrier—not a composition reader—so it must use an
undefined real DB derivation.

Hard-coding the example values, hashing an arbitrary JSON shape, using the
process projection digest or deriving `composition-<order>-v<version>` are
different production contracts. Gate 2 cannot choose among them independently,
and a real worker cannot produce the exact accepted root/fingerprint evidence.

Smallest amendment: define exact production composition identity/hash canonical
encoding from named order/installer/engineer columns (with binary ordering and
null/type rules), or add persisted immutable identity/hash columns plus setup
migration/fixture values. Publish the exact Example-A preimage and digest.

Task 4.1 remains unchecked. The additive canonical maintenance RED created in
this turn is retained: it reaches Gate5 setup, creates the two exact eligible
orphan fixtures, then fails on the absent production orphan-fixture seam; source
asserts completion/replay, exact blob and maintenance request/audit evidence.
It does not claim full task completion.

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalPrivateOrphanFixtureFactory seam is absent.
RED_ASSERTION: expected failing behavior observed
$ independent SCHEMATA query
NO_AOOU_DATABASE_LEAKS
$ git diff --check
PASS (no output)
```

```text
914e9f06fc62b4fc1137f491315cb0d48b71fdc3c3c70260afa5445bd34054e0  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
00268fa4dc83074af133c7079830373eaa46fa567fe23125c79067647523c6a5  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
c26e606c65ab66140f5364ce6171d9d65944690a0e35196095a57e764c0819f9  tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
```
