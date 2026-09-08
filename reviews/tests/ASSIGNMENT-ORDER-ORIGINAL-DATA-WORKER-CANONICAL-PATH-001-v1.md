# Test review: DATA-WORKER canonical temporary path patch v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Patch base: `94a17bfef8175669a2265ebd03a33e77c143bfee`
- Patch SHA-256: `948aaeee779fda9d7575dd8510f2c26df41b52a1f1bb48fb2e32131c12b51f86`
- Manifest SHA-256: `e25cee6c31cf7b7c1483eed398a2e0da7e69ead0ab01e14aa362a5f8d3f2e130`
- Evidence SHA-256: `0fa79a94f8dd8c4ab5db7b640ed6c4214c38ea062efa082f89e531f48ad5e436`
- Verdict: **APPROVED**

The reviewer authored neither the candidate patch nor the three existing tests. The patch remains unapplied during review.

## Patch assessment

The candidate changes only three approved test fixtures:

```text
assignment_order_original_lease_race_001_test.php
assignment_order_original_worker_post_finalize_negative_001_test.php
assignment_order_original_worker_transport_001_test.php
```

Each change resolves `sys_get_temp_dir()` once through `realpath(...)` before constructing a new task-owned descendant. Failure uses a fixed RuntimeException before fixture creation. All password, safe-log, private-root, config and cleanup paths are then derived from the canonical base. The post-finalize fixture's cleanup-bound assertion uses the same resolved base, so it validates the path actually created rather than its pre-resolution alias.

No SUT command, result, barrier, lease, storage, database, log, cleanup, timing or expected-value assertion changes. Production source is byte-identical to the base. The patch applies cleanly and its after hashes match the manifest:

```text
3eb368ba45160ab8d212d756408594ea7ec4872649daa329e84ef3f85434cad4  lease_race
f61d27c98ff1ec63567f30e7143ef975ba6813200192012a640ba1e58ae8f6ca  worker_post_finalize_negative
63fb32ec5c69a51f0a8f7811f27b6fa7425e2fcdedf871ad1dd953ee248982e9  worker_transport
```

The correction preserves strict production path validation. It does not add a Darwin `/var` exception, weaken canonical identity, or convert failures to skips.

## Evidence and retained functional RED

With the patch applied in the isolated candidate copy:

```text
ASSIGNMENT_ORDER_ORIGINAL_LEASE_RACE_OK
ASSIGNMENT_ORDER_ORIGINAL_WORKER_POST_FINALIZE_NEGATIVE_OK
```

Both prior setup failures are therefore closed by fixture canonicalization alone.

Worker transport proceeds beyond the former password-path failure and reaches its existing identical-correction race assertion. It exits 255 with:

```text
expected: REPLAYED, revision-0070, revisionNumber 7, loser request echo
actual:   CONFLICT/STALE_REVISION with null evidence
```

This is an intended functional RED at unchanged approved expectations. Both workers previously passed the same accepted-fingerprint miss and paused at `AFTER_FINGERPRINT_MISS_BEFORE_CAS`. After A commits, B must restore winner-fingerprint precedence before selecting stale current state. A matching winner produces REPLAYED under B's request ID. Current source instead selects stale during the normal step-11 current check without the required winner reread.

The patch does not manufacture or weaken this result. It merely allows the established real worker race to execute. Its exact expected replay payload, revision, date, digest, size, uploaded time and loser request echo remain unchanged.

Minimal production correction is authorized only after this patch is applied at its exact hashes: when the post-barrier correction current ID differs from expected, perform the already normative accepted-fingerprint reread first; validated matching FOUND returns replay, UNAVAILABLE/malformed returns persistence failure, and valid miss returns stale. Do not finalize or allocate again, do not create a loser request/audit/event, and retain the existing barrier/cleanup order. The correction requires independent Gate 5 after GREEN.

## Verification

```text
git apply --check candidate.patch
PASS

candidate run exits:
lease race = 0
post-finalize negative = 0
worker transport = 255 at exact identical-race mismatch
```

The candidate archive contains exact logs for all three runs. No source or test file was modified in the repository by this review.

This approval covers only the fixture compatibility patch and the qualification of the newly exposed unchanged functional RED. It is not implementation approval, DATA-INTEGRITY Gate 5, combined command approval or launch readiness.
