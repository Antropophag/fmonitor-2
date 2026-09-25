# BITRIX-DOCUMENTATION-INTEGRATION-STATUS-001 — Gate 5 final code review

- Date: 2026-09-25
- Reviewer: `/root/issue267_gate5` (independent; no specification, test, or production authorship)
- Base: `99bd0974150617a01e195cec28f7d886f1ede761`
- Exact candidate source: `00b79657ce18087df23942ebb16edb533bb16f23a1b889b63f44310cab39d9f9`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T001507Z-bfc7617249/package.json`
- Contract: `specs/BITRIX-DOCUMENTATION-INTEGRATION-STATUS-001.md` (`a3a55317d96fb14d6c3dee659ec36c3b5f557f49ac9fdaaaeb4eaaf723fcf744`)
- Gate 3 record: `reviews/tests/BITRIX-DOCUMENTATION-INTEGRATION-STATUS-001.md`
- Current verdict: **APPROVED** (see correction rereview below)

## Findings

### 1. Blocking — retry queue state is inferred without its required durable event

Location: `app/YiiRuntime/Controllers/BitrixDocumentationStatusRead.php:68-74`

`queueState()` classifies every `ready` row with `attempt > 0` as `retry`, even
when no `retry_scheduled` event exists. The approved design requires
`ready/attempt>0` **with retry event** for retry-wait, while contradictory or
incomplete durable facts must remain unknown. This can turn a manually damaged,
partially persisted, or otherwise contradictory row into the affirmative UI
statement `Ожидает повторной попытки`.

Correction: query/correlate the applicable durable `retry_scheduled` event for
the current attempt transition and return `unknown` when it is absent or
contradictory. Add a focused regression for `ready`, positive attempt, and no
retry event.

### 2. Blocking — history outcomes are not constrained to follow their claim and the page query can omit pairs

Location: `app/YiiRuntime/Controllers/BitrixDocumentationStatusRead.php:90-98`

The normative attempt identity is a particular `claimed` event, with an outcome
correlated only from subsequent events of the same `(job_id, attempt)`. The
history query filters only by pair and event type; unlike `attempt()`, it has no
`occurred_at_utc >= claimed.started_at` boundary. A malformed/pre-claim outcome
is therefore presented as the result of a later claim. In addition, the global
`LIMIT 25` is applied to outcome rows before deduplication by pair. Multiple
eligible outcome events for one malformed pair can consume the limit and leave
other claims on the bounded 25-row page without their saved outcome. Both cases
violate A4 and the design's subsequent-event correlation rule despite retaining
bounded SQL.

Correction: make the bounded outcome read correlate each page claim to its
newest subsequent outcome while guaranteeing at most one selected outcome per
page pair (for example, a bounded derived/page-pair query with per-pair ranking
or an equivalent bounded strategy). Add regressions for a pre-claim outcome and
duplicate outcome events that cannot crowd another page pair out.

## Reviewed behavior and evidence

- Queue and latest-started-attempt are displayed separately; enqueue time is not
  used as attempt start time.
- Last success independently requires a completed event and JSON-valid,
  non-negative integer `published`; corrupt newer receipts do not replace an
  older valid success.
- Output is allowlisted/escaped. Unknown failure codes and malformed details are
  not disclosed, and no payload, credential, URL, exception, or raw JSON is
  rendered.
- The adapter uses filtered reads, fixed claim pagination, bounded event
  materialization, and no write, network, enqueue, retry, or lock operation.
  Existing controller authorization remains the public access boundary.
- Production scope is confined to the local reader, controller wiring, and local
  integration-status partial/view. Existing neighboring failed-job behavior is
  unchanged.
- The partial gives the document summary first, keeps contained-scroll history,
  independent pager parameters, keyboard-operable links, and a one-column narrow
  summary. No shared CSS/JS was changed.
- Exact-source focused records
  `1790295281787718000-72ee8d1c1e1349a6a9e00bd95f17cd55` and
  `1790295291436917000-75a0b9739a214de5a17a030c648a7e15` are GREEN. They do
  not cover the two contradictory/corrupt event sequences above. Mandatory
  exact-source GitHub CI remains pending/UNKNOWN and cannot supply approval.

## Final decision

**CHANGES_REQUESTED.** The read-only/security boundary, receipt validation,
scope, and responsive presentation are acceptable, but the two event-correlation
defects can make the status/history assert facts not supported by the durable
journal. Correct both, refresh exact-source focused evidence, and request an
independent final rereview before publication.

---

## Correction rereview — 2026-09-25

- Reviewer: `/root/issue267_gate5` (same independent reviewer; no specification,
  test, or production authorship)
- Corrected exact source: `8fc3cc223fa8cef8445ca198d959f876eec9b0c2dac421ee76a317bdbdb47b7c`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T002054Z-3b32aa268a/package.json`
- Previous reviewed snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T001507Z-bfc7617249/snapshot`
- Corrected snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T002054Z-3b32aa268a/snapshot`
- Verdict: **APPROVED**

### Prior finding dispositions

1. **Retry state without a durable event — fixed.** A positive-attempt `ready`
   job now becomes `retry` only when the immediately preceding attempt has a
   durable `retry_scheduled` event; otherwise it remains `unknown`. The new
   contradictory fixture proves that the mutable attempt counter alone cannot
   produce the affirmative retry label.

2. **Pre-claim correlation and outcome crowding — fixed.** Both the latest-attempt
   and history readers now require an outcome `event_id` after the particular
   claim event. History performs at most one `LIMIT 1` point lookup for each of
   the already bounded 25 page claims, so duplicate outcomes for one pair cannot
   displace another page pair and the whole journal is never materialized. The
   new pre-claim and 25-pair duplicate-event fixtures exercise both failure modes.

### Full-delta review

The correction is confined to the document status reader, focused tests/review
evidence, and a local wording adjustment that makes the latest-attempt outcome
consistent with the established queue labels. It does not change authorization,
neighboring integration reads, writers, schema, network behavior, shared assets,
or pagination ownership. Query count is fixed by `PAGE_SIZE`; every history
outcome query is filtered by one selected `(job_id, attempt)` pair, ordered, and
limited to one row. Read-only, allowlisting, malformed-data fail-closed behavior,
last-success receipt validation, desktop/narrow hierarchy, and keyboard-operable
pagination remain intact.

Exact-source focused records
`1790295626233761000-770714d0572145f4ba20ab9287db9438` and
`1790295635904275000-d6ec29e834dc48caa452d24aed6e2b4d` are GREEN for the
corrected candidate. `git diff --check` is also clean. Mandatory exact-source
GitHub CI remains pending/UNKNOWN; this approval is the required independent
code verdict and does not claim CI, merge, deployment, or live Bitrix success.

No blocking or non-blocking code findings remain. **APPROVED** for publication
to the required exact-source CI/PR stage.

---

## CI ownership correction final rereview — 2026-09-25

- Reviewer: `/root/issue267_gate5` (same independent reviewer; no specification,
  test, production, or policy authorship)
- Exact source: `c3edbe4af21e47cafb6ebad5478026df255eb96fa935762a1581517ebbb63dda`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T005409Z-d1f491b4da/package.json`
- Previous approved source: `8fc3cc223fa8cef8445ca198d959f876eec9b0c2dac421ee76a317bdbdb47b7c`
- Verdict: **APPROVED**

### Findings and ownership review

No blocking or non-blocking findings.

The correction moves the unchanged reader to
`app/Jobs/MariaDbBitrixDocumentationStatusRead.php`, the existing persistence
owner for `fm2_jobs` and `fm2_job_events`, and renames it consistently. The Yii
controller only imports and composes that reader through the existing Yii-owned
connection; authorization, safe-read failure handling, paging and connection
lifecycle remain unchanged. The old controller-local reader is deleted, so
there is no second ownership path.

The verification policy adds exactly this reader to the existing
`bitrix-order-document-links` capability. It does not create a duplicate
capability or broaden production behavior, and it selects the established
application, delivery, mapping, read, schema, scheduler and console consumer
frontier. Verification input, the structural bounded-reader oracle and the
OpenSpec design all point to the same canonical path. The stable behavioral
contract correctly remains unchanged.

The prior exact-source CI failure is fully recorded as the nine
`sql_ownership` findings in `fast/architecture-check` plus the dependent
aggregate `verify` failure, with no `REGRESSION_FAILURE`; all other CI jobs were
GREEN. The correction directly addresses that inventory without changing query,
queue/event correlation, receipt validation, allowlisting, pagination, UI or
security semantics.

### Verification

Prepared exact-source records
`1790297617928473000-43446b5546d04aadadb9ceeb7d1c7084` and
`1790297629078315000-956c930969d1437a8fd49af81add1129` are GREEN for the
primary A1–A8 and adjacent integration-status browser consumers.

The reviewer also ran the bounded correction frontier on the prepared source:

- architecture guard: 59/59 GREEN;
- change-verification policy suite: 18/18 GREEN;
- moved reader and controller PHP syntax: GREEN;
- all seven registered `bitrix-order-document-links` consumer verifiers:
  GREEN;
- `git diff --check`: GREEN.

No full local suite was run. A fresh exact-source GitHub CI run remains required;
the previous failed run is not converted into GREEN by this review. **APPROVED**
for commit/publication and the required exact-source CI stage.
