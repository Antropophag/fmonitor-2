# INSPECTION-ITEM-COMPLETE-001 — independent HTTP/architecture Gate 3 review

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved composition-amended executable spec: SHA-256
  `cdd85ba009e3bbb6993fd50b26ab199caf5017086d43d43bc474586ff0982e7b`.
- Owner composition approval: SHA-256
  `638658b9ed5300d0ae3f6dfb32d10cf4600ea1587415c3d50228ed0185de67c7`.
- HTTP wiring test: SHA-256
  `000eb8e81fc3c4e016e67edf26be9e322d7a553dc3a72dd9184e8fa0f7858f0f`.
- SQL-owner policy test: SHA-256
  `2d60153605fbd51aebb1ddaa875f1cbce26acaddb45a25ef11bde786c7b240a3`.
- HTTP RED evidence: SHA-256
  `d6659ca3a4c304f425715b983e1494b7f6a7436bce2a8d19c442307ce2b426f1`.
- Current `ChecklistSync`: SHA-256
  `eba45bff34689c54088e89b9d4801c8c16bef2b420b3abdaed7f9f98e8c7bef6`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Architecture checker: SHA-256
  `bcf507eba9010d9a0b1ced6101b7800fa919bcae88eae1d2b9f81c9837156b22`.
- Delivery process: SHA-256
  `a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504`.

The worktree contains unrelated and previously existing dirty/untracked state;
this review did not modify it. Only this review record was added.

## Independent reproduction

I ran the evidence commands verbatim:

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php -l tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
php tests/InstallationProcess/inspection_evidence_sql_owner_policy_test.php
```

Both syntax checks passed. The focused behavior test produced:

```text
PHP Fatal error: Uncaught Error: Unknown named parameter $inspectionRecording
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_http_wiring_test.php
```

The architecture test produced:

```text
PASS: InspectionEvidence SQL owner policy
```

The RED is honest for the first missing behavior: current production has no
`ChecklistSync` composition parameter for the approved public recording seam.
Construction reaches ordinary PHP execution without a database fixture, and
the runner recognizes the expected failure. It is not a broken external setup.

## Checks that pass

- The spy implements the public `InspectionRecording` interface and observes
  only the public `ChecklistSync::accept` call. It neither reads persistence nor
  exposes a private production hook.
- The deliberately unconnected `mysqli` makes the item branch sensitive to any
  remaining legacy SQL before or after delegation. The exact call count makes a
  duplicate delegation detectable.
- Command projection uses literal expected values. In particular, malicious
  client `actorUserId=9999` is ignored, authenticated `HttpUser` ids `7301` and
  `7303` reach the command, and the remaining envelope fields are independently
  asserted. Authorization stays in the application seam rather than being
  reimplemented by HTTP.
- `ACCEPTED` and `ACTOR_NOT_AUTHORIZED` are returned by a public-interface spy,
  so those two adapter mappings are deterministic and mutation-sensitive.
- The SQL-owner policy is deterministic and independently green. It ratchets
  the stated filename ownership convention within `app/InspectionEvidence`;
  it is supporting architecture evidence, not a substitute for the behavioral
  RED or for the repository-wide architecture check.

## Blocking findings

### HTTP-01 — the claimed non-item isolation is not exercised

The only “non-item” input is `['type' => 'photo_uploaded']`. It is missing every
required common envelope field, so current `ChecklistSync::accept` returns from
line 33's common envelope validation before operation dispatch, transaction or
the `photo_uploaded` branch. Consequently the test would still pass if an
implementation accidentally delegated every *valid* operation type to
`completeItem`: the spy remains untouched only because this malformed input
never reaches a branch.

This does not support the assertion text “Non-item branches never call
completeItem” or the approved design's requirement to switch only
`item_completed` while preserving photo, correction and section behavior.

Required Gate 2 change: add at least one deterministic, syntactically valid
non-item operation which actually reaches an existing non-item branch, assert
its characterized public result, and assert zero additional calls to
`InspectionRecording`. Use a legitimate isolated fixture/seam so the case does
not merely fail on an unconnected database. Alternatively narrow the present
assertion/evidence to “malformed common envelopes do not delegate” and add a
separate reviewed RED that genuinely proves valid non-item dispatch isolation
before implementation.

### HTTP-02 — typed-result translation is under-sensitive and partly unspecified

The approved seam has stable typed outcomes beyond the two exercised here,
including at least `DUPLICATE`, `STALE_REVISION` and
`OPERATION_PAYLOAD_CONFLICT`, plus the remaining rejected statuses. An adapter
that maps `ACCEPTED` specially and collapses every other status to the tested
`['status' => 'rejected', 'revision' => N]` would pass this test while breaking
replay/conflict semantics visible to the existing sync client. In particular,
no test protects the legacy public `duplicate` and `conflict` response shapes.

The executable spec says HTTP adapters “map its typed result” but does not give
an exact status-to-HTTP response table. The test independently chooses an exact
shape for `ACTOR_NOT_AUTHORIZED`, while leaving the other mappings unconstrained.
That is both a sensitivity gap and a Gate 1 observability ambiguity for minimal
Gate 4 implementation.

Required change: first record/cite an approved or characterized exact mapping
for every typed result that this HTTP adapter can receive (including the
deployment/infrastructure failure path). Then drive the adapter through the
public spy for the meaningful mapping classes—at minimum accepted, duplicate,
stale revision, payload conflict, ordinary business rejection and
infrastructure failure—and assert exact public responses and no extra seam
calls. If several typed statuses intentionally share one public response, make
that equivalence explicit in the approved/characterized mapping rather than
letting implementation invent it.

## Gate decision

Traceability, public seam choice, actor trust, literal command translation,
determinism and the initial missing-composition RED are sound. However HTTP-01
allows a plausible wrong dispatcher to pass, and HTTP-02 allows a plausible
wrong result mapper to pass while the exact mapping is not fully fixed at Gate
1. Per `docs/development-process.md`, these blockers return the increment to
Gate 1/2. Gate 4 must not start from these exact tests as an approved complete
HTTP-wiring increment.
