# Test review: CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001 v2

- Gate: 3 — fresh independent test re-review
- Reviewer: separately tasked agent `/root/photo_limit_concurrency_test_review_v2`
- Test author: separately tasked Gate 2 agent (not this reviewer)
- Reviewed commit: dirty working tree; exact artifacts pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md`, version `0.1`
- Public seam: `php rapid-pilot/verify-checklist-photo-limit-concurrency.php`
- Red command: `make test-env-up && php tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUESTED`

## Blocking finding

### The required child-timeout classification and cleanup path is still untested

The corrected runner is bounded, but a deadline expiry in the nominal run is
asserted with a `RED_ASSERTION` message (`tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php:234-237`). The outer catch therefore exits `1`, while the specification classifies a child timeout/barrier protocol failure as `SETUP_FAILURE` with exit `2`. Moreover, after that assertion the test does not inspect the timed-out token's SQL/storage namespace or ambient decoys. Killing only the verifier process at `:62-69` also does not itself prove that its child processes were reaped.

The controlled failure matrix at `:282-299` covers `child_crash_after_mutation` and `regression_after_mutation`, but has no controlled child/barrier timeout mode. Thus an implementation can pass while misclassifying a stuck child, leaking its owned fixture, or leaving a child alive after the parent verifier exits. This is an explicit fixture/failure requirement, not an optional hardening case.

Add one controlled timeout-after-mutation probe which requires the verifier's own bounded child handling to return exit `2` with exactly one `SETUP_FAILURE` line, then proves the exact token's SQL/storage cleanup, ambient-decoy preservation, and child termination. The meta-test's emergency deadline should remain a final anti-hang guard; if it fires, classify the test outcome as setup/harness failure rather than behavioral RED and perform the same best-effort leak probes.

## Corrections successfully verified

- An echo-only verifier cannot pass: the test requires a per-run JSON audit file and exact protocol/run token, consumes two test-owned literal PNG fixtures, and verifies their independent literal sizes and SHA-256 values.
- The audit requires two distinct child PIDs outside the meta-test process, two distinct MariaDB connection IDs, both contenders ready behind one released barrier, exact before/after aggregates, winner-neutral accepted/rejected results, the accepted blob/operation identity, zero loser mutation, and exact same-content-at-cap no-mutation evidence.
- SQL-prefix and storage-child collisions are exercised before mutation and fingerprinted afterward.
- Controlled child-crash and regression failures exercise post-mutation cleanup and ambient SQL/storage decoy preservation.
- Concurrent stdout/stderr draining and a 15-second outer deadline remove the previous unbounded blocking-read defect.
- The indefinite verifier-hash placeholder/bypass is gone. Gate 3 pins the spec and test; the future Gate 5 record can pin the implemented verifier without violating RED-before-GREEN ordering.
- Two distinct nominal tokens must produce byte-identical normalized stdout, and every nominal namespace must be absent afterward.

The JSON audit is evidence emitted by the verifier rather than an independent database observer, but it is sufficiently sensitive for this characterization because its exact test-owned inputs and winner-dependent aggregates cannot be satisfied by the prior echo-only implementation. Gate 5 must still inspect the verifier and pin its hash to ensure the audit is derived from the public `ChecklistSync` calls rather than fabricated.

## Reproduced intended RED

MariaDB became healthy and the focused test exited `1` at the absent public seam:

```text
RED_ASSERTION: missing public photo-limit concurrency verifier must become a successful first run; evidence={"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-limit-concurrency.php\n","timed_out":false}
Expected: 0
Actual: 1
```

This is a qualifying RED rather than setup failure: the disposable database was healthy, fixture/decoy construction completed, the verifier file was absent, and the test's `finally` removed its generated artifact root and SQL decoy. `make test-env-down` then removed the disposable container, volume and network.

## Reviewed hashes

```text
e9481cf5239c407c52383a91289c4d17779ef32b6dd3da82d1aff9a1c6dfd820  specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md
049253b8658256b0a590cf53d80552d5f61cc55ad787139965ae35d98deee4a7  docs/operations/inspection-photo-limit-concurrency-evidence.md
821fddb6e8a4041902626d0ed743634a79d3b05ca03387018e9e560c8ba17616  reviews/tests/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md
eb454953e09e8bf78520829ea13de1335bc66f0012023ec6924c30a8aa48a335  tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
```

Gate 4 must not begin from this test revision. A fresh independent Gate 3 review is required after the timeout probe and classification are corrected.
