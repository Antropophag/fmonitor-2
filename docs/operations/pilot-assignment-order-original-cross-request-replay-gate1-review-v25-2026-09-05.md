# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v25 cross-request replay — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_cross_replay_gate1`
- Reviewed commit: `c889b0326a02a4e6ad585faa9893515e4821853d`
- Amendment base/gap: `dd515e6`
- Scope: cross-request fingerprint replay Result identity and persistence effects,
  reviewed against the complete current executable specification, OpenSpec,
  approved schema/setup and worker-result contracts; no tests or production
  implementation reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Finding

### G1-V25-01 — BLOCKER: mandatory distinct-request race still has no exact IPC example

The amendment resolves the semantic contradiction identified by `dd515e6`:
every returned Result echoes the current invocation request ID; a distinct
request that finds the winner fingerprint returns `REPLAYED`, copies all seven
winner evidence fields, persists no loser request row, safe audit or domain
event, and leaves the winner row unchanged. A later retry of that loser ID must
again pass authorization, order, stream and fingerprint proof because no
terminal loser row exists. The identical race inventory therefore contains
only the winner accepted request/revision/event, whereas the different-race
loser retains the already specified terminal conflict request and audit. This
is coherent with request-precedence, accepted fingerprint identity, the
read-only evidence reader, v1 schema constraints, append-only history and the
authorization fail-closed rule.

But the gap explicitly required an exact two-distinct-request worked example
and canonical Result line so the five-FD parent can derive its expectation
without implementation knowledge. V25 adds only prose. Section 12 still has no
distinct-request replay example; the canonical worker fixture section names no
request IDs for the two identical workers; and the exact worker Result block
still contains only Example A acceptance, its same-request retry, and a
new-request stale conflict. Consequently the RED author must invent both loser
identity and the byte-exact replayed JSON line, despite task 1.19 claiming IPC
independence. OpenSpec's new scenario repeats the semantics but supplies no
literal oracle.

Required correction: publish fixed, distinct winner and loser request IDs for
the identical correction race and the exact LF-terminated canonical loser
`REPLAYED` Result line. It must contain the loser request ID and the winner's
exact root/current revision, revision number, document date, SHA-256, byte size
and uploaded-at values. State the exact post-race request/audit/event inventory
using those identities (winner only), and retain the existing rule that a
different-payload loser commits its specified terminal conflict request/audit.
No schema change or new product behavior is required.

Until corrected and freshly reviewed, OpenSpec task 1.19 remains unchecked and
command-matrix task 4.1 must not claim complete Gate 1 authority for the
identical cross-request race.

## Verification

```text
$ git rev-parse HEAD
c889b0326a02a4e6ad585faa9893515e4821853d

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff dd515e6..c889b032 --check
PASS (no output)

$ reviewed exact-result inventory
Example A accepted: present
Example A same-request replay: present
new-request stale conflict: present
distinct-request fingerprint replay: missing
identical-race fixed winner/loser request IDs: missing
```

OpenSpec structural validation and whitespace checks pass; they do not close
the missing executable oracle.

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
36d06704c5024c3006b74b528b2df2d2cad8847a14000590e0a2a2a82107a613  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
d15f345aa37a148e8e03c613c050f7220d4b94c36674e86f26e732348b3cf27d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
be3f46d1efd8724cb630a43df1e656f7dd075d0e38420f044981b19af4ea4bcf  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
58b4ca795afc090c80bfe2137446a0c02c5d57b73319a0b9af6becc6163679a7  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d4fe2591767be15aefd24e689d0a9e71d0b4812c0f2a2b7ac1883399c77676d9  docs/operations/assignment-order-original-cross-request-replay-gate1-gap-2026-09-05.md
f926588401abd7f2bab9655b3072b75c0f06ea07d76321f50a4e6fe6a2b623d6  docs/operations/assignment-order-original-database-setup-technical-approval-2026-09-04.md
e71a4bc2e24f4984a52b31935a8dffed4106356282acf6a26e6b82538efe3157  docs/operations/assignment-order-original-worker-result-technical-approval-2026-09-05.md
```

This review record omits its own circular hash.
