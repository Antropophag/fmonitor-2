# INSPECTION-ITEM-COMPLETE-001 — independent endpoint/prefix Gate 3 rereview v4

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, evidence, specification or production)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Endpoint admission/cleanup test v4: SHA-256
  `95fc1678a392023fd2629997896b472b27575ebc4dc9677596cd4759bc45d779`.
- Endpoint cleanup evidence v4: SHA-256
  `9ff1bff39f5ebb9f7b497cd730b480db4f081b9d5689fe3f5c0f50ea9db8f19f`.
- Unchanged prefix test: SHA-256
  `1e8b7f4a58a1a34d86923cf74cf8160cbb7908eec7b98c179043635feb70b04e`.
- Prior v3 review: SHA-256
  `a07d850a5aad609a653392ae5998d0893ad337d7f2bdb4ce90c5c4746419bc58`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

No production or test file was edited by this review.

## Independent reproduction and normal cleanup

With a healthy isolated Compose database, both syntax checks passed. I ran:

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
```

Endpoint v4 reproduced:

```text
Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

Prefix reproduced the unchanged `bad-prefix` RED. Both runner commands exited
`0`; endpoint stderr contained no `CLEANUP_FAILURES` block. I independently
verified no exact private database, artifact root or router remained. Compose
`down -v --remove-orphans` removed container, volume and network; final
`ps --all` was empty.

## Reconfirmed closed behavior findings

- `EP-01` remains closed: real page-issued session/CSRF, malformed-item raw
  POST exact 422/rejected/revision-zero expectation, separate non-item 403 and
  sync-context 200/revision-zero distinguish all relevant endpoint gates.
- `EP-02` remains closed: exact `SHOW CREATE TABLE`, all ordered v8 evidence
  rows and artifact hashes must be unchanged for these rejected/read-only
  probes.
- `PX-01` remains closed: invalid ASCII punctuation, length overflow and
  non-ASCII each require pre-DB `InvalidArgumentException`.

## EP-03 status

### Bounded process ownership — closed

The stop primitive now observes status after bounded TERM and KILL polling. It
calls `proc_close` only after confirmed non-running status; otherwise it records
a cleanup failure and returns instead of entering an unbounded reap. It closes
only owned nonblocking pipes. The forced long-running child self-check verifies
the ordinary TERM/KILL/reap path completes within 3.5 seconds. Recursive owned
root removal is also exercised against a forced nested partial tree.

### Aggregated, verdict-affecting self-verification — still open

The evidence claims cleanup diagnostics are accumulated after the primary
assertion. They are accumulated and printed, but they are not made part of the
test verdict:

- the shutdown function only writes `CLEANUP_FAILURES` to stderr. If behavior
  becomes GREEN but router/database/root cleanup fails, PHP can still exit `0`
  after printing `PASS`; the test suite/runner is not required to interpret
  that free-form stderr as failure;
- the main `finally` still calls database close/drop, admin close and `rmdir`
  directly, outside per-cleanup `try/catch`. A thrown DROP/close failure can
  replace and hide the primary behavior assertion rather than being aggregated;
- router absence is conditional on `function_exists('posix_kill')`; without
  that extension the claimed exact process-absence self-check is silently
  skipped instead of using another supported check or declaring setup failure.

Normal cleanup succeeding in this review does not make these failure paths
sensitive. The forced self-check tests a successful cleanup of an owned child
and tree, not whether a deliberately reported cleanup failure makes an
otherwise-green test fail while retaining its primary diagnostic.

Required change: capture the primary throwable, execute every exact owned
cleanup and absence probe under individual guards, then decide once after
cleanup. With no primary failure, any cleanup finding must produce a nonzero
`TestFailure`. With a primary behavior failure, report that primary class/
message plus all cleanup findings in one deterministic aggregate rather than
allowing a cleanup exception to replace it. Make process absence unconditional
for the supported test environment (or fail setup when no reliable process
probe exists). A small injected cleanup-failure self-check should prove both
verdict-affecting and primary-preserving branches, not merely successful cleanup.

## Gate decision

The HTTP/prefix behavior contours, no-mutation oracle and bounded confirmed
reap are sound, and normal cleanup is clean. The sole remaining blocker is that
cleanup self-verification is diagnostic-only and direct `finally` cleanup can
still mask the primary result. Endpoint/prefix Gate 3 v4 remains narrowly
`CHANGES_REQUESTED` until EP-03 cleanup failures deterministically affect the
verdict without replacing the primary behavior evidence.
