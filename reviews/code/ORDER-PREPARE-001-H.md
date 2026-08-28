# Code review: ORDER-PREPARE-001-H

- Reviewer: `Codex agent /root/order_prepare_001_h_code_review` (independent; did not author the specification, test, or implementation)
- Implementation author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after minimal ORDER-PREPARE-001-H implementation`
- Specification: [`specs/ORDER-PREPARE-001-H.md`](../../specs/ORDER-PREPARE-001-H.md), version 0.2
- Approved test review: [`reviews/tests/ORDER-PREPARE-001-H.md`](../tests/ORDER-PREPARE-001-H.md)
- Production change: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Verification commands:
  - `for test_file in tests/InstallationProcess/order_prepare_001{,_b,_c,_d,_e,_f,_g,_h}_test.php; do php "$test_file" || exit 1; done`
  - `php -l app/InstallationProcess/InstallationProcess.php`
  - `php -l tests/Support/InMemoryInstallationProcessEnvironment.php`
  - `php -l tests/InstallationProcess/order_prepare_001_h_test.php`
  - `git diff --check`
- Verdict: `APPROVED`

## Verification evidence

```text
PASS ORDER-PREPARE-001 example A
PASS ORDER-PREPARE-001 example B
PASS ORDER-PREPARE-001 example C
PASS ORDER-PREPARE-001 example D
PASS ORDER-PREPARE-001-E audit projection
PASS ORDER-PREPARE-001-F audit projection
PASS ORDER-PREPARE-001-G combined audit projection
PASS ORDER-PREPARE-001-H security audit
No syntax errors detected in app/InstallationProcess/InstallationProcess.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
No syntax errors detected in tests/InstallationProcess/order_prepare_001_h_test.php
```

All commands exited `0`.

## Findings

- **Specification conformance:** the denied `prepareOrder(...)` call returns exactly the single approved `FORBIDDEN` violation and appends exactly one `assignment_order_prepare_rejected` security event. The event has the fixed timestamp, denied actor, sole reason code, and approved aggregate composition facts. Actor `7`, explicitly granted `security_audit.read`, receives the exact event list through `getSecurityAudit(...)`.
- **Authorization precedence and information hiding:** `actorCanPrepareOrder(...)` is the first environment interaction and the method returns immediately after recording the denial. It does not execute participant validation, process lookup, people-catalog access, or any success-path behavior. The command response contains no participant violations, order state, composition count, or other domain information. Aggregate values used only inside the closed security event are derived from the submitted command after authorization has already failed, as required by H's exact event contract.
- **Closed read seam and denial without disclosure:** `getSecurityAudit(...)` checks `security_audit.read` before calling `getSecurityEvents(...)`. An unauthorized reader receives only the exact rejection envelope, with no `events` key, count, empty-list signal, or indication that an order or event exists. The test's strict comparison catches additional top-level disclosure as well as a permissive read implementation.
- **Append-only security history:** the environment stores security events separately from ordinary process events and appends with `$this->securityEvents[$orderId][]`; neither the command nor the read seam exposes an update or delete operation. This implements one append-only security fact per denied command for the approved in-memory boundary. Physical database separation, retention, and production transaction mechanics remain outside H.
- **Privacy:** the stored and returned event contains only the approved event metadata, reason code, installer count, and engineer-presence boolean. It excludes installer and engineer identifiers, names, the full command, process state, storage identifiers, and diagnostics. Strict event comparison would reject any extra projected fields.
- **No process mutation and ordinary-history separation:** the forbidden branch calls only the dedicated security append before returning; it does not call `appendEvent(...)`, `getProcess(...)`, or any domain mutation capability. The separate `securityEvents` collection prevents this implementation from placing the event in the ordinary `getOrderProcess(...)` history. As noted in the approved Gate 3 review, the focused H test does not itself observe a seeded process projection, so the verdict relies on direct code inspection for this invariant and does not claim broader process-read authorization behavior.
- **Role separation and integration seam:** granting `security_audit.read` affects only `getSecurityAudit(...)`; it does not make `actorCanPrepareOrder(...)` true. Authorization and security auditing live in the shared `InstallationProcess` seam rather than a UI-only route, so future callers cannot bypass the reviewed decision by avoiding a tab or client-side control.
- **Maintainability and scope control:** H adds two narrowly named environment capabilities for a separately protected journal and one public read method. It does not introduce a system-administrator UI, global search, export, archival policy, audit-of-audit, successful order preparation, document generation, legacy writes, or 1C DO integration. The broad `object` environment dependency remains the previously accepted temporary technical limitation; H does not materially deepen it beyond the approved capabilities.
- **Test sensitivity:** the independently approved test strictly checks the command denial, unauthorized audit read, and authorized full event list. It fails for validation leakage, absent/duplicate/wrong security events, permissive reads, existence signals in denial responses, privacy-sensitive extra fields, incorrect authorization, or use of the ordinary audit projection for the authorized result.
- **Regression verification:** all eight tests A–H pass together. PHP syntax checks pass for production, the in-memory environment, and the H test; `git diff --check` exits successfully. The new forbidden-event append and protected reader preserve A–G's exact validation responses, normalization, stable reason ordering, ordinary rejection auditing, and unchanged process projections.

## Required changes

None for the independently reviewed ORDER-PREPARE-001-H version 0.2 slice.

Gate 5 is approved for ORDER-PREPARE-001-H version 0.2. Production persistence and transaction guarantees, audit retention/search/export, audit-of-audit, broader process-read authorization, and successful order preparation remain outside this verdict and require separate executable slices.
