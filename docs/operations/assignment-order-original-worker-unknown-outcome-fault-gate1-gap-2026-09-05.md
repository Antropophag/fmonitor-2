# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker unknown-outcome fault Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved cross-request base: `df0f85c597c0174e33dde151e32d1e121e4be5cf`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

The required real-MariaDB failure matrix has three distinct outcomes after an
unknown commit result: fresh request lookup `FOUND`, reliable `NOT_FOUND`, and
`UNAVAILABLE`. The worker config exposes only one nullable canonical
`faultPoint`; its enum has one `COMMIT_AFTER_UNKNOWN` value and one separate
`REQUEST_LOOKUP` value. Config cannot compose faults or select the lookup state.

An after-commit injected failure can deterministically leave a committed row and
therefore exercise `FOUND`. It cannot also make the same invocation's fresh
lookup unavailable. A before-commit failure is a different `COMMIT_BEFORE`
axis, not unknown-outcome reliable absence. One fault string cannot express
`COMMIT_AFTER_UNKNOWN+REQUEST_LOOKUP`, rollback-before-unknown, or a finite
lookup script.

The generic verification factory could use a custom in-memory repository, but
V27 explicitly says in-memory Gate 2 cannot satisfy the persistence/CAS/failure
matrix; MariaDB acceptance must use the production repository plus independent
evidence reader. No public factory exposes that real repository with a scripted
typed commit/lookup sequence.

Smallest amendment: make worker `faultPoint` a bounded exact scenario enum with
`unknown_found`, `unknown_not_found`, `unknown_unavailable` (and precise durable
effects), or add a verification-only real-repository fault script factory. State
one exact worker config/result/evidence example for each and preserve production
inert binding.

Task 4.1 remains unchecked. Existing parser/evidence partial RED remains valid;
no production, test, specification or OpenSpec artifact was edited.

```text
fae5997cb826e3b3489390dc90b0f3c66834f8cdc45258787d8b079be4267845  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
d15f345aa37a148e8e03c613c050f7220d4b94c36674e86f26e732348b3cf27d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
3fb4dbf64918534b080d2ac0f12dd483f2dd0a832e33635ba2b04b044ddd0429  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
d4fe2591767be15aefd24e689d0a9e71d0b4812c0f2a2b7ac1883399c77676d9  docs/operations/assignment-order-original-cross-request-replay-gate1-gap-2026-09-05.md
```
