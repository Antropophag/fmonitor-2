# INSTALLATION-COMPLETION-SCHEMA-001 — configured runtime Gate 2 correction v2

- Date: `2026-09-04`
- Prior integration review: card-local v1, historical after fixture change
- Production changes: none

The v1 test never entered configured `PilotE2ECoordinator`: it omitted
configured pilot CSS, artifact root and clock. V2 supplies real pilot CSS, a
private task-owned artifact root with cleanup, fixed process time and exact
`inspection.item.complete` alongside the existing local permissions.

This closes setup and exposes the next approved boundary in missing/drift modes:
checklist page GET/HEAD now reaches the E2E handler but maps infrastructure
failure through JSON `retryable` instead of the inherited exact plaintext 503
with GET/HEAD content-length parity. Current RED is therefore response behavior,
not identity or configuration setup. After that mapping is corrected, the same
unchanged test continues to the exact card local-identity expectation and the
85% completion append/no-unrelated-mutation matrix.

Fresh independent Gate 3 review of the new test hash is required. Artifact root,
database/users and every prefixed family are removed in `finally`.
