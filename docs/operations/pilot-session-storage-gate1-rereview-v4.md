# PILOT-SESSION-STORAGE-001 v2 — independent amended Gate 1 rereview

Date: 2026-09-02  
Reviewer: separately tasked agent `/root/grill009_classification_update`  
Independence: reviewer did not author or edit the reviewed session-storage
planning artifacts, executable specification, tests, support harness or
production code; this append-only review record is the reviewer's only change
to the slice  
Gate: 1 rereview after GRILL-009 amendment  
Verdict: **CHANGES_REQUIRED**

## Exact reviewed hashes

```text
9af67cdccbc1b078a33cbc99a7fa5344e8c096da70c3dbb8a4af25e408480bdb  specs/PILOT-SESSION-STORAGE-001.md
391c8b248d2a2cb18e2e5dc8cf4c9f4907b78b2fd36d2f856f6b7b51c4af246c  openspec/changes/define-pilot-session-storage-contract/proposal.md
babaf3d87a13e0a68281cafb03d055138308b9bd32b6451d4385cd0f0067b2f3  openspec/changes/define-pilot-session-storage-contract/design.md
61f7795da97ab88ecf13533e9dc1597fec5e9ecda6cde11b82fe987874cec8a1  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
3a348e52b3543dee51d043acbdbdcc314ffcce64225f0e63197213524f7612f5  openspec/changes/define-pilot-session-storage-contract/tasks.md
acc9d92e9a96b7bf066a78a35cee16d43d00c767403755660230fec07963291d  docs/operations/grill-009-owner-decision-2026-09-02.md
```

## GRILL-009 amendment traceability

The package faithfully incorporates the owner-approved planning direction:

- one public `PilotSessionStorageFactory` constructs the real owner used by
  both HTTP consumers;
- filesystem primitives, wall/monotonic clocks, entropy and lifecycle observer
  are explicit injected dependencies, while production binds native/system/
  CSPRNG/no-op implementations;
- no environment, request, cookie or Compose selector can activate test faults
  or pause behavior in production;
- exact before/after primitive events identify only closed logical artifacts,
  hash where applicable, operation ordinal and typed outcome;
- faults select exact `(operation, logical artifact, ordinal)` native-shaped
  outcomes, and pause/kill happens only after a real owner-emitted event;
- immutable result DTOs contain owner outcomes rather than test-supplied event
  or filesystem claims;
- tests are required to invoke the real factory/owner and independently inspect
  inode/bytes/material state and raw HTTP. A dispatcher or child JSON claim is
  explicitly rejected as evidence;
- the Compose seam is specified as read-only and must be joined with real cookie
  behavior rather than self-attest authentication;
- task 1.3 requires fresh reviewed-hash owner approval, tasks 2.2–2.3 require a
  replacement RED/review, and the two pre-amendment Gate 3 reviews remain
  historical only.

This resolves the self-attestation/testability defect identified by
`reviews/tests/PILOT-SESSION-STORAGE-001.md` and `-v2.md` at the planning level.
Production code alone can make the future approved verifier GREEN: test
wrappers choose faults and record calls, but do not implement the lifecycle,
calculate its result, complete a paused operation or claim HTTP success.

## Blocking finding

### 1. Compose inspector output simultaneously exposes and forbids the session ID

Executable section 2 fixes the committed filename as:

```text
s-<session-id>.session
```

Executable section 10 then requires the inspector snapshot to contain each
entry's literal `basename`, while also requiring that it never emit a session
ID. Those requirements cannot both hold for a committed entry: its basename
contains the complete bearer session ID. The OpenSpec requirement repeats the
same contradiction at lines 183–185 by requiring `basename` and forbidding a
returned session ID.

This is security-relevant and executable-test-relevant, not presentation
wording. A literal basename leaks the credential through an operator-readable
JSON seam; a redacted/hashed basename violates the current canonical snapshot
shape. It also leaves binary filename ordering ambiguous if the returned key is
not literal.

Before Gate 1 approval, choose one exact non-secret representation, for example
a closed logical type plus SHA-256 of the complete basename (or a separately
specified opaque stable entry key), and define whether sorting is by the real
basename before redaction or by the emitted key. Reconcile executable section
10, the OpenSpec inspector requirement/scenarios, proposal/design wording and
future verifier expectations. Preserve the inspector's read-only/fail-closed
behavior and the requirement to combine snapshots with real HTTP cookie reuse.

## Checks that pass

- Filesystem event vocabulary and fault key are exact enough to detect skipped,
  reordered and short-I/O primitive behavior.
- Pause occurs after a production-observed after-event; killing a verifier-owned
  child does not let test code finish or declare the transaction.
- Clock and entropy ownership is explicit and covers expiry/mtime, lock deadline,
  ID/tokens and correlation failures.
- The result DTO has a closed safe shape and contains no test-supplied event or
  snapshot claims.
- Default production composition is native/system/CSPRNG/no-op and has no
  test-control configuration plane.
- Compose authentication proof requires both read-only snapshots and raw HTTP
  reuse of the original cookie; inspector JSON alone is insufficient.
- Gate reset is coherent: original Gate 1 approval and both Gate 3 reviews do
  not authorize amended Gate 2 or production GREEN.
- `openspec validate define-pilot-session-storage-contract --strict` passes.
- Targeted `git diff --check` for the reviewed artifacts and GRILL decision
  passes.

## Gate decision

Gate 1 remains closed. Correct the inspector basename/session-ID contradiction
coherently, produce new exact hashes, and request another fresh independent
Gate 1 rereview. Do not seek owner hash approval, start replacement Gate 2 or
reuse pre-amendment Gate 3 approvals until that rereview returns
`READY_FOR_OWNER_APPROVAL`.
