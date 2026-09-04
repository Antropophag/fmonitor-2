# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — totality/replay RED correction v2

- Prior Gate 3: `dd1e50df92cafc9ccf3717479e3da87b867d3028` (`CHANGES_REQUESTED`)
- Result: **INTENDED RED preserved**

Invalid commands now assert zero authorizer calls in addition to zero request,
stream and storage calls. The shared test harness exposes uniquely marked
Throwable injection at authorization, request/fingerprint/lineage/outcome
lookup, composition, clock, lifecycle, fault/observer, stream, stage begin,
write, completed-read, inspector, finalize, root/revision IDs, accepted commit,
attempt audit, abort/close cleanup, lease release, safe log and delivery.

The matrix requires exact typed outcomes, abort/close before finalize, one lease
release after finalize, no uncaught Throwable, and absence of each unique secret
from all Result fields and safe logs. Restart-durable `NO_CHANGES` and
fingerprint-before-stale cases remain unchanged.

PHP lint and `git diff --check` pass. Execution remains honest RED at malformed
UUID being accepted instead of rejected before authorization. Production is
untouched.
