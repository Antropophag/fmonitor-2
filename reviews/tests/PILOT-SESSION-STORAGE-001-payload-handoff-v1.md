# Test review: PILOT-SESSION-STORAGE-001 v10 payload handoff — RED v1

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/session_payload_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, test, RED evidence, or production implementation
- Reviewed commit: `fff8622619f6fc53e0b8476851acbaf19122b3a3`
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved for Gate 2 in `docs/operations/pilot-session-storage-v10-payload-owner-decision-2026-09-03.md`
- Public seam: real `PilotSessionStorageFactory::create(...)` owner; `start(null)`, `writeCommit(...)`, and `start(valid committed id)`; immutable `PilotSessionOperationResult` accessors
- Verdict: **APPROVED**

## Findings

No blocking findings.

### Traceability and scope

The test cites v10 sections 3 and 8 and proves the first amended acceptance
statement: a successful `start(valid committed id)` exposes the exact opaque
bytes read by the storage owner through `PilotSessionOperationResult`. This is
the smallest owner-result tracer needed before the HTTP codec and consumer
replacement slices. It does not claim coverage of malformed payload decoding,
HTTP 503 mapping, `$_SESSION` restoration, or removal of native-session
consumers; those remain subsequent slices.

### Public seam and anti-self-attestation

The test constructs the real owner through the approved public factory and
observes only public operation results. The support fixture supplies material
filesystem, clock, entropy, and observer ports but neither constructs an owner
result nor supplies a session outcome. The test does not call the public
`@internal` result factories, inspect private state, reopen the committed file,
or use filesystem/event inspection as its success oracle. Thus the expected
handoff is produced by the real owner, not verifier self-attestation.

### Sensitivity and independent expected bytes

The expected payload is the independently fixed literal
`a:1:{s:12:"auth_user_id";i:17;}`. It is valid whole-array PHP serialization
under the normative grammar and is not calculated by production serialization
or read back by the test. Missing `sessionPayload()` fails the explicit
intentional-RED assertion. An accessor that returns null, empty, altered, or
re-encoded bytes reaches and fails the subsequent byte-exact assertion. The
preceding status and ID assertions prevent a setup failure or wrong-session
outcome from being mistaken for payload-handoff RED.

### Determinism, isolation, cleanup, and rejected cases

Clock and all owner entropy are deterministic. The random task-root suffix
only isolates concurrent runs and does not affect expected behavior. The test
creates a private 0700 task root and removes it recursively in `finally`; the
review rerun left no task root behind. It does not contact a production system.

No rejected-case assertion is required for this one positive tracer: the v10
null-payload invariants for non-start outcomes and malformed-codec rejection
are outside the explicitly reviewed minimal owner-result GREEN. This approval
does not waive their later RED/review gates.

## Intended RED reproduced

Command:

```text
php tests/InstallationProcess/pilot_session_storage_payload_handoff_001_test.php
```

Observed exit: `255`.

```text
PHP Fatal error:  Uncaught TestFailure: INTENTIONAL_RED: successful start exposes owner-read payload
Expected: true
Actual: false in tests/bootstrap.php:36
```

The real owner had already returned `OK` for anonymous start, committed the
literal bytes, and returned `OK` with the same ID for committed start. The
failure is therefore the missing approved result accessor/handoff, not broken
filesystem, entropy, setup, or cleanup.

Hash command:

```text
sha256sum specs/PILOT-SESSION-STORAGE-001.md \
  tests/InstallationProcess/pilot_session_storage_payload_handoff_001_test.php \
  docs/operations/pilot-session-storage-payload-red-evidence-2026-09-03.md
```

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
288ba764ab60fd7f0abd767c6181c56efcb840a622c668f97d752a0bcc9c41b4  tests/InstallationProcess/pilot_session_storage_payload_handoff_001_test.php
5176afef22ef5aed401df0374705dce1f334f65ba96d805149f12a4fe1d63496  docs/operations/pilot-session-storage-payload-red-evidence-2026-09-03.md
```

## Required changes

None.

## Authorized minimal GREEN

Gate 3 authorizes only the immutable result shape and real-owner plumbing
needed for successful `start` to return exact owner-read committed bytes (and
the specified empty bytes for anonymous start) while every other result keeps
payload null. It does not authorize HTTP codec/consumer work or expectation
changes. Any reviewed test expectation change restarts Gate 2.

Gate 3 is **APPROVED**.
