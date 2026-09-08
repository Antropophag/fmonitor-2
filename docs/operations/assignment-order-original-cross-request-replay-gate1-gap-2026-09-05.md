# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — cross-request replay Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Approved worker-result base: `cee706fc7cfa495d52e3379f99635bdba7634b16`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR FULL TASK 4.1**

V24 defines exact Result JSON for Example-A acceptance, same-request retry and
new-request stale conflict. The mandatory identical two-worker correction race
uses two distinct request IDs and resolves the loser through accepted-operation
fingerprint replay. Its Result request identity is not defined coherently:

- Result DTO says `requestId: echoed canonical UUID`, implying the loser's
  current request ID;
- fingerprint replay says `REPLAYED` evidence equals stored accepted result;
- concurrency says loser returns `REPLAYED` “with result winner”;
- repository lookup returns an `AssignmentOrderOriginalResult` rehydrated from
  the stored winner, whose request ID is the winner's ID;
- V24 publishes no exact cross-request replay line or request-table effect.

The two interpretations differ in public Result bytes and idempotency evidence.
A worker verifier cannot independently decide whether to require winner or
loser request ID, whether a terminal replay row is stored for the loser, and
which request ID appears in fingerprint/request evidence. Same-request V24
fixture does not resolve this because both identities coincide.

Smallest amendment: state that cross-request fingerprint replay echoes either
current or stored request ID; publish an exact two-distinct-request example and
canonical Result line; define whether/how the replaying request is persisted in
requests/audits while creating no revision/event/storage effect. Apply the same
rule to the identical CAS loser.

Task 4.1 remains unchecked. Existing parser/evidence partial RED remains valid;
no production, test, specification or OpenSpec artifact was edited.

```text
b5138ce1520ef6fc261d94fd6e5b5a58e21586919f57c47715431e3b9d235bff  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
92154897e06427f58c46a1533e117561eeae4b7c5438a32dd51d587d70484242  openspec/changes/replace-pilot-registration-with-original-upload/design.md
d21234b126ebf947a0db8ba7a67f59c4a08457cc61cfd740ef4bfdce2ce3f86c  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b81d96e670cdd41d0288dfdbeb990edceb8e648acc50e85d99bb50ec44e6f68b  docs/operations/assignment-order-original-worker-result-json-gate1-gap-2026-09-05.md
```
