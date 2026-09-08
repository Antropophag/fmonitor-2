# Code rereview: PILOT-SESSION-STORAGE-001 v10 object/reference payload — GREEN v3

- Gate: 5 — fresh independent code rereview
- Reviewer: separately tasked agent `/root/session_malformed_code_review`
- Independence: reviewer did not author or edit the specifications, approved tests, RED evidence, or reviewed production implementation
- Reviewed commit: `d56d4e2203fc6e8c5e986051220a46c35d80b7aa`
- Reference-test commit: `18157938deacc356f9535b11ecbe5529a9f5dca1`
- Parent implementation commits: `0e808ff`, `0903051`
- Fixed point: `e78472b7748250db34f80bb28b1179f5d2b4d9e5`
- Full production range: `git diff e78472b..d56d4e2 -- app/PilotHttp/PilotE2ECoordinator.php app/PilotHttp/PilotSessionPayloadCodec.php`
- Approved test reviews: `reviews/tests/PILOT-SESSION-STORAGE-001-malformed-payload-v7.md`, `reviews/tests/PILOT-SESSION-STORAGE-001-reference-payload-v1.md`
- Prior append-only reviews: malformed-payload v1 and v2 — `CHANGES_REQUESTED`
- Verdict: **APPROVED**

## Fresh review findings

No blocking findings.

CR-1 remains closed. Session-payload decoding is isolated in the focused
34-line `PilotSessionPayloadCodec`; `PilotE2ECoordinator.php` remains exactly at
its 268-line architecture baseline. The codec is a cohesive HTTP-adapter seam
and does not add public owner/DTO construction API or a second storage reader.

CR-2 remains closed. The full production range implements only the two
independently authorized contours: warning-captured whole-array decode with
classes disabled, recursively reachable object rejection, and reference
rejection before recursive descent. It does not add canonical reserialization,
depth/entry limits, complete scalar-shape enforcement, encode/write validation,
or accepted-state restoration behavior belonging to later slices.

CR-3 is closed by the separately reviewed reference slice. For every array
element `containsObject()` first calls
`ReflectionReference::fromArrayElement($state, $key)` and returns rejection
before reading or descending into the value. The approved `R:1` self-reference
therefore cannot recurse. The raw-HTTP reference test now reaches the exact
safe 503 rather than its former bounded timeout/memory failure.

For both approved literals rejection occurs after the real owner returns its
opaque bytes but before session write and before route/authentication/DB
dispatch. The shared public HTTP harness proves the complete section-6 response,
one exact `payload_invalid` record with a fresh 12-lowercase-hex correlation,
no generic entrypoint record, unchanged committed material, DB-sentinel
silence, and absence of payload/markers/CSRF/credentials/session ID/root from
HTTP and stderr. Warning conversion is restored in `finally`; the diagnostic
write suppresses adapter warnings; no exception/class/parser details cross the
boundary.

The private codec API is small and intention-revealing. No blocking Fowler
smell, duplicated policy, public-API drift, or unrelated production refactor is
present in the reviewed range.

Valid payload restoration and whole-array write encoding remain later RED
slices and are not claimed by this approval.

## Verification evidence

```text
php -l app/PilotHttp/PilotSessionPayloadCodec.php
php -l app/PilotHttp/PilotE2ECoordinator.php
php -l tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php
php tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
php tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php
php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
```

Result: all lint commands exit `0`; object, reference, and protocol tests PASS.

All 17 `tests/InstallationProcess/pilot_session_storage_*_test.php` files were
run individually. Result: **17/17 PASS**.

`git diff --check e78472b d56d4e2` exits `0`.

`make architecture-check` remains nonzero only for the exact known predecessor
set of 13 `session_storage_ownership` fingerprints: two in
`PilotE2ECoordinator.php`, six in `rapid-pilot/LocalAuth.php`, and five in
`rapid-pilot/UserAccessView.php`. There is no hotspot finding and no new
fingerprint; this is the declared consumer-removal RED, not a regression in the
approved object/reference implementation.

## Reviewed hashes at `d56d4e2`

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
04b35c57792b90fb8b6ad26c3c082b08731e5e8a0057118861f66ddc3fafab88  reviews/tests/PILOT-SESSION-STORAGE-001-malformed-payload-v7.md
bd43ef0692c1f6b794a0daa85c66978847982b5d08072298d24009a5dd825edc  reviews/tests/PILOT-SESSION-STORAGE-001-reference-payload-v1.md
342833c29488684837495d9c85289fef3f7ae18a78ad90486c4c17ae6a762d6f  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
a0c67f82512619da5912be10b9f15c60f9db1043c1ca98e7497622bdd0700653  tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php
721cf5ade1d37decc0962e39b22ac72c17688a6efe36c90d0b632bcf5089a95c  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
3dea7b6038baa556ae4edd2237e7f45f2ccf5d0a8bb2f0c4e5db52d8626bdd3e  docs/operations/pilot-session-storage-reference-payload-red-evidence-2026-09-03.md
7aac9889bf04db5e6c30648eee296853f9c9981ea5c8e6517c6551ad87af0c34  app/PilotHttp/PilotE2ECoordinator.php
074aab19ab69947435f6ffb07b525329e21bff6550b57ebf10e5b42ea9ed7bb2  app/PilotHttp/PilotSessionPayloadCodec.php
f45b5249c3ce83f9280a3b8215a2d203187dc7b51b8aa628eec64f5f7802351e  reviews/code/PILOT-SESSION-STORAGE-001-malformed-payload-v1.md
278095a9bb3417ad5e3a4be21c399ab7eab32108dc6cdaa6f34ab708dd8de382  reviews/code/PILOT-SESSION-STORAGE-001-malformed-payload-v2.md
```

Gate 5 is **APPROVED** for commit
`d56d4e2203fc6e8c5e986051220a46c35d80b7aa`.
