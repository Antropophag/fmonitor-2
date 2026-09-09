# OTIZ-SETTLEMENT-001 — concurrent exact reversal replay Gate 3 v2

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test author: executor `/root/settlement`; this reviewer authored neither test nor production
- Reviewed exact candidate: `ec1ed395ac9e65042157aef474e0cf8e5939725b`
- Correction baseline: `52d7e2fd578c60230184d09aeaaf7ea8f0c10cf1`
- Public seam: `FMonitor2\Otiz\OtizSettlement::reverse()` from two worker processes
- Verdict: **CHANGES_REQUESTED**

This rereview is limited to the deterministic-overlap and RED-evidence
corrections requested by the first concurrent replay review.

## Closed findings

The candidate adds append-only evidence at
`docs/operations/otiz-settlement-red-evidence-2026-09-09.md`. It records the
focused command, exit `255`, exact expected `[true,true]`, two observed actual
orders `[false,true]` and `[true,false]`, and the intended missing behavior.
That closes the absence-of-evidence finding for the reviewed bytes.

The executor also regenerated the ignored verification plan after the candidate
commit and reported literal `CHANGE_VERIFICATION_OK`. A later check in the
shared worktree cannot independently qualify those frozen bytes because this
reviewer's authorized untracked review records are now additional actual paths;
that later stale result is not classified as an `ec1ed395` candidate defect.

## T1 — BLOCKING: a fixed sleep is not a deterministic two-party barrier

At `tests/Otiz/settlement_concurrency_001_test.php:39`, a `BEFORE INSERT`
trigger sleeps for 400 ms when a reversal closure is inserted. This makes the
observed race likely and produced two useful RED samples, but it does not prove
both commands entered the relevant transaction state before the winner commits.

`oscRunPair()` writes `GO` to the two processes in sequence. Each worker then
constructs the service and invokes `reverse()`. Nothing acknowledges that both
workers passed the initial receipt lookup, reached the financial-object lock,
or even entered `reverse()` before the first worker's 400 ms expires. Under a
pause, busy host, or scheduler delay longer than 400 ms, worker two can begin
after worker one commits. It then performs ordinary sequential exact replay and
returns success, allowing `[true,true]` on the currently broken concurrent path.

The evidence statement that the losing command “observes no receipt before
waiting” is an inference from typical timing; the fixture neither observes nor
asserts it. Two consecutive failures demonstrate sensitivity in those runs but
do not make the test deterministic and isolated as Gate 2 requires.

Replace the timing assumption with an explicit two-party rendezvous or another
deterministic database synchronization protocol. The test must fail setup if
both workers do not reach the controlled pre-commit state, release the winner
only after that state is established, and retain the same public outcomes and
one-reversal expectations. Capture fresh RED for the corrected exact bytes.

## Retained correctness

The expected values remain independently derived from the normative replay
contract. Same actor, same UUID and same fingerprint must return the stable
successful result twice and append one reversal. The earlier distinct-actor,
distinct-UUID race still separately expects one success and one
`ALREADY_REVERSED`, so the two cases remain semantically distinct.

## Gate consequence

Gate 3 remains **CHANGES_REQUESTED**. Gate 4 is not authorized by this record.
After deterministic synchronization and fresh exact-plan/RED evidence, request
another independent rereview. Required later gates remain focused GREEN and
regressions, independent Gate 5, and one exact-source full CI run.
