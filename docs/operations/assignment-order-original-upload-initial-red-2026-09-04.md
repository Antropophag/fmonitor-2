# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 2 initial RED

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red`

Scope: task 2.1 and bounded authorization/evidence groundwork for task 2.2

Outcome: **INTENDED RED — production application seam absent**

## Authority

Gate 2 was authorized by owner decision
`docs/operations/assignment-order-original-upload-v4-owner-approval-2026-09-04.md`
at commit `565be908a101ec26aff52c219df642083e610f6a`. The executable
specification and four non-task OpenSpec artifacts still match the approved
hashes. The current task-file hash differs only because that owner-approval
commit changed task 1.7 from open to complete.

```text
97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a99946c8662b8cf6dbc21ff8e513bf0813cc6d6604a92087a03c019e2922c482  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
b81f11b5aabd69645404b624d5301cd65a209b870d06ef587dcb34eebbcfc9b2  openspec/changes/replace-pilot-registration-with-original-upload/design.md
127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
08a3f37cc6d03e1057f5ceb0347ff53c337a5369bef455b0e23961229c78cbf7  docs/operations/assignment-order-original-upload-gate2-constructibility-gap-2026-09-02.md
5b459540a6bae9737ce52b4c78f501d33137cfa1836d3d5e6fc0b8e0c20d444d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

## Added verifier

`tests/InstallationProcess/assignment_order_original_upload_001_test.php`
calls only the approved public
`AssignmentOrderOriginalVerificationFactory::create(...)->submitAssignmentOrderOriginal(...)`
application seam. It uses independent literals from Example A rather than
deriving expected values through production code:

- request `00000000-0000-4000-8000-000000000001`, case `4512`, order `81`,
  actor `18` and exact capability `assignment_order.original.upload`;
- immutable composition `composition-81-v1`, installers `[7001,7002]`, engineer
  `901` and the literal sixty-four-`1` composition digest;
- document date `2026-09-01` and distinct upload instant
  `2026-09-02T09:15:30Z`;
- approved literal 327-byte PDF and independent digest
  `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`;
- application-owned `original-0001` / `revision-0001`, revision number `1`,
  exact protected evidence JSON and exact initial domain-event type;
- unchanged canonical process snapshot covering order composition, case,
  opening, tasks and unrelated decoy facts;
- exactly-once stream close, stage close, content-lease release and delivery,
  with no abort or rejected-attempt commit.

The test support implements only approved dependency ports. It cannot construct
an application `ACCEPTED` result and therefore cannot self-attest GREEN. No
production, executable-spec, OpenSpec or review file was edited by the RED
author. The verifier uses no database, network, shared storage, production
document or secret and creates no temporary resource requiring cleanup.

## Reproduced RED

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_upload_001_test.php
```

The direct command and RED harness both reach the same single cause. This is a
behavior RED, not a Docker, MariaDB, PHP, fixture, parser or configuration
failure. The guard is sensitive to the approved fully-qualified factory name;
once production provides that seam, execution continues into the typed fixture
and all acceptance assertions rather than accepting class presence as GREEN.

## Gate 2.2 remaining matrix

The initial fixture establishes reusable exact-capability, composition, clock,
ID, stream, injected-passive-inspector, private-stage/lease, repository and
observer ports. It does not claim completion of task 2.2. Remaining RED cases
are:

- post-template/direct parity and full invalid command/date/composition
  precedence;
- authorization denial/unavailable and forbidden role-name/adjacent-capability
  fallbacks;
- real owned PDF algorithm, MIME/magic, exact byte ceiling and active/encrypted/
  zero-page/structural parser corpus;
- chunk, abort, close, finalize, release and ordered storage/lifecycle events;
- terminal rejection/conflict audit, safe-log redaction and audit failures;
- request-ID/cross-request fingerprint replay, no-change and append-only
  correction lineage;
- root/current/target collision matrix and identical/different correction CAS;
- shared MariaDB/private-storage five-FD two-worker barrier;
- definite rollback, outcome-unknown lookup and committed response-loss paths;
- maintenance authorization, candidates/cursor, shared digest lock, reference
  reread, replay, bounded deletion and upload/maintenance races;
- independent zero-public-orphan cleanup and sensitivity proof before Gate 3.

Task 2.1 is ready for independent Gate 3 review. Task 2.2 remains open and no
production implementation is authorized by this RED record.
