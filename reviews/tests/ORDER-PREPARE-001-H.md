# Test review: ORDER-PREPARE-001-H

- Reviewer: `Codex agent /root/order_prepare_001_h_test_review` (independent; did not author the specification or test)
- Test author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after approved ORDER-PREPARE-001-G`
- Specification: [`specs/ORDER-PREPARE-001-H.md`](../../specs/ORDER-PREPARE-001-H.md)
- Public seams: `InstallationProcess::prepareOrder(...)` and `InstallationProcess::getSecurityAudit(...)`
- Test: [`tests/InstallationProcess/order_prepare_001_h_test.php`](../../tests/InstallationProcess/order_prepare_001_h_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_001_h_test.php`
- Intended failure: protected security-audit seam and `FORBIDDEN` audit persistence are not implemented
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error: Uncaught Error:
Call to undefined method FMonitor2\InstallationProcess\InstallationProcess::getSecurityAudit()
in tests/InstallationProcess/order_prepare_001_h_test.php:50
```

Exit code: `255`.

The RED is genuine and caused by the missing approved public observation seam. The earlier `prepareOrder(...)` assertion succeeds before execution reaches that call, so the failure is not a fixture, bootstrap, syntax, or unrelated regression failure.

## Findings

- **Traceability and command information hiding:** the command fixture is exactly the approved H/Example D input (`orderId = 4512`, empty participant composition, unauthorized actor `91`). Its strict expected result proves authorization is evaluated before participant validation at the observable command boundary: only `FORBIDDEN` is returned, with the approved message and `null` field, and neither participant violation is disclosed.
- **Authorized security-audit observation:** the test grants only actor `7` the `security_audit.read` capability and reads through the public `getSecurityAudit(...)` seam. Its strict expected list independently follows the approved specification and detects a missing or duplicate event, wrong type/time/actor/reason, incorrect aggregate composition facts, reordered or extra events, and extra privacy-sensitive fields in the returned event.
- **Closed projection and negative authorization:** before the authorized read, actor `91` calls the same seam without `security_audit.read`. Strict comparison requires exactly the version 0.2 rejection envelope with one `FORBIDDEN` violation and no `events`, count, or other existence signal. An implementation that exposes the audit to every authenticated caller, leaks its size, or returns a differently shaped denial cannot pass.
- **Privacy:** the expected security event contains only the approved aggregate installer count and engineer-presence flag. Exact comparison rejects installer identifiers, an engineer identifier, names, the complete command, storage identifiers, or other diagnostic data in the authorized projection.
- **Determinism and isolation:** the environment is newly constructed, its security history is empty by default, its only read grant is explicit, and the clock is fixed. Expected values are literal values from the approved specification rather than values read from production code or adapter state. The single-process PHP test does not depend on external databases, legacy FMonitor, network services, or execution order with A–G.
- **Scope:** the test appropriately avoids persistence schema, UI, global search/export, retention, and audit-of-audit behavior. It tests through process-module public seams rather than reading the in-memory adapter directly.
- **Ordinary-history non-disclosure and partial-state assertions:** H also states that the security event must not appear in `getOrderProcess(...)` and that domain state must not change. The current test does not seed a readable process or observe that projection. This is a meaningful invariant, but it is not listed as one of H's two declared public seams and the approved H result gives no complete `getOrderProcess(...)` fixture. It should not be improvised in this test. Its executable proof requires either reusing an explicitly approved complete projection contract with stated H preconditions or a separate approved slice.

## Required changes

None for the independently reviewed ORDER-PREPARE-001-H version 0.2 test.

Gate 3 is approved. Minimal production implementation may proceed. Ordinary process-history non-disclosure and complete no-partial-state observation remain unproved by this focused test and require an executable projection contract in this or a later approved slice.
