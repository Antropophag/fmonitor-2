# Independent Gate 5 review — ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001 binding v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification, tests, or implementation)
- Production source commit: `9eab3b159945c53767574549bba4b91911883fa0`
- Exact lifecycle evidence commit: `fdbd634b21a57c6b994103c359796791c32858df`
- Gate 1 spec SHA-256: `81c1c686d20075345564451626063a60741393ed039592b2aa674cb41d80aaf4`
- Direct/source archive: `/Users/antropophag/.local/state/fmonitor2-verification/selected-original-green-w7cwj56u`
- Direct/source manifest SHA-256: `e1ae2654cd89a08161514c2e5ddeb5d8acb3831f18ca404b5e11de72c18f473d`
- Lifecycle archive: `/Users/antropophag/.local/state/fmonitor2-verification/selected-original-lifecycle-final-4ru_g0az`
- Lifecycle manifest SHA-256: `acda7671fc08c2da791f816a31460f4cd70a14e6c53fe7416e47e936624ecf70`
- Review date: 2026-09-06

## Findings

No substantive blocker remains in the selected-original binding slice.

The explicit production and verification constructors retain the single original command owner and real authority, PDF, storage, audit, recovery, and repository dependencies. Existing physical constructors remain physical-only. The extracted registered-composition query preserves historical reader behavior and supplies the same source validation inside the selected preflight snapshot and original-owned transaction.

Direct evidence proves native selection 81 accepts an original without physical preparation or a template, persists the exact immutable composition/hash and PDF lineage, preserves selection/opening/assignment facts, and replays silently. Lifecycle evidence proves replaced unsigned 81 fails before PDF read, current 82 accepts, accepted 81 remains correctable after pending 82, and old receipts remain stable.

The shared installation-case lock serializes both race orders. Replacement winning before the original lock produces audited `target_not_current` with no original facts. Original winning the lock accepts, after which replacement observes `original_already_accepted`. The internal `COMPOSITION_NOT_CURRENT` signal is returned only after confirmed rollback; acknowledgement uncertainty remains on the existing recovery path.

Other-case identities do not disclose or probe malformed same-case source data. Same-case malformed source fails closed. Original upload authority precedes selected composition lookup, and repeated denials preserve the approved first-terminal plus per-invocation-audit behavior. Selection permission alone never grants original upload/correction.

## Evidence and decision

The source archive records 26 passing commands, including direct constructors, registered reader, relevant original composition/write/lifecycle/recovery/audit regressions, native selection tracer, architecture, OpenSpec, diff, and changed-file lints. The clean lifecycle archive adds six lifecycle cases, repeats the two direct cases, and runs four native concurrency regressions. Production source did not change, so Gate 4 for the lifecycle extension is correctly no-op.

Gate 5 is **APPROVED** for `ASSIGNMENT-ORDER-SELECTED-ORIGINAL-BINDING-001` at the exact source and evidence commits above.

Canonical schema activation, portal/HTTP routes, template generation, composition application, opening, full `make verify`/`VERIFY_OK`, CI, deployment, restart, and golden-path validation remain outside this slice and are not approved or claimed here. No legacy writer migration or historical import requirement is introduced.
