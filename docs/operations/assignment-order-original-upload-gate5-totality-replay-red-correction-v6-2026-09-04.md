# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — totality/replay RED correction v6

- Prior Gate 3: `97e2e39d96106c1c9d8946eecd3f94dbe6ec863b` (`CHANGES_REQUESTED`)
- Result: **INTENDED RED preserved**

The storage harness now invokes the actual injected fault and observer ports;
it no longer calls their owner fault switches directly. The multi-call matrix
records and checks the exact typed `FaultPoint`/`StorageEvent` sequence at every
success-path invocation, including the post-commit close boundary. Exact
per-outcome safe-log cardinality/event/phase assertions and both audit paths are
retained.

Harness and focused test lint, adjacent replay/failure suites remain GREEN, and
diff hygiene passes. Malformed UUID acceptance remains the honest first RED.
Production is untouched.
