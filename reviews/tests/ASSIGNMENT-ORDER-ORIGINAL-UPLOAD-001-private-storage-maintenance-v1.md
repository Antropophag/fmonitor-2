# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — private storage/maintenance Gate 3 review v1

- Reviewer: separately tasked agent `/root/original_upload_factory_worker_red/original_upload_private_maintenance_gate3`
- Review timestamp: `2026-09-04T07:08:00+03:00`
- Reviewed RED commit: `950cd580bfe345e09c0317e86a286cf362f511c5`
- Trigger: Gate 5 `reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-v1.md`, private orphan maintenance and verified reuse finding
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md` v4, owner-approved SHA-256 `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Normative OpenSpec delta SHA-256: `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Verdict: **CHANGES_REQUESTED**

## Independence and reviewed surface

The reviewer did not author or edit the specification, OpenSpec artifacts,
test, RED evidence or production implementation. This append-only review record
is the reviewer's only change. The reviewed artifacts are:

```text
09ab5aa66a1dccc5637184686470eb3a831fc8e70e39238b611ba306ebcd8e96  tests/InstallationProcess/assignment_order_original_upload_001_private_storage_maintenance_test.php
55ad7c464d87745a05f75627317ff7fa0ee49170edcf0ac45a16d2cfba24bc14  docs/operations/assignment-order-original-upload-gate5-private-maintenance-red-2026-09-04.md
```

The test uses the production filesystem storage factory and the canonical
maintenance verification factory. It does not inspect production source or
private implementation fields. Its root is a randomized child of the
repository-owned `.verification-artifacts/aoom-` namespace, recursive cleanup
refuses any other lexical prefix, treats symlinks as unlink-only entries, and
runs in `finally`. Independent reproduction left no `aoom-*` child behind.

## What the RED establishes

The setup creates two real abandoned stages and one real finalized content
object, assigns one stage and the finalized object timestamps older than the
cutoff, and leaves one stage younger. The first public inventory page is bounded
to one candidate, requires a non-null cursor, and the continuation requires the
other old kind and a terminal null cursor. Canonical inventory must report the
approved schema and the actual two-stage/one-finalized cardinality. This is
sensitive to the Gate 5 implementation that returns an unconditional empty
page. The final scenario also reaches the real `finalize` public seam and
expects corrupt existing content not to yield a lease.

## Blocking findings

1. **The claimed lock/reference/delete coverage is not asserted.** The only
   finalized candidate is configured unreferenced and deleted. The test never
   supplies a referenced finalized candidate, never holds its digest lease/lock
   during maintenance, and never asserts a retained/locked result. It also does
   not assert the environment call trace. An implementation that skips the
   repository reference recheck, acquires/deletes in the wrong order, or fails
   to unlock can satisfy every current assertion. This does not close the Gate 5
   requirement or v4 sections 10 and 16 (`lock -> reference -> delete`,
   referenced/locked retention, shared exclusion domain).

2. **The corrupt-reuse oracle does not prove exact-byte verification.** The
   expected content is `collision` (9 bytes), while the pre-existing corrupt
   bytes are `corrupt` (7 bytes). A storage adapter that checks only file size
   and never hashes or compares exact bytes would reject this fixture and pass.
   Add a same-size, different-byte collision at the canonical digest path and
   require `FAILED` with no lease; retain a valid exact-byte reuse case so that
   unconditional rejection cannot pass.

3. **Pagination/cursor identity and young retention are under-observed.** The
   assertions check only the two distinct kinds after concatenation, not exact
   opaque identities/timestamps in binary `(time, identity)` order. They do not
   prove that the young stage remains after reconciliation. Consequently a
   cursor implementation that happens to split these three fixture entries but
   skips or deletes the wrong identity can evade the oracle. Assert exact page
   identities/order, verify continuation excludes the first page, and inspect
   the post-run public inventory/filesystem evidence for preservation of the
   young entry.

These are test-observability gaps, not requests to inspect private production
state or grep source. Each can be observed through the approved public storage
and maintenance seams plus verifier-owned filesystem facts and controlled
ports.

## Independent RED reproduction

At the timestamp above on exact commit
`950cd580bfe345e09c0317e86a286cf362f511c5`:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_private_storage_maintenance_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_private_storage_maintenance_test.php

$ php tests/InstallationProcess/assignment_order_original_upload_001_private_storage_maintenance_test.php
PHP Fatal error: Uncaught TestFailure: Batch limit bounds first page.
Expected: 1
Actual: 0
exit 255

$ find .verification-artifacts -maxdepth 1 -type d -name 'aoom-*' -print
(no output)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output)
```

The failure is the intended production inventory gap and not setup failure, but
the remaining oracles are not yet sufficient to authorize Gate 4 for the full
private storage/maintenance correction.

## Decision

Fresh Gate 3 at exact RED commit `950cd580bfe345e09c0317e86a286cf362f511c5`
is **CHANGES_REQUESTED**. Add public-seam assertions for referenced and actively
locked retention with exact call/order/delete/replay effects; make corrupt
reuse same-sized but byte-different and include successful exact reuse; and pin
exact pagination identities plus post-run young preservation. Then record a
new RED/evidence commit and request a fresh independent Gate 3 review.
