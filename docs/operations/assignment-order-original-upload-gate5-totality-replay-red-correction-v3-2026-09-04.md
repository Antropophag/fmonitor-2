# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — totality/replay RED correction v3

- Prior Gate 3: `658fb968cfbabe9bb770995f9ee06145000a4b9b` (`CHANGES_REQUESTED`)
- Result: **INTENDED RED preserved**

Secret-leak assertions now serialize every public Result field. The matrix adds
stream-close Throwable, post-CAS conflict fingerprint/current-lineage reread
faults while the lease is held, release-failure preservation with the exact
safe-log allowlist, and second-call outcome lookup injection. Replay now asserts
repository trace ordering (fingerprint before any lineage lookup), while the
restart `NO_CHANGES` case proves a newly constructed repository adapter and
application over the same committed evidence.

Both PHP files lint, existing replay/failure suites remain GREEN, and diff
hygiene passes. The focused run remains honest RED at malformed UUID acceptance.
Production is untouched.
