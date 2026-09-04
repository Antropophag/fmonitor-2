# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 rereview, initial RED v2

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/assignment_original_gate3`
- Owner-approved Gate 1 commit: `565be908a101ec26aff52c219df642083e610f6a`
- Prior Gate 3 record commit: `27b5eb9`
- Reviewed correction commit: `8be5a7a79096e80fe7300ab6b2b065d7a01c7612`
- Scope: OpenSpec task 2.1 only; task 2.2 remains open
- Public seam: `AssignmentOrderOriginalVerificationFactory::create(...)->submitAssignmentOrderOriginal(...)`
- Verdict: **APPROVED**

The reviewer did not author the executable specification, OpenSpec artifacts,
owner approval, test, support fixture, production code, RED correction, or RED
evidence. This append-only rereview is the only review edit.

## G3-1 closure

The correction replaces the disconnected local-string comparison rejected in
v1 with one independently seeded `AssignmentOrderOriginalInitialProcessState`.
That same state is reachable from the application dependency graph through both
the composition-reader adapter and accepted-commit repository adapter. A
separate read-only evidence reader, not the application result or production
projection, captures canonical JSON before and after the public command.

The before-image is checked against one exact literal containing all six
required families:

1. order composition;
2. installation case;
3. opening state;
4. process tasks;
5. checklist availability;
6. unrelated decoy facts.

The after-image must remain byte-identical. The original state object is no
longer a disconnected test variable; it participates in the same adapter graph
that the application must traverse for the composition lookup and accepted
commit.

Before the missing-factory guard, six isolated sensitivity cases each capture a
fresh seeded state, perturb exactly one named family, and require the canonical
comparison to change. The run reaches the later intended RED, proving all six
perturbation checks execute and pass. This is a bounded test-support mutation
API only; it is not a production or application port.

## Preserved Gate 2 properties

- The exact owner-approved spec/OpenSpec hashes remain unchanged, and the v1
  `CHANGES_REQUESTED` record remains immutable history.
- The support fixture still cannot construct or return an application result;
  only the missing production factory/application can make Example A GREEN.
- Exact request/case/order/actor, capability
  `assignment_order.original.upload`, literal 327-byte PDF and independent
  digest, immutable composition identity/hash, document date, distinct upload
  time, application-owned root/revision identity, revision number, protected
  evidence JSON, one accepted commit, exact domain-event type and zero rejected
  attempts remain asserted.
- The accepted resource lifecycle still requires exactly one upload-stream
  close, private-stage close, content-lease release and delivery, and zero stage
  aborts.
- The verifier remains deterministic and uses no DB, network, shared or
  production storage, temporary files, children, production documents, or
  secrets. Its sensitivity states are iteration-local memory objects, so no
  external cleanup target exists.
- Authorization-negative, real parser, full event ordering,
  replay/correction/concurrency/fault, maintenance and worker-barrier matrices
  correctly remain task 2.2 and are not claimed by this approval.

## Reproduced evidence

```text
$ php -l tests/Support/AssignmentOrderOriginalInitialProcessState.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalInitialProcessState.php

$ php -l tests/Support/AssignmentOrderOriginalInitialFixture.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalInitialFixture.php

$ php -l tests/InstallationProcess/assignment_order_original_upload_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_test.php

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_upload_001_test.php
exit 0

$ git diff --check 27b5eb9..8be5a7a
PASS (no output)
```

The sole current RED remains the absence of the approved production
`AssignmentOrderOriginalVerificationFactory`; every six-family sensitivity
assertion runs before that guard. Gate 4 may proceed for the exact task-2.1
bytes reviewed here. This approval does not authorize treating task 2.2, the
whole executable specification, or the OpenSpec change as complete.

## Reviewed SHA-256 inputs

```text
97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a99946c8662b8cf6dbc21ff8e513bf0813cc6d6604a92087a03c019e2922c482  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
b81f11b5aabd69645404b624d5301cd65a209b870d06ef587dcb34eebbcfc9b2  openspec/changes/replace-pilot-registration-with-original-upload/design.md
127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
5b459540a6bae9737ce52b4c78f501d33137cfa1836d3d5e6fc0b8e0c20d444d  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
0579c317f073fb303ec6bbe89c11b9f42643989546501ac4f35b2702f3559469  tests/InstallationProcess/assignment_order_original_upload_001_test.php
ad45dd35a05b0252497ec56812da22bea13e8be23ef3441fa82dad457f527075  tests/Support/AssignmentOrderOriginalInitialFixture.php
ffec151ff4d11b66184ffb88f8860fac9bac7a2d5166faa1a1f6a736d96f76fa  tests/Support/AssignmentOrderOriginalInitialProcessState.php
75c316b5f994e59c391c5338c8263300acd0b331b88f357a158a119e5e0a62d7  docs/operations/assignment-order-original-upload-initial-red-v2-2026-09-04.md
db24ffaffe46c2b8c47e09879de64b286331fdf07351c93f585294426dcf8ff2  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-initial-v1.md
```

The review record path is metadata because a self-hash is circular. Any change
to reviewed test/support bytes requires a fresh independent review.
