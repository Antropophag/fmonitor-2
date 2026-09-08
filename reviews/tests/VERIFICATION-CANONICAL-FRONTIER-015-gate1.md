# Independent Gate 1 — VERIFICATION-CANONICAL-FRONTIER-015

- Verdict: **APPROVED**
- Reviewer: `/root/original_gate1_v3`, separately tasked agent; not the author of the reviewed specification, planning artifacts or proposed test changes.
- Date: 2026-09-07
- Reviewed HEAD: `a8e6e9202df5c4e92f91877e8116d2e3f85f190f`.
- Specification: version 0.1, SHA-256 `3fe10d537d3d10eada7bcb62be111ca9a1d3dba28beef2d6f69fbdac7b9ec588`.

## Findings and decision

No blocking findings. The public migration CLI and its existing consumer workflows are explicit seams. Current canonical success has fixed, independently approved expectations: terminal 15, exact ordered clean applications 1–15, exact empty repeat, and the specified partial-v5 recovery sequence 5–15. Expected values must not be inferred from production registration. Restricted historical compositions and earlier failures retain their own exact versions and outcomes.

Oracle authority is traceable to the independent original-attempt-audit v1 and selection-canonical-registration v1 code approvals. The latter approved only registration of existing engines at 14/15 and explicitly proved full catalogue membership, repeat and conflict behavior. The existing `production_migration_runner_001_test.php` demonstrates a composed proof with literal v1–v13 metadata, an exact seven-name registry/selection extension, global no-extras membership, and explicit delegation to registry completion/selection readiness for those successor families. Reusing that established delegation is allowed; replacing predecessor metadata, constraints or history assertions with a readiness boolean is prohibited.

Scope is closed to the 13 enumerated non-protected consumers and test-only catalogue support. The specification preserves prefix limits, rows/bytes/counters, interruption, permissions, error redaction, early-conflict no-later-write behavior and exact catalogue checks. Inspection of current consumers confirms the distinction matters: object-detail engine assertions legitimately remain at 12, while calls to the complete CLI expect the new terminal; workforce early conflicts remain at 1/5 and its partial recovery must retain every required predecessor. Broad replacement of numeric literals would violate this contract.

The RED plan is valid for this test-only reconciliation. Read-only inspection of exact commit `fbbb41e54aedf240211ba56041269e8d2264cf63` confirms its real CLI registration ends at canonical 13. Amended tests overlaid without production changes can therefore demonstrate absence of approved successors 14/15 at the same native public seam. The existing mismatch of old terminal-12 expectations against terminal 15 is diagnostic evidence only. Gate 3 must verify that the new failures reach the intended CLI-result assertions, rather than fail on missing support, fixture incompatibility or environment setup, and that cleanup completes.

Production is already approved, so Gate 4 needs no production patch. Protected E2E and its fixtures/assertions remain unchanged; pilot-demo-bootstrap is rerun through the corrected import consumer without editing its own assertions. Any newly exposed unrelated failure needs separate scope. Current full-run evidence is not mixed with edits, and local GREEN/Gate 5 do not replace the required full exact-source VERIFY or resolve the separate E2E label failure.

## Evidence and limits

Read the normative specification, all four OpenSpec artifacts, both referenced code approvals, selection registration Gate 3, the existing composed catalogue proof, the pre-registration CLI and relevant current-consumer assertions. Reused previously read AGENTS.md and mandatory delivery/product documents. No tests, CLI commands against a database, production files or runner changes were executed or authored for this review; only this record was added.

| OpenSpec artifact | SHA-256 |
| --- | --- |
| proposal.md | `ac97cd32468fef82af461dcaa56ea5bafa26345ad0339c17c565864b718891b6` |
| design.md | `5fe3024e28f448379d96ca0622afccd2fe0a70b9f0ce55bddacb746525a08e47` |
| tasks.md | `b50f54cfddc9fd898d2166ef30dfc6c790ae067c34b94d42fe6cb01e0b6ba1b8` |
| specs/delivery/canonical-frontier-verification/spec.md | `56e93eced29383e6977273553553774d5346a8bcb97c354e94e48291a105e84c` |

Gate 1 only is **APPROVED**. Intended RED, independent test review, affected-consumer GREEN, independent code review and full verification remain outstanding.
