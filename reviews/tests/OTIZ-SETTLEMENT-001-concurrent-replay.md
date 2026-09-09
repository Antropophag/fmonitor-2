# OTIZ-SETTLEMENT-001 — concurrent exact reversal replay Gate 3

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test author: executor `/root/settlement`; this reviewer did not author the specification, reviewed test, production implementation, or reported RED run
- Reviewed exact candidate: `3ca97e96f505ac9208b4136028a5ecb5e3e356ce`
- Supplemental test commit: `4a8b31bdac4d78f008d63826882ba1e2b3146152`
- Public seam: `FMonitor2\Otiz\OtizSettlement::reverse()` from two independent worker processes
- Verdict: **CHANGES_REQUESTED**

This review is limited to the supplemental concurrent same-actor, same-operation
reversal replay increment. It does not reopen the earlier settlement test
approvals or approve the complete OTIZ settlement delivery.

## Findings

### T1 — BLOCKING: overlap is not guaranteed

`tests/Otiz/settlement_concurrency_001_test.php:39` releases two workers with a
`GO` line and immediately compares their results. The worker at
`tests/Otiz/settlement_worker.php:15-17` starts the command after reading `GO`,
but there is no barrier after both commands enter the reversal path and no
test-only delay or lock holding the first reversal open. The insertion-delay
trigger used for concurrent bulk is dropped at settlement test line 31, before
the reversal cases.

The scheduler may therefore allow the first worker to commit before the second
worker calls `reverse()`. Ordinary sequential exact replay then correctly
returns `[true,true]`, so the test can pass while the concurrent pre-receipt
race still returns `ALREADY_REVERSED`.

Add deterministic test-only synchronization that proves both reversal commands
have entered the relevant race before either can commit. Preserve a fresh RED
from that synchronized case.

### T2 — BLOCKING: the saved plan is stale for the reviewed candidate

Candidate `3ca97e96` corrects
`openspec/changes/otiz-settlement-owner/verification-input.json`, but the saved
`.local/verification/otiz-settlement-owner-plan.json` still binds head
`4a8b31bdac4d78f008d63826882ba1e2b3146152` and the previous input snapshot.
The required exact check returned:

```text
$ python3 tools/delivery/change-verification.py check \
    --plan .local/verification/otiz-settlement-owner-plan.json
SETUP_FAILURE: stale or tampered verification plan
exit 1
```

Regenerate the plan against the exact corrected candidate, inspect its changed
obligations, and preserve a successful `check` result before rereview.

### T3 — BLOCKING: actual RED output is not saved

A repository and available local-state search found no artifact containing the
reported line-39 mismatch `expected [true,true]`, `actual [false,true]`, and
exit `255`. The assertion is present in the test source, but the actual run is
available only through the executor handoff. Gate 3 requires retained actual
failure evidence bound to the exact test bytes.

Preserve the exact command, candidate and test identity, relevant output, exit
code, and explanation that the failure is the missing exact-replay behavior
rather than setup failure.

## Confirmed review properties

The expected outcome is independent of the implementation: the normative exact
replay rule requires the same actor and operation UUID with the same fingerprint
to return the stable successful result while appending no second reversal. The
line-39 assertions correctly require two successes, byte-equivalent decoded
results, and exactly one linked reversal.

The separate line-36 race uses different actors and different operation UUIDs
and requires one success plus `ALREADY_REVERSED`. Thus exact same-UUID replay is
not conflated with a distinct reversal command.

The test uses the public settlement owner rather than a private production
method. Candidate `3ca97e96` also corrects the complete verification mapping:
the existing discipline URL is
`POST /pilot/otiz/snapshots/{snapshotId}/closures`, and the canonical catalogue,
schema migration, and schema acceptance test are planned explicitly.

## Gate consequence

Gate 3 is **CHANGES_REQUESTED**. After test-only synchronization, regenerated
plan validation, and fresh exact RED evidence, request another independent Gate
3 review. Remaining delivery gates are minimal Gate 4 implementation, focused
GREEN and required regressions, independent Gate 5 review, and one authoritative
full CI run for the exact candidate.
