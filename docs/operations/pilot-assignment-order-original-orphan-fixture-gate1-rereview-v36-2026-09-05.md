# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v36 orphan fixture — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_orphan_fixture_rereview2`
- Reviewed commit: `15155c303cddbe01cb7ffddd34c2d50e0cb5a0e1`
- Predecessor review: `e28ee0f`
- Prior review: `docs/operations/pilot-assignment-order-original-orphan-fixture-gate1-rereview-v35-2026-09-05.md`
- Scope: verification-only private-orphan fixture amendment, its OpenSpec
  coherence, and sufficiency for deterministic real-maintenance Gate 2; no
  tests, fixture implementation, or production implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed executable specification, OpenSpec
artifacts, tests, fixtures, or production implementation. This append-only
review is the only authored artifact.

## Rereview of the remaining v35 findings

### 1. Exact observable inventories and rejection sensitivity — closed

V36 publishes the exact `aoou-blobs-v1` inventory after both canonical creates,
requires exact replay to leave that evidence byte-identical, and publishes the
exact empty inventory after completed maintenance and replay. Invalid, future,
collision, and each one-shot `STAGE`, `STAGE_WRITE`, and `PRIVATE_FINALIZE`
primitive-failure path must throw the already fixed exception contract and
leave its captured pre-call inventory byte-identical. This is observable at the
existing real evidence-reader seam without a private DB/filesystem oracle.

The contract is sensitive to partial primitive effects: even a fault after an
earlier storage primitive cannot leave a stage, final blob, metadata member,
request, audit, event, revision, or reference fact. Fault scripts remain
injected, one-shot verification dependencies and have no production selector.

### 2. Task-root authority and configured-production-root isolation — closed

The fixture factory now receives the separately trusted
`AssignmentOrderOriginalProductionConfig`. Its configured private root must
first pass production validation. Canonical task-root and production-root
realpaths must be disjoint: equality and both ancestor/descendant directions
are rejected as the fixed fixture-unavailable outcome before fixture member
reads or writes. Canonical realpath comparison also closes alias spellings.

This composes with the already approved exact task-root marker/token, UID,
`0700` root, `0600` regular marker, `lstat`/`realpath`, link-count, parent/member
graph, repository exclusion, no-repair rule, and fresh pre-removal revalidation.
The factory still cannot delete and remains unavailable to production
bootstrap/application/HTTP selection.

### 3. Main, boundary, newer, and future deterministic runs — closed

The main isolated run fixes clock `09:00:00Z`, cutoff `07:30:00Z`, full request
UUID `...0201`, principal, limit/cursor, binary candidate order, exact
`COMPLETED` and `REPLAYED` counts/result fields, request/audit JSON, pre/post
blob inventories, and stable replay. Its abandoned and finalized metadata use
the command-owned `07:00:00Z`, exact byte sizes `15`/`19`, and the independently
fixed finalized digest.

Separate fresh DB/root runs fix cutoff-equal `07:30:00Z` and next-second
`07:30:01Z` candidates. Requests `...0202` and `...0203` have exact
COMPLETED/REPLAYED outcomes, counts, null cursor, request/audit JSON, and empty
versus retained post-storage evidence. Each run has audit ID 1 and exactly one
request plus audit; replay is byte-identical and no state is shared. The exact
future fixture second `09:00:01Z` is rejected before mutation and its before
inventory remains byte-identical. The fixture accepts only whole UTC seconds,
so neither the boundary test nor future test needs sleep, mtime edits, or an
unapproved finer timestamp representation.

### 4. Size, digest, and metadata time versus filesystem time — remains closed

V36 preserves the exact `15` and `19` byte literals and finalized SHA-256
`edebbe397df1e6932d83cbf742512b480524c89bc6e9b6b679fafec5896db24f`.
Command-owned metadata time remains the sole candidate-age input; filesystem
mtime/ctime are expressly ignored. Fixture creation uses the same production
validator, codec, atomic write/rename/fsync primitives, metadata grammar, and
digest-lock namespace later consumed by real maintenance.

## Gate decision

Gate 1 for the v36 verification-only orphan fixture amendment is **APPROVED**.
The contract is executable at the declared fixture, real-maintenance, and
evidence-reader seams and resolves every blocking finding from v34 and v35.
Gate 2 may now add the smallest deterministic RED that implements this approved
matrix without changing its expectations.

This approval is limited to the specification amendment. It does not approve
future tests or production code and does not close task 1.25 by itself.

## Verification

```text
$ git rev-parse HEAD
15155c303cddbe01cb7ffddd34c2d50e0cb5a0e1

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 21a9fb9..15155c3 --check
PASS (no output)

$ printf %s stage-orphan-v1 | wc -c
15

$ printf %s finalized-orphan-v1 | wc -c
19

$ printf %s finalized-orphan-v1 | shasum -a 256
edebbe397df1e6932d83cbf742512b480524c89bc6e9b6b679fafec5896db24f

$ printf %s boundary-orphan-v1 | wc -c
18

$ printf %s newer-orphan-v1 | wc -c
15
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
85e0584008ce452d1ab8c364e989cec447aee4d6945461e81a72bdb0572f9bb2  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
012ca687d9cf6a3733648a874c59424b636a772707b33a35e7b9285be75a579a  openspec/changes/replace-pilot-registration-with-original-upload/design.md
24277de90f1cb0ff0fea6d01ce951ddcf75275619ef34fe396fa82af9b72579a  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9d18e3f5094b13d3c4242aa7ccd60c99cbd008a6699a7df5d3012a96b1c15493  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
8b76d052de227b011c96a60b306624512089f7d53a2632beef0cbe9d7aac425b  docs/operations/pilot-assignment-order-original-orphan-fixture-gate1-rereview-v35-2026-09-05.md
```

This review record omits its own circular hash.
