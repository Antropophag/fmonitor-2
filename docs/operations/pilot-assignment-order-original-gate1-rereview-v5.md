# Fresh independent Gate 1 rereview v5 — assignment-order original upload

Date: 2026-09-04  
Reviewer: separately tasked agent `/root/assignment_original_gate1_v5`  
Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v4  
Scope: full current executable/OpenSpec package and closure of the v4 CAS-conflict content-lease finding; no tests or production implementation reviewed  
Verdict: **READY_FOR_OWNER_APPROVAL**

## Exact reviewed artifacts

```text
97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a99946c8662b8cf6dbc21ff8e513bf0813cc6d6604a92087a03c019e2922c482  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
b81f11b5aabd69645404b624d5301cd65a209b870d06ef587dcb34eebbcfc9b2  openspec/changes/replace-pilot-registration-with-original-upload/design.md
1ae8fb503863c602dd034e972dda32b0a5d228c9a7b4cd5017c42ddb506c7c3e  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
08a3f37cc6d03e1057f5ceb0347ff53c337a5369bef455b0e23961229c78cbf7  docs/operations/assignment-order-original-upload-gate2-constructibility-gap-2026-09-02.md
c3151db747091a8a15508998b44e693ba7d3d29b9995c3678f0dc2e763949878  docs/operations/pilot-assignment-order-original-gate1-rereview-v4.md
```

Canonical context and process consulted:

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

## Verdict basis

The current batch is coherent, observable at one public application seam and
constructible for Gate 2 without the RED author inventing architecture. It
defines exact PHP command/result and dependency ports, production and verifier
factories, immutable evidence readers, real-parser obligations, storage and
repository outcomes, maintenance composition, and the bounded five-FD worker
protocol. Expected values and stable transcript are independent literals. The
slice remains limited to private original evidence: HTTP/read/download,
composition application, opening and the blocked legacy E2E amendment remain
explicitly outside it.

Authorization is fail-closed and exact-capability based after active-user and
active-role checks. Request replay precedence, composition identity, received
byte ceiling, passive-PDF policy, append-only revisions, terminal attempt audit,
commit ambiguity and response loss have observable result and mutation
contracts. The executable spec, proposal, design, tasks and delta spec agree on
these boundaries and on the named downstream changes.

## v4 blocking-finding disposition

**Resolved.** For `commitAccepted() -> CONFLICT`, the upload-owned typed content
lease now remains held through both mandatory accepted-fingerprint and
current-lineage rereads. Only after those reads select a provisional `REPLAYED`,
exact `CONFLICT/*`, or `FAILED/PERSISTENCE_FAILURE` outcome is release attempted
exactly once. This ordering prevents maintenance from deleting the finalized
content while the CAS loser is resolving its durable outcome.

A typed `FAILED` release or thrown exception preserves that selected outcome,
is safe-logged exactly once with phase `commit_conflict`, leaves the exclusion
token to storage-owned recovery, and does not skip the required non-replay
conflict terminal-result/attempt-audit transaction. The failure matrix,
normative PHP contract prose, design and delta scenario state the same rule.
The former permanent-`LOCKED` ambiguity is therefore closed without adding a
public blob or allowing the business command to delete/retry storage content.

## Constructibility and scope review

- The prior constructibility gap's application, DTO, deterministic input, PDF,
  storage, repository, evidence, maintenance and worker surfaces are present as
  syntactically valid typed PHP declarations and exhaustive normative prose.
- The literal precedence resolves authorization versus stored replay, order
  lookup versus shape validation, future-date checks versus stream acquisition,
  semantic replay versus stale-target checks, and CAS conflict reconciliation.
- `TARGET_NOT_FOUND` is present in the result matrix, PHP reason enum, correction
  rules and delta scenario; changed-composition collision is derived from the
  trusted immutable/current snapshots rather than caller input.
- Maintenance has exact command/result statuses, authorization, pagination,
  lock/reference/delete ordering, count invariant, retryability and atomic
  terminal result/audit behavior. It shares the digest exclusion domain with
  upload leases.
- Verification can prove no downstream mutation and private-orphan behavior
  through declared evidence readers, while the real parser and shared
  MariaDB/private-storage races have explicit obligations.
- No current acceptance claim silently restores manual registration or changes
  opening/checklist state. Owner exact-hash approval remains required before
  Gate 2; this review does not itself approve product behavior.

No blocking ambiguity or new user-visible decision was found in the reviewed
batch. It is ready for owner approval at the exact hashes above.

## Verification evidence

```text
$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ (prepend <?php and namespace; concatenate all normative php fences) | php -l
No syntax errors detected in Standard input code

$ git diff --check
PASS (no output)
```

The reviewer changed no executable specification, OpenSpec artifact, test or
production file. Only this append-only review record was added. Task 1.7 remains
open: Gate 2 is not authorized until the owner records approval of this exact
current executable/OpenSpec hash batch.
