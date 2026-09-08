# VERIFICATION-PR-CYCLE-001 — Gate 3 make-environment corrective review v2

- Gate: 3 — corrective executable contract
- Reviewer: separately tasked agent `/root/cycle_review`
- Independence: reviewer did not author the amended specification, test, implementation, or RED evidence
- Date: 2026-09-08
- Verdict: **CHANGES_REQUESTED**

## Reviewed correction

The new scenario calls the real public `make test CATEGORY=unit` against an
isolated copied Makefile and category catalog. Its fake PHP runtime invokes a
real nested make whose explicit target rejects the observed leaked `CATEGORY`.
The 30-second subprocess deadline bounds the recursion failure. The captured RED
is behaviorally relevant: the outer category executes its two assigned fixtures,
the nested make reports `category leaked into nested make`, and the public command
returns nonzero. This reproduces the reason the governance CI job recursed and
hung; it is not a missing-tool or fixture setup failure.

## Finding

The amended requirement explicitly says the category runner clears all four make
control variables before test runtime: `CATEGORY`, `MAKEFLAGS`, `MFLAGS`, and
`MAKEOVERRIDES`. The executable currently checks only the resulting `CATEGORY`
inside the nested make. It does not observe the environment received by the fake
PHP test process, so a partial implementation that suppresses the category while
leaving `MFLAGS` or `MAKEOVERRIDES` available to every test can pass.

Make the fake PHP runtime reject any present/nonempty value among all four
variables before it invokes nested make, while retaining the existing inner target
check as the behavioral recursion proof. Then reproduce the focused RED and request
a narrow rereview. No additional matrix expansion is required.

## Evidence and validation

```text
688b2274da86392b9f8fc476ee701ddd3457259b45f7749efd3e37b85dbacd33  tests/Verification/verification_ci_001_test.py
3db577024f92eb1e19cfbb5c5647085710b21db2b8c473dc5439fbcd8b4fb969  openspec/changes/optimize-verification-pr-cycle/specs/verification/pr-cycle/spec.md
4247e588bd3c2bd0c18239f652bbabca942cec77b04edd147e984165a6c9f9f6  /tmp/fmonitor25-make-env-red.log
```

Python syntax, strict OpenSpec validation, and changed-file diff check pass.
Production implementation was not changed or executed by this review. The prior
Gate 5 approval cannot support readiness after this discovered defect and amended
contract; a fresh Gate 5 is required after the minimal correction.
