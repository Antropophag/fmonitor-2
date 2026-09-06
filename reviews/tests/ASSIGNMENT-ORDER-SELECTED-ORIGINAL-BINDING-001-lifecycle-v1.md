# Independent Gate 3 review — ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001 lifecycle v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed tests)
- Reviewed commit: `fdbd634b21a57c6b994103c359796791c32858df`
- Lifecycle-test SHA-256: `07eea68ff7cae28fdbc7aaf8b3ce6a5227603d01b0b46c99d610ee08e356658a`
- Worker SHA-256: `db3ed6b9a420f27162be402e0e718535967f8e8dd1f0ef749101e4b95296ade3`
- Historical constructor RED: `a0e3784d0327589ffd1de29b07d4b43502299d81`
- Final archive: `/Users/antropophag/.local/state/fmonitor2-verification/selected-original-lifecycle-final-4ru_g0az`
- Manifest SHA-256: `acda7671fc08c2da791f816a31460f4cd70a14e6c53fe7416e47e936624ecf70`
- Review date: 2026-09-06

## Findings

No blocking test finding was found in this verification-only lifecycle extension.

The six cases cover the required policy boundaries through public original and selection commands. Replaced unsigned order 81 returns `target_not_current` before PDF read and records only its terminal/audit, while current 82 accepts. Accepted original 81 remains append-only correctable after new pending 82, and its earlier request receipt replays unchanged.

Both race orders use real independent connections. Replacement between original preflight and case lock yields an audited stale-target conflict with no original facts. In the inverse order, the persistence observer runs after original holds the shared case lock; scoped `PROCESSLIST` evidence proves the selection worker blocked on the exact case `FOR UPDATE`, after which original accepts and replacement returns `original_already_accepted`.

The nondisclosure case removes the member source: another-case identity returns `order_not_found` without probing it, while the same-case target fails with `persistence_failure`. The authority case removes original upload permission and the selected source, proving original denial precedes confidential composition access. It preserves the inherited original contract: the first denial stores one terminal request and audit, and each later denied invocation appends only another audit.

The worker's optional expected-revision argument defaults to zero, preserving all existing callers. Synthetic databases, processes, locks, streams, and private resources are owned and cleaned. The initial archive's wrong denial expectation was corrected before this final evidence and is not product RED.

## Evidence and decision

The exact clean archive records all six lifecycle cases, both direct constructor cases, and four native concurrency regressions passing at `fdbd634b21a57c6b994103c359796791c32858df`. Production source remains the already reviewed `9eab3b159945c53767574549bba4b91911883fa0`; the original constructor RED is retained for the same binding slice, so Gate 4 is a valid no-op verification extension.

Gate 3 is **APPROVED** for these lifecycle, policy, nondisclosure, authority, correction, and race cases. They do not by themselves constitute final Gate 5 approval of the whole selected-original binding.
