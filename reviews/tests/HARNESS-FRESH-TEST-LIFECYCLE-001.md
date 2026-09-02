# Test review: HARNESS-FRESH-TEST-LIFECYCLE-001 v0.1

- Gate: 3 — independent test review
- Reviewer: separately tasked fresh agent `/root/fresh_test_lifecycle_test_review_v2`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed ancestry: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`; dirty-tree bytes pinned below
- Public seam: `make fresh-test-verify` through the root `Makefile`, with isolated overlay implementations of `verify` and `test-env-down`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

The test traces every v0.1 acceptance path through the agreed public seam: complete success, verification failure, teardown failure, and dual failure. Each scenario requires exactly one `verify` invocation followed by exactly one `test-env-down` invocation, preserves unique stdout and stderr evidence from both children, fixes the public GNU Make statuses, checks exact terminal protocol cardinality and placement, classifies teardown failure only, and rejects a success marker on every failure path.

All four scenarios run both normally and with `MAKEFLAGS=-j4`, so the exact ordering and once-only teardown contract cannot be accidentally implemented as parallel prerequisites. Signal/process-group recovery is explicitly outside the narrowed specification and is therefore correctly absent from this Gate 2 test.

The fixture prepends an executable `docker` sentinel to `PATH`; every scenario asserts its invocation log remains empty. Thus the test neither requires Docker nor permits the lifecycle wrapper to duplicate Docker orchestration instead of delegating to the two public targets. The overlay owns only deterministic child outcomes and stream markers: it does not define `fresh-test-verify`, its exact stage log cannot be produced merely by fixture setup, and literal expected values come from the specification rather than production logic. Random fixture paths isolate runs, and a `finally` block removes scripts, overlay, logs, and directories after assertion failures as well as success.

## Reproduced RED

Commands:

```text
php -l tests/Verification/harness_fresh_test_lifecycle_001_test.php
php tests/Verification/harness_fresh_test_lifecycle_001_test.php
```

Result: syntax check passed; the behavior test exited `255` at the intended first `RED_ASSERTION`. For the green/normal fixture, current output reported:

```text
make: *** No rule to make target 'fresh-test-verify'.  Stop.
observed=[]
docker=[]
```

The expected stage log was `verify PASS`, then `test-env-down PASS`. The RED therefore proves the lifecycle seam is missing while fixture setup is healthy and the PATH-first Docker sentinel records zero calls. A second run produced the same intended failure with a different random fixture path; inspection afterward found no `/tmp/fmonitor-hftl-*` directories.

## Reviewed hashes

```text
e66500de74561075e784be1317df0db681751632ed25d490bc6f33830b7b9474  specs/HARNESS-FRESH-TEST-LIFECYCLE-001.md
f334a670b34c9de6ea7b86da36e63af28441181f9fbbe7b368fdf92ed10f389a  tests/Verification/harness_fresh_test_lifecycle_001_test.php
399e947104b1e62003b29e634ca59a315e1872f360a84b5cfa3d9c4f7755726a  Makefile
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
```

Gate 3 is approved. Gate 4 may add only the lifecycle orchestration required by the reviewed contract without changing these expectations.
