# PILOT-SESSION-STORAGE-001 v6 — independent exhaustive-API Gate 1 rereview

Date: 2026-09-02  
Role: fresh independent Gate 1 reviewer  
Independence: reviewer did not author or edit the reviewed executable spec or
OpenSpec artifacts and did not review their own work.

## Reviewed exact package

```text
47df90f7eed6ff7d131308db5f2a485732f4c5940436ce70ffadb481d84cbc8b  specs/PILOT-SESSION-STORAGE-001.md
463ccbd7a034c81e1e455f35fd476e276cb34cad0604f8a7f3950a930fa01ea3  openspec/changes/define-pilot-session-storage-contract/proposal.md
78ddda3a8158081a1e77b75fe8c8338ee650d2d2112d58ed2ec8b16f72416bdd  openspec/changes/define-pilot-session-storage-contract/design.md
290924777a6090546f1ffd58b355e1d2b4dabd045290f210237428feeec0cf8d  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
f90657aaab8aabd1f237f6240f36f1b901bed839a9852b208992ca16b88b0e8b  openspec/changes/define-pilot-session-storage-contract/tasks.md
```

## Verdict

**CHANGES_REQUIRED**

The v6 amendment closes the backed-enum, closed primitive failure-code,
deterministic-clock, raw-HTTP production-graph composition and inspector
CLI/JSON/exit-protocol gaps. It also preserves the lifecycle ordering,
production-selector prohibition and anti-self-attestation guarantees. However,
the exact public PHP surface is still not constructible without inventing an
unapproved API or using reflection/private-state bypasses. Gate 1 is therefore
not executable and is not ready for owner exact-hash approval.

## Blocking findings

### 1. Owner operation results cannot be constructed

Sections 8 and 11 require every real owner operation to return an immutable
`PilotSessionOperationResult`. Section 8 fixes its accessors and explicitly says
that the DTO has no public constructor, but supplies no public named factories
for `OK`, `NOT_FOUND`, `INVALID` or `UNAVAILABLE`. Unlike the entropy and
primitive result DTOs, a separate production owner implementation has no legal
PHP call with which to create any required result.

Define the exact constructible surface and invariants for all four statuses,
including which success operations may carry `currentSessionId`, the null rules
for category/correlation/current ID, and prevention of test-supplied event or
snapshot claims.

### 2. Inspector results cannot be constructed

Section 10 requires `PilotSessionStorageInspector::inspect()` to return a final
`PilotSessionInspectionResult`, while explicitly stating that this result has
no public constructor or named factory. PHP has no package-private or friend
access: a separate inspector class cannot invoke a private constructor or
private factory on the result class. Consequently neither the `OK` canonical
JSON result nor `UNAVAILABLE` can be returned through the specified API.

Add exact public named factories (or another ordinary, explicitly specified
constructible design) with the stated `OK`/`UNAVAILABLE` null invariants. The
factory must not allow callers to claim authentication or an owner result.

### 3. Filesystem events have no exact construction seam

`PilotSessionFilesystemEvent` is required to be immutable, produced by the real
owner around every filesystem-port call and delivered to the observer. The
contract calls the PHP surface exact and lists only accessors; it specifies no
constructor or named factories. A public constructor inferred by an implementer
would expand the supposedly exact API, while a private constructor would be
unusable by the separate owner implementation. Fix the exact constructor or
named-factory surface and validate the BEFORE/AFTER outcome invariant, positive
sequence/ordinal, and nullable session hash rules without exposing path, ID or
bytes.

## Confirmed properties

- All filesystem-operation, logical-artifact, phase, primitive-outcome,
  primitive-failure, file-type, entropy-status, owner-status,
  unavailable-category and inspection-status enums have explicit case names and
  string backing values.
- Primitive failures are closed to safe codes and cannot carry a Throwable,
  message or path; primitive results cannot calculate an owner outcome.
- The raw-HTTP seam injects exactly filesystem, clock, entropy and observer into
  the same complete factory graph. Production calls only `create`; no env,
  request, cookie, CLI or Compose selector can choose the injected seam.
- Wall time owns age/expiry, monotonic nanoseconds own lock deadlines, and the
  clock has no failure channel. Entropy remains the only typed randomness
  failure channel.
- The inspector command has exact ordered four-argument argv grammar, exact
  canonical JSON member order and redaction, binary `entryKeySha256` sorting,
  and closed `0|64|65|70` stdout/stderr behavior.
- The inspector is read-only and cannot alone attest authentication. Real raw
  HTTP cookie reuse remains required for restart proof.
- No test dispatcher, scenario/result argument, production fault selector or
  test-owned success claim has been reintroduced.
- Existing atomic publish, regeneration/destroy crash regions, response
  buffering, cookie/CSRF/route priority, bounded GC and attempt-all cleanup
  requirements remain present.

## Verification

All fenced PHP blocks in `specs/PILOT-SESSION-STORAGE-001.md` were concatenated
under one PHP opening tag and linted:

```text
No syntax errors detected in Standard input code
```

OpenSpec strict validation:

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid
```

Whitespace validation of the reviewed package:

```text
git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
  openspec/changes/define-pilot-session-storage-contract
```

Result: exit 0, no output.

## Gate consequence

Task 1.5 remains open. Tasks 2.2 and later remain blocked. After the three
constructibility findings are corrected coherently in the executable spec and
OpenSpec package, obtain another fresh independent Gate 1 review and then a new
owner approval of the exact reviewed hashes. Existing v5 approval and all prior
Gate 3 reviews remain historical only.
