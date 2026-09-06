# Independent Gate 3 test review — ASSIGNMENT-ORDER-SELECTION-NATIVE-001 persistence v1

- Verdict: **APPROVED**
- Reviewer: `/root/selection_native_review` (`gpt-5.6-sol`, `low`; separately tasked, did not author the specification or reviewed tests)
- Reviewed commit: `3d2b183429e25b9f54c5b7aa1a385b95a356457a`
- Accepted-test SHA-256: `73466a6058bce3c558a458029faccd83b7aa9d88453f72be1140192504862ff7`
- Outcomes-test SHA-256: `9a25b121074c60e40ae35fa4624569816141acd33a810d7983e3aa9fc52b5c9e`
- Example SHA-256: `f2a9ae4ee029b25a83912703c79f020f6c7dbdc1b56b86ceeefdfa3c2f7b5353`
- RED archive: `/Users/antropophag/.local/state/fmonitor2-verification/selection-native-persistence-red-xmmsvxjc`
- RED manifest SHA-256: `c1b8a51eca393bdfc3573de98eb8c5d48f208cad0b5e0f135101c89801c3cd2e`
- Review date: 2026-09-06

## Findings

No blocking test finding was found in this bounded persistence suite.

The six real-UoW accepted scenarios allocate native identity 81/version 1 and use the normative literal composition identity and SHA-256. The valid control commits and passes schema coherence. Each invalid axis changes one payload property. Transaction-visible before/after comparison requires invalid staging to leave no row after reservation, and durable comparison proves the reservation and all facts roll back. The dismissed-member case is especially sensitive to validation order: the current adapter writes the header before the member constraint fails, so rollback alone cannot satisfy the immediate inside-transaction assertion.

The 16 public-command controls cover missing, dismissed, and future workers; inactive engineer; completed-before-PTO precedence; PTO; malformed object date; unavailable catalog; exact registry/event/audit capacity rollback; pending, stale, no-change, and missing-selection outcomes; and corrupt terminal-request failure without mutation. Exact statuses, reasons, retryability, terminal footprints, canonical hash, dates, and capacity values come from the approved core/native contracts rather than production calculations.

All cases use public application or native dependency/UoW seams. SQL establishes synthetic preconditions or observes owned transaction state and does not implement the action. Every scenario owns its database and confirms setup and cleanup.

## Reproduced RED

The accepted suite exited `1`: wrong Moscow date, future employment, dismissed member, and malformed source instant failed at their intended assertions; the valid and malformed-engineer controls passed. The outcomes suite exited `0` with all 16 controls passing. All 22 scenarios reported `SETUP_OK` and `CLEANUP_OK`.

## Gate decision

Gate 3 is **APPROVED** for these stated persistence cases at the exact reviewed commit and hashes. Gate 4 may add the minimal accepted-payload validation needed to reject the four demonstrated invalid cases before stage facts appear, while preserving the valid control and all public outcomes. This does not approve the remaining counter representations, race/unknown outcomes, accepted-root edges, prefix/readiness edges, or the full native binding. Fresh Gate 5 review is required.
