# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — totality/replay RED correction v4

- Prior Gate 3: `6ac2b290709898ab07c477d0194b859250dc5c6c` (`CHANGES_REQUESTED`)
- Result: **INTENDED RED preserved**

Every emitted safe log is now checked against exact envelope and safe-field
allowlists, opaque correlation grammar and finite phase values. Replay requires
zero lineage lookup after its accepted fingerprint hit. Post-CAS fault cases
reset traces and require exact held-lease repository reads plus exact storage
finalize/close events. Restart durability now crosses an explicit
serialize/unserialize reconstruction boundary before constructing new adapters
and application.

PHP lint and diff hygiene pass. The focused suite remains honest RED at
malformed UUID acceptance before later assertions. Production is untouched.
