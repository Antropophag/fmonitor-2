# INSPECTION-ITEM-COMPLETE-001 — independent endpoint/prefix Gate 3 rereview v2

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
reviewed tests, evidence, specification or production)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved executable spec: SHA-256
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Endpoint test v2: SHA-256
  `7d1ef839d2296cea0946e9b365026f5430f132c56ad72fa324b039affb104808`.
- Prefix test v2: SHA-256
  `1e8b7f4a58a1a34d86923cf74cf8160cbb7908eec7b98c179043635feb70b04e`.
- Combined endpoint/prefix RED evidence v2: SHA-256
  `3b701c657834f67b93c5cf175ac57bc85a5765c34613efa1841f8f998959d378`.
- Prior review: SHA-256
  `88116c1827fdfa1864929e0ad2cbdb0c82e8ce46de122c7cd12e96aafbad86ac`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

No production or test file was edited by this review.

## Independent reproduction and cleanup

With a healthy isolated Compose database I ran both syntax checks and:

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_prefix_validation_test.php
```

The endpoint runner reproduced the actual POST admission RED:

```text
Unassigned exact-capability engineer POSTs item_completed.
Expected: 200
Actual: 403
RED_ASSERTION: expected failing behavior observed
```

The prefix runner reproduced the newly first invalid-character RED:

```text
Invalid production prefix must fail configuration before DB access: bad-prefix
RED_ASSERTION: expected failing behavior observed
```

Both runner commands exited `0`. After the endpoint failure I independently
found no private `t_iea_%` database, `.test-artifacts/iea-*` directory or owned
PHP router. I then removed the Compose container, volume and network with
`down -v --remove-orphans`; final `ps --all` was empty.

## Finding closure status

### EP-01 — actual POST gap closed, outcome contour still inconsistent

The harness now obtains a real HTML-issued session cookie and CSRF token and
sends a raw JSON POST to the production checklist operations route. This closes
the previous false-GREEN in which only sync-context could be relaxed while the
actual command route stayed 403. The exact current 403 is a genuine missing
admission behavior with no policy fake.

However, the test labels the envelope valid and requires POST HTTP 200 without
asserting its JSON `status` or revision. More importantly, it simultaneously
requires the entire four-table snapshot to remain byte/value-identical and the
later sync-context revision to remain `0`. A valid successful
`item_completed` returning 200 must append its operation/installer evidence and
advance revision to `1`. Correct production therefore cannot make all v2
assertions green. Conversely an arbitrary HTTP 200 body is sufficient for the
POST assertion.

Required change: choose one coherent admission contour:

1. Prefer the smallest admission-only case: send an otherwise-valid item with
   one literal malformed UUID, require exact HTTP 422 and exact public
   `rejected/revision 0`, require sync-context HTTP 200/revision 0, and retain
   strict no-mutation snapshots; or
2. Keep the valid accepted command, but require exact HTTP 200
   `accepted/revision 1`, sync-context revision `1`, exact approved append-only
   evidence/revision delta, and unchanged schema/unrelated facts.

In either case parse and assert the POST JSON rather than status alone.

### EP-02 — observation added but its expected state contradicts the POST

Exact `SHOW CREATE TABLE`, ordered rows for all four evidence tables and owned
artifact hashes are now captured around the HTTP sequence. This is an
appropriate mutation-sensitive projection for a rejected/read-only contour.
It is not appropriate to demand equality after the currently valid POST that
the same test expects to succeed. EP-02 closes automatically once EP-01 adopts
one of the two coherent outcome/delta choices above; the observation mechanism
itself no longer has the prior blind spot.

### EP-03 — improved, but failure cleanup is not fully closed

Startup now retains process ownership, detects early exit and calls teardown;
sockets have connect/read deadlines and a response cap; pipes are nonblocking;
TERM has a deadline and KILL escalation. Normal cleanup passed independently.

Two relevant failure paths remain:

- after the KILL deadline, `ieaStop` does not assert the process stopped and
  calls `proc_close` even if it is still running, which can block beyond the
  claimed bound;
- artifact cleanup is only unchecked `rmdir($root)`. If the mutation assertion
  detects an unexpected file—the exact failure it is designed to catch—the
  directory is non-empty, `rmdir` fails, and the artifact leaks. Database/root/
  process absence is checked externally in this review, not self-verified by
  the test, and cleanup failures can mask the primary failure.

Required change: after bounded TERM/KILL, verify owned process termination
before any potentially blocking reap and report a cleanup failure if it cannot
be reaped. Recursively remove only the exact owned artifact root, verify its
absence, verify the private database/process absence, and aggregate cleanup
failures without replacing the primary assertion diagnostic.

### PX-01 — closed

Literal `bad-prefix` is now the first case, followed by the 26-byte and
non-ASCII cases. All require `InvalidArgumentException` using an unconnected
handle, so a length+ASCII-only validator or pre-validation DB access cannot
pass. Expectations are literal and derived from the canonical
`[A-Za-z0-9_]*`, 0..25-byte contract.

## Preserved strengths

- The raw oracle is the production router over a real TCP HTTP exchange.
- Fixture actor `7301` has only `inspection.item.complete`, lacks
  `checklist.edit`, and differs from assigned engineer `7302`.
- Canonical v1-v8 migration, working case, registered order/installer,
  template association and revision fixture make the endpoint path independent
  of planned implementation details.
- The endpoint 403 and prefix invalid-character failure are intended behavior
  REDs rather than setup failures.

## Gate decision

PX-01 is closed, raw POST now closes the central admission observability gap,
and the state projection is capable. But the endpoint test has no possible
spec-conforming GREEN because it expects both accepted HTTP 200 and zero
accepted mutation/revision, while failure cleanup can still block or leak the
artifact it detects. The v2 increment remains `CHANGES_REQUESTED` pending the
focused EP-01/02 reconciliation and EP-03 cleanup completion.
