# Test review: PILOT-CASE-IMPORT-001

- Reviewer: `Codex agent /root/migration_runner_test_review` (independent; did not author specification, test, tracer, or implementation)
- Test author: separately tasked Codex agent, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/PILOT-CASE-IMPORT-001.md`](../../specs/PILOT-CASE-IMPORT-001.md), version `0.2`, `APPROVED 2026-08-28`
- Public seam: isolated importer CLI processes whose stdin writer remains open through observed child termination
- Focused command: `php tests/InstallationProcess/pilot_case_import_001_test.php`
- Superseding verdict: `APPROVED`

## Findings

- **Traceability:** the test header cites v0.2 and removes the obsolete nonempty-stdin rejection. Its three stdin cases directly implement section 3/12: open-empty, immediate nonempty bytes, and bytes delivered after process start while stdin remains open.
- **Exact no-EOF behavior:** `pciRunBeforeStdinEof()` does not close descriptor 0 until `proc_get_status` observes the child exited and a complete output line is present. All three cases therefore fail if the importer waits for EOF, including an implementation that prints output and then blocks.
- **Immediate-byte sensitivity:** the fixture writes the complete adversarial payload and checks the exact byte count/flush before observing the child. Payload content resembles an alternate object ID and credential assignment. Exact output must still select/import only argv ID `4514`, and the payload/alternate ID/secret cannot appear in stdout or stderr.
- **Delayed-byte determinism:** a separate admin connection holds a WRITE lock on the delayed case table, preventing that importer from completing. After 150 ms the tracer writes/flushed delayed bytes while fd0 remains open, then invokes the explicit unlock callback. This guarantees the bytes precede possible completion rather than racing a fast child. The callback is also invoked from `finally` if any earlier write/select/assertion fails, so the database lock cannot leak into outer cleanup.
- **Same effects/no reflection:** each stdin variant uses a separately migrated process prefix but the same literal argv/legacy row. All require byte-identical exact JSON/stderr/exit. Independent database reads require the same sole empty-case shape; alternate stdin ID `4601` cannot add/change a row. Secret/raw stdin literals are explicitly checked absent from captured output.
- **Exit/result preservation:** the tracer retains the first observed child exit code when `proc_close` returns `-1` after status polling, drains output, and routes each result through the standard exact line/newline/key-order JSON assertion.
- **Cleanup/reap:** success and failure paths close all descriptors and reap exactly. Timeout/failure terminates a still-running child before outer database deletion; the delayed lock is released independently in both callback and surrounding `finally`.
- **Valid deterministic RED:** reproduced exit `255` in about 1.7 seconds at `immediate stdin bytes are wholly ignored`. Open-empty succeeds first. Current implementation returns exact `CONFIGURATION_INVALID`/64 for preloaded bytes instead of the required unchanged success, isolating the obsolete stdin-validation behavior.
- **Prior suite preserved:** argv/env validation, migration/schema setup, eligibility/mapping, atomicity, idempotency/progress/mixed batch, race, least privilege, rollback, commit reconciliation, redaction and bounded proxy cleanup remain unchanged after the stdin cases.

## Required changes

None. Gate 3 for `PILOT-CASE-IMPORT-001 v0.2` is approved for minimal Gate 4 implementation without changing the reviewed stdin expectations or tracer.
