# PILOT-SESSION-STORAGE-001 v2 — independent amended Gate 1 rereview v5

Date: 2026-09-02  
Reviewer: separately tasked independent agent `/root/grill009_fresh_rereviews`  
Gate: 1 rereview after GRILL-009 correction  
Verdict: **READY_FOR_OWNER_APPROVAL**

The reviewer did not author or edit the reviewed executable specification,
OpenSpec artifacts, tests, support harness or production code. This append-only
review record is the reviewer's only change to the slice.

## Exact reviewed hashes

```text
24165f58b0910d26d7848149e5fdcf1c955b7ac6e1c1e7b5c3f95c96eeae9d2f  specs/PILOT-SESSION-STORAGE-001.md
b9ef581f30763216620995cc67444ebac0e51a6504a8ca1d264889934cb3661e  openspec/changes/define-pilot-session-storage-contract/proposal.md
f97c347af68118a70ce897a11a99dd84cf12851d1296d5365a6bd217bb9f4509  openspec/changes/define-pilot-session-storage-contract/design.md
d5917188588a34570d3ac9ab9f7aba83242dcb664ab4bdcdb102f11dc2da99a6  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
7d7dbc54c06969a1d10a5367837ce43bea2db132a9096a5eec206c1c52b3d676  openspec/changes/define-pilot-session-storage-contract/tasks.md
acc9d92e9a96b7bf066a78a35cee16d43d00c767403755660230fec07963291d  docs/operations/grill-009-owner-decision-2026-09-02.md
```

## Owner-decision traceability and coherence

The package faithfully implements the session-storage amendment approved in
GRILL-009. One public factory constructs the real production owner used by both
HTTP consumers. Filesystem primitives, wall/monotonic clocks, entropy and the
lifecycle observer are explicit injected dependencies; production binds
native/system/CSPRNG/no-op implementations and exposes no environment, request,
cookie or Compose selector for verifier behavior.

The closed before/after primitive events, exact fault key, pause-after-observed-
event/parent-kill contract and immutable result DTO make filesystem ordering,
short I/O and crash regions observable while leaving the lifecycle owned by
production. Tests must invoke the real owner and independently inspect material
state and raw HTTP behavior; a test dispatcher, child JSON claim, event recorder
or inspector cannot calculate or attest success. Production alone can therefore
make the future replacement verifier GREEN.

## Previous finding closed

The basename/session-ID contradiction from
`pilot-session-storage-gate1-rereview-v4.md` is closed coherently across the
executable spec, proposal, design, delta spec and tasks. The read-only inspector
now emits `entryKeySha256`, defined as the exact lowercase SHA-256 of the
complete raw basename bytes, sorts binary-ascending by that emitted key, never
returns the literal basename/session ID, and fails closed on duplicate emitted
keys with no partial success snapshot. It performs no mutation and Compose
restart proof still requires real raw-HTTP cookie reuse in addition to equal
material snapshots.

The remaining lifecycle, ownership, response-buffering, cookie/protocol,
cleanup and Compose constraints are coherent between the executable and
OpenSpec package. The inspector correction does not alter production session
semantics or weaken the fail-closed boundary.

## Gate reset

Task 1.3 correctly remains open until this reviewed hash receives explicit
owner approval. Tasks 2.2 and 2.3 require replacement real-owner RED evidence
and a fresh independent Gate 3 review after that approval. The original Gate 1
approval and both pre-amendment CHANGES_REQUIRED test reviews remain historical
only and do not carry forward.

## Verdict

No blocking Gate 1 finding remains. The exact package above is
**READY_FOR_OWNER_APPROVAL**. This verdict does not approve tests, RED evidence,
production code, GREEN, code review or Done.

## Verification

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
  openspec/changes/define-pilot-session-storage-contract \
  docs/operations/pilot-session-storage-gate1-rereview-v5.md
exit 0, empty output
```
