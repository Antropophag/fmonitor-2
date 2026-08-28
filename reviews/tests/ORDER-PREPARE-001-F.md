# Test review: ORDER-PREPARE-001-F

- Reviewer: `Codex agent /root/order_prepare_001_f_test_review` (independent; did not author the specification or test)
- Test author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after approved ORDER-PREPARE-001-E`
- Specification: [`specs/ORDER-PREPARE-001-F.md`](../../specs/ORDER-PREPARE-001-F.md)
- Public seams: `InstallationProcess::prepareOrder(...)` and `InstallationProcess::getOrderProcess(...)`
- Test: [`tests/InstallationProcess/order_prepare_001_f_test.php`](../../tests/InstallationProcess/order_prepare_001_f_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_001_f_test.php`
- Intended failure: rejection audit for `CONTROL_ENGINEER_REQUIRED` is not implemented
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error: Uncaught TestFailure:
ORDER-PREPARE-001-F must audit the missing control engineer without changing process state.
Expected events: [assignment_order_prepare_rejected with CONTROL_ENGINEER_REQUIRED]
Actual events: []
in tests/bootstrap.php:27
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-001-F` and reproduces its exact command inputs, initial process projection, authorized actor, and fixed server time. The expected event type, timestamp, actor, reason code, installer count, and engineer-presence flag come directly from sections 2–4 of the approved specification and its approved E projection contract.
- **Public seams:** fixture state and time are arranged through the in-memory environment, while the behavior is exercised through `InstallationProcess::prepareOrder(...)` and observed only through `InstallationProcess::getOrderProcess(...)`. The test does not query the adapter, storage, private methods, or implementation collaborators for its assertion.
- **Audit sensitivity:** strict comparison requires exactly one `assignment_order_prepare_rejected` event with the complete approved public shape. The test fails if the event is missing or duplicated, has the wrong time or actor, records another reason, reports a different normalized count/presence flag, or exposes any additional payload field.
- **No-partial-state sensitivity:** the complete expected projection fixes `processState`, empty `assignmentOrders`, empty `assignments`, and the original single open `prepare_order` task. Creating a partial order or assignment, changing state, closing/replacing/duplicating the task, or adding another visible fact fails the assertion. The only permitted visible change is the one append-only rejection event.
- **Privacy:** neither the expected event nor any other expected projection field contains installer tab ID `1042`, a person name, `controlEngineerUserId`, the command body, or a storage identifier. Because comparison is exact, adding any such field to the public event or projection fails the test.
- **Expected-value independence:** all expected values are literal consequences of the approved F specification: `CONTROL_ENGINEER_REQUIRED` follows from Example B, `installerCount = 1` follows from the one supplied installer, `controlEngineerProvided = false` follows from `null`, and the timestamp is independently fixed. No production result, persistence representation, or earlier implementation assertion supplies an expected value.
- **Determinism and isolation:** authorization, initial projection, command inputs, and clock are fixed in memory. The test has no wall-clock, database, filesystem-state, network, legacy-application, production-catalog, or execution-order dependency.
- **Scope:** the test intentionally focuses this vertical slice on F's newly specified audit and unchanged projection. The exact command rejection is already covered by the independently reviewed Example B test; implementations of F must run that regression together with this focused test. Workforce lookup and proof that installer `1042` is admissible are explicitly outside this slice.
- **Red cause:** syntax validation passes. Independently running `php tests/InstallationProcess/order_prepare_001_f_test.php` exits `255` after the command returns and the public projection is read: expected one missing-engineer rejection event, actual events are empty. This is the intended absence of F behavior, not malformed setup, an unavailable seam, or an external dependency failure.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to append the specified `CONTROL_ENGINEER_REQUIRED` rejection event without changing the reviewed expectation; the focused test and prior ORDER-PREPARE-001 regression tests must be green before Gate 5.
