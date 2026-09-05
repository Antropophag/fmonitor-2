# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v52 worker barrier event — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_barrier_event_rereview2`
- Reviewed commit: `d6ffa28c548ee474fadda789395a518d021c0ba0`
- Predecessor review commit: `585827e`
- Scope: v52 executable-spec/OpenSpec worker barrier-event amendment only; no
  test or production implementation reviewed or changed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests, production implementation or prior evidence. This append-only review is
the only authored artifact.

## Review result

The v52 amendment closes the sole v51 finding. The isolated lease-race upload
request `00000000-0000-4000-8000-000000000400` now has all twelve exact
top-level Command fields and all three exact upload fields: `INITIAL`, case
`4512`, order `81`, actor `18`, document date `2026-09-01`, confirmed
composition, null root/target/expected/reason, the canonical 327-byte PDF,
filename `lease-race.pdf`, and declared media type `application/pdf`.

The referenced v42 production-derived fixture is itself exact and observable:
`seedExampleA()` provides the active actor/role/upload capability, case/order
ownership and order version/date, installers `7001,7002`, engineer `31`,
composition identity `composition-81-v1`, and composition SHA-256
`388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5`.
The command therefore cannot silently select a different eligible fixture.

Clock `2026-09-02T07:00:00Z`, root/revision sequences
`original-0040`/`revision-0040`, canonical PDF SHA-256
`4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`,
327-byte size and content identity are exact. After release, the complete
accepted Result is independently determined across all eleven fields: accepted
status, null reason, false retryable, request0400, root original-0040, current
revision-0040, revision 1, document date `2026-09-01`, the canonical digest,
327 bytes and uploadedAt `2026-09-02T07:00:00Z`.

## Lease/maintenance causality and evidence

All v51-confirmed lifecycle properties remain coherent and normative:

- `barrierEvent` is the final required worker-config key and accepts only the
  fingerprint-miss or after-private-finalize event; the selected event alone
  writes exact READY and waits for exact RELEASE, while production has no
  selector;
- request0400 pauses after private finalize, with the digest-scoped lease held,
  the exact finalized identity timestamped 07:00 and no commit attempted;
- real maintenance request0401 at 09:00 with cutoff 07:30, exact principal,
  limit 10 and null cursor observes the eligible content but receives
  `PARTIAL/LOCKED`, retryable true, counts `1/0/1/0`, retaining the unchanged
  blob and atomically publishing its terminal request/audit pair;
- only after maintenance evidence is observed does RELEASE permit the real
  upload commit and exact accepted result; request0402 with the same maintenance
  inputs then receives `COMPLETED/null`, retryable false, counts `1/0/1/0`
  because the real repository reference is rechecked;
- a fresh production evidence reader must show both maintenance request/audit
  pairs, exactly one upload request/root/revision/event referencing the same
  byte-identical content, unchanged process evidence, and no fake storage or
  repository participation.

This is sufficient for Gate 1. Task 4.1 may resume subject to the remaining
mandatory RED and independent Gate 3 requirements.

## Verification evidence

```text
$ git rev-parse HEAD
d6ffa28c548ee474fadda789395a518d021c0ba0

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 585827e..d6ffa28c548ee474fadda789395a518d021c0ba0
PASS (no output)
```

## Exact reviewed hashes

```text
4891acd83990b9f1b93aee94b7b1326572f8259d332d1e48a9a1567ea57dad40  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
37cca851a6d5983f78f0185c6174e51f6aadb72f14347182ec4272fdfd2382bc  openspec/changes/replace-pilot-registration-with-original-upload/design.md
61a7aa2e1c65a479528a18a86c62893862c05e6b89f1bbefc39fca1b93036b82  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b686e65052a3948c3daa837f416868ca4d4acb173e72cb1c0a48c74c083056c3  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
b25e3a8506cf3313fba8d23b7fea2da798fe1ff1c548dc1ae987f9c4a6ae7a1a  docs/operations/assignment-order-original-worker-composition-source-gate1-gap-2026-09-05.md
```

This record intentionally omits its own circular hash.
