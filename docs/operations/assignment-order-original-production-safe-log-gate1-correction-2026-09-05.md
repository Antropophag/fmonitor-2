# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v55 — production safe-log Gate 1 correction

- Date: `2026-09-05`
- Author: `/root`
- Owner-approved planning commit: `5c9a530286e8d0bad4d245b14052da89ae246ea6`
- Independent Gate 1 finding commit: `003735aed4feb50cdd1c3b0f8850d3426f1d86eb`
- Prior verdict: `CHANGES_REQUESTED`

## Correction

The active executable specification now requires the third production config
field `safeLogFile`, distinguishes it from worker/evidence-reader config, binds
the real append-only file observer, and removes the contradictory inert
production safe-log statement.

The contract fixes validation before any database operation or private-storage
validation/access. It accepts only an already existing absolute canonical
non-symlink regular file owned by the effective current user with exact mode
`0600`; create/replace/truncate/chmod/chown/repair are forbidden. Invalid
configuration throws the fixed
`AssignmentOrderOriginalProductionConfigurationUnavailable` error with basename
message, code zero, no previous exception and no path/secret/detail leakage.

No executable test, production file or OpenSpec artifact was changed by this
correction.

## Exact reviewed-candidate identities

```text
4cae80e141ad4caf758e792d0ae5a8383c32f6b9d6a779d6eace6122ec71b255  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
09fbfb8d9a5405989ad40150d011b557d8fea1ba030530fbf68ce06c3a710abb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
dd4c5f05ddfadfcff57a8f4303165a806a43a4b339227407ceac6a37b8d7d2b2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
482fb11555e9f10968a2485c2f22e863543c67fa78c41cd8148ce3f1b0e4ce09  openspec/changes/replace-pilot-registration-with-original-upload/design.md
42a03479da33ecc99f2b6e6f76600480a723e7a8c8e25296d33285584d8d3c88  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b22fcf35c284a1d27dec65a5660788c8fa963b4ae658e81c8dca569f64ab4c4e  docs/operations/assignment-order-original-production-safe-log-gate1-review-2026-09-05.md
```

This is a Gate 1 correction candidate, not approval. Safe-log tests and
production remain blocked until a different fresh independent reviewer records
`APPROVED` for these exact identities.
