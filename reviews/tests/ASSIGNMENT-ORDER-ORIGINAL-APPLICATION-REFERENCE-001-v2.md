# Test review: ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001 — v2 unselected database

- Reviewer: `/root/original_gate3`, independent agent; not test author.
- Test author: root implementation agent.
- Specification: unchanged v0.1; existing Gate 1 and initial Gate 3 remain preserved.
- Public seam: real reference factory/reader with a live native connection.
- Verdict: `APPROVED`.

## Bounded amendment

The author identified one explicit connection state not independently exercised by closed-connection or wrong-prefix checks: a live connection without a selected database. Reviewed the added identity-group case. It constructs native mysqli with a null database argument, sets the otherwise valid utf8mb4 charset, requires lookup unavailable, then checks that DATABASE() remains null and the connection stays idle. Finally closes the owned connection. This catches fallback selection or leaked transaction ownership without any native interception. Existing expectations, worker, fixture and production files are unchanged.

Test SHA-256: `e61aae78ca06f11b82b658152608b1cb976cacbdb77de2c949344a64b46aec56` for `tests/AssignmentOrderComposition/original_application_reference_001_test.php`. Other reviewed hashes remain those in the initial record.

Root reran the native PHP test; `/Users/antropophag/.local/state/fmonitor2-verification/original-reference-20260907/red-v4.log` contains 14 healthy setup, 14 cleanup and 14 intended missing-factory assertions, exit 1. Independently verified those counts and the final hash. This is slice RED before implementation, not a claim the newly added downstream case has executed GREEN. No separate reviewer execution is claimed.

No blocking findings. Gate 4 may begin with the final test; all native GREEN/concurrency, regression and independent Gate 5 obligations in the initial review still apply. Only this record was written by the reviewer.
