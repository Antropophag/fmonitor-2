# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v27 cross-request replay — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_cross_replay_rereview2`
- Reviewed commit: `20cb5166385fa86af7344e8d929f67c56644b168`
- Previous review: `8b9a3c2`
- Scope: v27 correction of the exact identical-race requests inventory,
  reviewed against the complete executable specification, OpenSpec, evidence
  encoding and worker Result IPC contracts; no tests or production
  implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Findings

No blocking findings.

V27 resolves `G1-V26-01` without changing behavior. The exact post-race
requests literal is valid compact UTF-8 JSON whose top-level keys are in
bytewise order `items,schema`; every request object is independently in
bytewise key order
`byteSize,currentRevisionId,documentDate,reasonCode,requestId,retryable,
revisionNumber,rootOriginalId,sha256,status,uploadedAt`. A recursive parser
found no out-of-order object at any level, and compact sorted re-encoding is
byte-identical to the published literal. Array order remains the separately
specified evidence order: initial accepted request first, deterministic winner
A second.

The evidence encoding is correctly separate from Result IPC. The loser B line
retains the mandatory Result key order
`status,reasonCode,retryable,requestId,rootOriginalId,currentRevisionId,
revisionNumber,documentDate,sha256,byteSize,uploadedAt`; it is not recursively
sorted and must not be rewritten as evidence JSON. It echoes B request
`00000000-0000-4000-8000-000000000102`, reports lower-case `replayed`, and
copies the winner's complete revision-2 evidence.

All v26 race semantics remain coherent: A and B have fixed distinct IDs and
the same fixed clock; both reach `AFTER_FINGERPRINT_MISS_BEFORE_CAS` and publish
READY before any release; A is released, its complete ACCEPTED Result and fresh
commit evidence are proved, then B is released. Request evidence contains only
initial plus accepted A. Domain, fingerprint and event evidence contain only
initial plus A's revision-2 winner facts; safe audit contains accepted A and no
B. A later same-B retry repeats authorization/order/stream/fingerprint proof,
returns the same B Result line and leaves every inventory byte-identical.
Different-payload race losers still commit the specified terminal conflict
request and safe audit. This is consistent with execution precedence,
append-only history, authorization fail-closed behavior and the read-only
evidence reader.

Gate 1 is approved for this v27 amendment. Gate 2 may use the exact Result and
requests inventory literals as independent oracles; approval does not imply
that tests or production behavior exist or pass.

## Verification

```text
$ git rev-parse HEAD
20cb5166385fa86af7344e8d929f67c56644b168

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 8b9a3c2..20cb516 --check
PASS (no output)

$ recursive requests-literal check
literal bytes: 743
top-level keys: items,schema
item 0 keys: byteSize,currentRevisionId,documentDate,reasonCode,requestId,retryable,revisionNumber,rootOriginalId,sha256,status,uploadedAt
item 1 keys: byteSize,currentRevisionId,documentDate,reasonCode,requestId,retryable,revisionNumber,rootOriginalId,sha256,status,uploadedAt
recursive sort errors: 0
compact sorted round-trip equals literal: true
request IDs: 00000000-0000-4000-8000-000000000001,00000000-0000-4000-8000-000000000101

$ separate B Result IPC check
keys: status,reasonCode,retryable,requestId,rootOriginalId,currentRevisionId,revisionNumber,documentDate,sha256,byteSize,uploadedAt
status/request ID: replayed / 00000000-0000-4000-8000-000000000102
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
fae5997cb826e3b3489390dc90b0f3c66834f8cdc45258787d8b079be4267845  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
d15f345aa37a148e8e03c613c050f7220d4b94c36674e86f26e732348b3cf27d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
be3f46d1efd8724cb630a43df1e656f7dd075d0e38420f044981b19af4ea4bcf  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
be3146951109aa2baccac4b78a27e5fd1d7a27f366d2303556d307612b99a760  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d4fe2591767be15aefd24e689d0a9e71d0b4812c0f2a2b7ac1883399c77676d9  docs/operations/assignment-order-original-cross-request-replay-gate1-gap-2026-09-05.md
f926588401abd7f2bab9655b3072b75c0f06ea07d76321f50a4e6fe6a2b623d6  docs/operations/assignment-order-original-database-setup-technical-approval-2026-09-04.md
e71a4bc2e24f4984a52b31935a8dffed4106356282acf6a26e6b82538efe3157  docs/operations/assignment-order-original-worker-result-technical-approval-2026-09-05.md
```

This review record omits its own circular hash.
