# Test review: CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/photo_limit_concurrency_test_review_v1`
- Test author: separately tasked Gate 2 agent (not this reviewer)
- Reviewed commit: dirty working tree; artifacts pinned below
- Specification: `specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md`, version `0.1`
- Public seam: `php rapid-pilot/verify-checklist-photo-limit-concurrency.php`
- Red command: `make test-env-up && php tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php`
- Date: `2026-09-01`
- Verdict: `CHANGES_REQUESTED`

## Findings

### Blocking — the test can pass without exercising either characterized behavior

The only evidence of the race, two distinct processes/connections, the exact
winner-neutral aggregate invariants, and the same-content-at-cap call is three
literal stdout lines (`tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php:189-200`).
A verifier which merely validates its environment, prints those lines, and exits
successfully satisfies the current test. The meta-test has no independent
observation that two contenders reached `ChecklistSync::accept(...)`, used distinct
connections, produced one accepted operation/photo/blob, left no loser mutation,
or made the same-content call through the public seam.

This is not sufficiently sensitive to the missing behavior required by Gate 2.
Add test-owned, independently derived evidence which makes an echo-only verifier
fail. In particular, pin the two image byte fixtures and their literal MIME, size,
SHA-256, operation identifiers and metadata in the Gate 2 artifact as required by
fixture contract item 4, and require independently auditable race/same-content
evidence rather than treating the verifier's normalized transcript as proof of
its own assertions.

### Blocking — occupied namespaces and failure cleanup are not exercised

The specification requires occupied owned SQL **or** storage namespaces to be
rejected before mutation, and requires cleanup after pass, regression, setup
failure and child failure. The test only asserts that the two nominal namespaces
start empty (`:176-187`). It never creates an occupied owned SQL namespace or an
occupied owned storage child and invokes the verifier against it. Its invalid-token
and `/tmp` probes (`:202-219`) do not cover collisions, post-mutation regression,
or child crash/timeout cleanup. Add explicit SQL-collision and storage-collision
probes with exact before/after fingerprints, plus controlled failure paths that
prove cleanup and ambient-decoy preservation after mutation/child failure.

### Blocking — process execution has no bounded timeout

`ciplcRun()` performs blocking reads of stdout and then stderr and calls
`proc_close()` without polling or a deadline (`:42-60`). A deadlocked or crashed
barrier harness can hang the verification suite indefinitely; a child that fills
stderr can also block while the parent waits on stdout. The test therefore cannot
enforce the specification's bounded timeout or reliably classify child timeout as
setup failure. Use non-blocking pipes with a fixed deadline, terminate/reap the
process on timeout, and assert the exact setup-failure classification and cleanup.

### Blocking — verifier integrity is explicitly optional

`CIPLC_VERIFIER_SHA256` is a placeholder accepted by the test (`:10`, `:142-147`),
and the hash comparison is skipped while it remains a placeholder (`:221-224`).
Consequently Gate 4 can go green while the verifier remains unpinned, despite the
specification requiring reviewed artifact hashes. Reconcile the gate ordering
without weakening TDD: Gate 3 must approve the test before implementation, while
the implemented verifier is a Gate 4/5 artifact. The approved test must not claim
that Gate 3 pins an implementation which does not yet exist, and the final
verification/review record must pin the actual verifier without leaving a bypass
that passes indefinitely.

### Non-blocking observations

- The expected milestones are winner-neutral and assert exact aggregate counts;
  they do not accidentally select A or B.
- Token grammar is exact 12-character lowercase hexadecimal, generated nominal
  namespaces are repository-private, `/tmp` is rejected, and ambient SQL/filesystem
  decoys are fingerprinted for the currently exercised paths.
- The current intended RED is correctly classified as an assertion failure rather
  than environment setup failure: MariaDB was healthy, fixture/decoy creation
  succeeded, and the absent public verifier exited `1`.
- The test's `finally` removed the generated decoy and artifact root for the
  reproduced RED; no `characterize-photo-limit-*` artifact remained. This does not
  substitute for the missing collision and controlled child-failure probes.

## Reproduced RED evidence

The disposable MariaDB reported healthy. The focused test exited `1` with:

```text
RED_ASSERTION: missing public photo-limit concurrency verifier must become a successful first run; evidence={"first":{"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-limit-concurrency.php\n"},"second":{"status":1,"stdout":"","stderr":"Could not open input file: rapid-pilot/verify-checklist-photo-limit-concurrency.php\n"}}
Expected: 0
Actual: 1
```

`make test-env-down` then removed the disposable container, volume and network.
No generated `characterize-photo-limit-*` artifact directory remained.

## Reviewed hashes

```text
e9481cf5239c407c52383a91289c4d17779ef32b6dd3da82d1aff9a1c6dfd820  specs/CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001.md
7c5d101661214407fef42417ef4dc7551d037eb351c0c9635fc03b80175e0037  tests/Verification/characterize_inspection_photo_limit_concurrency_001_test.php
```

## Required changes

1. Make an echo-only verifier fail by adding independently test-owned evidence for
   both the two-process race and the same-content-at-cap operation, including the
   literal contender fixtures required by the specification.
2. Add occupied SQL and storage namespace probes, and controlled post-mutation and
   child-failure cleanup/decoy probes.
3. Make verifier execution bounded and deadlock-safe, with timeout/child failure
   classified as `SETUP_FAILURE`.
4. Remove the optional verifier-hash bypass and reconcile verifier pinning with the
   Gate 3-before-Gate 4 workflow.

Gate 4 must not start from this test revision. A fresh independent Gate 3 review is
required after correction.
