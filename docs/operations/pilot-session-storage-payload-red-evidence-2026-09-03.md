# PILOT-SESSION-STORAGE-001 v10 payload handoff — Gate 2 RED

Date: 2026-09-03

Approved executable specification SHA-256:
`054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f`

Test SHA-256:
`288ba764ab60fd7f0abd767c6181c56efcb840a622c668f97d752a0bcc9c41b4`

Public seam: real `PilotSessionStorageFactory::create(...)` owner followed by
`start(null)`, `writeCommit(...)`, and `start(valid committed id)`, observed
only through immutable `PilotSessionOperationResult` accessors.

Command:

```text
php tests/InstallationProcess/pilot_session_storage_payload_handoff_001_test.php
```

Result: exit `255`, intended RED.

```text
PHP Fatal error:  Uncaught TestFailure: INTENTIONAL_RED: successful start exposes owner-read payload
Expected: true
Actual: false in tests/bootstrap.php:36
```

Setup classification: the task-owned root was created and removed; the real
owner successfully generated an ID, committed the independently fixed literal
whole-array payload, reacquired the same ID and read the committed file. The
status and ID assertions passed before the failure. The failure is therefore
the missing approved result accessor/handoff, not setup, entropy, filesystem or
test-fixture failure.

Sensitivity: removing the intended production payload accessor necessarily
keeps this exact RED. Returning changed/empty bytes will pass the method-exists
assertion but fail the following independently fixed byte comparison. The test
does not construct an owner result, inspect private fields, calculate bytes via
production code, or read the committed file as its success oracle.

This evidence covers only the first payload-handoff tracer. Malformed codec and
raw-HTTP behavior require subsequent red-green slices after this test receives
independent Gate 3 approval.
