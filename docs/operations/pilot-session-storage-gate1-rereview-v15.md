# PILOT-SESSION-STORAGE-001 v10 — independent codec/owner Gate 1 rereview v15

Date: 2026-09-04

Reviewer: `/root/session_gate1_v15`

Role: fresh independent Gate 1 reviewer for task 1.7

Independence: this reviewer did not author or edit the reviewed executable
specification, OpenSpec planning artifacts, tests or production. The prior v14
record was used only as append-only finding history. This record does not make
the product-owner approval decision.

## Exact reviewed package

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
78d3ad3a82d4ac3a3ee80e72412a2aa31d101724b09e0fda5172ed0ba1ec1aef  openspec/changes/define-pilot-session-storage-contract/proposal.md
7c12ecc8c52f9ce411f57bf93270bc5fac09e35166e64482983e212122fb8ceb  openspec/changes/define-pilot-session-storage-contract/design.md
5c3bdc92ea02540f650250c572361fcf23cd25f07277a051193372f75660511c  openspec/changes/define-pilot-session-storage-contract/tasks.md
79f41f73ff2f64c52b4c07d0a10fb14cf09f2517650d97ffb5ab4a3f2ef0d1b2  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
```

Review base: repository `HEAD` `7542d37`. The pre-existing working-tree change
in `tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php`
is outside this review package and was neither inspected as authority nor
modified. Any byte change to one of the five artifacts above requires another
independent Gate 1 review and a new exact-hash owner decision.

## Verdict

**READY_FOR_OWNER_APPROVAL**

The current package closes both blocking findings in v14 and is constructible
at the bounded session-codec and one-owner HTTP seams. No new Gate 1 gap is
introduced by sequential writes using an anonymous owner-generated ID.

## Independent findings

### Exact bounded whole-array codec

- Committed bytes are exactly PHP `serialize()` of the complete `$_SESSION`
  associative array, never native session-module `name|value` framing.
- Existing payload decode is exactly warning-captured
  `unserialize($payload, ['allowed_classes' => false])`. Empty bytes are a
  special sentinel only for `start(null)` and mean an empty array; they are not
  passed to `unserialize`.
- The accepted graph is closed to integer/string keys and
  null/bool/int/string/array values, with maximum depth 16 and at most 4096
  total entries. The storage layer separately caps opaque payload bytes at
  1,048,576.
- Every array element is checked with
  `ReflectionReference::fromArrayElement(...) === null`, closing aliases and
  cycles before recursive acceptance. Objects, resources, floats, malformed or
  trailing encodings, non-array roots, and non-canonical encodings are rejected.
  Byte-identical reserialization is the exact canonicality test.
- The identical shape check occurs before encoding for both `writeCommit` and
  `regenerate`, making authenticated user, CSRF, return-to, flash and other
  allowed array state round-trip through one defined representation.

### PAYLOAD_INVALID and atomic no-partial restore

- Decode and complete graph/canonicality validation finish before route or auth
  execution. Only the fully accepted local value may become in-memory session
  state; therefore no partially decoded or partially validated `$_SESSION` is
  dispatched.
- Every malformed, unsafe or over-limit read maps to the closed
  `PAYLOAD_INVALID = 'payload_invalid'` category and then to the exact redacted
  storage-unavailable 503. Parser warnings, payload, object/class data and
  partial state cannot reach the response or application route.
- Write-side invalid state fails before persistence. Response buffering still
  requires successful explicit `writeCommit`, `regenerate` or `destroyCommit`
  before status, headers, body or cookie publication, so encode rejection
  cannot leak a partial success response.

### Single owner and representation identity

- Successful `start` is the sole filesystem-to-HTTP transfer:
  `ownerStarted(string $currentSessionId, string $sessionPayload)` carries the
  exact committed bytes, and `sessionPayload(): ?string` is non-null only for
  successful start.
- Both HTTP consumers must restore only from that in-memory handoff and are
  expressly forbidden to reopen/read committed storage or invoke a second
  session owner. The injectable real production graph and owner-emitted
  filesystem trace make a second read independently observable.
- The payload is consistently treated as secret: it is excluded from logs,
  events, inspector output, correlation data and unavailable responses.

### Sequential writes after anonymous start are constructible

`start(null)` generates the owner-controlled candidate ID but publishes no file
or cookie. The first `writeCommit(start.currentSessionId(), encodedState)` uses
the anonymous no-clobber publication path. Its successful result returns the
actual current ID: normally the original generated ID, or a wholly new
owner-generated ID after a bounded target collision. HTTP therefore binds the
cookie to `writeCommit.currentSessionId()`, not to a stale pre-commit candidate.

A later `writeCommit` on the same request owner uses that returned current ID.
The target is then an existing locked committed ID, so the specified atomic
same-directory rename replaces only its bytes and `ownerWriteCommitted` returns
the same validated ID. A further buffered write can repeat this sequence
without creating a second owner or changing cookie identity. Thus sequential
UserAccess/flash-style commit behavior is expressible by the exact public API;
it does not require a Gate 1 amendment. Gate 2 should nevertheless include a
sensitivity case that performs two commits after anonymous start, consumes the
first commit's returned ID, proves one final cookie identity and reads the final
whole-array bytes through a subsequent real `start`.

### Normative references

- Executable header, Done rule and approval prerequisite consistently identify
  the current v10 amendment and v10 hashes.
- The delta binds the API to the current executable sections 8 and 10 and to
  the exact eight factories in the current executable contract; it no longer
  calls the historical v8 one-argument `ownerStarted` surface normative.
- Remaining v2/v5/v7/v8 mentions describe completed historical approvals or
  their insufficiency. Task 1.7 accurately remains open pending this review and
  explicit owner approval; later-gate records remain non-authoritative for the
  amendment until that approval.
- Proposal, design, delta and tasks agree on the payload handoff, bounded
  whole-array codec, `PAYLOAD_INVALID`, second-read prohibition and replacement
  review/approval requirement.

## Verification

```text
$ openspec validate define-pilot-session-storage-contract --strict
Change 'define-pilot-session-storage-contract' is valid

$ git diff --check -- specs/PILOT-SESSION-STORAGE-001.md \
    openspec/changes/define-pilot-session-storage-contract
(no output; exit 0)

$ sha256sum <the five reviewed artifacts>
(exactly the five hashes recorded above)
```

The exact PHP signatures were also reviewed as syntax-bearing fenced examples:
the two-argument `ownerStarted`, nullable payload accessor requirement, closed
enum case and unchanged owner operation signatures are mutually consistent.

## Gate consequence

Gate 1 review passes for exactly the five hashes above. Task 1.7 may be marked
complete only after the product owner records explicit approval of this exact
package. That approval may authorize replacement Gate 2/3 evidence for the v10
codec/consumer behavior; this reviewer does not grant owner approval, approve
tests, or authorize production GREEN by itself.
