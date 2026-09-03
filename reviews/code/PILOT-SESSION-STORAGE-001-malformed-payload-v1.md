# Code review: PILOT-SESSION-STORAGE-001 v10 malformed object payload — GREEN v1

- Gate: 5 — independent code review
- Reviewer: separately tasked agent `/root/session_malformed_code_review`
- Independence: reviewer did not author or edit the specification, approved test, RED evidence, or reviewed production implementation
- Reviewed commit: `0e808ff86685a3c22c20c7b2e243bf1dca955158`
- Parent/fixed point: `e78472b7748250db34f80bb28b1179f5d2b4d9e5`
- Production diff: `git diff e78472b..0e808ff -- app/PilotHttp/PilotE2ECoordinator.php`
- Approved Gate 3 record: `reviews/tests/PILOT-SESSION-STORAGE-001-malformed-payload-v7.md`
- Verdict: **CHANGES_REQUESTED**

## Blocking findings

### CR-1 — New hotspot growth violates the architecture ratchet

`app/PilotHttp/PilotE2ECoordinator.php:264-276` adds the codec, recursive shape
policy and correlation generation to an already baselined 268-line HTTP
hotspot. `make architecture-check` reports:

```text
hotspot_ratchet: app/PilotHttp/PilotE2ECoordinator.php grew 268 -> 280 lines
```

This is a hard violation of `docs/architecture/guardrails.md` section 4: an
existing hotspot may shrink but may not grow, and moving behavior behind a seam
is explicitly preferred. The codec policy also gives this coordinator another
reason to change (possible Divergent Change / Feature Envy): the class already
owns routing, HTTP envelopes, cookies and application delegation, while the new
methods own serialization grammar, reference/depth/count limits and decode
warnings. Move the codec behind a focused session-state seam in a non-hotspot
module; do not rebaseline this growth merely to make the check green.

The check's 13 `session_storage_ownership` findings — two fingerprints in this
coordinator, six in `rapid-pilot/LocalAuth.php`, and five in
`rapid-pilot/UserAccessView.php` — are the exact known predecessor set recorded
by the earlier payload-handoff review. They remain the declared consumer-removal
RED and are not newly introduced by this commit. The hotspot finding is new.

### CR-2 — GREEN exceeds the independently authorized contour

Gate 3 v7 authorizes only the production codec/consumer plumbing required to
reject the exact recursively reachable object-bearing payload. It explicitly
does not authorize implementation or coverage claims for references/cycles,
trailing/noncanonical encodings, or depth/entry limits. Nevertheless:

- `decodeSessionPayload()` rejects noncanonical/trailing encodings by exact
  reserialization equality;
- `validSessionState()` rejects references, applies depth 16 and entry count
  4096, and rejects all scalar types outside the final full codec contract.

Those are eventually required by the approved v10 specification, but under
`docs/development-process.md` Gate 4 they need their own demonstrated RED and
independent Gate 3 authorization before production implementation. Narrow this
GREEN to the reviewed malformed-object contour. The approved test already has
a sensitive matching-CSRF and DB-dispatch sentinel, so object rejection cannot
be replaced with an unrelated early failure.

## Additional review notes

For the exact reviewed object payload, behavior is correctly ordered: the
owner-provided bytes are decoded before session write and before login/DB
dispatch; the object is rejected; committed material remains unchanged; and the
shared response builder emits the exact redacted section-6 503. The focused
test proves exactly one canonical `payload_invalid` stderr record, no generic
entrypoint record, a 12-lowercase-hex correlation and absence of all fixture
secrets in HTTP/stderr. No public DTO/API surface changed.

Warning restoration around `unserialize()` is exception-safe because
`restore_error_handler()` is in `finally`; however `$previous` is unused and
should be removed. The output `$state` is likewise assigned but never consumed
in this slice, making the private decoder contract misleading (possible
Mysterious Name / Speculative Generality).

The new diagnostic path writes directly with `file_put_contents()` outside
warning capture. A stderr write failure can therefore emit an uncontrolled PHP
warning instead of preserving the specified closed diagnostic boundary. Also,
`sessionCorrelationId()` falls back to constant `000000000000`; it matches the
syntax but not section 3's requirement that every failure correlation be fresh.
When extracting the seam for CR-1, keep diagnostic failure closed and preserve
fresh-correlation semantics without leaking exception details.

Valid payload restoration and whole-array write encoding are not claimed by
this malformed-object GREEN and remain later RED slices. Their pre-existing
incomplete consumer behavior is not treated as a regression in this diff.

## Verification evidence

```text
php -l app/PilotHttp/PilotE2ECoordinator.php
php tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
```

Result: syntax check exits `0`; focused test exits `0` with
`PASS: PILOT-SESSION-STORAGE-001 v10 malformed payload raw HTTP`.

All 16 `tests/InstallationProcess/pilot_session_storage_*_test.php` files were
run individually in binary path order. Result: **16/16 PASS**, including the
malformed-payload and owner payload-handoff tests.

```text
make architecture-check
```

Result: exit `2` from `make` (`tools/architecture/check` finding exit is
nonzero): the exact 13 known ownership fingerprints plus the new
`PilotE2ECoordinator.php` hotspot growth described in CR-1.

`git diff --stat e78472b..0e808ff` contains one production file, 13 insertions
and one deletion. Tests, specification and RED evidence are unchanged.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
7db8e4d6758afc0ea6561e536bce500fc09e57f5dbf48e88dd9a741c9013a5e5  tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
a0b52d4880c36c18ffccb32f5e4670d5faa9da207dced4c0bbb2191ff405ed51  docs/operations/pilot-session-storage-malformed-payload-red-evidence-2026-09-03.md
bcdd0a91ac00c6f47307944a1fcc7be67218bcdfec69d58abfa8d4600c787d55  app/PilotHttp/PilotE2ECoordinator.php
```

Gate 5 is **CHANGES_REQUESTED** for commit
`0e808ff86685a3c22c20c7b2e243bf1dca955158`.
