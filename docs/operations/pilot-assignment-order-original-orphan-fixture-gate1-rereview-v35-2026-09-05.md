# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v35 orphan fixture — independent Gate 1 rereview

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_orphan_fixture_rereview`
- Reviewed commit: `21a9fb90f91bf5f67e486dd09a71bc2f91950930`
- Predecessor amendment: `d61aa3c`
- Prior review: `docs/operations/pilot-assignment-order-original-orphan-fixture-gate1-review-v34-2026-09-05.md`
- Scope: verification-only private-orphan fixture amendment, its OpenSpec
  coherence, and sufficiency for deterministic real-maintenance Gate 2; no
  tests or production implementation reviewed
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the reviewed executable specification, OpenSpec
artifacts, tests, fixtures, or production implementation. This append-only
review is the only authored artifact.

## Rereview of the four v34 findings

### 1. Exact outcomes and precedence — partially closed

V35 now fixes two exception classes, basename messages, code `0`, null previous,
and the precedence `command scalar/identity/bytes grammar -> root authority ->
clock/future -> replay/collision -> primitive creation`. Exact replay is a
no-op, collision is distinguished from unavailable, and both failures are
specified before mutation. This closes the previously missing exception and
precedence contract.

However, the required post-call observable inventory is still not published.
`AssignmentOrderOriginalPrivateOrphanFixture::create()` remains `void`, and the
specification gives neither an exact canonical storage inventory after create
and replay nor an exact inventory before/after each rejection/collision.
Candidate prose supplies enough fields to infer a likely page, but Gate 2 must
not invent the exact abandoned `sha256` representation, inventory JSON shape,
or which inventory/read seam proves byte-identical no mutation. Publish exact
created, replayed, conflict, unavailable, and primitive-failure inventories (or
a closed result DTO plus exact inventory evidence) at the real public storage
seam.

### 2. Root authority and cleanup — not closed

V35 materially improves the boundary: absolute canonical directory, repository
realpath exclusion, `0700` root, `0600` regular marker, same UID, exact marker
bytes and 32-lower-hex token, `lstat`/`realpath`, no symlink, `nlink == 1`, no
repair, and revalidation before removal are all explicit. Production code also
cannot select the fixture factory.

The v34 requirement to reject the configured production private-storage root
and its alias/ancestor/descendant is still absent. The fixture factory receives
only `(privateStorageRoot, ownershipToken, clock)`, so it has no production-root
authority against which to prove non-overlap. “Outside repository realpath” is
not equivalent: a deployed private root may legitimately be outside the
repository. A same-UID verifier can also create the stated marker in an
otherwise production-capable directory. The contract therefore does not make
accidental fixture writes to configured production storage unrepresentable.

Add a trusted verification-root authority/config input and require canonical
non-overlap with every configured production private root (not equal, ancestor,
or descendant), checked before any mutation and again before cleanup. Define
the fixed unavailable outcome for every overlap/alias case. Retain the current
fresh root identity/marker/mode/link revalidation and maintenance-only cleanup.

### 3. Real maintenance worked example — partially closed

The real-adapter verification factory now binds real repository, storage and
evidence layout while injecting only the approved clock/fault ports. The
canonical clock, cutoff, request UUID, principal, limit/cursor, ordered two-item
page, `COMPLETED 2/2/0/0`, null cursor, replay, and recursively sorted request
and audit JSON are fixed. Production has no verification selector. These are
substantial and correct closures.

The required boundary/newer sensitivity is not constructible as written. The
fixture accepts only an “UTC second timestamp”, but the only exact newer value
published is `07:30:00.000001Z`. Gate 2 therefore cannot create that candidate
through the approved fixture and cannot use private metadata edits. The exact
boundary candidate at `07:30:00Z` is also stated only as a rule and is absent
from the canonical command/candidate/result/inventory example. Publish a
separate exact real-maintenance run whose fixture commands create one
cutoff-equal candidate and one constructible newer candidate (for example
`07:30:01Z`), with request, ordered page, result, replay, post-inventory, and
exact request/audit JSON. Keep `09:00:01Z` as the separately exact future
fixture rejection and publish its unchanged inventory evidence under finding 1.

### 4. Size, digest, metadata time versus mtime — closed

V35 fixes byte sizes `15` and `19`, finalized SHA-256
`edebbe397df1e6932d83cbf742512b480524c89bc6e9b6b679fafec5896db24f`,
and explicitly makes the command-owned metadata timestamp the sole age input,
with filesystem mtime/ctime ignored. Both fixture kinds are bound to the same
production validator, codec, atomic write/rename/fsync primitives, metadata
grammar, and digest-lock exclusion domain used by maintenance. No further
finding remains here.

## API, clock, authorization, and security consistency

`AssignmentOrderOriginalClock::nowUtc(): string` is consistently injected into
both the orphan fixture factory and the real-maintenance verification factory;
the canonical instant is `2026-09-02T09:00:00Z`. The production maintenance
factory keeps production bindings, accepts only trusted authorization
composition, and exposes no HTTP/request/CLI/environment verification selector.
Exact principal/capability matching remains fail-closed and creates no user
capability row. These aspects are coherent.

The root non-overlap defect above remains security-significant despite the
absence of a production selector, because the separately callable verification
factory still accepts an arbitrary path without a trusted configured-production
root exclusion.

## Gate decision

Gate 1 remains **CHANGES_REQUESTED**. Do not amend maintenance Gate 2 tests to
invent storage inventories, production-root exclusion, or a constructible
boundary/newer worked example. Task 1.25 remains open until a fresh independent
Gate 1 rereview approves an exact amended commit.

## Verification

```text
$ git rev-parse HEAD
21a9fb90f91bf5f67e486dd09a71bc2f91950930

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff d61aa3c..21a9fb90 --check
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
c9d7789c58cac7b9c82c2ec93fc8269794634c6cd4cf17d70ae6dc4d36821ce9  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
4b79c9a5699191066447f36e0a6a50eb98fabe347f1b61fc166deae46396206b  openspec/changes/replace-pilot-registration-with-original-upload/design.md
24277de90f1cb0ff0fea6d01ce951ddcf75275619ef34fe396fa82af9b72579a  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
fe9fab0a22de9122535568e03bfc12181d89a775a76d69eaf47c079e4bf8e080  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
62af4bd87ecc3c9f66168470c956ab340a802bb2efd852010878d0bb123cfd2b  docs/operations/assignment-order-original-maintenance-age-fixture-gate1-gap-2026-09-05.md
8d66e8224b379e094b6c2de70c71eb189416d76b6d63b4f79afab325f2b66f66  docs/operations/pilot-assignment-order-original-orphan-fixture-gate1-review-v34-2026-09-05.md
```

This review record omits its own circular hash.
