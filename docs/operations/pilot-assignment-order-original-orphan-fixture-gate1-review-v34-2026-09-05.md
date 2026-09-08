# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v34 orphan fixture — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_orphan_fixture_gate1`
- Reviewed commit: `00df4434a07596d57a825337ddb0cd63b5276447`
- Triggering gap: `e073af9fac64dd8e9a2032b48bc2b1b0b67d3341`
- Scope: verification-only private-orphan fixture amendment, its OpenSpec
  coherence, and sufficiency for a deterministic real-maintenance RED; no tests
  or production implementation reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed executable specification, OpenSpec
artifacts, tests or production implementation. This append-only review is the
only authored artifact.

## Findings

### 1. Fixture outcomes are not executable or independently assertable

`AssignmentOrderOriginalPrivateOrphanFixture::create()` returns `void`, while
the prose requires exact replay to be a no-op, a same-identity mismatch to be a
“fixed fixture conflict before mutation”, and invalid root/identity/bytes/time
to be rejected. No exception class, exact exception message, result/status enum,
or precedence among these cases is declared. Consequently a Gate 2 test cannot
distinguish the approved collision from an arbitrary `Throwable`, cannot assert
future-time/path rejection, and cannot prove fail-before-mutation without
inventing observable behavior.

Define one closed fixture result/failure contract (or fixed exception types and
messages), with exact precedence for root validation, command scalar validation,
existing exact replay, and existing collision. Include the post-call observable
inventory for created, replayed and rejected/conflicting calls.

### 2. “Task-owned root” is not a closed security boundary

The public factory accepts only an arbitrary `string $privateStorageRoot`.
“Uses the same production path validator/ownership/mode checks” and “accepts
only task-owned root” do not define how task ownership is established or how a
production, repository, symlink/alias, ancestor, sibling, or pre-existing
non-owned root is rejected. The ordinary private-storage factory intentionally
accepts the trusted production root, so reusing that validator alone does not
establish verification ownership or production isolation.

Specify the exact verification-root authority: canonical absolute-path rules,
required pre-existing ownership marker/token (or equivalent unforgeable
construction input), repository/production-root exclusion, symlink and mode
rules, validation timing, and fixed failure outcome. Cleanup must remain limited
to maintenance plus a fresh revalidation of that exact owned root; no fixture
API delete is needed.

### 3. The requested deterministic real-maintenance example is incomplete

The triggering gap explicitly required a canonical
clock/cutoff/candidate-timestamp example. V34 fixes only the candidate timestamp
(`2026-09-02T07:00:00Z`) and leaves production maintenance on the real clock.
It publishes no exact maintenance request UUID, cutoff, batch limit/cursor,
attempt clock/result line, expected ordered candidate page, terminal request or
audit JSON. Thus the real MariaDB/private-storage RED still has to invent values
and cannot independently assert age-boundary selection, both deletions, counts,
cursor, replay, audit atomicity, or unchanged DB/domain facts.

Publish one complete worked example. At minimum pin the maintenance clock (or a
closed rule for its captured real instant), an eligible cutoff, request ID,
principal, limit/cursor, ordered two-candidate inventory, exact
`COMPLETED`/counts/result fields, post-delete inventory, replay result, and
maintenance request/audit JSON. Include a boundary/newer candidate or exact
future timestamp rejection example so an implementation using file mtime,
private metadata edits, or an off-by-one age comparison is caught.

### 4. Canonical fixture metadata lacks independent byte/digest oracles

The two byte strings are named, but their exact sizes and the finalized digest
are not published as independent expected literals. The independently computed
values are `15` bytes for `stage-orphan-v1`, `19` bytes for
`finalized-orphan-v1`, and finalized SHA-256
`edebbe397df1e6932d83cbf742512b480524c89bc6e9b6b679fafec5896db24f`.
Without these literals a verifier is invited to derive expectations through the
same codec/storage implementation it is testing.

Add these exact size/digest literals and the expected immutable metadata fields
for each kind. State that created/finalized time comes from the command literal,
not filesystem mtime, and that both fixture kinds use the same candidate codec,
atomic file/fsync behavior and digest-lock namespace later consumed by the real
maintenance application.

## Preserved boundaries

The proposed direction is otherwise correctly narrow: it does not create
original request/revision/event/audit/reference facts, does not delete, does not
change composition/opening/checklist state, and keeps fixture selection out of
production bootstrap/application/HTTP. The two kinds and canonical identities
are coherent with the existing maintenance candidate model. These properties
are not sufficient to approve Gate 1 until the observable and security gaps
above are closed.

Task 1.25 remains open. Gate 2 maintenance tests must not be amended to invent
the missing fixture behavior.

## Verification

```text
$ git rev-parse HEAD
00df4434a07596d57a825337ddb0cd63b5276447

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 00df443^ 00df443 --check
PASS (no output)

$ printf %s stage-orphan-v1 | wc -c
15

$ printf %s finalized-orphan-v1 | wc -c
19

$ printf %s finalized-orphan-v1 | shasum -a 256
edebbe397df1e6932d83cbf742512b480524c89bc6e9b6b679fafec5896db24f
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
d09a8da857114d5710aef71ece5699bab35a9007e3b262d3dafdfb00e587efd6  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
4b79c9a5699191066447f36e0a6a50eb98fabe347f1b61fc166deae46396206b  openspec/changes/replace-pilot-registration-with-original-upload/design.md
24277de90f1cb0ff0fea6d01ce951ddcf75275619ef34fe396fa82af9b72579a  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
34050b862f8327a9fb7e562af82905e6730cbc7af0b14bd96f0a21269027ded1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
62af4bd87ecc3c9f66168470c956ab340a802bb2efd852010878d0bb123cfd2b  docs/operations/assignment-order-original-maintenance-age-fixture-gate1-gap-2026-09-05.md
```

This review record omits its own circular hash.
