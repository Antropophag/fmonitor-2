# Original command — bounded safe-log best-effort source audit

Дата: 2026-09-06.  
Reviewer task: `/root/selection_v04_readiness`.  
Reviewed HEAD: `1afdec2ea26651ee4cf81ab45b37900e7e3df843`.  
Verdict: **GAP CONFIRMED; existing public-path coverage is insufficient**.

Это независимый ограниченный review существующего original command против уже
утверждённого best-effort safe-log contract. Product decision не требуется.
Код, тесты и спецификации не изменялись; DB/file/native/OS probes не запускались.
Audit не относится к opened-file owner Gate 5, protected E2E или safe-log file
ownership implementation.

## Exact reviewed artifacts

- `app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php`:
  `4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d`.
- `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md`:
  `d23b9cd924be6ce9deb905a0c742e7b0449eb8755fdb5af8d9caac094934fbf3`.
- `tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php`:
  `3f006e89be2967d511cf8c0a00828d38ebc20d83240fc5d428738a3e2c2a2716`.
- `tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php`:
  `f788e80143c25cda53fb02d79a4089248ce6079fcf1586b6aeb65b53d5ba6486`.
- `tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php`:
  `7b6297b8bed813f682db82a496dd599f17a89cea6c85aae9554300483a036b4f`.
- `tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php`:
  `513d315779988ef87f93b175cddd652188d33a5c2665f2ac4af667e62d526a53`.

## Approved behavior

Normative spec requires:

- cleanup failure logs one safe canonical event and returns the already selected
  Result after all applicable cleanup attempts (`spec:639`, `:948`);
- lease release failure preserves accepted/replayed/provisional conflict or
  persistence Result, performs no second release, and does not prevent the
  response-delivery observer after the release attempt (`:913-917`, `:948`);
- a later safe-log append failure is best effort and cannot replace the selected
  Result (`:967-972`);
- throwing injected safe-log observer emits no bytes, receives no second logging
  attempt and preserves Result (`:1030-1034`);
- every port call is total at the application boundary (`:952`).

## Source findings

### P0 — `finish()` lets safe-log failure replace the selected Result and repeat cleanup

`AssignmentOrderOriginalRuntime.php:95` invokes `safeLog->record()` directly in
three cleanup paths:

1. stage abort returns FAILED;
2. stage abort throws and its catch logs directly;
3. stage close or stream close throws and its catch logs directly.

None of these log calls has its own best-effort catch. When the observer throws,
the exception escapes `finish()` into the command-wide catch at `:92`. That
catch invokes stage abort, stage close and stream close again, suppresses their
errors, and returns `FAILED/PERSISTENCE_FAILURE` (or its fault-target mapping).

Observable violations:

- a selected rejection such as `REJECTED/INVALID_PDF` is replaced;
- abort/close/stream cleanup can be attempted twice;
- the logger is called once only because the outer catch does not log, but the
  approved “no second attempt” also applies to cleanup ownership, not merely the
  log call;
- terminal rejected audit ordering can be skipped because `finish()` never
  reaches `commitAttempt`.

### P0 — `releaseLease()` lets logging failure replace durable/provisional Result

At `AssignmentOrderOriginalRuntime.php:97`, `lease->release()` is protected, but
the `safeLog->record()` in both the typed-FAILED branch and catch branch is not.
Callers at `:79-91`, `finishLeased()` at `:96`, and the final release at `:91`
therefore receive a thrown observer exception.

The command-wide catch at `:92` converts it to a retryable technical failure.
On the committed path this replaces an already durable `ACCEPTED` Result and
skips `delivery->afterCommitBeforeReturn()` at `:91`. On conflict/fresh-lookup
paths it can replace the already selected replay/conflict/persistence Result and
can skip the required conflict attempt audit. Lease release itself is not retried
by the outer catch, but result preservation and post-attempt sequencing are
violated.

### P1 — request correlation setup is outside the application boundary catch

At `AssignmentOrderOriginalRuntime.php:62`, optional
`AssignmentOrderOriginalRequestSafeLogObserver::useRequest()` executes before the
command `try` begins at `:64`. A throwing observer escapes the public seam,
returns no typed Result and leaves the input stream unclosed. This contradicts
the total-port boundary and best-effort observer role. It can affect every
command, including a valid accepted invocation, before authorization or replay.

## Existing coverage assessment

- Worker transport test line 22 covers primitive stage abort/stage close/stream
  close failures with a successful real logger and verifies one exact log line,
  selected `INVALID_PDF`, and mutation-free replay.
- Production boundary test line 18 covers a throwing stream close with a
  successful real file logger and verifies result preservation and exact append.
- Safe-log owner tests exercise owner append success and direct append failure,
  but do not send a throwing observer through
  `submitAssignmentOrderOriginal()`.
- Remaining-contract and Gate5-domain tests use nonthrowing shared observers;
  they do not cover record/useRequest Throwable or selected-result preservation
  under observer failure.

No existing reviewed public-path test was found that injects a throwing
`AssignmentOrderOriginalSafeLogObserver` into the application and asserts zero
bytes, one call, exact selected Result, exact cleanup counts and delivery/audit
continuation. The spec explicitly calls for that verification at `:1030-1034`.

## Smallest public-seam regression outline

One pure deterministic test file can use
`AssignmentOrderOriginalVerificationFactory::create(...)` and invoke only
`AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal(...)` with
in-memory typed spies. Expected values come directly from the approved contract.

Minimum sensitive cases:

1. **Cleanup log throws.** Make a passive-PDF candidate select
   `REJECTED/INVALID_PDF`; make exactly one cleanup primitive fail; inject a
   safe-log observer whose `record()` increments a counter and throws. Assert
   exact result remains `REJECTED/INVALID_PDF`, nonretryable; `recordCalls=1`;
   failing cleanup and each later applicable cleanup primitive are attempted
   exactly once; terminal rejected request/audit is committed once; no domain
   event/revision/fingerprint and no log bytes.
2. **Post-commit lease-release log throws.** Commit accepted successfully; make
   lease release return typed FAILED; throwing logger as above. Assert exact
   durable `ACCEPTED` payload is returned, `releaseCalls=1`, `recordCalls=1`,
   delivery observer runs once after the release attempt, committed evidence is
   unchanged and no log bytes are emitted.
3. **Correlation setup throws.** Inject a request-aware safe logger whose
   `useRequest()` throws and whose `record()` would count calls. Submit an
   otherwise valid command. Assert the best-effort setup failure does not escape,
   command returns its independently expected accepted Result, input stream is
   closed once, `useRequestCalls=1`, `recordCalls=0`, and no log bytes.

If implementation chooses to ignore `useRequest` failure and continue, case 3
must also prove later cleanup diagnostics do not disclose or reuse a stale prior
request correlation. The existing contract may require an exact correlation
fallback clarification before that branch is implemented; this is a technical
contract detail, not a product-policy question.

The test must first demonstrate RED for the actual reasons above, then receive
independent Gate 3 before a minimal production change. No DB, filesystem logger,
worker fault composition or protected E2E change is needed for this regression.

## Bounded verdict

The approved safe-log best-effort behavior is not implemented at the public
application seam for throwing observers. The gap is genuine and currently
under-tested. It requires a focused executable-spec clarification only if stale
correlation fallback is not already pinned, followed by RED, independent test
review, minimal GREEN and independent code review. Existing successful-log tests
remain valid but do not close this failure branch.
