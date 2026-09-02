# PILOT-SESSION-STORAGE-001 v7 — independent constructibility Gate 1 rereview

Date: 2026-09-02  
Reviewer: separately tasked agent `/root/session_v7_review`  
Gate: fresh Gate 1 rereview after the v6 constructibility findings  
Verdict: **READY_FOR_OWNER_APPROVAL**

The reviewer did not author or edit the executable specification, OpenSpec
artifacts, tests, support harness or production implementation. This
append-only review record is the reviewer's only change to the slice.

## Exact reviewed package

```text
74b4966946c73448aa1dd0e6d5e06993ed228599ce579eee54fc61739e48d920  specs/PILOT-SESSION-STORAGE-001.md
8c2a66a22cfdb672c5dcd3aee88e2cbf1b48dcc9187969855018e714afb7589a  openspec/changes/define-pilot-session-storage-contract/proposal.md
74c41cff1e55659f1fd8117b93d4bbd82683b92ae5e9f88c6e1f606419250a0f  openspec/changes/define-pilot-session-storage-contract/design.md
bd0b55401a4556b257bcd7bea5e4b29de47f9540bc2d8363e796e57bd9061e83  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
7cce5a5fdbb638fee3a80867dc136ec7031b9e16f8c1fe924b8d53a0c4318b1e  openspec/changes/define-pilot-session-storage-contract/tasks.md
```

## Review result

The v7 package closes all three blocking findings in
`pilot-session-storage-gate1-rereview-v9.md` without reopening the earlier v9
API, observability or RED-plan gaps.

1. `PilotSessionOperationResult` now has exactly eight constructible
   `public static` owner factories. Their invariants distinguish the three
   successful ID-bearing operations, successful destroy/close, `NOT_FOUND`,
   `INVALID` and typed `UNAVAILABLE`; category/correlation/current-ID nullability
   and the exact 12-lowercase-hex correlation grammar are fixed.
2. `PilotSessionFilesystemEvent` now has exact `ownerBefore` and `ownerAfter`
   factories. They fix phase/outcome consistency, positive sequence/ordinal and
   the artifact-dependent nullable 64-lowercase-hex session hash without adding
   a path, literal ID or bytes channel.
3. `PilotSessionInspectionResult` now has exact `inspectorOk` and
   `inspectorUnavailable` factories with the canonical-JSON/status nullability
   invariant. It still cannot represent an HTTP, authentication or owner
   outcome.

Public factory visibility is necessary because PHP has neither package-private
nor friend access for the separate real owner and inspector classes. It does
not grant verifier authority: the normative architecture call-site ratchet
rejects owner-result/event factory calls outside the concrete real owner and
inspection-result factory calls outside `PilotSessionStorageInspector`.
Test/support code is expressly limited to accessors plus independently observed
primitive events, material state and raw HTTP behavior. Primitive wrappers can
still return only native-shaped primitive results and cannot calculate an owner
result. Consequently production behavior, not a test dispatcher or DTO
constructor, is the only permitted source of a passing lifecycle claim.

The owner implementation class may remain an internal implementation selected
by `PilotSessionStorageFactory`: its public contract is the exact
`PilotSessionStorage` interface. The call-site ratchet's allowlist is therefore
an implementation-boundary detail, while the forbidden outside-call property
is normative and testable.

## Preserved exhaustive contract

- All operation, artifact, phase, primitive outcome/failure, file type,
  entropy, owner and inspection enums retain exact backed values.
- The raw-HTTP verifier receives only filesystem, clock, entropy and observer
  ports through `createWithSessionStorageDependencies`; production bootstrap
  calls only `create`, and no environment/request/cookie/CLI/Compose selector
  can choose the injected seam.
- Wall time and monotonic time retain separate deterministic ownership; the
  clock has no failure channel and entropy retains the typed failure channel.
- The observer can only observe or block after real owner events. It cannot
  skip, complete or declare an owner operation. Pause/kill evidence remains
  parent-owned and material.
- The read-only inspector retains exact ordered argv grammar, canonical JSON
  envelope/member order, basename hashing/redaction, binary sorting, duplicate
  failure and closed `0|64|65|70` output protocol. Authentication still
  requires independent real raw-HTTP cookie reuse.
- The full Gate 2 matrix still covers configuration, identity/swap/EEXIST,
  primitive failures and short I/O, lock timing, atomic write, regeneration and
  destroy crash regions, GC, entropy, both HTTP consumers, GET/HEAD/POST, route
  priority, host/image behavior and Compose stop/start. No child JSON claim or
  test-owned dispatcher is accepted as evidence.
- Existing no-clobber publication, dual-valid-ID prohibition, response
  buffering/exact 503, cookie/CSRF/return-to semantics, attempt-all cleanup and
  foreign/default-root preservation remain unchanged.

No additional product or architecture decision is required by this review.

## Verification

All fenced PHP blocks from the executable specification were concatenated under
one PHP opening tag and linted on the repository runtime:

```text
PHP 8.5.4 (cli)
No syntax errors detected in Standard input code
```

Strict OpenSpec validation:

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid
```

Whitespace validation:

```text
git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
  openspec/changes/define-pilot-session-storage-contract
```

Result: exit 0, no output.

## Gate consequence

The exact package above is **READY_FOR_OWNER_APPROVAL**. Task 1.5 remains open
until the owner explicitly approves these exact hashes. Tasks 2.2 and later
remain blocked until that decision; prior owner approvals and pre-amendment
Gate 3 reviews remain historical and do not authorize RED or GREEN for v7.
