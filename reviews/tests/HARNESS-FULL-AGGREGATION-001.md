# Test review: HARNESS-FULL-AGGREGATION-001 v0.2

- Gate: 3 — independent test review
- Reviewer: separately tasked fresh agent `/root/canonical_migrate_stage_test_review_v3`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed ancestry: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`; dirty-tree bytes pinned below
- Public seam: `make verify` through the root `Makefile` plus isolated fixture recipes
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

The revised test covers all v0.2 acceptance paths. It requires the exact nine-stage reset-then-migrate order for a middle DB regression, a fully green run, a reset setup failure, and two non-adjacent ordinary failures. The two-failure scenario executes all nine fixture commands and fixes the exact terminal aggregate as `count=2 stages=architecture-check,unit-test`; it therefore rejects fail-fast behavior, a stale eight-stage runner, wrong ordering, duplicate/missing stage protocol, and incomplete aggregation.

Every executed fixture emits unique stdout and stderr evidence. Assertions require those streams to remain visible for every ordinary pass/failure. Setup fixtures intentionally emit no production protocol, so the runner—not the fixture—must classify reset failure, suppress migration/DB/E2E execution, emit setup-causal skip lines, publish all nine ordered results, and produce the exact four-stage terminal summary. Expected results are literal contract values and are not derived from the production recipe.

The overlay changes only stage implementations while invoking the real public `verify` target. The PATH-local `git` fixture covers `diff-check`; all other stage calls are logged by one deliberately simple executable. Temporary artifacts use a random isolated directory and are removed in `finally`. No Docker or database is required, and the fixture cannot satisfy the absent migration orchestration itself.

## Reproduced RED

Command:

```text
php tests/Verification/harness_full_aggregation_001_test.php
```

Result: exit `255` at the intended first `RED_ASSERTION`. Current `make verify` logged:

```text
test-db-reset PASS
architecture-check PASS
lint PASS
unit-test PASS
db-test FAIL
characterization-test PASS
e2e-test PASS
diff-check PASS
```

Expected output inserts `migrate PASS` immediately after reset. The failure is the missing required migration stage; fixture setup and the later independent stages all ran.

## Reviewed hashes

```text
5599f71b9abe97b44a40ca8eee68ffaabc548634d76a3c05e462e04b33591f42  specs/HARNESS-FULL-AGGREGATION-001.md
fac819bb073ad29d3e1599b83e6ede3373713d22f6f3e9c7bdf75dfaaef3f8f1  tests/Verification/harness_full_aggregation_001_test.php
40d3205574ffa122e6cfcc03fcf4a0f32407aa91002631d0fe6cd95e09079e0b  Makefile
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
```

Gate 3 is approved. Gate 4 may implement the nine-stage orchestration and setup protocol without changing the reviewed expectations.
