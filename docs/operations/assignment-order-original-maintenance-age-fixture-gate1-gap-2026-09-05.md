# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — maintenance orphan-age Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved maintenance-authorization base: `47458bef03a8083af47e5bb763e779623d09bd03`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

V33 makes the production maintenance seam authorizable and its MariaDB audit
observable. A deterministic eligible orphan still cannot be created through
approved public real bindings.

Production storage timestamps every newly created abandoned stage/finalized
content with current production time. Production maintenance binds its own real
clock and accepts only `cutoffUtc <= now-3600s`; a fresh candidate is therefore
at least one hour too new and must be retained/not listed. Neither
`AssignmentOrderOriginalProductionConfig` nor production maintenance factory
accepts a clock or candidate-age fixture.

The maintenance verification factory accepts an injected clock, but it also
requires repository/reference implementations. No public factory binds the real
MariaDB repositories/storage while injecting only clock/authorizer. Using
in-memory repositories cannot satisfy the mandated real persistence/audit
evidence. Sleeping one hour is not a bounded Gate 2 test, and touching private
storage timestamps requires guessing adapter layout and bypasses the public
storage seam.

Smallest amendment: expose a verification-only production maintenance factory
that binds real repository/storage with injected maintenance clock, or add an
exact private-storage verification clock/candidate fixture factory. Publish one
canonical clock/cutoff/candidate timestamp example and keep production binding
real/inert.

Task 4.1 remains unchecked. Existing parser/evidence partial RED remains valid;
no production, test, specification or OpenSpec artifact was edited.

```text
0b5050e793191cee26e08a3e2f1c21e7a4a9862d5feefef04354c83b47a3d4e0  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
70d6dba9fb35c2e2aed51c170870c6ec633418c5f63d073dc5ff8a566cfcf1c8  openspec/changes/replace-pilot-registration-with-original-upload/design.md
b1710c99a4528d1e95de8a5bbe7da2087d7074d99b0db71fbb2b66234382b7ad  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
4a24b9c6d2fdc086cdd8f4e806f746afda0be6cd2b1401d155d6982f3b54b7a1  docs/operations/assignment-order-original-maintenance-authorizer-gate1-gap-2026-09-05.md
```
