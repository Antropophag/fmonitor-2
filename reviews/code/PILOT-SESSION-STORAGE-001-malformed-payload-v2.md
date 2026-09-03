# Code rereview: PILOT-SESSION-STORAGE-001 v10 malformed object payload — GREEN v2

- Gate: 5 — fresh independent code rereview
- Reviewer: separately tasked agent `/root/session_malformed_code_review`
- Independence: reviewer did not author or edit the specification, approved test, RED evidence, or reviewed production implementation
- Reviewed commit: `0903051ea49605e676e1e26ff52f7cafa34654fd`
- Parent implementation commit: `0e808ff86685a3c22c20c7b2e243bf1dca955158`
- Fixed point: `e78472b7748250db34f80bb28b1179f5d2b4d9e5`
- Full production range: `git diff e78472b..0903051 -- app/PilotHttp/PilotE2ECoordinator.php app/PilotHttp/PilotSessionPayloadCodec.php`
- Prior append-only review: `reviews/code/PILOT-SESSION-STORAGE-001-malformed-payload-v1.md` — `CHANGES_REQUESTED`
- Approved Gate 3 record: `reviews/tests/PILOT-SESSION-STORAGE-001-malformed-payload-v7.md`
- Verdict: **CHANGES_REQUESTED**

## Resolution of prior findings

CR-1 is closed. The codec moved to the focused 32-line
`PilotSessionPayloadCodec`; `PilotE2ECoordinator.php` is back at its baselined
268 lines. `make architecture-check` now reports only the exact known set of 13
session-owner fingerprints (two coordinator, six LocalAuth, five
UserAccessView) and no hotspot regression.

CR-2 is closed as scoped. The codec no longer performs canonical
reserialization, explicit reference rejection, depth accounting, entry
accounting, or full scalar-shape enforcement. Its production behavior is
narrowed to warning-captured `unserialize(..., allowed_classes=false)` plus
recursive object rejection required by the approved object-bearing example.
The unused output-state and prior-handler variables are gone. The exact
diagnostic write suppresses its own adapter warning, and the constant zero
correlation fallback was removed.

## Blocking finding

### CR-3 — Recursive object walk is not safe on a referenced/cyclic payload

`PilotSessionPayloadCodec::containsObject()` recursively descends every nested
array without detecting a reference/cycle. A PHP whole-array payload can
legitimately decode to a self-reference using the `R:` representation. Passing
such owner-provided bytes to the new codec repeatedly recurses at line 28 until
PHP exhausts process memory and emits a fatal error and an enormous stack trace.
It therefore does not return `null`, and the HTTP adapter cannot map the failure
to the exact redacted 503.

This is a security and exception-safety regression in the newly activated
payload boundary. Before this change the coordinator did not recursively walk
the owner payload. Although the Gate 3 record correctly withholds a claim that
the reference/cycle matrix is implemented, minimal object detection still must
be total for arbitrary opaque bytes admitted by `start()`: an unreviewed shape
cannot be allowed to crash or exhaust the HTTP worker. This also violates the
inherited v10 section 3 invariant that reference/cyclic payload is
`PAYLOAD_INVALID` and section 6's no-parser-diagnostic exact failure boundary.

Reproduced with a bounded direct robustness probe:

```php
$a = [];
$a['self'] =& $a;
$payload = serialize($a);
(new PilotSessionPayloadCodec())->decode($payload);
```

Run under `memory_limit=32M` and a three-second process timeout, it exits `255`
with `Allowed memory size ... exhausted` in `containsObject()` rather than
returning. The fix needs a fresh RED/test review if it changes expectations for
the reference contour. Do not merely add an unreviewed reference/cycle feature
to this GREEN; first make the executable gate explicitly sensitive to the
required safe behavior.

## Conforming reviewed behavior

For the approved object-bearing literal, rejection remains before session
write and before authentication/DB dispatch. The focused public HTTP test
proves the exact section-6 response, one safe `payload_invalid` diagnostic with
fresh 12-hex correlation, committed-byte preservation, DB sentinel silence and
complete known-secret redaction. Warning-handler restoration remains in a
`finally` block. No public DTO or construction API changed.

Valid payload state restoration and whole-array write encoding remain later
slices and are not claimed by this malformed-object GREEN.

## Verification evidence

```text
php -l app/PilotHttp/PilotSessionPayloadCodec.php
php -l app/PilotHttp/PilotE2ECoordinator.php
php tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
```

Result: all exit `0`; both focused public tests PASS.

All 16 `tests/InstallationProcess/pilot_session_storage_*_test.php` files were
run individually. Result: **16/16 PASS**.

`make architecture-check` remains nonzero only for the exact known predecessor
set of 13 session-storage ownership fingerprints. No hotspot or new fingerprint
is present.

The independent bounded cyclic-payload robustness probe exits `255` with a
memory-exhaustion fatal at `PilotSessionPayloadCodec.php:28`; this is CR-3.

## Reviewed hashes at `0903051`

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
04b35c57792b90fb8b6ad26c3c082b08731e5e8a0057118861f66ddc3fafab88  reviews/tests/PILOT-SESSION-STORAGE-001-malformed-payload-v7.md
7db8e4d6758afc0ea6561e536bce500fc09e57f5dbf48e88dd9a741c9013a5e5  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
a0b52d4880c36c18ffccb32f5e4670d5faa9da207dced4c0bbb2191ff405ed51  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
7aac9889bf04db5e6c30648eee296853f9c9981ea5c8e6517c6551ad87af0c34  app/PilotHttp/PilotE2ECoordinator.php
06f3652026d74ade7b76e094b5c861b59ee0af7f6ea7c2b6c0a6ee4dc65afa0d  app/PilotHttp/PilotSessionPayloadCodec.php
f45b5249c3ce83f9280a3b8215a2d203187dc7b51b8aa628eec64f5f7802351e  reviews/code/PILOT-SESSION-STORAGE-001-malformed-payload-v1.md
```

Gate 5 remains **CHANGES_REQUESTED** for commit
`0903051ea49605e676e1e26ff52f7cafa34654fd`.
