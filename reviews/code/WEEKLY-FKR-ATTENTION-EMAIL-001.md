# WEEKLY-FKR-ATTENTION-EMAIL-001 — independent Gate 5 final review

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_final_review`
- Independence: the reviewer authored none of the specification, OpenSpec
  artifacts, tests, production implementation, or verification evidence
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Exact candidate source:
  `0e52f31dc9927f12095d4749d282fedb43fe1118f1a761d31075ef86c197293e`
- Prepared package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T224026Z-372b5e1616/package.json`
- Package snapshot patch:
  `eaed28279d60440ef4b9b8c8afb1520017fab0afbef7cfe2e91928351166cd4b`
- Verdict: **CHANGES_REQUESTED**

The exact source is a content-addressed dirty-worktree snapshot, not a Git
object. The prepared package, reconstructible patch, required context,
verification plan, complete modified/untracked file set, approved Gate 3 and
post-Gate-3 test-delta record, and every package evidence record were reviewed.
This review changes only this append-only record.

## Evidence reviewed

The package contains exact-source GREEN records for:

```text
php tests/WeeklyFkrReport/weekly_schedule_001_test.php
php tests/WeeklyFkrReport/weekly_report_001_test.php
php tests/Jobs/weekly_fkr_report_delivery_001_test.php
php tests/Jobs/outbox_delivery_lifecycle_001_test.php
php tests/Jobs/durable_queue_concurrency_001_test.php
php tests/Runtime/weekly_fkr_smtp_configuration_001_test.php
python3 tests/Verification/change_verification_001_test.py
python3 tests/Verification/architecture_guard_001_test.py
```

All eight records name exact source `0e52f31d...`. They establish the focused
fixture behavior but do not override the code findings below. Exact-source
GitHub CI has not yet been supplied and remains `UNKNOWN`, as expected before
task 5.3; it is not treated as GREEN.

No committed `k2` password or other new literal SMTP secret was found.

## Blocking findings

### G5-1 — the feature is not integrated into the production Jobs/outbox runtime

`app/Jobs/MariaDbWeeklyFkrScheduler.php:7-23` is not MariaDB-backed: it accepts an
arbitrary callback and deduplicates in the process-local `$weeks` array. A
restart or another scheduler process forgets that state. More importantly, the
production runtime never constructs or calls it. `app/Jobs/JobsRuntimeCommand.php:18,33-41`
still registers only workforce, outbox-dispatch and document-link jobs;
`app/Jobs/JobsSchedulerProcess.php:10-23` has no weekly scheduler;
`app/Jobs/JobHandlerClaim.php:14-17` rejects `weekly-fkr-report.generate`; and
`app/Jobs/JobHandlerRuntime.php:9-19` cannot execute the new handler.

The generation handler is likewise callback-only. It neither discovers active
managers nor appends the existing `MariaDbOutbox` shape. Its intent keys/data do
not match `MariaDbOutbox::append()` (`eventId`, `channel`, `template`, `version`,
`data`), and no production composition supplies native recipient, global
`objects.read`, report-source, history, or opening-eligibility adapters. Finally,
`JobHandlerRuntime` still returns `OUTBOX_TRANSPORT_UNCONFIGURED` for every
outbox delivery, so `SmtpTransport` is unreachable in production.

The focused tests replace each missing owner with callbacks and therefore stay
GREEN while the deployed scheduler/worker can neither generate nor send issue
#11. This violates spec sections 1, 2 and 9 and OpenSpec design sections 2 and 4.

### G5-2 — report projection and rendering contradict several exact requirements

- `WeeklyFkrReportRenderer.php:12` emits `Еженедельный отчёт ФКР — YYYY-MM-DD`,
  not the required subject `FMonitor — недельный отчёт ФКР,
  <DD.MM.YYYY>–<DD.MM.YYYY>`; neither plan-period boundary is shown in the
  message (`spec` section 3).
- `WeeklyFkrReportBuilder.php:22` omits work `0..85`, documents `0..15`, total
  and UNKNOWN from planned closing rows (`section 4.2`).
- `WeeklyFkrReportBuilder.php:31` uses `closing > planStart`, excluding a closing
  due on the generated Monday even though the attention window is the local
  generated date through `+6 days`, inclusive (`section 7.1`).
- `WeeklyFkrReportBuilder.php:33-34` and
  `WeeklyFkrReportRenderer.php:16` do not implement the required universal
  `(planned date, registration number bytewise, object id)` ascending order.
  Progress is sorted by delta and then re-sorted by ending total; object-id is
  absent as a tie-breaker elsewhere (`section 3`).
- `WeeklyFkrReportBuilder.php:28-29` collapses two overdue actions on the same
  object to the earlier one, although overdue rows are action/type facts and
  both unfinished overdue opening and closing deadlines must be represented
  (`section 6`).

These are user-visible correctness defects. The tests hard-code the current
reduced output and consequently do not detect them despite the earlier Gate 3
approval.

### G5-3 — SMTP configuration and live protocol do not meet the contract

`SmtpConfiguration.php:12` accepts `ssl`, although the normative runtime value
is exactly `ENCRYPTION=tls`. `SmtpTransport.php:21-22` implements a hand-written
SMTP client rather than the supported dependency/Yii-mailer choice required by
the approved design. Once a socket is open, every protocol, authentication,
recipient and DATA failure is collapsed to `UNKNOWN_DELIVERY`; this makes known
pre-acceptance failures non-retryable automatically under the outbox policy,
instead of distinguishing safe transient/permanent failures from genuinely
unknown post-acceptance ACK state. It also does not dot-stuff DATA lines and
does not validate advertised AUTH/STARTTLS capabilities.

Thus TLS is configured in the isolated test, but actual authentication,
classification, retry and unknown-ACK semantics are not production-safe as
required by sections 9 and 10.

### G5-4 — deployment documentation claims wiring that does not exist

`deploy/runtime/compose.yaml:127-140` passes SMTP variables only to
`jobs-worker`; that worker never constructs `SmtpConfiguration` or
`SmtpTransport`. `docs/operations/production-runtime-runbook.md:297-305` says
the weekly report uses SMTP even though the runtime still returns
`OUTBOX_TRANSPORT_UNCONFIGURED`. The planned
`docs/operations/weekly-fkr-email.md` is absent, `.env.example` contains no
`FMONITOR_SMTP_*`/public-base entries, and no manual test-send command or safe
`k2-mailer` profile was delivered. OpenSpec tasks 4.4, 4.7 and their checkmarks
therefore overstate implementation completeness.

### G5-5 — security-sensitive code is not maintainable enough to approve

The new production classes compress constructors, authorization decisions,
calendar classification, HTML generation and the entire SMTP protocol into
very long single lines (notably `SmtpConfiguration.php:8-17`,
`SmtpTransport.php:9-27`, `WeeklyFkrReportBuilder.php:18-38`, and
`WeeklyFkrReportRenderer.php:14-42`). This conflicts with the repository's
documented readable-source/normal-formatting direction and materially obstructs
review of TLS, authorization, escaping and retry behavior.

Recipient eligibility is also duplicated as primitive arrays in
`WeeklyFkrReportBuilder.php:11-12` and `SmtpTransport.php:12-14`, with
`objects.read` checked only by the former, inviting authorization drift.
`fingerprint()` on all three production read interfaces is unused by production
and exists only to support test doubles.

## Gate 5 verdict

**CHANGES_REQUESTED**

The candidate has useful deterministic report/render tests and correctly keeps
the discovered `k2` secret out of the checkout, but it is not a deployable
implementation of issue #11. Re-review requires production composition through
the existing durable scheduler/job/outbox owners, native recipient/scope/history/
eligibility adapters, correction of report semantics and SMTP outcomes, honest
deployment documentation, focused regression coverage for those corrections,
and a newly prepared exact-source package. Required exact-source CI remains a
separate later gate and cannot cure these code defects.

---

# Correction re-review — exact source `9cbf2da8`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_final_review`
- Independence: unchanged; the reviewer authored none of the corrected code,
  specification, tests, or evidence
- Previous reviewed source:
  `0e52f31dc9927f12095d4749d282fedb43fe1118f1a761d31075ef86c197293e`
- Corrected exact source:
  `9cbf2da86da3cf621fca471496c0a1d019d2d401c71a9b4619d58fdfb9ebf3f8`
- Prepared package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T225834Z-c875b0a6d5/package.json`
- Package snapshot patch:
  `c8951e8ac75cb87e10f6a1c5288c7a1b4d75662e92d7f0d0d355da42107bbb29`
- Test-delta 2: **APPROVED** for exact source `33c09f5f...`
- Re-review verdict: **CHANGES_REQUESTED**

The complete correction snapshot, Test-delta 2 section, all modified/untracked
files and all eight planner evidence records were reviewed. The eight records
are exact-source GREEN for `9cbf2da8...`; the reported broader thirteen focused
commands are consistent with those records. No local full suite was run.

## Original finding disposition

### G5-1 — partially fixed; native semantics remain blocking

The durable wiring portion is fixed. `MariaDbWeeklyFkrScheduler` now persists a
Monday slot and job transactionally; the scheduler process invokes it; queue,
worker and claim admission include `weekly-fkr-report.generate`; generation
appends transactional `MariaDbOutbox` intents; and outbox dispatch constructs
the SMTP transport. Active `manager` identities are selected with the global
`objects.read` grant, so the owner decision does not introduce per-manager
filters.

The new native adapters do not, however, implement the promised canonical
facts:

- `MariaDbWeeklyFkrOpeningEligibility.php:8-15` treats existence of any
  `fm2_assignment_order_original_roots` row as complete opening readiness. It
  does not establish an accepted and applicable original/application, valid
  composition, workforce/control-engineer eligibility, completion/PTO blockers,
  or the other checks enforced by the native opening seam. It can therefore
  omit an object from «Обратить внимание» even though canonical opening would
  reject it. This violates spec section 7.2's requirement to use one native
  opening-eligibility seam rather than copying a reduced rule.
- `MariaDbWeeklyFkrReportSource.php:14` asks for the progress start at
  `<progressStart>T00:00:00Z`. The contract requires the snapshot as of the end
  of the day before that period in `Europe/Moscow`; the implementation is both
  the wrong boundary and the wrong timezone. Its end snapshot similarly treats
  local Sunday as UTC. Historical 85/15 changes around either Moscow boundary
  can be attributed to the wrong week.
- The same source hard-codes a new item-weight map and derives completion from
  it (`lines 7,19-23`) instead of consuming the canonical confirmed progress
  projection. SQL/storage failure is caught and returned as `null` per object,
  while initial object/legacy queries throw the whole job; this is not a single
  coherent UNKNOWN boundary.

The callback-focused A2-A7 test does not execute these MariaDB adapters, so its
GREEN result does not close these defects. G5-1 remains open.

### G5-2 — fixed

The correction supplies the exact subject and both plan boundaries, renders
planned-closing work/document/total readiness with UNKNOWN, includes Monday in
the attention window, preserves both overdue action rows, and applies the
contract's date/registration/object ordering including the corrected progress
oracle approved by Test-delta 2.

### G5-3 — partially fixed; UNKNOWN is still automatically retried

TLS-only configuration, STARTTLS and AUTH capability checks, complete socket
writes, dot-stuffing, and pre-DATA 4xx/5xx classification are now present.

The required unknown-ACK terminal behavior is still wrong in production.
`SmtpTransport.php:46-47` correctly returns `status=unknown` after a failure once
DATA writing begins. But `JobHandlerRuntime::deliverOutbox()` maps every status
other than delivered/permanent/transient to `ambiguous_retryable`, then maps the
outbox result back to a retryable job. `MariaDbOutbox::recordAttempt()` leaves
that intent pending. The worker therefore retries it automatically, contrary to
spec section 9.4: accepted SMTP with unknown ACK must remain
`UNKNOWN_DELIVERY`, must not count as delivered, and must not be automatically
retried. This can send duplicate manager emails.

The production adapter also discards safe specific failure codes when mapping
permanent/transient outcomes, so the direct transport callback test does not
exercise the behavior that the runtime actually persists. G5-3 remains open.

### G5-4 — fixed

Environment examples, compose wiring, production readiness, the explicit
`--send` CLI, safe operator procedure, rollback guidance, and
`docs/operations/weekly-fkr-email.md` are now present. The test-send remains a
separate explicit operation and no SMTP secret is committed.

### G5-5 — partially fixed; maintainability remains open

`SmtpConfiguration` and `SmtpTransport` are improved enough to audit the main
protocol phases. Much of the new security and historical data code remains
compressed into dense multi-operation lines, especially
`MariaDbWeeklyFkrReportSource.php:7-25`,
`MariaDbWeeklyFkrOpeningEligibility.php:10-17`,
`WeeklyFkrReportBuilder.php:7-38`, `WeeklyFkrReportRenderer.php:16-44`, and the
new `JobHandlerRuntime` generation/delivery methods. The source adapter puts an
entire historical projection algorithm and several SQL operations into a few
lines. This preserves the original auditability concern rather than fixing it.

Recipient eligibility also remains duplicated between builder and transport as
untyped arrays, and test-only `fingerprint()` members remain on production
ports. These are secondary to the semantic blockers above but G5-5 is not fully
closed.

## Correction verdict

**CHANGES_REQUESTED**

The correction establishes a real persisted scheduler/job/outbox path and fixes
the observed report and operations-documentation defects. Approval still
requires the report to consume canonical opening eligibility and correctly
bounded Moscow historical progress, an explicit durable non-auto-retry state
for unknown SMTP ACK, and readable formatting of the corrected security/history
paths. Add focused integration coverage through the production MariaDB adapters
and production outbox mapping, then prepare a new exact-source package for
re-review. Exact-source GitHub CI remains a separate later requirement.

---

# Final correction re-review — exact source `729b6daf`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_final_review`
- Independence: unchanged; the reviewer authored none of the corrected code,
  tests, specification, or evidence
- Previous reviewed source:
  `9cbf2da86da3cf621fca471496c0a1d019d2d401c71a9b4619d58fdfb9ebf3f8`
- Corrected exact source:
  `729b6dafdf3d9a99c8a00a7ec2b61cadf29537346a6d98498ffa977e3cabddec`
- Prepared package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T231952Z-50e4e26e45/package.json`
- Package snapshot patch:
  `ab13223f0482ec14944985085c3bd9801fa78a3c3dbc77cf824fe153db636306`
- Re-review verdict: **CHANGES_REQUESTED**

The complete correction diff, latest test-delta approvals and all seven mapped
exact-source GREEN records were inspected. The inspection-schema check that
could not start without checkout `vendor`, and the later integration-profile
runtime-storage failure on a read-only fixture filesystem, are recorded as
environment `UNKNOWN`, not as product regressions and not as GREEN. The other
reported inspection characterizations, confirmed-opening and architecture
checks are GREEN. Required exact-source GitHub CI remains pending separately.

## Remaining-blocker disposition

### Canonical opening eligibility — fixed

`MariaDbWeeklyFkrOpeningEligibility` now delegates to the read-only
`MariaDbCurrentOpeningEligibility`. That seam reads the current applied
composition and bound original revision, detects reapplication mismatch,
checks the same completion/PTO evidence as opening, and calls
`AssignmentOrderCurrentEligibility::confirm()` for workforce and control-
engineer eligibility. Unknown dependency state fails safe as «Недостаточно
данных для оценки». The weekly adapter no longer substitutes mere existence of
an original root for canonical readiness.

### Historical 85/15 boundaries — fixed

`MariaDbWeeklyFkrReportSource` now uses the shared
`ProductionChecklistProgressFactory`/`MariaDbChecklistProgress` owner rather
than carrying a second weight map. The start snapshot is the previous local
day's `23:59:59.999999 Europe/Moscow`, and the end snapshot is the progress
period Sunday's corresponding Moscow end-of-day, both converted to exact UTC
instants before the as-of query. Current progress remains bounded by the exact
generation instant. This closes the prior week-boundary and duplicate-owner
findings.

### Unknown SMTP acknowledgment — fixed

`JobHandlerRuntime::mapEmailTransportOutcome()` is the public mapper used by
the production outbox transport closure. It maps transport `unknown` to
terminal `permanent/UNKNOWN_DELIVERY`; `OutboxDeliveryOutcome` allowlists that
safe code. The exact-source regression uses the real queue, outbox, dispatch
scheduler and delivery handler and proves one persisted attempt tied to the
intent/job/attempt and idempotency reference, a dead intent/job, one network
call, and zero enqueue on the next sweep. Unknown ACK is neither delivered nor
automatically retried.

### Central recipient eligibility and test-interface cleanup — fixed

Builder and transport now share `WeeklyFkrRecipientEligibility`, while the
test-only `fingerprint()` methods have been removed from the three production
ports and native adapters.

### Readability/auditability — still open

The correction extracted classification helpers and formatted the high-level
runtime control flow, but it did not complete the original readability cleanup.
New or materially changed production code still combines multiple decisions,
SQL operations and state transitions into very long physical lines:

- `MariaDbCurrentOpeningEligibility.php:38-49` includes 194–384 character SQL,
  transaction and result-mapping lines;
- `MariaDbChecklistProgress.php:7,25-33` retains a 262-character domain map and
  a 324-character historical query line;
- `MariaDbWeeklyFkrReportSource.php:29-46` includes a 364-character query and
  an approximately 800-character projection return;
- `WeeklyFkrReportBuilder.php:47-87` still joins multiple calculations and
  branches on individual 180–366 character lines;
- `WeeklyFkrReportRenderer.php:24` places the complete HTML document on one
  approximately 1,500-character line, with other rendering decisions likewise
  compressed;
- `SmtpTransport.php:41-63` still compresses authentication, envelope, DATA,
  exception classification and reply parsing into multi-operation lines up to
  roughly 365 characters.

This is the same G5-5 auditability issue, not new scope. It matters here because
the compressed lines own authorization, append-only historical projection,
TLS authentication, unknown-delivery classification, escaping and Outlook
markup. The repository's documented normal-formatting/readable-source rule was
also the independent Standards-axis HIGH finding. Extracting a few methods does
not make those remaining lines reviewable or maintainable.

## Final correction verdict

**CHANGES_REQUESTED**

The functional corrections close the remaining eligibility, historical-time
and duplicate-delivery risks, and the current mapped evidence is GREEN. Gate 5
is not approved because the explicitly requested readability cleanup remains
materially incomplete across the new production implementation. Reformat the
listed paths into normal multiline source without changing behavior, run the
bounded syntax/focused checks affected by that mechanical delta, and prepare a
new exact-source package for one final review. The two environment-only checks
remain honestly `UNKNOWN`; they are not a reason to rerun the prohibited local
full suite.

---

# Final verdict after readability correction and rebase — exact source `40f15990`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_final_review`
- Independence: unchanged; the reviewer authored none of the implementation,
  specification, tests, rebase, or evidence
- Rebased main: `8ce66adb26b73a81931cb3a0dc2697bd96c58729`
- Candidate commit: `736f70c70ca2934189c3d0ad5d51c48f139ebfbf`
- Exact candidate source:
  `40f15990c2f59af996116ce4703f8ae68b5b714ea5689c540200a8ca5fa10060`
- Prepared package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T232953Z-d4994e09d5/package.json`
- Verdict: **APPROVED**

The prepared package, committed diff, latest test approvals, seven exact-source
records and the post-rebase repository state were inspected. The worktree was
clean before this append-only review update. `HEAD` is one issue #11 feature
commit above the requested latest-main merge base. No conflict markers or
candidate-path omissions were found.

## Rebase and current-goal preservation

The feature is based on `8ce66adb...`; `git merge-base HEAD 8ce66adb...` returns
that exact commit. The rebased branch preserves main's current operational goal
for issue #21 in `docs/operations/current-delivery-goal.md` rather than
reintroducing the stale issue #11 queue marker. Issue #11's specification,
OpenSpec lifecycle, implementation, tests, operations documentation and review
history remain present in the feature commit.

## Prior finding closure

- **G5-1 fixed:** the persisted Moscow scheduler, job admission/worker registry,
  transactional outbox, active-manager/global-`objects.read` directory and
  native report adapters remain wired. Opening attention delegates to the
  canonical current application/original/workforce/control-engineer/completion/
  PTO eligibility owner.
- **G5-2 fixed:** exact subject/period presentation, 85/15/UNKNOWN closing
  readiness, stable universal sorting, inclusive attention boundaries and dual
  overdue actions remain intact after rebase.
- **G5-3 fixed:** TLS-only STARTTLS/AUTH handling, peer verification,
  dot-stuffing and safe outcome classification remain intact. Production uses
  `mapEmailTransportOutcome()`; unknown ACK persists one terminal
  `UNKNOWN_DELIVERY` attempt, leaves the intent/job dead and produces no
  automatic resweep or second network call.
- **G5-4 fixed:** environment/compose readiness, explicit manual `--send`, safe
  SMTP operations documentation and rollback guidance remain present without a
  committed `k2` secret.
- **G5-5 fixed:** builder and transport retain the shared recipient-eligibility
  rule; test-only fingerprints remain absent from production ports. Every
  production file cited in the prior readability finding now has zero physical
  lines longer than 180 characters. SQL, projection, HTML rendering, SMTP
  protocol and outcome mapping are structured into readable multiline blocks
  and cohesive helpers without changing their reviewed behavior.

Historical progress still consumes the shared checklist-progress owner and
uses exact `Europe/Moscow` prior-day/period-end instants converted to UTC. The
approved unknown-delivery persistence regression is included in the current
package.

## Evidence and honest unknowns

All seven mapped records are exact-source GREEN for `40f15990...`:

```text
php tests/Jobs/durable_queue_concurrency_001_test.php
php tests/Jobs/outbox_delivery_lifecycle_001_test.php
php tests/Jobs/weekly_fkr_report_delivery_001_test.php
php tests/Jobs/weekly_fkr_unknown_delivery_001_test.php
php tests/Runtime/weekly_fkr_smtp_configuration_001_test.php
php tests/WeeklyFkrReport/weekly_report_001_test.php
php tests/WeeklyFkrReport/weekly_schedule_001_test.php
```

The reported post-rebase weekly, core opening, Jobs and architecture checks are
GREEN. The inspection-schema consumer remains `UNKNOWN`: bare checkout lacked
`vendor`, and the integration profile later reached a read-only runtime-storage
fixture failure. Those diagnosed environment conditions are neither product
regressions nor GREEN and do not weaken this code-review verdict. The prohibited
local full suite was not run.

## Final Gate 5 verdict

**APPROVED**

Gate 5 is approved for exact source
`40f15990c2f59af996116ce4703f8ae68b5b714ea5689c540200a8ca5fa10060`
on base `8ce66adb26b73a81931cb3a0dc2697bd96c58729`. This approval covers the reviewed
code/spec/test/configuration completeness and does not convert the inspection
consumer environment gap or required exact-source GitHub CI into GREEN. CI and
publication readiness remain separate subsequent gates.
