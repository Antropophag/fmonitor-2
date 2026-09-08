# Assignment-order original production safe-log owner resolution — 2026-09-05

This append-only record resolves the decision blocker preserved in
`docs/operations/assignment-order-original-production-safe-log-owner-blocker-2026-09-05.md`.
That earlier record remains unchanged historical evidence.

## Owner decision

The owner explicitly approved adding mandatory `safeLogFile` to
`AssignmentOrderOriginalProductionConfig`. The production factory accepts only
an already existing canonical non-symlink regular file owned by the current
effective user with exact mode `0600`; it does not create or repair the file.
Validation happens before database or private-storage access. Real
cleanup/release diagnostics append through that file. Invalid configuration
fails construction with one fixed redacted error and does not disclose secrets
or configured paths.

This decision changes only the production command-factory configuration
contract. It does not replace or broaden the separately approved worker and
evidence-reader `safeLogFile` contracts.

## Planning amendment evidence

Repository base before the amendment:

`6d893a36df56ccc6dfaa45571eda4413a2222e48`

Exact SHA-256 hashes after the coherent OpenSpec amendment:

```text
09fbfb8d9a5405989ad40150d011b557d8fea1ba030530fbf68ce06c3a710abb  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
dd4c5f05ddfadfcff57a8f4303165a806a43a4b339227407ceac6a37b8d7d2b2  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
482fb11555e9f10968a2485c2f22e863543c67fa78c41cd8148ce3f1b0e4ce09  openspec/changes/replace-pilot-registration-with-original-upload/design.md
42a03479da33ecc99f2b6e6f76600480a723e7a8c8e25296d33285584d8d3c88  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

`openspec validate replace-pilot-registration-with-original-upload --strict`
reported that the change is valid. No executable test or production file was
changed in this planning amendment. Gate 2/4 remain prohibited until a fresh
independent Gate 1 review approves the amended contract.
