# Independent planning review — assignment-order composition selection

- Date: `2026-09-05`
- Reviewer task: `/root/selection_planning_review`
- Reviewed commit: `d4a40748752e8d97ebc8519ebae8f49d350e72c1`
- Change: `select-assignment-order-composition-without-template`
- Verdict: **APPROVED_FOR_PLANNING**

This verdict approves only the coherence and readiness of the OpenSpec planning
package. It is not approval of an executable Gate 1 contract, RED, tests,
production code, Gate 5, release, archive, or Done.

## Exact reviewed hashes

Planning artifacts at the reviewed commit:

```text
7555aa06e53ea191a4fd7854f1fcf46ceb785a0f87b18154b84981bfe40bce5e  openspec/changes/select-assignment-order-composition-without-template/.openspec.yaml
2a37e18a892002ac4470eabca78b1015cf250ca65beb68aff7ada6159315dfe6  openspec/changes/select-assignment-order-composition-without-template/proposal.md
0feff7e6ad36e2aa43094661d0dbb94731012e185a323e7c98f44fb16a7ccede  openspec/changes/select-assignment-order-composition-without-template/design.md
6e7068848032486a5e6ee45b0ad07fd7142a926f010fd10549ece273560aa0f2  openspec/changes/select-assignment-order-composition-without-template/specs/pilot/assignment-order-composition-selection/spec.md
b5c78c4f2a8373d1fedb6cfa7067c8cc5bd19aea32073dcfdc4b4c7460383258  openspec/changes/select-assignment-order-composition-without-template/tasks.md
```

Independent inventory and approved-behavior anchors:

```text
7db4bfc46e41ece5537cde0001974c23f88d89d7897a2fe60b6f80991a7cbb90  docs/operations/assignment-order-direct-upload-seam-inventory-2026-09-05.md
39a0d3454c63f64a54a8bbe8a8f8abd172f2f7576a236319894ab25cf81f4a4d  docs/operations/pilot-assignment-order-original-owner-decision-2026-09-02.md
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
```

Governing sources read at the reviewed commit:

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
c54abf9d290bbc2653f03a9d7ce4c842167b682b0b4d682bb0fd2ff271abe259  openspec/config.yaml
```

The design's inventory anchor resolves to exact evidence commit
`16ba3e65401ab6b3acb883a96f590bc1e9eb7c3d`.

Supplemental authorization handoff evidence, committed after the unchanged
reviewed package:

```text
a724bb0eeaf84a035c7b08aa050bd89e9ab85d19  evidence commit
7652471af14b171756aecc4095a0aa015fea1c9975ad0fd25c2bc463261b06bc  docs/operations/selection-authorization-handoff-evidence-2026-09-05.md
```

## Assessment

### Coherence and approved intent

The four planning artifacts describe one bounded missing seam: persist the
chosen installers and one control engineer as an immutable composition/order
identity before original upload, without requiring PDF template rendering or
template storage. This is consistent with the owner's explicit decision that
composition is selected before upload whether or not a template is generated.
Proposal value, delta requirements, design decisions, and ordered tasks agree.

The independent inventory truthfully establishes the gap: the only current
public creator renders before persistence, while the original-upload command
requires an existing order/composition. The plan neither mistakes fixture SQL
for production behavior nor authorizes an HTTP/private-SQL bypass.

### State boundaries and optional rendering

Selection remains preparation evidence only. The package consistently forbids
it from applying active assignment intervals, accepting original evidence,
setting actual start, opening work, or unlocking the checklist. Original upload
and opening remain separate commands; therefore the plan does not weaken the
approved opening gate or change active assignments.

The optional-render follow-up is properly bounded: rendering consumes the same
saved immutable composition identity, owns no second selection path, and its
failure cannot roll back selection or block direct original upload. Task 3.2 is
an integration step after the executable contract, RED review, and minimal
selection owner; it does not authorize changing the renderer, original PDF
parser/storage, historical bytes, or the protected `PILOT-E2E-FLOW-001`
contract during planning.

### Truthful Gate 1 gaps and grants

The package accurately leaves exact command/result DTOs, rejected codes,
request/replay/stale behavior, pre-original correction/version semantics, and
physical `order_date`/`documentDate`/composition-hash compatibility to the named
executable specification. Those omissions are explicit Gate 1 work rather than
claims that planning is executable.

Authorization is likewise not invented. The plan names the owner-approved FKR
actors for the workflow but does not infer an exact selection capability from
original read, upload, prepare, administrator status, or observed legacy
behavior. Exact capability and role mapping must be established from approved
authority or receive an owner decision in Gate 1. The separate role/capability
mapping analysis is outside this review and cannot silently grant access. The
supplemental handoff evidence confirms that current prepare and original
authorization use distinct persisted mechanisms; it requires Gate 1 to
reconcile them and supplies negative controls, but expressly makes no new grant
decision and does not choose a permission code.

Append-only history, atomic persistence, retry idempotence, expected-version
conflict protection, one mutation owner, and no runtime DDL/new rapid-pilot
domain logic are all explicit. No unresolved product decision is disguised as
an implementation detail; `NEEDS_GRILL` remains required if inherited approved
contracts do not answer a new behavior question.

## Verification

```text
$ openspec validate select-assignment-order-composition-without-template --strict
Change 'select-assignment-order-composition-without-template' is valid

$ git diff --check
PASS (exit 0 before this append-only review record was added)
```

## Verdict consequence

**APPROVED_FOR_PLANNING.** The exact package may advance to drafting
`ASSIGNMENT-ORDER-COMPOSITION-SELECT-001` and resolving its explicitly listed
authority, correction/version, replay/conflict, and date/identity questions.
This review grants no executable Gate 1 approval and no Done status. Gate 2 and
all test or production edits remain closed until a fresh independent Gate 1
review approves the completed executable contract at exact hashes.
