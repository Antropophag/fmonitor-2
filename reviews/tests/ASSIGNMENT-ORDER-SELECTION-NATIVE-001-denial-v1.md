# Independent Gate 3 test review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 denial audit v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed test)
- Reviewed commit: `bd56ec30af36d8df13e67c5662d62ab2ea7679ab`
- Reviewed test SHA-256: `a5368ff6d03d6a3cc58f925b616a0de35a2b09a828d4364cb7662d90c70156ee`
- Controlling native spec SHA-256: `0e53e27441d2f1317b1c18088f12165a1f12be79c4d10c578d85077581770f09`
- Inherited core authority: `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001` sections 5, 8, and 10
- RED archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-denial-red-qs1nrkym`
- RED manifest SHA-256: `a4fa2efc2e623e6af462a2357b6108e01c7fe3c51676a481b13474b408b19c92`
- RED log SHA-256: `ea2d8afe49790a588f3726215010ebaef9038c4873da1787d3b706b096f6bd70`
- Review date: 2026-09-06

## Findings

No blocking test finding was found in this focused denied-audit boundary.

All four cases use the public `selectAssignmentOrderComposition` command. The principal case first stores a valid selected result, corrupts only its confidential request fingerprint in the owned fixture, revokes the actor's grant, and requires two independent `authorization_denied` results and two exact safe audits. Full before/after comparison requires every non-audit table, including the corrupted request, to remain byte-for-byte unchanged. The test therefore rejects an implementation that reads, validates, repairs, or mutates the confidential request ledger before completing the denial audit.

The revocation control proves that each denied invocation creates its own audit and that restoring authority returns the exact stored success as a silent replay without another clock read. The changed-intent case independently proves the safe request-conflict audit and no terminal/domain mutation. The audit-schema-drift case requires `failed/persistence_failure` with no facts, preventing an adapter from acknowledging a denial whose independent audit storage is incompatible.

Expected statuses, reasons, retryability, safe audit fields, clock behavior, and mutation boundaries are direct literals from the inherited core contract. Synthetic setup SQL creates the preconditions only and does not implement the command. Every case owns an isolated database and confirms setup and cleanup.

## Reproduced RED

The exact retained run executed four cases and exited `1`. Three controls passed. The malformed-confidential-request case reached the intended assertion but returned `failed/persistence_failure` instead of `rejected/authorization_denied`, demonstrating that the current independent audit writer incorrectly depends on global readiness that inspects the confidential ledger. All four cases reported `SETUP_OK` and `CLEANUP_OK`.

## Gate decision

Gate 3 is **APPROVED** for the exact reviewed test commit and hash. Gate 4 may implement the minimal audit-only metadata readiness required to append the safe denial audit without reading the request, selection, member, event, registry, original, or other confidential state. It must retain fail-closed behavior for an incompatible audit table and preserve the existing independent transaction, generated-ID, rollback, capacity, and unknown-outcome contract. Fresh Gate 5 review is required.
