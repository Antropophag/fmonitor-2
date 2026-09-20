# WEEKLY-FKR-ATTENTION-EMAIL-001 — independent Gate 3 test review

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_gate3`
- Role: independent Gate 3 test reviewer; the reviewer authored none of the
  specification, OpenSpec artifacts, tests, production code, or RED evidence
- Declared authorship: root authored scope/spec/tests under the owner-approved
  separate-executor workflow; no production implementation exists in the
  reviewed source
- Exact candidate source:
  `b9d57c044e8d2f6ae45ced3bce8ce57190b130995bbfd3d4555f6c77ae032d5d`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Prepared reviewer package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T213512Z-fcedc27a16/package.json`
- Scope: normative contract, four mapped tests, image-free Outlook/web
  requirements, exact-source RED evidence, traceability, public seams,
  sensitivity, fixed values, authorization/scope/history, idempotency and
  concurrency
- Verdict: **CHANGES_REQUESTED**

This append-only review record is the only file changed by the reviewer. No
specification, test, fixture, inventory, or production file was altered.

## Evidence reviewed

The package, required-context document, task-context manifest, verification
plan, reconstructible snapshot and every source named by the package were
reviewed. Required canonical context included `AGENTS.md`, `PRODUCT.md`,
`CONTEXT.md`, `docs/development-process.md`, the pilot specification/data model,
and the current delivery goal.

The four external RED records and their stdout/stderr payloads were inspected:

```text
/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789940055141712000-e877831e33114c7a970b8df152225a5b.json
/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789940068139877000-23d887e6c4864a4d86b2cdae840b1fc2.json
/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789940068139926000-a98b6ce94d5e40d8a306dcbf19b31638.json
/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789940068139971000-7a910274812a4311864f80d39fa73715.json
```

All four records are exact-source and deterministic (`exit 255`) and fail on
their first intentional missing-class assertion. Their fixture reachability is
recorded as `UNKNOWN`; no substantive assertion after the existence guard ran.

## Blocking findings

### G3-1 — A8/A9/A12 delivery lifecycle is largely untested

`tests/Jobs/weekly_fkr_report_delivery_001_test.php:8-17` proves one direct
successful transport call, revoked-recipient rejection, in-memory intent-key
replay, and size overflow. It does not exercise the contract's transient retry,
terminal permanent failure/operator retry, confirmed-delivery suppression,
unknown-ACK `UNKNOWN_DELIVERY` behavior, persisted attempt history, or maximum
one concurrent attempt. It also never changes a recipient from one valid email
to another before retry: the configured test override makes the only wire
address constant, so A9 address re-resolution is not observable. No before/after
domain snapshot exists, despite A12 and task 2.5 requiring proof that build,
replay, failure, and delivery do not mutate domain facts.

The callback-backed `$intents` array cannot validate the existing durable
Jobs/Outbox ownership or its concurrency/state transitions. Add public-seam
tests through the owning durable job/outbox boundary with independently fixed
attempt outcomes, a real concurrent claim/race, changed-email and revoked-role
retry cases, unknown ACK, and complete domain-state fingerprints.

### G3-2 — the report test bypasses the required native security/history seams

`tests/WeeklyFkrReport/weekly_report_001_test.php:7-17` supplies recipient,
scope, object facts, opening reasons, current progress, and two history
boundaries as caller-owned arrays. The "query boundary" is an inline
`array_filter`, and the opening eligibility seam is replaced by fixture field
`openingReasons`. A new isolated implementation can therefore satisfy this test
without canonical identity/role grants, server-side access selection, native
opening eligibility, or append-only as-of history. That contradicts the public
seam and ownership decisions in the normative spec/design.

The same fixture also omits expired/wrong role, missing/invalid email, a built
report for the second leader, cross-scope counts/text/link checks, corrected
plan-date as-of behavior, explicit period boundary exclusions, corrected
history, an unknown progress boundary/delta assertion, old-without-cutoff plus
today-not-overdue behavior, attention window exclusions, and exact closing
reasons/current values/active violation. A5's label says "independently fixed
85/15 delta", but it checks only totals and delta; it does not require the
separate `40→55 of 85` and `0→5 of 15` observable values.

Move the integration coverage to the approved public report seam backed by
native test-owned identity/access, plan/history and eligibility facts, while
retaining small pure unit cases only as supplemental tests. Add independent
literals and negative controls for the missing cases.

### G3-3 — the image-free Outlook/web contract is not executable end to end

`tests/WeeklyFkrReport/weekly_report_001_test.php:25-30` performs substring
checks only. It does not invoke `tests/WeeklyFkrReport/weekly_report_browser.mjs`;
that script is absent from the four registered commands and no generated HTML
fixture/capture command exists. Consequently 680px and 320px no-overflow,
single-column readability and working-link behavior are not part of Gate 2 RED.
Even the unregistered browser helper checks only headings, HTTPS schemes,
images, and horizontal overflow; it does not assert full text/data parity,
status text independent of color, 14px/1.4 typography, 44px narrow touch links,
inline critical styles, `td` padding, explicit width/align/valign, key-cell
foreground/background contrast, or absence of reserved image/alt gaps.

The static forbidden-token list also does not prove a standalone document and
does not reject all external requests or unsupported content-hiding CSS. Wire
the browser verifier into a mapped focused test with deterministic generated
HTML, assert the normative structural/accessibility properties and equal
section/data order in HTML and text, and preserve the explicit no-image/no-
external-resource mutations.

### G3-4 — the recorded RED proves absence, not behavioral sensitivity

Each mapped test puts `class_exists(...)` before every acceptance assertion.
The exact RED outputs therefore demonstrate only that four production classes
are absent. The records report `fixture_reachability_boundary=null`,
`fixture_reachability_probe_kind=null`, and `end_fixture=UNKNOWN`. This is not
evidence that the A1-A12 fixtures are reachable or that plausible wrong
implementations fail for the claimed reasons. In particular it cannot validate
scope, history, retry, concurrency, mutation isolation, or email rendering.

Provide the repository-required fixture-reachability/sensitivity evidence (or
an approved staged-seam mechanism) so Gate 3 can distinguish a meaningful
behavior RED from wrapper-only missing-class RED. Re-run all four exact-source
records after correcting the tests.

## Non-blocking observations

- The normative spec is unusually clear about Moscow calendar boundaries,
  identity-keyed deduplication, unknown delivery, the 5 MiB pre-encoding limit,
  secret handling, image-free email constraints, and the separation between
  actual overdue work and attention.
- `weekly_schedule_001_test.php` contains useful exact literals for before/at
  09:00, week periods, idempotency key and next-week identity, although a
  non-Monday/after-week invocation would strengthen its scheduler boundary.
- `weekly_fkr_smtp_configuration_001_test.php` fixes the principal TLS/sender/
  HTTPS/secret/test-override configuration decisions. Additional malformed
  host/encryption/timeout/address and non-test non-production cases can be
  added while repairing the blocking lifecycle coverage.
- The 5 MiB overflow expectation is fail-complete and secret-free. The dense
  render fixture should be made production-like and preserved alongside the
  exact bound.

## Exact reviewed digests

```text
9ef1b465ff1aa5edce99476a03532a9bbd950fc5fc9bbf46789af200839ed0a6  specs/WEEKLY-FKR-ATTENTION-EMAIL-001.md
6c3fe6c749161dac83a4938bce31f302ae325d520f7c4a34bb24d130d2f15577  tests/WeeklyFkrReport/weekly_schedule_001_test.php
4704e8f4dc6b69c7f11cce5c0308c85345743d20142b18ef9e0646bc29698c0a  tests/WeeklyFkrReport/weekly_report_001_test.php
b0ecd203597b35d35e0d3570d094908acd0ece9efdbb1cdaa36bfee987aff01e  tests/WeeklyFkrReport/weekly_report_browser.mjs
1c250941955ada3e483a065a85afc83c6955b4d791079838a82598cb82ef2fef  tests/Jobs/weekly_fkr_report_delivery_001_test.php
0b018d9d449fa916f20f645f0b2dcb57eee5e0a670aefaab62e4f51508668a8a  tests/Runtime/weekly_fkr_smtp_configuration_001_test.php
cfc0759f5a276eacc2b09b72a8485bd68a02af86cbbf19931cba7fa30e9b1db4  openspec/changes/weekly-fkr-attention-email/proposal.md
06e5fe2f9b66ecfe3eea953228b1e98d0d43d13aac2ddc0b9c01ec7cf9cff9d8  openspec/changes/weekly-fkr-attention-email/design.md
e86794fc0adb41dabf51f981e895f497b44e2aad9bcaebfba97e06a62e8ab83a  openspec/changes/weekly-fkr-attention-email/specs/weekly-fkr-attention-email/spec.md
09c017e31713e58a730453762b6956d70412ffa8c8ad70e1d6d27b2a9c7170f4  openspec/changes/weekly-fkr-attention-email/tasks.md
2b9cc78d46547e667a001e38ec88872e9d896d9ef7a69efe5c8dc3567e533905  prepared package.json
df9aad003140320cd28972c4699157670d98a633ccf460523eeaa2c50ca648af  required-context.json
f5f2bff93a265b3b9b123b1c2ba52d100fff3e1d801204dbc4e728bf8e3408ff  task-context-manifest.json
3b967e8c234ccffe70e356845cb8516112efee7991f478e1a81b7bf1cb3bc50e  verification-plan.json
```

`reviews/tests/WEEKLY-FKR-ATTENTION-EMAIL-001.md` is metadata and is not
self-hashed.

## Verdict

**CHANGES_REQUESTED**

The contract is coherent, but the four mapped tests do not yet trace or
sensitize several critical requirements, and their RED evidence stops before
all behavioral fixtures. Gate 4 is not authorized for exact source
`b9d57c044e8d2f6ae45ced3bce8ce57190b130995bbfd3d4555f6c77ae032d5d`.
Correct the four blockers, capture fresh exact-source behavioral RED evidence,
and obtain a fresh independent Gate 3 review.

---

# Re-review 2 — corrected exact source `c68ba098`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_gate3_r2`
- Independence: the reviewer authored none of the specification, OpenSpec
  artifacts, tests, production code, or evidence
- Exact candidate source:
  `c68ba098cd30a98e2602d02101dcc961b627a11be64f99c13fd80e28a6e854cf`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Prepared reviewer package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T215113Z-1055f6abd0/package.json`
- Owner decision applied: every eligible active FKR manager has the same
  complete global `objects.read` object set; per-manager scope is not required
- Verdict: **CHANGES_REQUESTED**

This section is append-only. The reviewer changed no specification, OpenSpec
artifact, test, fixture, inventory, or production file.

## Evidence and prior-finding disposition

Every source and evidence record named by the prepared exact-source package was
reviewed. The four mapped tests have exact-source `INTENDED_RED` records and now
also have separate read-only `FIXTURE_REACHABLE` records for
`weekly-schedule-fixture`, `weekly-report-fixture`,
`weekly-delivery-fixture`, and `weekly-smtp-config-fixture`. Prior finding
G3-4 is therefore **fixed**.

The package also contains exact-source GREEN records for
`tests/Jobs/outbox_delivery_lifecycle_001_test.php` and
`tests/Jobs/durable_queue_concurrency_001_test.php`. The former proves durable
attempt history, transient exhaustion, explicit operator recovery, permanent
failure recovery, and suppression after confirmed delivery; the latter proves
a real two-process single-claim race. Together with the corrected typed SMTP
outcomes, changed-address retry, revoked-recipient pre-network rejection, and
identity-keyed intent test, this is sufficient reuse of the existing owning
Jobs/Outbox lifecycle. Prior finding G3-1 is **fixed for the agreed composition
with the existing durable subsystem**. No second in-memory outbox is approved
by this conclusion.

The report test now names typed recipient-directory, report-source, and native
opening-eligibility interfaces; asserts the global-object owner decision for
two leaders; observes exact source-call boundaries and separate 85/15 values;
and covers disabled, wrong-role, and invalid-email recipients. This fixes the
structural seam and owner-scope parts of prior finding G3-2, but not its complete
behavior matrix. Prior finding G3-2 is therefore **partially fixed and remains
open** as G3R2-1 below.

The PHP test now executes `weekly_report_browser.mjs` and requires both captures.
The helper checks 680px/320px overflow, system typography, padded/attributed
cells, narrow touch height, headings, HTTPS schemes, forbidden resources, and
limited HTML/text data parity. Prior finding G3-3 is **partially fixed and
remains open** as G3R2-2 below.

## Blocking findings

### G3R2-1 — the report matrix still permits material contract regressions

`tests/WeeklyFkrReport/weekly_report_001_test.php:9-41` uses one compact fixture,
but does not exercise several independent rules promised by tasks 2.2 and 2.4:

- no otherwise-valid manager lacking `objects.read`, nor a missing email;
- no opening/closing plan-date values immediately outside either inclusive
  period boundary, no `null`/UNKNOWN plan-date assertion, and no corrected
  append-only plan-date as-of case;
- no corrected append-only progress-history case, no changed UNKNOWN
  component/boundary row, and no assertion that an impossible delta renders
  `Недостаточно данных`;
- no old deadline without a lookback cutoff, today-is-not-overdue negative
  control, or completed-action overdue exclusion;
- no attention-window values immediately before/after the inclusive range, no
  completed-action exclusion, and no in-window closing row whose active
  canonical violation is asserted. `A-3` contains a violation but is already
  overdue, so the expected attention list excludes it;
- the second manager comparison checks section counts only. It would pass if
  the implementation substituted different object ids, text, or links having
  the same counts, so it does not prove the owner-decided identical complete
  global set.

These are not redundant examples: they distinguish inclusive calendar logic,
append-only as-of reads, UNKNOWN semantics, overdue versus attention, native
violation propagation, and authorization. Add literal positive and negative
controls through the same typed seams. For the second manager, compare the
materialized object ids and rendered data/links, not only counts.

### G3R2-2 — browser execution remains incomplete for the normative email contract

`tests/WeeklyFkrReport/weekly_report_browser.mjs:14-23` executes, but its parity
oracle only compares whether five registration numbers occur in each body. It
does not prove equal section order, row order, values, reasons, generated/period
text, or link targets between HTML and plain text. A renderer can reorder rows,
drop reason/value text, or change a link while satisfying the helper.

The helper also accepts links that merely have an HTTPS scheme without checking
the expected canonical targets, and it does not execute them or otherwise prove
the specified working-link behavior. It does not check that critical styles are
inline, that key cells have explicit foreground/background contrast, that
status meaning has a textual label independent of color, or that unsupported
content-hiding CSS and image-placeholder gaps are absent. The PHP substring
checks at lines 35-36 do not close those paths.

Make the browser oracle compare an independently fixed normalized sequence of
sections and row data for both bodies, validate exact canonical link targets,
and assert the remaining structural/accessibility rules. A deterministic local
navigation interception or equivalent exact href/target validation is enough;
the test must not contact an external system.

### G3R2-3 — the no-domain-mutation assertion is disconnected from the public seams

`tests/Jobs/weekly_fkr_report_delivery_001_test.php:20` hashes a local
`$domainFacts` array that neither the handler nor transport receives. It is
therefore unchanged regardless of implementation behavior and cannot prove A12.
The existing outbox GREEN evidence proves durable delivery facts, but does not
connect this new report generation/build/replay/failure workflow to a domain
snapshot.

Capture a test-owned domain snapshot through the report source (or another
public read boundary backed by the fixture) before and after generation,
replay, failure, and delivery outcomes. Compare the complete relevant domain
facts while allowing only the expected Jobs/Outbox operational history.

## Exact reviewed digests

```text
592ba949bc78715b5e95c8b36b09bdc4fc1b7afc6ebbaca7b4106fbb517b77dd  specs/WEEKLY-FKR-ATTENTION-EMAIL-001.md
361df250fffc2119fb6ee1cefbb2171b397a4f8dcb5376c75131f5d5c080069b  tests/WeeklyFkrReport/weekly_schedule_001_test.php
aa1c52924dcc4fbf57ac47e7a1d6f1b742ac068c759a327acb90eaa1e5d50071  tests/WeeklyFkrReport/weekly_report_001_test.php
3d718124d99c1af743519f38d0d90d107ea758745f1e5a2226f2789df299b39d  tests/WeeklyFkrReport/weekly_report_browser.mjs
39ddbefe2b6b40bc2a8d7a0877013541b6046872fb68f3fd62ce2cc1727e1c66  tests/Jobs/weekly_fkr_report_delivery_001_test.php
1533be09ffc51a7ee6e0948e7343e4c0a1698d9fe1d5dbdd0f9c05cf2df4d8a8  tests/Runtime/weekly_fkr_smtp_configuration_001_test.php
0a1a1f10310601390627d5135587498fa49511fa6f288b8d3b40a69d3c468649  prepared package.json
```

## Re-review verdict

**CHANGES_REQUESTED**

The corrected source closes fixture reachability and properly reuses durable
outbox/concurrency coverage, but the report matrix, browser oracle, and A12
mutation proof still admit foreseeable non-conforming implementations. Gate 4
is not authorized for exact source
`c68ba098cd30a98e2602d02101dcc961b627a11be64f99c13fd80e28a6e854cf`.

---

# Re-review 3 — rebuilt acceptance matrix, exact source `b3708a12`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_gate3_r3`
- Independence: the reviewer authored none of the specification, OpenSpec
  artifacts, tests, production code, or evidence
- Exact candidate source:
  `b3708a12504009145d50c7e97182b18d7833d25c1eb4233fe5d2679a70bacea8`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Prepared reviewer package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T215721Z-b1222ad2ba/package.json`
- Owner decision applied: every eligible active FKR manager has the same
  complete global `objects.read` object set; per-manager scope is not required
- Verdict: **CHANGES_REQUESTED**

This section is append-only. The reviewer changed no specification, OpenSpec
artifact, test, fixture, inventory, production file, or evidence record.

## Evidence and prior-finding disposition

Every source and evidence record in the prepared exact-source package was
reviewed. The four mapped tests retain exact-source `INTENDED_RED` plus separate
read-only `FIXTURE_REACHABLE` records, and the existing outbox lifecycle and
two-process concurrency tests retain exact-source GREEN records. Prior G3-1 and
G3-4 remain **fixed** under the composition decision recorded in Re-review 2.

The rebuilt report matrix adds a manager without `objects.read`, a missing-email
recipient, before/after date controls, a corrected plan date, an UNKNOWN
progress boundary, an attention UNKNOWN row, an active closing violation, and
exact per-section object-id equality for the second manager. These changes fix
the permissions and owner-decided identical-global-set portions of G3R2-1.
They do not complete the correction and exclusion matrix, so G3R2-1 remains
open as G3R3-1.

The browser helper now proves a standalone document, rejects two content-hiding
properties, checks section order, and observes at least one styled and
contrasted cell. These are useful improvements, but the equivalence, target,
and per-element structural or accessibility gaps remain; G3R2-2 remains open
as G3R3-2.

The new hashes are closer to the relevant flows, but neither exposes mutable
domain storage through the public seam nor spans the required delivery
outcomes. G3R2-3 remains open as G3R3-3.

## Blocking findings

### G3R3-1 — correction and completed-action behavior is still untested

`tests/WeeklyFkrReport/weekly_report_001_test.php:9-36` materially improves the
date and UNKNOWN matrix, but still has no corrected append-only progress-history
case. A builder that ignores effective progress corrections can pass. It also
has no completed action with a past plan date and no completed action inside
the attention window, so implementations that include completed work in
`Просрочка` or `Обратить внимание` can pass. The current completed rows do not
exercise either exclusion: their relevant dates are null or today's ready
closing.

Add literal corrected-progress as-of input and completed overdue/attention
negative controls through `WeeklyFkrReportSource`, with exact section IDs and
values asserted. Preserve the now-covered permission, global-set, period-edge,
UNKNOWN, readiness, and violation cases.

### G3R3-2 — HTML/text equivalence and canonical-link behavior remain weak

`tests/WeeklyFkrReport/weekly_report_browser.mjs:22-25` checks HTML section
heading order, then only compares the presence of selected tokens in HTML and
text. It does not compare the complete normalized section/row sequence,
per-row values and reasons, generated/period text, or link targets. Rows or
reasons can be reordered or duplicated in one body, and arbitrary HTTPS targets
pass line 23. The PHP check at
`tests/WeeklyFkrReport/weekly_report_001_test.php:41` fixes only one HTML href
and proves neither the full canonical target set nor plain-text targets.

The structural checks at browser lines 20 and 24 are existential: one attributed
cell, one inline-styled cell, and one contrasted cell satisfy them. They do not
prove explicit width/align/valign and critical inline CSS where required,
foreground/background contrast on key cells, or textual status labels
independent of color. The hiding check rejects only `display:none` and
`visibility:hidden`, leaving other straightforward hidden-content mutations
undetected.

Compare independently fixed normalized rows for both bodies in exact order,
including expected canonical hrefs/text URLs, values, reasons, periods, and
generated timestamp. Assert the required attributes/styles/contrast/status text
on every applicable element and extend the deterministic no-hidden-content
oracle. No external navigation is required.

### G3R3-3 — A12 fingerprints still cannot observe mutations by the seams

At `tests/WeeklyFkrReport/weekly_report_001_test.php:20-22`, `$objects` is hashed
outside a source constructed with a by-value private array. The builder receives
only arrays returned by value, so mutation of either a builder-local result or
the source's private storage is not observable in the hash at line 50.

At `tests/Jobs/weekly_fkr_report_delivery_001_test.php:17-20`, `$domainFacts` is
captured by the report-producing closure, but the handler is never given a
mutable domain repository or write seam. The assertion therefore shows only
that the test closure did not mutate its own capture. It is taken after
generation/replay, but not around delivered, transient, permanent, UNKNOWN, or
overflow paths, which use a separate transport fixture.

Back the report source with test-owned inspectable storage (or expose a
read-only fingerprint from the same public boundary), fingerprint the complete
relevant facts before and after build/render/replay, and connect the same
snapshot to delivered, transient, permanent, UNKNOWN, and size-failure paths.
Only Jobs/Outbox operational history may differ.

## Exact reviewed digests

```text
592ba949bc78715b5e95c8b36b09bdc4fc1b7afc6ebbaca7b4106fbb517b77dd  specs/WEEKLY-FKR-ATTENTION-EMAIL-001.md
361df250fffc2119fb6ee1cefbb2171b397a4f8dcb5376c75131f5d5c080069b  tests/WeeklyFkrReport/weekly_schedule_001_test.php
7c011a918cc55c157015d0d7efe3ce2562101e7aa144f456e75061533393c5f7  tests/WeeklyFkrReport/weekly_report_001_test.php
fbd7fc40bf7fd38aa00b7f36959a78777d30dd01ee8ed52f5f394facad79f260  tests/WeeklyFkrReport/weekly_report_browser.mjs
81b19a21c2ec0e4baf0c4ea092c7a1e5e781e4dde6925eab1617bc619122f632  tests/Jobs/weekly_fkr_report_delivery_001_test.php
1533be09ffc51a7ee6e0948e7343e4c0a1698d9fe1d5dbdd0f9c05cf2df4d8a8  tests/Runtime/weekly_fkr_smtp_configuration_001_test.php
4fd8dc5cd37cd85c934239b69efad737e04c57a5c950f6176cb23da50fef6ce4  openspec/changes/weekly-fkr-attention-email/proposal.md
0594972473755e6c4256026185fde78cc46640df3a0f5110b2be93a1633ede62  openspec/changes/weekly-fkr-attention-email/design.md
ef4cb86d135120f0bf961e3a30a867042aa8c002031f299ad47b1546ac65f1a3  openspec/changes/weekly-fkr-attention-email/specs/weekly-fkr-attention-email/spec.md
09c017e31713e58a730453762b6956d70412ffa8c8ad70e1d6d27b2a9c7170f4  openspec/changes/weekly-fkr-attention-email/tasks.md
18ce3ac01a419d1b71375d775c72239af9891949898b9bf47dcc9becdcef3117  prepared package.json
df9aad003140320cd28972c4699157670d98a633ccf460523eeaa2c50ca648af  required-context.json
3414097876d2306d43479f070398258d076d57155d8204dbc34fd0f72ac74787  task-context-manifest.json
1b8700cf3d17e8f5efbb2fa9e5d89d0bb0559339dfd75efec0e1b4a3b1ca0351  verification-plan.json
```

`reviews/tests/WEEKLY-FKR-ATTENTION-EMAIL-001.md` is metadata and is not
self-hashed.

## Re-review verdict

**CHANGES_REQUESTED**

The rebuild closes permissions, exact global-object identity, several calendar
edges, UNKNOWN/violation examples, and part of the browser structure. It still
admits foreseeable regressions in corrected progress history, completed-action
exclusions, full HTML/text order/data/reason/link parity, canonical targets,
per-element email constraints, and domain mutation. Gate 4 is not authorized
for exact source
`b3708a12504009145d50c7e97182b18d7833d25c1eb4233fe5d2679a70bacea8`.

---

# Re-review 4 — full rebuilt matrix, exact source `71be6577`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_gate3_r4`
- Independence: the reviewer authored none of the specification, OpenSpec artifacts, tests, production code, or evidence
- Exact candidate source: `71be657752f17792821cd63d9d719ae9343cb7bb067abcc77a15f3ed27c3182f`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T220504Z-5ec3240981/package.json`
- Owner decision: all eligible active FKR managers receive the same complete global `objects.read` object set
- Verdict: **CHANGES_REQUESTED**

This section is append-only. The reviewer changed no specification, OpenSpec artifact, test, fixture, inventory, production file, or evidence record.

## Evidence and prior-finding disposition

Every source and all ten evidence records in the exact-source package were reviewed. The four new acceptance tests retain exact-source `INTENDED_RED` and separate `FIXTURE_REACHABLE` records. Existing outbox lifecycle and two-process concurrency tests retain exact-source GREEN records. Prior G3-1 and G3-4 remain **fixed**.

The report fixture now includes corrected append-only progress history and a completed object with past opening and closing dates. Exact assertions observe the corrected 85/15 boundary and exclude the completed object from overdue and attention. Prior G3R3-1 is **fixed**.

The browser helper now requires width/align/valign, inline style, and padding on every `td`, checks ordered tokens in both bodies, and checks two exact canonical links. These are material improvements, but the manifest is not exhaustive and the contrast/status/hiding checks remain incomplete. G3R3-2 remains open as G3R4-1.

The report source now exposes an inspectable fingerprint, so build/render mutation is observable through the owning source seam. Delivery checks fingerprint recipient state around transient, unknown, permanent, and revoked-recipient outcomes. But the domain projection is connected only to generation/replay; delivery outcomes use a separate transport fixture with no access to it. G3R3-3 is **partially fixed** and remains open as G3R4-2.

## Blocking findings

### G3R4-1 — the browser manifest still permits divergent bodies and links

At `tests/WeeklyFkrReport/weekly_report_001_test.php:49`, `parity.ordered` enumerates rows only for the two plan sections. It names no progress, overdue, or attention rows after their headings. `parity.values` is a small selection of reasons and the generated timestamp; it omits report-period text, most row values/reasons, and section-specific duplicate occurrences. `parity.links` contains only A-1 and A-2 although many linked rows are materialized. HTML and text can therefore omit, duplicate, reorder, alter, or retarget unlisted rows while browser lines 26-28 remain GREEN. Successive `indexOf` token searches are not an independently fixed normalized section/row comparison and cannot distinguish a token in the wrong row or one occurrence satisfying multiple sections.

Browser line 25 also accepts one contrasted cell rather than explicit foreground/background on every key cell, and no assertion ties a visible textual label to each status/badge. The hiding oracle at line 11 covers only `display:none` and `visibility:hidden`; clipping, zero-size, opacity, off-canvas, or hidden-attribute regressions remain admissible. Use a complete independently fixed normalized manifest for every section and row in both bodies, including all values, reasons, period/generated text, and canonical targets; assert contrast and textual status per applicable element and complete deterministic no-hidden-content checks.

### G3R4-2 — domain mutation is not observed across delivery outcomes

`tests/Jobs/weekly_fkr_report_delivery_001_test.php:17-20` connects `$domainFacts` to the report-producing closure and fingerprints it only around generation/replay. Delivered, transient, unknown, permanent, recipient-ineligible, and overflow paths at lines 9-16 and 21 use independent `SmtpTransport` instances not connected to that projection; their fingerprints cover only `$recipient`. A transport or orchestration implementation that mutates installation/progress/event facts during delivery settlement can pass A12. Existing outbox GREEN evidence proves the technical lifecycle but does not supply this report's domain projection to those outcomes.

Use one inspectable domain source/repository fingerprint shared by generation and each delivery outcome (delivered, transient, permanent, unknown, revoked, and size rejection). Compare complete relevant facts before and after each path while allowing only Jobs/Outbox operational history to change.

## Exact reviewed digests

```text
592ba949bc78715b5e95c8b36b09bdc4fc1b7afc6ebbaca7b4106fbb517b77dd  specs/WEEKLY-FKR-ATTENTION-EMAIL-001.md
361df250fffc2119fb6ee1cefbb2171b397a4f8dcb5376c75131f5d5c080069b  tests/WeeklyFkrReport/weekly_schedule_001_test.php
3bb9463174a17a1b3d2a38bd18e8b5c8e19e7eaf9afd16445ba30b96777c4214  tests/WeeklyFkrReport/weekly_report_001_test.php
12c0e194551fba22dc3523c796db5aa7728da76dfc05ee1d2e27a95fb7237f46  tests/WeeklyFkrReport/weekly_report_browser.mjs
188a856f2dc27e49f416205edc3f9cc2236e7c392715d370a89be421be02fcb0  tests/Jobs/weekly_fkr_report_delivery_001_test.php
1533be09ffc51a7ee6e0948e7343e4c0a1698d9fe1d5dbdd0f9c05cf2df4d8a8  tests/Runtime/weekly_fkr_smtp_configuration_001_test.php
4fd8dc5cd37cd85c934239b69efad737e04c57a5c950f6176cb23da50fef6ce4  openspec/changes/weekly-fkr-attention-email/proposal.md
0594972473755e6c4256026185fde78cc46640df3a0f5110b2be93a1633ede62  openspec/changes/weekly-fkr-attention-email/design.md
ef4cb86d135120f0bf961e3a30a867042aa8c002031f299ad47b1546ac65f1a3  openspec/changes/weekly-fkr-attention-email/specs/weekly-fkr-attention-email/spec.md
09c017e31713e58a730453762b6956d70412ffa8c8ad70e1d6d27b2a9c7170f4  openspec/changes/weekly-fkr-attention-email/tasks.md
2ac52ef84dd1fad989a0f018bee1ebc65c5e56d81e473a5c71aa306d704c83cc  prepared package.json
df9aad003140320cd28972c4699157670d98a633ccf460523eeaa2c50ca648af  required-context.json
bf65058480d697c1a4c2262acaeb55ed2d3c96fd367daeede3bf6617405cb110  task-context-manifest.json
7ecf70df278f1880ef2c8da2734d3542513806f9430d1c0e6fe706bda6b2ce4e  verification-plan.json
```

`reviews/tests/WEEKLY-FKR-ATTENTION-EMAIL-001.md` is metadata and is not self-hashed.

## Re-review verdict

**CHANGES_REQUESTED**

The rebuild fixes corrected-history and completed-action behavior and makes source mutation observable during build/render. It still admits foreseeable regressions in complete HTML/text row/value/reason/order/link equivalence, per-element accessibility/visibility structure, and domain immutability across delivery outcomes. Gate 4 is not authorized for exact source `71be657752f17792821cd63d9d719ae9343cb7bb067abcc77a15f3ed27c3182f`.

---

# Re-review 5 — exhaustive render inventory, exact source `3a3d4da1`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_gate3_r5`
- Independence: the reviewer authored none of the specification, OpenSpec artifacts, tests, production code, or evidence
- Exact candidate source: `3a3d4da134d847a618df9e4a08477d84e8e5ced53a4fd90dfe2061b4f1ddfde4`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T221034Z-9ddbf359a0/package.json`
- Owner decision: all eligible active FKR managers receive the same complete global `objects.read` object set
- Verdict: **CHANGES_REQUESTED**

This section is append-only. The reviewer changed no specification, OpenSpec artifact, test, fixture, inventory, production file, or evidence record.

## Evidence and prior-finding disposition

Every source and all ten evidence records in the exact-source package were reviewed. The four acceptance tests retain exact-source `INTENDED_RED` and separate read-only `FIXTURE_REACHABLE` records. Existing outbox lifecycle and two-process concurrency tests retain exact-source GREEN records. Prior G3-1, G3-4, and G3R3-1 remain **fixed**.

The browser manifest now fixes the exact object order for every section and lists the canonical target for every object materialized in the report. Its literal inventory covers the fixture's addresses, registration numbers, important reasons and values, and generated timestamp. This fixes the row-order and canonical-link portions of G3R4-1. The per-element contrast and comprehensive hiding portions remain open as G3R5-1.

Every delivery callback now captures the same `$domainFacts` projection, and the final assertion spans delivered, transient, unknown, permanent, revoked, overflow, generation, and replay execution. This connects the projection to all required paths, fixing the disconnected-callback portion of G3R4-2. Because the domain fingerprint is checked only after the entire sequence, outcome-specific mutation sensitivity remains open as G3R5-2.

## Blocking findings

### G3R5-1 — contrast and no-hiding assertions are not exhaustive

`tests/WeeklyFkrReport/weekly_report_browser.mjs:17,21,25,29` requires every `td` to have padding, width/align/valign, and an inline `style`, and checks every `[data-status-label]` for visible label text and non-transparent computed colors. However, foreground/background contrast outside status nodes remains existential: `contrastedCells > 0` passes even if every other key cell has no explicit foreground/background or unreadable colors. The test also checks only non-transparency, not actual contrast, so equal foreground and background on a status node passes.

The static hiding ban at browser line 11 rejects six CSS spellings but still admits deterministic hidden-content mutations such as the HTML `hidden` attribute, `clip`/`clip-path`, zero width/height, off-canvas positioning or transforms, and clipped zero-area overflow. These are exactly the unsupported-CSS/content-hiding regressions the normative contract says must not erase content. Assert explicit foreground/background and a sufficient contrast ratio for every applicable key/status cell, and reject or geometrically detect the complete supported set of hidden/zero-area/off-canvas content mutations.

### G3R5-2 — domain facts are not fingerprinted after each delivery outcome

`tests/Jobs/weekly_fkr_report_delivery_001_test.php:9-21` lets delivered, transient, unknown, permanent, revoked, overflow, generation, and replay paths observe the same `$domainFacts`, but compares `$domainBefore` only once at line 20 for generation/replay and once at line 21 after the entire delivery sequence. The intermediate assertions fingerprint only `$recipient`.

An implementation can therefore mutate domain facts during transient or unknown settlement and restore or overwrite them during a later outcome while the test remains GREEN. It can likewise mutate during delivered and restore on the first direct attempt. Capture and compare the complete domain fingerprint immediately after each independently invoked path: delivered, transient, unknown, permanent, revoked/ineligible, overflow, generation, and replay. This preserves the connected callback proof while making each A12 outcome sensitive on its own.

## Exact reviewed digests

```text
592ba949bc78715b5e95c8b36b09bdc4fc1b7afc6ebbaca7b4106fbb517b77dd  specs/WEEKLY-FKR-ATTENTION-EMAIL-001.md
361df250fffc2119fb6ee1cefbb2171b397a4f8dcb5376c75131f5d5c080069b  tests/WeeklyFkrReport/weekly_schedule_001_test.php
fae86c609fa1c967e9795d5dd165c30e117688132803cd41572ac17334456832  tests/WeeklyFkrReport/weekly_report_001_test.php
fab931d7ecef7a667db0ecda6eade480c6a4b4fc9fa3f2f62c575ed29ac2b44d  tests/WeeklyFkrReport/weekly_report_browser.mjs
18264d33768656a0f9f33ecf5b813dbe76495a299b567b855b9a8153df22185e  tests/Jobs/weekly_fkr_report_delivery_001_test.php
1533be09ffc51a7ee6e0948e7343e4c0a1698d9fe1d5dbdd0f9c05cf2df4d8a8  tests/Runtime/weekly_fkr_smtp_configuration_001_test.php
4fd8dc5cd37cd85c934239b69efad737e04c57a5c950f6176cb23da50fef6ce4  openspec/changes/weekly-fkr-attention-email/proposal.md
0594972473755e6c4256026185fde78cc46640df3a0f5110b2be93a1633ede62  openspec/changes/weekly-fkr-attention-email/design.md
ef4cb86d135120f0bf961e3a30a867042aa8c002031f299ad47b1546ac65f1a3  openspec/changes/weekly-fkr-attention-email/specs/weekly-fkr-attention-email/spec.md
09c017e31713e58a730453762b6956d70412ffa8c8ad70e1d6d27b2a9c7170f4  openspec/changes/weekly-fkr-attention-email/tasks.md
1a313584e1d8d42a2e539fa2526f1adb1a1c16d60658d3d7363e9e7c956a6331  prepared package.json
df9aad003140320cd28972c4699157670d98a633ccf460523eeaa2c50ca648af  required-context.json
7648554c9d7a3de151ee25057d7c30f9540d968acf0d19d5c081a27c1fe28db5  task-context-manifest.json
ec4020fd0e57f442a0ac9c28e74ac0bb57f501efc068bb6cf2057bb11e430d26  verification-plan.json
```

`reviews/tests/WEEKLY-FKR-ATTENTION-EMAIL-001.md` is metadata and is not self-hashed.

## Re-review verdict

**CHANGES_REQUESTED**

The rebuilt tests now fix exhaustive per-section object order, every rendered canonical object link, and shared domain-projection reachability across all delivery paths. They still admit foreseeable regressions in per-cell/status contrast, hidden or zero-area content, and outcome-specific domain mutation. Gate 4 is not authorized for exact source `3a3d4da134d847a618df9e4a08477d84e8e5ced53a4fd90dfe2061b4f1ddfde4`.

---

# Re-review 6 — visibility/contrast and per-path fingerprints, exact source `3e988133`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_gate3_r5`
- Independence: the reviewer authored none of the specification, OpenSpec artifacts, tests, production code, or evidence
- Exact candidate source: `3e988133e66b01c16fa1d130fda5981d561aa1537572cad0a68ceda20970b233`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T221456Z-d446afc482/package.json`
- Verdict: **CHANGES_REQUESTED**

This section is append-only. The reviewer changed no specification, OpenSpec artifact, test, fixture, inventory, production file, or evidence record.

## Evidence and prior-finding disposition

Every source and all ten evidence records in the exact-source package were reviewed. The four acceptance tests retain exact-source `INTENDED_RED` and separate read-only `FIXTURE_REACHABLE` records. Existing outbox lifecycle and two-process concurrency tests retain exact-source GREEN records.

`weekly_report_browser.mjs` now computes relative luminance and enforces WCAG contrast ratio `>= 4.5` for every status node, along with positive geometry and viewport presence. That fixes the status-contrast portion of G3R5-1. Its static ban is broader, but geometric visibility remains status-only, leaving G3R5-1 partially open as G3R6-1.

`weekly_fkr_report_delivery_001_test.php` now compares the connected domain fingerprint immediately after delivered, transient, unknown, permanent, revoked and overflow outcomes. Those portions of G3R5-2 are fixed. Generation and replay are still executed together before their first fingerprint, leaving G3R5-2 partially open as G3R6-2.

## Blocking findings

### G3R6-1 — hidden/off-canvas/zero-area detection is not comprehensive for report content

`tests/WeeklyFkrReport/weekly_report_browser.mjs:11` adds useful static checks for `hidden`, clipping, some zero-size declarations and negative `left`/`top`. But width/height zero, transforms, positive off-canvas positioning, and other computed zero-area or viewport-excluded content remain admissible. More importantly, browser line 29 performs computed geometry/visibility checks only on `[data-status-label]`. A renderer can hide an entire non-status row, reason, value, or link with computed zero area or off-canvas layout while retaining it in the DOM and satisfying the object-id/link/token or `innerText` checks.

Apply computed visibility, positive-area and viewport-presence checks to every materialized report row and its required value/reason/link content (or to an equivalent exhaustive manifest target set), not only statuses. Keep the now-correct per-status WCAG ratio check.

### G3R6-2 — generation is not fingerprinted before replay

At `tests/Jobs/weekly_fkr_report_delivery_001_test.php:20-21`, the first generation and replay calls run consecutively and only then invoke `$assertDomain('generation and replay')`. A mutation during initial generation that replay restores or overwrites remains invisible. The third handler invocation at line 23 has an immediate comparison, but it is another replay and does not repair the missing post-generation observation.

Compare the connected domain fingerprint immediately after the first `handle` call and again immediately after each replay call. This completes the requested independent delivered/transient/unknown/permanent/revoked/generation/replay/overflow path sensitivity.

## Exact reviewed digests

```text
592ba949bc78715b5e95c8b36b09bdc4fc1b7afc6ebbaca7b4106fbb517b77dd  specs/WEEKLY-FKR-ATTENTION-EMAIL-001.md
361df250fffc2119fb6ee1cefbb2171b397a4f8dcb5376c75131f5d5c080069b  tests/WeeklyFkrReport/weekly_schedule_001_test.php
fae86c609fa1c967e9795d5dd165c30e117688132803cd41572ac17334456832  tests/WeeklyFkrReport/weekly_report_001_test.php
7db5ed3a5df7048468b121d77cd156ac95a75023e64f456c8342fb6a6a8f4873  tests/WeeklyFkrReport/weekly_report_browser.mjs
cd7398cb9aa40ab4c158e100de5a5c0cdac6e35596b3c866c5250c1fcfc3547c  tests/Jobs/weekly_fkr_report_delivery_001_test.php
1533be09ffc51a7ee6e0948e7343e4c0a1698d9fe1d5dbdd0f9c05cf2df4d8a8  tests/Runtime/weekly_fkr_smtp_configuration_001_test.php
4fd8dc5cd37cd85c934239b69efad737e04c57a5c950f6176cb23da50fef6ce4  openspec/changes/weekly-fkr-attention-email/proposal.md
0594972473755e6c4256026185fde78cc46640df3a0f5110b2be93a1633ede62  openspec/changes/weekly-fkr-attention-email/design.md
ef4cb86d135120f0bf961e3a30a867042aa8c002031f299ad47b1546ac65f1a3  openspec/changes/weekly-fkr-attention-email/specs/weekly-fkr-attention-email/spec.md
09c017e31713e58a730453762b6956d70412ffa8c8ad70e1d6d27b2a9c7170f4  openspec/changes/weekly-fkr-attention-email/tasks.md
28ce448b117645604dfa5a855902485ece85a8ec6288fc3fce9bcf636c6779b2  prepared package.json
df9aad003140320cd28972c4699157670d98a633ccf460523eeaa2c50ca648af  required-context.json
b028f492a23b21e7850d39118be0b9c61a7be643ae0ba63d761ecbf3636c18c0  task-context-manifest.json
de6c08356a65e53165de7ceb3458df828a4b8cf179cd2787d9579fbd231d2579  verification-plan.json
```

`reviews/tests/WEEKLY-FKR-ATTENTION-EMAIL-001.md` is metadata and is not self-hashed.

## Re-review verdict

**CHANGES_REQUESTED**

The corrections establish computed WCAG contrast for every status and immediate connected-domain checks for each delivery outcome. They do not yet comprehensively detect hidden/zero-area/off-canvas non-status report content, and generation remains unobserved until after replay. Gate 4 is not authorized for exact source `3e988133e66b01c16fa1d130fda5981d561aa1537572cad0a68ceda20970b233`.

---

# Re-review 7 — complete visibility and path isolation, exact source `61f84cae`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_gate3_r5`
- Independence: the reviewer authored none of the specification, OpenSpec artifacts, tests, production code, or evidence
- Exact candidate source: `61f84cae89988787d34c7ca8167ddf96a5860fdecf2cca759521bd3a838f85cc`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T221757Z-276cc0e655/package.json`
- Verdict: **APPROVED**

This section is append-only. The reviewer changed no specification, OpenSpec artifact, test, fixture, inventory, production file, or evidence record.

## Evidence and final disposition

Every package source and all ten exact-source evidence records were reviewed. The four acceptance tests have exact-source `INTENDED_RED` and separate read-only `FIXTURE_REACHABLE` evidence. The existing durable outbox lifecycle and two-process concurrency tests have exact-source GREEN evidence. All prior blockers remain fixed, and the final two blockers are closed.

G3R6-1 is **fixed**. `tests/WeeklyFkrReport/weekly_report_browser.mjs:11` rejects hidden attributes, zero width/height/max dimensions, clipping, transforms and off-canvas declarations in all four directions. Browser line 28 also evaluates computed display, visibility, opacity, positive area and viewport presence for every report section, materialized row, heading and link at both 680px and 320px. Line 30 retains computed WCAG contrast `>= 4.5`, visible text, positive geometry and viewport presence for every status node.

G3R6-2 is **fixed**. `tests/Jobs/weekly_fkr_report_delivery_001_test.php:13,17-18,20,22-23` compares the same connected domain fingerprint immediately after delivered, transient, unknown, permanent, revoked, initial generation, first replay, second replay and overflow paths. No path can rely on a later path restoring a mutation.

The complete suite remains internally consistent with the normative contract and prior owner decision: exhaustive per-section object order and rendered canonical links, hardcoded observable values/reasons/generated time, per-status contrast, deterministic no-hiding checks, native report seams, correction and UNKNOWN behavior, delivery lifecycle composition, concurrency reuse, and no-domain-mutation sensitivity are all represented without weakening earlier coverage.

## Exact reviewed digests

```text
592ba949bc78715b5e95c8b36b09bdc4fc1b7afc6ebbaca7b4106fbb517b77dd  specs/WEEKLY-FKR-ATTENTION-EMAIL-001.md
361df250fffc2119fb6ee1cefbb2171b397a4f8dcb5376c75131f5d5c080069b  tests/WeeklyFkrReport/weekly_schedule_001_test.php
fae86c609fa1c967e9795d5dd165c30e117688132803cd41572ac17334456832  tests/WeeklyFkrReport/weekly_report_001_test.php
ce87b9164bf8f78dad96bdd1a5539d263b5b1ca847b8017cf9337dc454ab9b08  tests/WeeklyFkrReport/weekly_report_browser.mjs
950c8b4d781af6811319e64beccc2dc9f93f1fb984eb7e1243b127c7bf3028c0  tests/Jobs/weekly_fkr_report_delivery_001_test.php
1533be09ffc51a7ee6e0948e7343e4c0a1698d9fe1d5dbdd0f9c05cf2df4d8a8  tests/Runtime/weekly_fkr_smtp_configuration_001_test.php
4fd8dc5cd37cd85c934239b69efad737e04c57a5c950f6176cb23da50fef6ce4  openspec/changes/weekly-fkr-attention-email/proposal.md
0594972473755e6c4256026185fde78cc46640df3a0f5110b2be93a1633ede62  openspec/changes/weekly-fkr-attention-email/design.md
ef4cb86d135120f0bf961e3a30a867042aa8c002031f299ad47b1546ac65f1a3  openspec/changes/weekly-fkr-attention-email/specs/weekly-fkr-attention-email/spec.md
09c017e31713e58a730453762b6956d70412ffa8c8ad70e1d6d27b2a9c7170f4  openspec/changes/weekly-fkr-attention-email/tasks.md
962fa58cc8a619b40c348f6dbe7467e51051a6f5937aee452c9d410037c0361a  prepared package.json
df9aad003140320cd28972c4699157670d98a633ccf460523eeaa2c50ca648af  required-context.json
f288a422ecd23064bc154a9c501549c2e2f7205fcdff9d5edb31467a87eebaab  task-context-manifest.json
67b93bff44acd9ce10aebaadc13e028b56c466e55865ae3d78a461d9ef4aa6ee  verification-plan.json
```

`reviews/tests/WEEKLY-FKR-ATTENTION-EMAIL-001.md` is metadata and is not self-hashed.

## Re-review verdict

**APPROVED**

Gate 3 is approved for exact source `61f84cae89988787d34c7ca8167ddf96a5860fdecf2cca759521bd3a838f85cc`. This approval is limited to the reviewed exact source and does not substitute for Gate 4 implementation evidence, required exact-source CI, or independent final review.

---

# Post-Gate-3 test-delta review — exact source `583f6bf0`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_test_delta`
- Independence: the reviewer authored none of the specification, OpenSpec
  artifacts, tests, production code, or prior evidence
- Approved Gate 3 source:
  `61f84cae89988787d34c7ca8167ddf96a5860fdecf2cca759521bd3a838f85cc`
- Current exact source:
  `583f6bf01bc87fbb2c549cc4b309785ec34866dbe2a66c1ede520f2f7963b7db`
- Approved package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T221757Z-276cc0e655/package.json`
- Current package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T223217Z-84cf3a934f/package.json`
- Review scope: only the root-authored delta in
  `tests/WeeklyFkrReport/weekly_report_001_test.php` and
  `tests/WeeklyFkrReport/weekly_report_browser.mjs`
- Verdict: **APPROVED**

This section is append-only. The reviewer changed no specification, OpenSpec
artifact, test, fixture, inventory, or production file.

## Delta assessment

The A12 seam-fingerprint assertion retains the approved expected value and now
runs immediately after the production build/render behavior, before the test
adds its own `wrong-role` and `bad-email` entries to the referenced recipient
fixture. This removes a setup/test-order false failure without reducing the
assertion's sensitivity to production mutation. The separate final fingerprint
of the connected source object facts remains unchanged after all negative
recipient cases.

The browser helper still imports and exercises Playwright's `chromium` API, but
resolves the module through `createRequire(import.meta.url)` from the explicit
path written into the parity manifest. The default is the repository-standard
sibling `shlz-ui/node_modules/playwright`; `FMONITOR_TEST_PLAYWRIGHT_MODULE`
allows an explicit host-test override. No viewport, parity, link, visibility,
geometry, contrast, capture, or forbidden-content assertion was removed or
relaxed.

Direct focused host verification is GREEN:

```text
$ php tests/WeeklyFkrReport/weekly_report_001_test.php
PASS: WEEKLY-FKR-ATTENTION-EMAIL-001 scoped report and image-free Outlook/web render
```

## Reviewed delta digests

```text
fae86c609fa1c967e9795d5dd165c30e117688132803cd41572ac17334456832  approved tests/WeeklyFkrReport/weekly_report_001_test.php
71106a73d6e53941b1ed7455ee947ed318bed94f64b62fe2831e5902190d1898  current  tests/WeeklyFkrReport/weekly_report_001_test.php
ce87b9164bf8f78dad96bdd1a5539d263b5b1ca847b8017cf9337dc454ab9b08  approved tests/WeeklyFkrReport/weekly_report_browser.mjs
49ba3f0aa311022df740774163186bcb4b0eb93a605ca15bee0dd64d9b9f7b6e  current  tests/WeeklyFkrReport/weekly_report_browser.mjs
```

## Test-delta verdict

**APPROVED**

The two bounded corrections fix the observed setup/host-resolution failures
without weakening the Gate 3-approved behavior. This delta approval is limited
to exact source `583f6bf01bc87fbb2c549cc4b309785ec34866dbe2a66c1ede520f2f7963b7db`
and does not substitute for implementation evidence, exact-source CI, or the
independent final review.

---

# Test-delta 2 review — exact source `33c09f5f`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_test_delta`
- Independence: the reviewer authored none of the specification, OpenSpec
  artifacts, tests, production code, or prior evidence
- Previously approved test-delta source:
  `583f6bf01bc87fbb2c549cc4b309785ec34866dbe2a66c1ede520f2f7963b7db`
- Current exact source:
  `33c09f5fd3ceacd60ee2024e24bf87ae7d4ea98ba20f37d396934056877db67d`
- Current package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T225516Z-f529bce9f7/package.json`
- Review scope: only the progress-section browser-order expectation in
  `tests/WeeklyFkrReport/weekly_report_001_test.php`
- Verdict: **APPROVED**

This section is append-only. The reviewer changed no specification, OpenSpec
artifact, test, fixture, inventory, or production file.

## Delta assessment

The package-to-package scoped diff changes only the progress-section order from
`A-3, A-10, A-2` to `A-2, A-3, A-10`. All three rows use the same end of
`progressPeriod` as their section date, so the normative remaining sort keys
from the contract apply: bytewise registration number and then object id,
ascending. Their registration numbers are respectively `REG-001`, `REG-003`,
and `REG-010`, making the corrected order exact. The row inventory, values,
links, HTML/text parity checks, visibility checks, captures, and all other
behavioral assertions are unchanged; `weekly_report_browser.mjs` is
byte-identical to the previously approved delta.

Direct focused host verification is GREEN:

```text
$ php tests/WeeklyFkrReport/weekly_report_001_test.php
PASS: WEEKLY-FKR-ATTENTION-EMAIL-001 scoped report and image-free Outlook/web render
```

## Reviewed delta digests

```text
71106a73d6e53941b1ed7455ee947ed318bed94f64b62fe2831e5902190d1898  previous tests/WeeklyFkrReport/weekly_report_001_test.php
e96327cea0a6b52e5d13963909650b17e08ca1b43b6e063ae909cad363dae9a6  current  tests/WeeklyFkrReport/weekly_report_001_test.php
49ba3f0aa311022df740774163186bcb4b0eb93a605ca15bee0dd64d9b9f7b6e  both     tests/WeeklyFkrReport/weekly_report_browser.mjs
```

## Test-delta 2 verdict

**APPROVED**

The corrected progress order strengthens the browser oracle by aligning it
with the contract's universal stable sort and does not remove or relax any
coverage. This delta approval is limited to exact source
`33c09f5fd3ceacd60ee2024e24bf87ae7d4ea98ba20f37d396934056877db67d`
and does not substitute for exact-source CI or the independent final review.

---

# Unknown-delivery regression test-delta review — exact source `12add95d`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_unknown_gate3`
- Independence: the reviewer authored none of the specification, test,
  production implementation, or evidence
- Current exact source:
  `12add95d9b6f7c9dfa9bb4e5b489beb36fcd439749302dbab539b61b596ca641`
- Prepared package:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T230754Z-33a5df42c6/package.json`
- Review scope: the root-authored
  `tests/Jobs/weekly_fkr_unknown_delivery_001_test.php` regression and its
  production Jobs/Outbox ownership path
- Verdict: **CHANGES_REQUESTED**

This section is append-only. The reviewer changed no specification, test,
fixture, production file, or evidence record.

## Evidence and assessment

The regression calls the public production
`JobHandlerRuntime::mapEmailTransportOutcome()` seam and fixes the required
mapping from transport `unknown` to terminal `permanent/UNKNOWN_DELIVERY`. It
then uses the real `MariaDbOutbox`, `OutboxDispatchScheduler`,
`MariaDbJobQueue`, and `OutboxDeliveryHandler` owners. The assertions prove one
transport call, a dead outbox intent, a non-retryable dead queue job, and zero
subsequent sweep enqueue. Those observations correctly protect the A8 rule
that an unknown post-DATA outcome is not delivered and is not automatically
retried.

The current exact-source harness record is GREEN and the separate read-only
fixture probe is current:

```text
/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789945687258830000-c3b50079bec24d9eb9e668490e9a7b40.json
PASS: WEEKLY-FKR-ATTENTION-EMAIL-001 unknown delivery no-auto-retry

/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789945687258832000-5496bf46c87a469f953a95d6026f126d.json
FIXTURE_REACHABLE: weekly-unknown-delivery-fixture
```

## Blocking finding

### UDG3-1 — durable attempt persistence is not directly observable

The test checks the result returned by `OutboxDeliveryHandler::handle()` and
the final intent status, but it never reads
`fm2_outbox_attempt_events`. A plausible regression in
`MariaDbOutbox::recordAttempt()` that omits the attempt-event insert while
still updating the intent to `dead` and returning the supplied outcome would
leave every current assertion GREEN. That would violate the required durable
attempt-history ownership and weaken recovery/audit evidence for precisely the
ambiguous delivery case.

Add a direct assertion that exactly one attempt event exists for this intent
and leased job, with the expected attempt number, terminal outcome,
`UNKNOWN_DELIVERY` failure code, and stable idempotency reference. Preserve the
existing one-network-call and zero-resweep assertions.

## Retained RED limitation

The test was observed as an `INTENDED_RED` immediately after root authored it,
failing because `mapEmailTransportOutcome()` was absent, but the executor
implemented that method before a harness RED record was retained. Therefore
the package contains current exact-source GREEN and reachability evidence, not
a reconstructible harness RED record for this delta. This review does not
represent the later GREEN as RED evidence and cannot cure that historical
evidence limitation.

## Reviewed digests

```text
592ba949bc78715b5e95c8b36b09bdc4fc1b7afc6ebbaca7b4106fbb517b77dd  specs/WEEKLY-FKR-ATTENTION-EMAIL-001.md
fe6485aed5ba85ea2f1839b277a9cf2f4eb875d96d0fb220b368f90bcedb0285  tests/Jobs/weekly_fkr_unknown_delivery_001_test.php
4c0035b30b31facd0770c8d9eb001639040a71459b54da45bd73eee01a72a250  app/Jobs/JobHandlerRuntime.php
02a1013a60441e0ae30dc9503bcb18e840728bf9c4eed268f3d953c44fb63cb8  app/Jobs/OutboxDeliveryHandler.php
fd4b17662d979f4def2012d72b62bb2f816bbb8007b9ca7a984468f6e15c7eee  app/Jobs/MariaDbOutbox.php
7598e846a6ef2797dc624f5bb3300e18962d99b3a902623fb561cc5232a9a6d1  app/Jobs/MariaDbJobQueue.php
ada0a2349f5176f55fa2e72dabc42ec9eb6de8c2cfb8270c34f8a95ec05eb7a4  prepared package.json
```

## Test-delta verdict

**CHANGES_REQUESTED**

The regression correctly proves terminal no-auto-retry behavior through the
production owners, but it does not yet detect loss of the durable unknown-
delivery attempt record. Add that direct persistence assertion and obtain a
fresh independent test-delta review. This verdict is limited to exact source
`12add95d9b6f7c9dfa9bb4e5b489beb36fcd439749302dbab539b61b596ca641`
and does not decide exact-source CI or final review.

---

# Unknown-delivery regression correction re-review — exact source `b4dade99`

- Date: `2026-09-21`
- Reviewer: separately tasked agent `/root/issue11_unknown_gate3`
- Independence: the reviewer authored none of the specification, test,
  production implementation, or evidence
- Previous reviewed source:
  `12add95d9b6f7c9dfa9bb4e5b489beb36fcd439749302dbab539b61b596ca641`
- Corrected exact source:
  `b4dade99a41a69cdb681fe6c73401d88b6b4239660e875eefb49a5e22906fe40`
- Review scope: correction of UDG3-1 in
  `tests/Jobs/weekly_fkr_unknown_delivery_001_test.php`
- Verdict: **APPROVED**

This append-only correction review is the only reviewer edit. No
specification, test, fixture, production file, or evidence record was changed.

## Correction assessment

UDG3-1 is **fixed**. The regression now reads
`fm2_outbox_attempt_events` after the production handler returns and asserts
that the complete result set contains exactly one row. That row is tied to the
created intent and claimed job, fixes `attempt=1`, `outcome=permanent`, and
`failure_code=UNKNOWN_DELIVERY`, and compares its persisted
`idempotency_reference` with the exact reference observed at the transport
boundary. Omitting the durable insert, inserting duplicates, associating the
attempt with the wrong owner identities, or persisting a retryable/other
outcome now fails the test.

The previously reviewed sensitivity remains intact: the public production
mapper converts transport `unknown` to terminal
`permanent/UNKNOWN_DELIVERY`; the real Jobs/Outbox owners are exercised; the
queue job and intent become dead; the next sweep enqueues zero jobs; and the
transport call count remains exactly one.

Direct bounded verification is GREEN:

```text
$ php tests/Jobs/weekly_fkr_unknown_delivery_001_test.php
PASS: WEEKLY-FKR-ATTENTION-EMAIL-001 unknown delivery no-auto-retry
```

The active prepared package remains bound to the previous source `12add95d`;
this correction re-review is therefore scoped to the inspected source and
direct bounded result and does not claim a refreshed exact-source harness
record.

## Retained RED limitation

The original test was observed as `INTENDED_RED` before the executor added
`mapEmailTransportOutcome()`, but no harness RED record was retained before
that implementation. The later GREEN evidence and this correction re-review
do not substitute for or reconstruct that missing RED record. The historical
limitation remains explicitly acknowledged.

## Corrected test digest

```text
86ddd6cb04cc5669ad03f3c1b5a025ad1e3db76bcd0efa3fedb7312f7d60747b  tests/Jobs/weekly_fkr_unknown_delivery_001_test.php
```

## Correction verdict

**APPROVED**

The corrected regression now detects loss or corruption of the durable
unknown-delivery attempt while preserving the complete terminal no-auto-retry
oracle. Gate 3 test-delta approval is restored for exact source
`b4dade99a41a69cdb681fe6c73401d88b6b4239660e875eefb49a5e22906fe40`.
This approval does not substitute for a refreshed package/evidence binding,
exact-source CI, or independent final review.
