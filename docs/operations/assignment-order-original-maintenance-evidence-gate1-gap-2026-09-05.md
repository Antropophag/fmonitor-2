# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — maintenance evidence Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved Result-publisher base: `458dee91fa838ca4a83db4d01b02138cb754662c`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

The maintenance contract requires `commitResultAndAudit` to persist terminal
maintenance result and append-only audit atomically. Replay/accounting tests can
observe the returned Result and a later request hit, but that does not prove an
audit row exists or that result and audit share one transaction.

The approved independent evidence reader exposes command domain, requests,
fingerprints, events, command safe audits, process state, blobs and safe logs.
It exposes no maintenance-request or maintenance-audit canonical method. The
`safeAuditsCanonicalJson()` shape is explicitly command-only
`{actorIdentity,mode,caseId,orderId,...}` and cannot represent
`systemPrincipalId,scanned,deleted,retained,failed`.

Using direct verifier SQL against the canonical maintenance tables would bypass
the approved independent reader factory and couple Gate 2 to private persistence
queries. An injected in-memory maintenance repository cannot satisfy the real
MariaDB persistence/audit acceptance or detect a production repository that
stores result without audit.

Smallest amendment: add exact
`maintenanceRequestsCanonicalJson()` and
`maintenanceAuditsCanonicalJson()` reader methods with closed versioned shapes,
ordering/null rules and fixed unavailable behavior; alternatively add one
combined canonical maintenance persistence snapshot. Include replay and audit-
commit-failure examples proving atomic absence/presence.

Task 4.1 remains unchecked. Existing parser/evidence partial RED remains valid;
no production, test, specification or OpenSpec artifact was edited.

```text
$ rg -n "Maintenance.*Canonical|maintenance.*audit|safeAuditsCanonicalJson|commitResultAndAudit" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
259: writes append-only maintenance audit/result
1365: commitResultAndAudit
1434: public function safeAuditsCanonicalJson(): string
```

```text
04bc3cf6af9fd0a171c20eca096a36f01ba5b4e398a4edac9bd1ac59097b3218  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
0d476557ed0d1b566f6818e18ac813bd3ace687ec02ee2bb01f90a9216d31096  openspec/changes/replace-pilot-registration-with-original-upload/design.md
02909be1bab53ce383926fb7c92be7624a50669c470604a91ed5935d072afd04  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
37a044143eccd6aa256332dc7e8a8d3192f465cf4192cfa16d553150a0e8e030  docs/operations/assignment-order-original-worker-result-write-fault-gate1-gap-2026-09-05.md
```
