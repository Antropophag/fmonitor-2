# PILOT-SESSION-STORAGE-001 v3 — independent exact-PHP-API Gate 1 rereview

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/session_v3_review`  
Gate: 1 rereview of the public PHP API amendment  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or edit the reviewed executable specification,
OpenSpec artifacts, tests, support harness or production code. This append-only
review record is the reviewer's only change to the slice.

## Exact reviewed hashes

```text
fb4feb4047ded992eb220a0cf65b888fcdf26fced99d62595cc16c5e90b53931  specs/PILOT-SESSION-STORAGE-001.md
a984d2c97a224c2b149df3a585a466b68b4c8bf722a191370759ec5cca94ea45  openspec/changes/define-pilot-session-storage-contract/proposal.md
df329bcb12af206c8de7f33f4eb827a38b581af97b0916743d1872f86b8370f7  openspec/changes/define-pilot-session-storage-contract/design.md
d9a6789a3a2f9b3f61acfbe824fead640123d2712c0e0c63feb9827e12bbaa27  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
f6c9f0b4eae50d16e17fa8dfb8ff5def66e18a25ee57dc393c7dc9b1844fea5d  openspec/changes/define-pilot-session-storage-contract/tasks.md
acc9d92e9a96b7bf066a78a35cee16d43d00c767403755660230fec07963291d  docs/operations/grill-009-owner-decision-2026-09-02.md
24ac06f9a6a2b6eb86ccc5eefdc5742f12cd3e034e887aa481a713d7b0be4f20  docs/operations/grill-009-classification-session-exact-hash-approval-2026-09-02.md
```

## Preserved and satisfactory v2 semantics

The amendment preserves the reviewed v2 filesystem lifecycle, no-clobber
publication, locking, crash regions, fail-closed HTTP mapping, cookie protocol,
bounded GC and non-secret Compose inspection semantics. The v3 package also
keeps the GRILL-009 anti-self-attestation boundary: injected filesystem, clock,
entropy and observer collaborators surround the real owner; events originate
from that owner; the observer cannot supply a primitive or owner result; and
material/HTTP success must be inspected independently. There is no premature
production implementation choice beyond the already approved infrastructure
seams.

## Blocking findings

### 1. The declared exact factory surface is not valid PHP

Executable section 8 declares a concrete `final class
PilotSessionStorageFactory`, but both its constructor and `create` method end in
semicolons and have no bodies. PHP permits bodyless methods only when they are
abstract or interface methods; a final concrete class with these declarations
cannot be loaded. Because the text calls this the **exact public PHP surface**,
an implementer cannot both copy the approved surface and produce a valid class.

Choose an executable representation that is syntactically valid and still pins
the intended public signature: for example, show method bodies as omitted
implementation bodies (`{ /* implementation */ }`) in the concrete final
class, or define an interface plus a separately named final implementation if
that additional public abstraction is truly intended. Do not silently leave
the choice to Gate 4.

### 2. Injected fault/entropy implementations have no public result creation seam

The verifier is required to implement/wrap
`PilotSessionFilesystemPrimitives` and `PilotSessionEntropy`, including returning
exact `FALSE`, `WARNING`, `EXCEPTION`, `SHORT_IO` and entropy `FAILED` outcomes.
However, the alleged exact public API gives only accessor signatures for
`PilotSessionPrimitiveResult` and `PilotSessionEntropyResult`; it specifies no
public constructors or named factories by which an independently authored
adapter can create those immutable values. The same omission affects successful
primitive values carrying the opaque handle/stat DTO unless their creation
ownership is explicitly pinned.

This leaves Gate 2 forced either to invent an unapproved construction API, use
reflection/private coupling, or substitute a different result type. That is the
same class of missing-signature problem the v3 amendment is meant to close, and
it prevents proving that production alone can make the verifier GREEN.

Specify the exact public creation signatures and invariants for the injectable
port result values (including safe exception representation and successful
opaque handle/stat creation ownership), or replace the generic result model
with exact public operation-specific result types/factories. The owner-level
`PilotSessionOperationResult` may correctly retain no public constructor because
tests must not synthesize owner outcomes; primitive and entropy results cannot.

## Verification

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
  openspec/changes/define-pilot-session-storage-contract
exit 0, empty output
```

## Gate decision

Gate 1 remains closed for v3. Correct the two exact-surface defects coherently
in the executable specification and OpenSpec package, generate new hashes and
request a fresh independent Gate 1 rereview. Do not seek owner approval of the
reviewed v3 hash or start replacement Gate 2 from this package. Historical v2
approval and pre-amendment Gate 3 records remain historical only.
