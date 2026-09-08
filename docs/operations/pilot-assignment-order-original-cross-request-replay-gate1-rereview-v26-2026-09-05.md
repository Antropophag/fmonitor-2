# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v26 cross-request replay — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_cross_replay_rereview`
- Reviewed commit: `f751eacf48a554e4caceffe8ee8da5471a1ce9cc`
- Previous review: `39778fc74606a1060be2c6c0374f07be345c6b94`
- Scope: v26 correction of the identical cross-request correction-race oracle,
  reviewed against the complete executable specification, OpenSpec, evidence
  schema and worker-result contracts; no tests or production implementation
  reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Findings

### G1-V26-01 — BLOCKER: the claimed exact requests JSON violates the normative canonical encoding

V26 fixes the substantive omissions from the previous review. It assigns fixed
distinct request IDs A `00000000-0000-4000-8000-000000000101` and B
`00000000-0000-4000-8000-000000000102`, fixes both clocks at
`2026-09-02T09:16:00Z`, and places both workers after fingerprint miss before
either release. The parent can therefore observe both `READY` records, release
A, prove its complete accepted result and fresh committed evidence, and only
then release B. The supplied B result has the exact approved 11-key result
order, lower backed status, explicit null/JSON boolean/integer values, B's
request ID, and the winner's revision-2 evidence. The prose also correctly
requires initial+A only in request evidence, no B domain/fingerprint/event/audit
fact, byte-identical evidence after a same-B retry, and retains terminal audited
conflict semantics for a different-payload loser.

However, section 15 normatively says that evidence JSON is **recursively
key-sorted** UTF-8 JSON. The newly added block is explicitly introduced as the
exact post-race requests evidence, but its top-level key order is
`schema,items`, and each item uses the declaration/display order
`requestId,status,reasonCode,...,uploadedAt`. Neither level is key-sorted. For
example, the top-level order must begin `items` then `schema`; an item must
begin `byteSize,currentRevisionId,documentDate,...` under bytewise ASCII key
ordering. A compliant evidence reader would therefore emit different bytes
from the published literal, while a non-canonical implementation could satisfy
the new oracle. This leaves the RED author without one coherent byte-exact
inventory expectation and fails the requested schema/literal independence.

Required correction: replace the exact requests block with the recursively
key-sorted canonical JSON required by section 15 (retaining exactly the initial
and A facts), or explicitly amend the canonical evidence encoding rule and all
affected shapes/literals through Gate 1. The narrow first option is technical
and does not change product behavior. Then obtain a fresh independent rereview.

Until corrected, OpenSpec task 1.19 remains unchecked and task 4.1 must not use
this exact inventory as approved Gate 1 authority.

## Verification

```text
$ git rev-parse HEAD
f751eacf48a554e4caceffe8ee8da5471a1ce9cc

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 39778fc..f751eacf --check
PASS (no output)

$ fixed race semantics
A request / B request: present and distinct
A/B clock: fixed
both READY before RELEASE: present
A release + accepted/evidence proof before B release: present
B exact 11-key LF Result: present and coherent
requests facts: exactly initial+A
B absent from domain/fingerprint/event/audit: required
same-B retry exact line/inventory: required
different-payload loser terminal conflict/audit: retained

$ exact requests inventory encoding
required recursively key-sorted JSON: FAIL
published top-level order: schema,items
published item prefix: requestId,status,reasonCode
```

OpenSpec structural validation and whitespace checks pass; they cannot detect
the contradictory byte-level JSON ordering.

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
922a9a67883d66a64c4245e78fb39471b2405321cd5457f2e59662b9a8a8f8fb  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
d15f345aa37a148e8e03c613c050f7220d4b94c36674e86f26e732348b3cf27d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
be3f46d1efd8724cb630a43df1e656f7dd075d0e38420f044981b19af4ea4bcf  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
be3146951109aa2baccac4b78a27e5fd1d7a27f366d2303556d307612b99a760  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d4fe2591767be15aefd24e689d0a9e71d0b4812c0f2a2b7ac1883399c77676d9  docs/operations/assignment-order-original-cross-request-replay-gate1-gap-2026-09-05.md
f926588401abd7f2bab9655b3072b75c0f06ea07d76321f50a4e6fe6a2b623d6  docs/operations/assignment-order-original-database-setup-technical-approval-2026-09-04.md
e71a4bc2e24f4984a52b31935a8dffed4106356282acf6a26e6b82538efe3157  docs/operations/assignment-order-original-worker-result-technical-approval-2026-09-05.md
```

This review record omits its own circular hash.
