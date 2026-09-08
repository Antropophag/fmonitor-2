# PILOT-SESSION-STORAGE-001 v10 codec amendment — independent Gate 1 rereview

Date: 2026-09-03

Reviewer: `/root/session_gate1_rereview_v11`

Role: fresh independent Gate 1 reviewer

Record name: `v13` is the next unused append-only review filename. Existing
committed `pilot-session-storage-gate1-rereview-v11.md` and `-v12.md` are
historical v8 and v9 records and were not overwritten.

Independence: this reviewer did not author or edit the reviewed executable
specification or OpenSpec planning artifacts. The prior v10 review was read only
as finding history; its conclusions were not reused as this verdict. This
review does not make the product-owner approval decision.

## Exact reviewed package

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
78d3ad3a82d4ac3a3ee80e72412a2aa31d101724b09e0fda5172ed0ba1ec1aef  openspec/changes/define-pilot-session-storage-contract/proposal.md
7c12ecc8c52f9ce411f57bf93270bc5fac09e35166e64482983e212122fb8ceb  openspec/changes/define-pilot-session-storage-contract/design.md
8d9110610fe4eb9b36424633b8d8db7077c35f347e7313354a1109ab856abcd3  openspec/changes/define-pilot-session-storage-contract/tasks.md
79f41f73ff2f64c52b4c07d0a10fb14cf09f2517650d97ffb5ab4a3f2ef0d1b2  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
```

Any byte change to one of these five artifacts requires another independent
Gate 1 review and a new exact-hash owner decision.

## Verdict

**READY_FOR_OWNER_APPROVAL**

The current amendment resolves both blocking findings recorded in the v10
history and meets Gate 1 at the confirmed public HTTP seam.

## Independent findings

- The read handoff is constructively implementable. Successful `start` alone
  returns the exact opaque committed bytes through
  `ownerStarted(string $currentSessionId, string $sessionPayload)` and
  `sessionPayload(): ?string`; a new anonymous session uses empty bytes as an
  explicit empty-array sentinel. Other operations and statuses cannot carry a
  payload. Both HTTP consumers are required to restore state only from this
  handoff and are forbidden to reopen committed storage or invoke a second
  session owner.
- The codec is exact enough for an independent implementation and RED test:
  the persisted representation is PHP `serialize()` of the complete session
  array, never native `name|value` framing; decode uses warning-captured
  `unserialize($payload, ['allowed_classes' => false])`; only a recursively
  bounded array of null/bool/int/string/array values is accepted. Integer/string
  keys, depth 16, 4096 total entries, and the 1 MiB storage ceiling bound the
  accepted shape.
- References and cycles are rejected before recursion through
  `ReflectionReference::fromArrayElement` on every array element. Objects,
  resources, floats and non-array roots are rejected by the closed value-shape
  grammar. Byte-identical `serialize($decoded) === $payload` rejects trailing
  bytes, malformed input and non-canonical encodings. The same shape validation
  runs before write/regeneration encoding, so unsafe in-memory state cannot be
  committed under this contract.
- Decode failure happens before route/auth execution and has one observable
  result: category `PAYLOAD_INVALID` mapped to the exact redacted 503 response.
  No partially restored state is dispatched. `PAYLOAD_INVALID =
  'payload_invalid'` is present in the executable closed enum and is referenced
  consistently by the executable spec, design and delta spec.
- The public seam remains observable without test self-attestation: immutable
  result accessors expose successful-start bytes to the real HTTP graph, raw
  HTTP exposes restored authentication/CSRF behavior or the exact failure, and
  the architecture ratchet forbids support/test factory calls. Tests can also
  prove that neither consumer issues a second committed-file read through the
  injected production filesystem port and owner-emitted trace.
- Payload is consistently classified as secret material. It is excluded from
  logs, event DTOs, inspection JSON, correlations and unavailable responses;
  parser diagnostics, exception data, paths, session IDs and credentials are
  likewise excluded from the exact HTTP failure and internal safe log grammar.
- Current normative API references point to the current executable contract;
  the former contradictory v8 API references are absent. Remaining v2/v5/v7/v8
  mentions in `tasks.md` and the executable Done section are explicitly
  historical. Current Done and approval language names the v10 exact reviewed
  hashes, so the two-argument `ownerStarted` surface cannot be confused with a
  historical one-argument version.
- Proposal, design, tasks and delta specification agree on the one-owner
  payload handoff, bounded codec, `PAYLOAD_INVALID`, no second read, reset of
  later-gate applicability, and need for fresh exact-hash owner approval.

## Verification evidence

```text
$ openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

$ git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
    openspec/changes/define-pilot-session-storage-contract
(no output; exit 0)

$ php -l <(awk extracting every php fenced block from \
    specs/PILOT-SESSION-STORAGE-001.md under namespace FMonitor\\IdentityAccess)
No syntax errors detected in /dev/fd/63

$ php reference/cycle sanity probes
ReflectionReference detected the self-cycle and both aliased elements.

$ php malformed/trailing/non-canonical sanity probes
Trailing bytes raised the captured warning; a non-canonical integer encoding
decoded but re-serialized differently; object and float values remained visible
to and rejectable by the specified shape validation.
```

The final `sha256sum` output after verification exactly matched the five hashes
recorded above.

## Gate consequence

Gate 1 review passes for exactly this package. Task 1.7 may be completed only
after the product owner records explicit approval of these five hashes. That
approval may then authorize replacement Gate 2 RED work for the amendment;
pre-amendment Gate 2–5 records do not cover the payload/codec behavior.
