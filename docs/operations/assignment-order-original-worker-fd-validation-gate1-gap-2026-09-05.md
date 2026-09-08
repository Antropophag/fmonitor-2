# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — worker FD validation Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved composition base: `2e4c484791ae78e82dc309d2b968c35b6103c2f3`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

Worker bootstrap accepts four integer descriptor arguments and V24/V31 says
invalid FD validation happens before command read. The valid/invalid descriptor
contract is not defined:

- allowed numeric range and whether `0/1/2` are forbidden;
- requirement that all four are pairwise distinct;
- open-descriptor/type requirements (pipe/socket/regular file);
- required read/write access direction for each channel;
- duplicate aliasing and same underlying open-file-description handling;
- exact behavior for closed, negative, overflow or wrong-direction values.

“Barrier uses separate FDs” establishes at most that barrier read/write differ;
it does not settle command/result aliases or stdio. Gate 2 cannot independently
choose invalid cases or prove fail-before-command without inventing this IPC
security boundary.

Smallest amendment: require four pairwise-distinct positive non-stdio open FDs,
specify channel direction/type checks and exact invalid vectors, all mapped to
the existing exit-70/empty-result/empty-barrier/unread-command contract.

Task 4.1 remains unchecked. An additive worker transport RED is retained: it
contains exact DSN/ID/mode/fault invalid channels and canonical initial
command/result framing, and currently fails on the absent worker bootstrap seam.
No production, specification or OpenSpec artifact was edited.

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed
$ independent SCHEMATA query
NO_AOOU_DATABASE_LEAKS
$ git diff --check
PASS (no output)
```

```text
e6141aa2df6d9e457a9f3ebd593a9defdbd68b2a233d8091a238afc404b317aa  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
96dc33790e24f18aec5090c5cf75119d41ea55b36d90085ce1b4d303cde3f13a  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
a707b2db6307e7376ecfab2f00b593383d7523aef54f919e4a92005059e5fba4  tests/Support/assignment_order_original_worker_entry.php
43eaaddb46ba5d383a5a351d6d278360b29303f3bbdfdba2d2ea62ad406d0e7b  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
```
