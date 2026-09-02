# INSPECTION-ITEM-COMPLETE-001 — independent endpoint/prefix Gate 3 rereview v3

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, evidence, specification or production)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Endpoint admission test v3: SHA-256
  `40e2c36434d8a3131fa1e9c2b3f784d79ab5da0596700ec5b46a8324a01c32b7`.
- Endpoint RED evidence v3: SHA-256
  `c1bf1aa63d8db5531246c12d32ae5f8bc3d302775a74921fd06ae86b5edcb282`.
- Unchanged prefix test: SHA-256
  `1e8b7f4a58a1a34d86923cf74cf8160cbb7908eec7b98c179043635feb70b04e`.
- Prior v2 review: SHA-256
  `81dcf6ce84e0af04b000a12e305c93e91411fd92a9562a072c4a43a22545eab6`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

No production or test file was edited by this review.

## Independent reproduction and normal cleanup

With a healthy isolated Compose database, both syntax checks passed and I ran:

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
```

Endpoint v3 reproduced the intended actual-command admission RED:

```text
Admitted malformed item maps to HTTP 422.
Expected: 422
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

Prefix reproduced the unchanged intended invalid-character RED:

```text
Invalid production prefix must fail configuration before DB access: bad-prefix
RED_ASSERTION: expected failing behavior observed
```

Both runner commands exited `0`. I independently found no private `t_iea_%`
database, `.test-artifacts/iea-*` root or owned PHP router after the endpoint
failure. Compose `down -v --remove-orphans` removed the container, volume and
network; final `ps --all` was empty.

## Finding closure

### EP-01 — closed

The public raw-HTTP sequence is now coherent and independently expected:

- an HTML GET supplies a real session cookie and CSRF token;
- an otherwise-valid `item_completed` changes only the literal
  `deviceInstallationId=not-a-uuid`;
- the actual operations POST must pass endpoint admission and return exact HTTP
  422 with `status=rejected`, revision `0`, and projection revision `0`;
- a separate `section_completed` POST by the same capability-only actor remains
  HTTP 403, proving the admission exception is item-specific rather than a
  blanket checklist bypass;
- sync-context must be HTTP 200 at revision `0`.

This distinguishes the item POST gate, sync-context gate and non-item legacy
gate through production TCP/HTTP. A fix to only one route, generic 200 response,
wrong result mapping or blanket non-item authorization cannot pass.

### EP-02 — closed

Because both POST probes are intentionally rejected, strict equality of exact
`SHOW CREATE TABLE`, all ordered rows in the four evidence tables, and all
owned artifact hashes is now consistent with the expected outcomes. Revision
zero is seeded before the snapshot, so GET projection cannot conceal an insert.
Any DDL repair, evidence/revision DML, auto-increment change represented in
`SHOW CREATE TABLE`, or artifact write is observable.

### PX-01 — reconfirmed closed

`bad-prefix`, 26 ASCII bytes and non-ASCII remain literal negative cases against
an unconnected handle, each catching only `InvalidArgumentException`. The
canonical grammar and pre-DB ordering remain mutation-sensitive.

### EP-03 — partially improved, still open

Socket connect/read/write deadlines, response cap, nonblocking server pipes,
startup ownership/early-exit handling, TERM deadline and KILL escalation are
present. Recursive shutdown cleanup now removes unexpected nested artifact
members, so the former non-empty-root leak is materially addressed. Normal
cleanup passed independently.

The remaining prior requirements are not implemented:

- after the one-second KILL polling deadline, `ieaStop` neither verifies nor
  asserts that `status['running']` became false; it closes pipes and calls
  `proc_close` even if the owned child is still running. `proc_close` may then
  block beyond the claimed bound;
- database drop, process stop/reap and root removal are performed directly in
  `finally`/shutdown rather than aggregated. A cleanup exception can replace
  the primary RED diagnostic;
- the test does not self-verify absence of its exact database, process and root.
  Those checks were only performed externally by this review.

Required change: make the stop primitive return/throw a bounded cleanup result
only after confirmed child exit; do not enter an unbounded reap while status is
running. In the outer `finally`, preserve the primary failure, attempt every
owned cleanup, aggregate cleanup failures, and verify the exact private
database, router process and artifact root are absent. The shutdown fallback
may remain as last-resort root cleanup but is not a substitute for observable
normal/failure cleanup completion.

## Gate decision

EP-01, EP-02 and PX-01 are fully closed, and both behavior REDs are genuine.
The only remaining blocker is the previously requested bounded/self-verifying
EP-03 failure lifecycle. Endpoint/prefix Gate 3 v3 remains narrowly
`CHANGES_REQUESTED` until that cleanup path cannot hang or mask/leak on an
adversarial failure.
