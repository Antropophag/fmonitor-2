# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — combined release fault Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved unknown-outcome base: `92f528a4d0f8e64140eba15c481465f22edc7422`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

V29 adds distinct real worker fault points for unknown commit lookup FOUND,
NOT_FOUND and UNAVAILABLE. The lease contract separately requires release
failure to preserve the selected result and emit the phase-specific safe log
after committed, rolled-back, unknown-found, unknown-not-found,
unknown-unavailable and commit-conflict outcomes.

Worker config still contains one nullable scalar `faultPoint`. Therefore:

- `CONTENT_LEASE_RELEASE` alone can exercise normal committed release failure;
- organic CAS conflict plus `CONTENT_LEASE_RELEASE` can exercise
  `commit_conflict`;
- rollback requires `COMMIT_BEFORE` plus release failure;
- unknown-not-found requires `COMMIT_UNKNOWN_NOT_FOUND` plus release failure;
- unknown-unavailable requires `COMMIT_UNKNOWN_UNAVAILABLE` plus release
  failure.

The last three require two faults in one invocation and cannot be represented
by one enum-backed string. An in-memory repository/lease double can compose
them, but the specification explicitly disallows using in-memory Gate 2 as
persistence/CAS/failure acceptance instead of the production MariaDB repository
and independent evidence reader.

Smallest amendment: define bounded exact combined scenario strings (for example
`commit_before+content_lease_release` and both unknown variants), or replace
`faultPoint` with an ordered unique list and exact allowed-combination grammar.
Publish expected Result, safe-log phase and durable blob/request evidence for
each combined case; production binding remains inert.

Task 4.1 remains unchecked. Existing parser/evidence partial RED remains valid;
no production, test, specification or OpenSpec artifact was edited.

```text
f52b6fa78913b2654247b722f65de2184378886430038d1df7c5197ed2d7d9f9  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
3b26a248dbc545bde5544303b7e83419c1148d4ecbbe0305aadee8633fea5c32  openspec/changes/replace-pilot-registration-with-original-upload/design.md
26e2a512780d00cf0c3b85e801e7695fdd7bc12340cb7300c6960f7bc96036b9  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
037e962e732f26b17c3f2dbdbe57ef29f34d419698e4db9c99a86319284c1add  docs/operations/assignment-order-original-worker-unknown-outcome-fault-gate1-gap-2026-09-05.md
```
