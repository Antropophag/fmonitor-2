# PILOT-SESSION-STORAGE-001 v10 — independent session-payload Gate 1 rereview

Record name note: this `CHANGES_REQUIRED` review was initially written over the
already committed historical v10 filename by mistake. Before commit it was
moved intact to the next unused append-only filename v14; historical v10 was
restored byte-for-byte.

Date: 2026-09-03

Reviewer identity: `/root/session_gate1_review`

Role: fresh independent Gate 1 reviewer

Independence: the reviewer did not author or edit the reviewed executable
specification or OpenSpec artifacts. This record does not make or approve the
product-owner decision.

## Reviewed exact package

```text
268eb93bcaba30a50ba0cbacdb0975e9fa7c4427841e7ba2720c78991f6f958c  specs/PILOT-SESSION-STORAGE-001.md
78d3ad3a82d4ac3a3ee80e72412a2aa31d101724b09e0fda5172ed0ba1ec1aef  openspec/changes/define-pilot-session-storage-contract/proposal.md
111c88d26e1a9bb696cefec3344ce47f44a0a8de950b8a4512497d65c7455e5a  openspec/changes/define-pilot-session-storage-contract/design.md
7081253b6bf6e8250d2257974353fe2f569d70b13c887edabc96405558baa66f  openspec/changes/define-pilot-session-storage-contract/tasks.md
906164ff13f4a2d0b639374662279e6901bec15aa581ca5ed134b251f1dd0c66  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
```

## Verdict

**CHANGES_REQUIRED**

The amendment fixes the original construction defect: successful `start` now
has an exact `ownerStarted(string $currentSessionId, string $sessionPayload)`
factory and a nullable `sessionPayload(): ?string` accessor, so the storage
owner can transfer committed bytes without a second filesystem owner. The DTO
nullability and leakage prohibitions are coherent. Gate 1 nevertheless remains
ambiguous at the observable HTTP seam and internally contradicts its v10
identity.

## Blocking findings

### 1. Opaque bytes to `$_SESSION` restoration has no exact behavior

The executable specification requires the HTTP adapter to restore `$_SESSION`
from the handoff, while the design says it decodes the payload in memory. None
of the reviewed artifacts defines the codec/operation used for that transition,
whether `session_decode` is allowed by the prohibition on native session
loading, or how malformed/unsupported committed bytes, warnings, `false`, and
`Throwable` map to the closed result/category and exact HTTP response.

This is observable and security-relevant: independent implementations can
accept different values, partially populate `$_SESSION`, instantiate different
types, or choose unauthenticated behavior versus the exact 503. Specify the
predecessor-compatible encoding/decoding seam, its success invariant, its
failure category and fail-closed state/HTTP mapping, including prohibition of
partial restored state. Add an independently determined malformed-payload
example/scenario. The corresponding write-side encoding must be identified
well enough to prove round-trip preservation of authenticated user, CSRF and
return-to state.

### 2. Normative version/hash references contradict the v10 amendment

The executable header identifies v10, but section 11 twice requires fresh owner
approval of the v8 hash(es). The delta requirement binds the exact amended API
to v8 in several places, including “only the exact eight ... owner factories in
v8”; that historical surface had a different one-argument `ownerStarted`
signature. Consequently an implementer or reviewer cannot tell whether the
v10 payload signature or historical v8 API/hash is normative.

Update current-contract references to the v10 amendment wherever they govern
the amended surface and approval. Historical task/review statements may remain
explicitly historical, but must not be phrased as the current normative API or
Done hash.

## Confirmed properties of the amendment

- `ownerStarted` has a syntactically valid exact two-string PHP signature and
  `sessionPayload(): ?string` has an exact accessor signature.
- Payload is non-null only for successful `start`; new anonymous state hands
  off exact empty bytes. Other statuses/operations retain null payload.
- The HTTP adapter is forbidden to reopen committed storage or invoke a second
  session owner, closing the identified dual-ownership bypass.
- Payload is excluded from logs, filesystem events, inspection output,
  correlation data and unavailable HTTP responses.
- Proposal, design, tasks and delta all recognize the owner-to-HTTP payload
  handoff and reset later-gate applicability for this amendment.
- The existing one-owner public seam, response buffering, route priority,
  atomic persistence and anti-self-attestation requirements remain intact.

## Verification

```text
openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
  openspec/changes/define-pilot-session-storage-contract
exit 0, no output

awk (all php fenced blocks) | php -l
No syntax errors detected in Standard input code
```

## Gate consequence

Task 1.7 remains open. The amendment is not ready for owner approval and does
not authorize replacement Gate 2/3 or consumer GREEN work. After both findings
are resolved coherently, obtain a fresh independent Gate 1 review over new exact
hashes; only the owner may then approve that reviewed package.
