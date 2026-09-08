# Independent Gate 3 review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 races/interruption v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed tests)
- Reviewed commit: `50e44bd1037beb76a8c7265e1db32ef19a8eb53f`
- Concurrency-test SHA-256: `5983233564349516b51d22f43bd1eca61758026f08781c8217f112f953b8e369`
- Interruption-test SHA-256: `079917911cddc182b85e6b34ebb1d1513e9f92275fbb2671a49dd34604465509`
- Worker SHA-256: `8bf25a8832927c94879fe28d13ad355072415179fbf56551a8c242be933ae559`
- Evidence archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-races-evidence-kpmr7mpc`
- Manifest SHA-256: `82a752e5c2b60ae87954ad679dd52e8719c0de8a7f4818802cd2519af78422e4`
- Historical native construction RED: commit `8877686efdad5e01a2e0cc13cfb6357c526ef97b`
- Review date: 2026-09-06

## Findings

No blocking test finding was found in this verification-only extension of the already-red native binding slice.

The concurrency suite uses separate public-command worker processes and real MariaDB locks. Before releasing the fixture-owned lock, it observes both exact worker thread IDs in the scoped database, executing the expected case `SELECT ... FOR UPDATE` or request `INSERT`, with sustained server wait time. Same-case writers produce one selected result and one stale conflict; same-request/different-case writers produce selected plus request-ID conflict without success disclosure; different cases with distinct requests select global IDs 81 and 82 with per-case version 1. Row counts, unrelated state, audits, and schema coherence exclude ghost or partial facts.

The lost-response case proves a public selected commit precedes worker delivery, terminates that delivery, then uses a fresh process to obtain a silent replay with no mutation. The two interruption cases stage real accepted rows and kill only their dedicated runtime connection before commit under mysqli exception and boolean modes. Both require `outcomeUnknown`, server rollback with no partial facts, a fresh read-only absent lookup, no blind retry, and an explicit later invocation allocating ID 82 after the native AUTO_INCREMENT gap.

The tests use no replacement proxy, interception, denied-permission probe, or private implementation seam. Setup, workers, locks, killed connections, and databases are synthetic and owned, with bounded cleanup. The earlier `INNODB_LOCK_WAITS` setup observation was discarded; the retained evidence uses scoped `PROCESSLIST` proof and is not mislabeled as product RED.

## Evidence and sensitivity

The exact clean capture runs four concurrency and two interruption cases, all passing. These tests would fail if case locking were absent, request races disclosed the winner, global allocation duplicated IDs, losing writers left facts, a killed commit claimed success or confirmed rollback, recovery mutated/retried, or response-loss replay appended state.

No new production change is required: these tests verify behavior supplied by the implementation of the same slice whose missing native binding produced the retained RED at `8877686efdad5e01a2e0cc13cfb6357c526ef97b`. A no-op Gate 4 verification extension is therefore valid; manufacturing a new failing behavior is neither required nor permitted.

## Gate decision

Gate 3 is **APPROVED** for these six race/interruption verification cases at the exact hashes above. They may join the native regression evidence without a production delta. This does not approve all race, unknown-outcome, capacity, prefix, accepted-root, or full-binding obligations; uncovered cases remain subject to their own gates.
