# Code review: ORDER-PREPARE-005

- Reviewer: `Codex agent /root/order_prepare_005_code_review` (independent; did not author the specification, test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-005.md`](../../specs/ORDER-PREPARE-005.md), version `0.1`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/ORDER-PREPARE-005.md`](../tests/ORDER-PREPARE-005.md), verdict `APPROVED`
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_005_test.php`](../../tests/InstallationProcess/order_prepare_005_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_005_test.php
for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
php -l app/InstallationProcess/InstallationProcess.php
php -l tests/InstallationProcess/order_prepare_005_test.php
php -l tests/Support/InMemoryInstallationProcessEnvironment.php
for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
git diff --check
```

Observed result: the focused test printed `PASS ORDER-PREPARE-005 repeated first preparation`; the complete `tests/InstallationProcess` suite printed eighteen PASS lines, including all preceding ORDER-PREPARE slices and the terminology contract. All PHP syntax checks exited `0`. `git diff --check` exited `0` with no output.

## Standards

No documented-standard violation or material maintainability issue was found. The implementation adds one compact decision to the existing command at the specified validation boundary and introduces no new abstraction or dependency. Selecting the final element of the ordered `assignmentOrders` projection is adequate for this slice's explicit current version `1` precondition; selection and concurrency for multi-version histories remain work for the separately scoped changing-order and concurrency behaviors.

## Specification and code findings

- **Exact ordering:** authorization and normalized mandatory-composition validation remain first. Only after both pass does the implementation read the existing process, and a `prepared` or `registered` version returns before installation-object data, workforce, engineer-directory, and renderer access. Earlier authorization and composition tests remain green.
- **Unified non-disclosing rejection:** both approved statuses use the exact singleton `ASSIGNMENT_ORDER_ALREADY_PREPARED` violation, Russian message, and `field = null`. No status, registration number, participant identity, or saved snapshot is exposed in the response.
- **Append-only audit and privacy:** rejection calls only `appendEvent` and records the approved time, actor, singleton reason code, normalized unique installer count, engineer-presence boolean, and current version. It does not record current status or registration number, incoming tab IDs or engineer ID, names, or command snapshots.
- **Immutability:** the approved test compares the complete process projection before and after each status and permits only the appended event. This covers the immutable version, snapshots, assignments, artifacts, registration number, prior events, process state, tasks, work-opening flag, and checklist gate.
- **Integration and security boundaries:** authorization is still the first operation. Test guards independently fail on any object-snapshot, installer-catalog, engineer-directory, or renderer call. There is no production legacy-projection adapter in the approved in-memory seam; the exact unchanged projection excludes modeled persistence changes.
- **Status handling:** status comparison is strict and limited to the two normative values `prepared` and `registered`. The audit version is taken from the rejected current version and is `1` in both executable examples, as specified.
- **Test sensitivity:** distinct saved and incoming participant IDs catch replacement or merging. Separate prepared and registered fixtures catch status-specific behavior and disclosure. Exact response and full-projection equality catch extra violations, privacy leaks, event replacement, duplicate events, artifact regeneration, partial persistence, or task/state mutation. Forbidden dependency guards catch moving the decision after irrelevant validation or rendering.
- **Scope:** changing orders, idempotency after unknown outcomes, concurrent calls and lock versions, registration correction, cancellation, UI/HTTP, and production adapters remain outside this approval.

## Required changes

None for `ORDER-PREPARE-005` version `0.1`.

Gate 5 is approved. Approval is limited to rejecting a repeated first preparation when the existing current version `1` is `prepared` or `registered`, with the exact append-only non-identifying audit behavior at the approved public seam.
