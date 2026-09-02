# Test review: CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 v3

- Gate: 3 — fresh independent test re-review
- Reviewer: separately tasked agent `/root/photo_limit_concurrency_test_review_v3`
- Test author: separately tasked Gate 2 agent (not this reviewer)
- Reviewed commit: dirty working tree; exact artifacts pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md`, version `0.1`
- Public seam: `php rapid-pilot/verify-checklist-photo-limit-concurrency.php`
- Red command: `make test-env-up && php tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

The current test is traceable to the approved characterization specification,
exercises the agreed public verifier seam, derives its expected values from
literal test-owned fixtures and the specification, remains winner-neutral, and
fails for the missing verifier rather than for broken setup. The future Gate 5
review must inspect and pin the implemented verifier because the JSON audit is
necessarily verifier-produced evidence.

## Prior blockers closed

- An echo-only verifier cannot pass. The test pins both PNG fixtures and requires
  exact fixture consumption, distinct child PIDs and MariaDB connection IDs, a
  shared barrier, winner-neutral terminal results, exact aggregate deltas, the
  accepted contender identity, zero loser mutation, and the separate
  same-content-at-cap call.
- Occupied SQL and storage namespaces are independently exercised and must be
  rejected with exit `2` before mutation. Their exact fingerprints must remain
  unchanged.
- Controlled child-crash and regression paths run after fixture mutation and
  require exact exit/status classification, owned SQL/storage cleanup, and
  preservation of ambient SQL/storage decoys.
- `timeout_after_mutation` now has its own bounded verifier-owned path. It must
  return exit `2`, emit no stdout, emit exactly
  `SETUP_FAILURE: controlled race child timeout after mutation`, remove its exact
  SQL/storage namespace, preserve both decoys, identify the invoked parent and
  two distinct child PIDs, establish an isolated process group, and prove the
  parent, both children, and the process group have been reaped.
- The outer meta-test deadline remains an emergency anti-hang boundary. If it
  fires, the thrown message begins `SETUP_FAILURE:` and therefore exits `2`, while
  recording owned namespace, decoy, parent, and process-group cleanup evidence;
  the test-wide `finally` still removes every safely discovered owned table and
  the exact private artifact root.
- Stdout and stderr are drained concurrently with a fixed deadline. The previous
  verifier-hash placeholder/bypass is absent; Gate 3 pins the spec and test, while
  Gate 5 will pin the implemented verifier.
- Two nominal runs use distinct unoccupied tokens, require byte-identical stdout,
  consume and delete their audit files, and prove exact SQL/storage cleanup plus
  decoy preservation after each run.

## Reproduced intended RED

The disposable MariaDB container became healthy. The focused test exited `1`
only at the absent public seam:

```text
RED_ASSERTION: missing public photo-limit concurrency verifier must become a successful first run; evidence={"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-limit-concurrency.php\n","timed_out":false,"pid":177786,"process_group_id":null}
Expected: 0
Actual: 1
```

This is the intended behavioral RED: specification and literal fixtures loaded,
the disposable database was reachable, and ambient SQL/storage fixtures were
created before invocation. `make test-env-down` subsequently removed the
container, volume, and network. No generated
`.local/test-artifacts/characterize-photo-limit-*` directory remained.

Additional review checks:

```text
$ php -l tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
No syntax errors detected in tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php

$ git diff --check -- tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md
# exit 0, no output
```

## Reviewed hashes

```text
e9481cf5239c407c52383a91289c4d17779ef32b6dd3da82d1aff9a1c6dfd820  specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md
c31b88a06770792f33eaaa1238cb995489529227f504eb32b1b5c4d46be51cba  tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
```

Gate 3 is approved for these exact hashes. Any change to the specification or
test invalidates this approval and requires a fresh independent Gate 3 review.
Gate 4 may now implement only enough verifier behavior to make this reviewed test
green.
