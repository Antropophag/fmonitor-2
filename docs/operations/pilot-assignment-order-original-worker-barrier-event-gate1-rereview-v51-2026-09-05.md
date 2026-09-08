# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v51 worker barrier event — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_barrier_event_rereview`
- Reviewed commit: `9cc9a0683d2103020f1c1cb240b2e40f93bde63c`
- Predecessor reviewed commit: `427d343895f97c64e62dd6fae1548e8e0f50df45`
- Amendment base named by assignment: `84c300a`
- Scope: v51 executable-spec/OpenSpec worker barrier-event amendment only; no
  test or production implementation reviewed or changed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests, production implementation or prior evidence. This append-only review is
the only authored artifact.

## Findings

### 1. The isolated lease-race upload command is still not a complete exact fixture

The v51 amendment closes both findings from the v50 review:

- the canonical IPC paragraph now makes the configured `barrierEvent` select
  the one lifecycle event that writes exact READY and waits for exact RELEASE,
  while the other event is observed without blocking;
- maintenance requests `00000000-0000-4000-8000-000000000401` and
  `00000000-0000-4000-8000-000000000402` now have an exact 09:00 clock,
  principal, 07:30 cutoff, limit 10, null cursor, results, counts and fresh-reader
  evidence. The 07:00 finalized timestamp makes the selected content eligible.

However, the isolated upload is specified only by request ID `...0400`, clock,
root/revision sequences, Example PDF and selected barrier. It does not state the
remaining exact `Command` values: `installationCaseId`, `assignmentOrderId`,
`actorUserId`, `documentDate`, `compositionConfirmed`, mode, the three lineage
fields, correction reason, filename and declared media type. Nor does it
normatively say that all unspecified fields are copied from the earlier
canonical initial worker command fixture.

Those values are observable and material here. They select the seeded
case/order/composition and authorization preconditions, determine whether the
upload reaches finalize, and determine the request/root/revision/event rows
later claimed by the fresh evidence reader. A Gate 2 test could choose different
eligible case/order/actor/date values and still claim the summarized lease-race
result, so the expected evidence is not independently determined.

Publish one complete canonical JSON upload command line for request `...0400`
(or state an exact field-by-field derivation from the already published initial
fixture, listing every override). Also publish the corresponding exact target
fixture identities/preconditions that make that command eligible. Retain the
already exact clock, generated IDs, content identity, paused no-commit proof,
maintenance commands/results, accepted revision and fresh-reader retention
requirements.

## Confirmed properties

Apart from the missing complete upload fixture, the reviewed amendment is
coherent with the inherited contract:

- finalized identity is deterministically
  `content-sha256-<pdfSha256>` and identical content reuses the same verified
  identity/lease;
- READY occurs after private finalize with the digest-scoped lease held and
  before any commit attempt;
- maintenance cannot acquire the content lock while paused, and after commit it
  retains the same byte-identical blob because the real repository reports the
  accepted reference;
- both maintenance terminal request/audit pairs and exactly one upload fact are
  required through a fresh production evidence reader;
- no fake repository/storage participates; the verifier factory composes real
  production adapters;
- the selector is confined to exact verifier-worker config, invalid config is
  pre-secret exit 70, and production composition binds inert observers with no
  selector.

## Verification evidence

```text
$ git rev-parse HEAD
9cc9a0683d2103020f1c1cb240b2e40f93bde63c

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 84c300a..9cc9a0683d2103020f1c1cb240b2e40f93bde63c
PASS (no output)
```

## Exact reviewed hashes

```text
1950354900548596f563821974cae4c84dfe61d205fc5316732059c8cf84c0d8  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
37cca851a6d5983f78f0185c6174e51f6aadb72f14347182ec4272fdfd2382bc  openspec/changes/replace-pilot-registration-with-original-upload/design.md
61a7aa2e1c65a479528a18a86c62893862c05e6b89f1bbefc39fca1b93036b82  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
881aa3ad68af633df55b286566a72430227f42ea3a04940b344ee31559e04ff0  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
b25e3a8506cf3313fba8d23b7fea2da798fe1ff1c548dc1ae987f9c4a6ae7a1a  docs/operations/assignment-order-original-worker-composition-source-gate1-gap-2026-09-05.md
```

This record intentionally omits its own circular hash.
