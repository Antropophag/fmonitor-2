# BITRIX-DOCUMENTATION-INTEGRATION-STATUS-001 — Gate 3 test/spec review

- Reviewer: `/root/issue267_gate3` (independent; did not author the specification or tests)
- Base: `99bd0974150617a01e195cec28f7d886f1ede761`
- Reviewed source: `7ba136f5486c2abad39c223ad2c7324693ce7c2af0ee4064a9fe6fdc65bb7e6a`, retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T234704Z-9057623363/snapshot/source.patch` (SHA-256 `f84a508be73ebf30464f27c3a0fa531c27e5756485cc0e7f79d43531ebe94880`)
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T234704Z-9057623363/package.json`
- Specification: `specs/BITRIX-DOCUMENTATION-INTEGRATION-STATUS-001.md` (SHA-256 `a3a55317d96fb14d6c3dee659ec36c3b5f557f49ac9fdaaaeb4eaaf723fcf744`)
- Primary test: `tests/Yii2/yii2_bitrix_documentation_status_001_test.php` (SHA-256 `596367be127c09421c152cbf3a2a199edbb2f68fa9fe4edb1cb22471bcc6ec1f`)
- Adjacent HTTP/browser test: `tests/Yii2/yii2_integration_status_001_test.php` (SHA-256 `941928b1a2ed8a866b2aa64a45f903768932a6530700595dcecc83dff42f0364`)
- Browser oracle: `tests/Yii2/integration_status_browser.mjs` (SHA-256 `cf6063479c4167a9fef53f5f2cb8f23b729237edf8ee98a083e8c0a22d0bbda1`)
- Public seam: authenticated `GET|HEAD /pilot/admin/integrations` and rendered browser DOM over disposable durable queue fixtures
- Sensitivity: CRITICAL; durable queue/event semantics, authorization, secret redaction, read-only behavior, and bounded server pagination
- Verdict: **CHANGES_REQUESTED**

## Findings

1. **The queue/event acceptance matrix is materially incomplete.** The primary
   test covers ready-before-claim and one ready-after-retry example, but never
   asserts a valid unexpired lease as `Выполняется`, an expired or contradictory
   lease as unknown, the completed queue summary, or a terminal dead summary. It
   also never creates multiple `claimed` events for one job, so an implementation
   that emits one row per job rather than one row per `(job_id, attempt)`, loses a
   prior outcome after lease/failure fields are cleared, or correlates an outcome
   to the wrong attempt can pass. These are explicit A3/A4 requirements and the
   core reason the design reads append-only events rather than only `fm2_jobs`.

2. **The pagination assertions do not prove bounded, filtered, stable history.**
   Lines 23–25 only assert `Страница 2 из 2` and the presence of query parameters.
   They do not assert which attempt rows are on either page, fixed page size,
   `occurred_at_utc DESC, event_id DESC` tie-breaking, exclusion of other job
   types from count/page contents, or preservation of all independent parameters
   in both directions. The foreign job fixture is deleted before the pagination
   phase. A reader that counts all jobs, sorts unstably, loads the whole journal
   into PHP and slices it, or returns the wrong records could pass. Add observable
   page-content/order/count oracles and a test-visible query-boundary assertion
   (for example command logging) that rejects unfiltered/unbounded event reads.

3. **Fail-closed receipt and security coverage is not sufficiently sensitive.**
   The corrupt receipt case merely rejects the canary and a fabricated zero. It
   does not require the specified unavailable/incomplete presentation or prove
   that the corrupt completed row is excluded from `Последний подтверждённый
   успех`; an implementation may label it confirmed success without a count and
   pass. The suite also does not place hostile values in the target job's
   `payload_json`, unexpected `details_json` keys, exception-shaped data, or an
   unknown failure code and assert the required generic state. Add direct target-
   type canaries (URL/token/credential/HTML/stack text), malformed JSON and
   negative/non-integer/missing `published` variants, while retaining an older
   valid success so selection and non-disclosure are independently observable.

4. **Both intended RED records stop before most new behavior is reached.** Record
   `1790293557677879000-b8bb1e1264e04869962df027b5167217` fails at line 16 on
   the absent heading, and record
   `1790293574182844000-1587992d623f425f987f93ad3dc97e4b` times out waiting
   for that same heading. They are exact-source, real-HTTP failures and establish
   a legitimate missing-feature RED, but provide no reachability evidence for the
   queue classifier, event correlation, receipt validation, pagination, read
   failure, or browser interaction branches. Split or stage the focused tests so
   materially distinct fixture families reach their intended missing behavior
   independently; preserve source-bound RED for those families before Gate 4.

## What is acceptable already

The stable contract and OpenSpec delta agree on the actor, public seam, durable
source, read-only boundary, failure honesty, and exclusions. Expected visible
values are contract-derived rather than copied from proposed production code.
The tests exercise real authenticated Yii HTTP, use isolated disposable fixtures,
snapshot jobs/events/outbox tables for GET/HEAD immutability, distinguish guest
and ordinary-user access, include a target-type receipt canary, preserve adjacent
pagination parameters, and include desktop/narrow keyboard browser checks.
`git diff --check` is clean. These strengths do not close the missing sensitive
semantics above.

## Required changes before rereview

- Complete the queue-state and per-attempt event-correlation matrix, including
  multiple claims on one job and cleared mutable job fields.
- Make pagination tests independently prove filtered totals, fixed bounded page
  contents, stable ordering/tie-breaking, parameter preservation, and bounded SQL
  rather than PHP materialization.
- Strengthen corrupt receipt, unknown failure, malformed JSON and secret-bearing
  target-job cases with positive fail-closed assertions and older-success
  selection.
- Capture source-bound RED evidence that reaches each materially distinct test
  family instead of stopping every path at the missing heading.

Gate 3 does not authorize production implementation. A corrected exact-source
candidate requires fresh independent rereview.

---

## Corrected candidate rereview — 2026-09-25

- Reviewer: `/root/issue267_gate3` (same independent reviewer; no specification,
  test, or production authorship)
- Corrected exact source: `c288a08d9db3aadcd2e8895ee9f683bcc872fc22628e10299e20a07726c4b70c`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T235456Z-90ac6c970b/package.json`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T235456Z-90ac6c970b/snapshot/source.patch` (SHA-256 `736c8a4a7dfad34b7cb24009aa02b6f902b76004b74844c461cc0c4f4e4a5095`)
- Specification SHA-256: `a3a55317d96fb14d6c3dee659ec36c3b5f557f49ac9fdaaaeb4eaaf723fcf744` (unchanged)
- Corrected primary test SHA-256: `46c42bad1e1d7cb03adb4f42d3bbc48adf0b055686a09432ef46d8779827ecc4`
- Adjacent HTTP/browser test SHA-256: `941928b1a2ed8a866b2aa64a45f903768932a6530700595dcecc83dff42f0364` (unchanged)
- Browser oracle SHA-256: `cf6063479c4167a9fef53f5f2cb8f23b729237edf8ee98a083e8c0a22d0bbda1` (unchanged)
- Verdict: **CHANGES_REQUESTED**

### Prior findings disposition

1. **Queue/event matrix — partially resolved, one normative branch remains.**
   The corrected test now covers queued-before-claim, retry wait, credible future
   lease, expired lease, completed and dead summaries, and two claimed attempts
   on one job with separate outcomes while mutable job failure/lease data is
   absent. That closes the principal per-attempt/history gap. It does not create
   a contradictory leased row (for example missing/invalid expiry or incoherent
   lease fields) and require unknown rather than `Выполняется`. The contract says
   both expired **and contradictory** lease states fail closed, so an
   implementation that handles expiry but treats malformed lease facts as running
   would pass.

2. **Pagination content/filter/order — resolved; bounded-query proof remains
   open.** The 27 target attempts plus 10 foreign attempts now independently prove
   a 25-row first page, two-row second page, job-type-filtered total, stable
   event-id tie-breaking at equal timestamps, boundary exclusion, and preservation
   of the other three page parameters in both page links. However line 33 only
   searches the proposed reader source for the unrelated substrings `limit(`,
   `offset(` and the job type, while rejecting three other substrings. Dead code,
   a bounded jobs query followed by an unbounded events query, or loading the full
   event journal and slicing it in PHP would satisfy this oracle. The normative
   constraint that outcomes are read only for the bounded page and the whole
   journal is never materialized therefore remains unprotected. Use an independent
   observable query boundary (query logger/profiler, bounded adapter spy, or an
   equivalently specific structural oracle tied to the history query and selected
   page pairs).

3. **Receipt/security matrix — substantially resolved, malformed JSON remains.**
   The corrected suite retains an older valid success, rejects newer string,
   negative, missing and fractional `published` values, requires the old success
   receipt, rejects target payload/result/details credential, URL, HTML and stack
   canaries, and makes an unknown failure code generic. It still generates every
   `result_json` and `details_json` through `json_encode`; no syntactically malformed
   JSON row is exercised. An implementation that fail-closes on wrong JSON types
   but throws, leaks, or misclassifies invalid JSON can pass. Add malformed target
   result/details JSON and require the same safe unavailable/incomplete behavior
   and non-disclosure.

4. **RED reachability — resolved for the fixture families.** Exact-source record
   `1790293948242273000-cd9ffb6a1e7c464d8e38b695140d3c45` completes every
   HTTP fixture family and reports their accumulated intended missing behavior,
   including queue states, both attempts, corrupt receipt selection, page contents,
   missing bounded reader and unavailable state. The adjacent browser record
   `1790293999286270000-6f52d76a4bd6446ab603a9054e731632` independently fails
   at the absent document heading. Both records are bound to the corrected source;
   setup no longer masks the principal semantic families.

### Verification notes

Expected values remain contract-derived and independent of proposed production
logic. The corrected deterministic fixture sequence replaces random IDs. PHP
syntax checks for both test files, Node syntax for the browser oracle, and
`git diff --check` pass. No production code or full local suite was run.

### Required changes before next rereview

- Add a contradictory/malformed lease case that must display unknown state.
- Replace the substring-only bounded-reader assertion with an oracle that cannot
  pass if outcomes or the event journal are read outside the bounded page pairs.
- Add syntactically malformed target `result_json` and `details_json` cases with
  explicit safe fail-closed and non-disclosure expectations.

The corrected suite closes substantial portions of all four original findings,
but the remaining sensitive branches keep Gate 3 closed. Production implementation
is not authorized; refresh exact-source RED evidence and request another
independent rereview after correction.

---

## Final Gate 3 correction rereview — 2026-09-25

- Reviewer: `/root/issue267_gate3` (same independent reviewer; no specification,
  test, or production authorship)
- Exact source: `d2ddc854a81e9138b6d4d4383d07fc1611692649534b870fb28aec5a4d79b32e`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T235845Z-b0d81e53be/package.json`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T235845Z-b0d81e53be/snapshot/source.patch` (SHA-256 `f13249948019bcd3bf2b83d405f13184e2b2f99d6fda87ef33320cab14efcf8c`)
- Specification SHA-256: `a3a55317d96fb14d6c3dee659ec36c3b5f557f49ac9fdaaaeb4eaaf723fcf744` (unchanged)
- Primary test SHA-256: `cc0e667f3b2c6e743e4cc96cc72dd9891a2f71b87943947114382951e226dc62`
- Verdict: **APPROVED**

### Disposition of the three remaining blockers

1. **Contradictory lease — resolved.** The suite now creates a `leased` job with
   a claimed event but no expiry and requires `Состояние выполнения неизвестно`
   while rejecting `Выполняется`. Together with the existing future-expiry and
   expired-lease cases, the queue classifier is sensitive to credible, stale and
   internally incomplete lease facts.

2. **Bounded event materialization — resolved for Gate 3.** The earlier generic
   `limit(`/`offset(` substring check has been replaced. The corrected guard
   examines every source statement that both references `fm2_job_events` and
   materializes via `->all(`, rejecting it unless that same statement carries a
   `->limit(`. Combined with the executable 25/2 page boundary, filtered foreign
   attempts, stable equal-timestamp ordering, and page-specific row assertions,
   this prevents the foreseeable implementation that loads the entire event
   journal and slices it in PHP. Final code review must still confirm that the
   chosen limit covers only outcomes belonging to the bounded page pairs; Gate 3
   does not approve future production code.

3. **Malformed JSON — resolved.** The fixture now temporarily disables check
   enforcement to persist syntactically invalid target `result_json` and completed
   event `details_json`, restores enforcement in `finally`, retains the older
   valid success, requires its count/time, rejects the malformed canaries and
   rejects the corrupt row's count. This complements the existing wrong-type,
   negative, missing and fractional receipt cases and target payload/result/event
   secret canaries.

### RED and verification

Exact-source record
`1790294248597645000-ea6bd540d957415e9cd63c9110fc1a9b` reaches the full
accumulated HTTP matrix and explicitly reports the new contradictory-lease family
before failing for the intended absent feature/reader. The malformed fixture setup
completes without masking later pagination and unavailable-state families. The
adjacent browser RED remains independently bound to this source through record
`1790294267619109000-7032d61503ca48f992e69ab87f50fd3b`.

The specification, public seam, deterministic expected values, access/read-only
checks, queue/event semantics, receipt/security matrix, filtered fixed pagination,
desktop/narrow browser behavior, isolation and cleanup are complete for the agreed
scope. `php -l` for the corrected primary test and `git diff --check` pass. No full
local suite was run.

No Gate 3 findings remain. The separate executor is authorized to implement this
exact reviewed contract/test candidate. Production GREEN, final independent review,
exact-source CI, merge and deployment remain outside this approval.

---

## Focused test-delta review — 2026-09-25

- Reviewer: `/root/issue267_gate3` (same independent reviewer; no specification,
  test, or production authorship)
- Exact source: `7329c90e945443e5aa063195799c88325874022e8bd04dde48539c734f0c9cf1`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T001112Z-e4cc8f4d80/package.json`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T001112Z-e4cc8f4d80/snapshot/source.patch` (SHA-256 `bd5086c709a2421e2fe324208c19f1c01046be64b35831fe2b03cd5a782727e3`)
- Specification SHA-256: `a3a55317d96fb14d6c3dee659ec36c3b5f557f49ac9fdaaaeb4eaaf723fcf744` (unchanged)
- Focused test SHA-256: `cbf9987afce9669eb5e43a938c5d2255675e1fbff69ad2ac387f78269cac6aad`
- Verdict: **APPROVED**

### Delta findings

No blocking findings.

The new `documentBlock` boundary scopes the initial positive values and foreign-
job/secret exclusions to the technical-documentation partial identified by
`data-integration="bitrix-documents"`. This removes the false oracle in which the
existing global failed-jobs table could disclose `SECRET_OTHER_FAILURE` while the
document projection correctly excluded that foreign job. The extractor fails
closed to an empty string when the bounded section marker or closing section is
absent, so missing integration markup cannot silently pass.

The corrected queued scenario now preserves a prior claimed/dead attempt, adds a
newer `ready` attempt-0 job, and independently requires all of the following in
the document block:

- queue summary `Ожидает запуска` for the newer unclaimed job;
- prior attempt start `22.09.2026 10:01` and its durable
  `TERMINAL_FAILURE` outcome;
- distinct `Состояние очереди` and
  `Последняя фактически начавшаяся попытка` presentation;
- absence of the queued job's enqueue timestamp as a synthesized start time.

Thus a projection that selects only the newest job, treats enqueue as claim, or
lets the queued item erase prior attempt history fails. Expected timestamps,
status and failure values remain fixture/contract-derived rather than copied from
production logic. The earlier approved queue, event correlation, receipt,
security, pagination, authorization and read-only coverage is retained unchanged.

### Evidence and checks

Exact-source record
`1790295046777104000-80f82b7acfc845bf92cda41ef086b722` reaches the full
fixture matrix and fails on the intended missing bounded document section and
queue/latest-attempt separation. Adjacent record
`1790295056359110000-12e9dbf54d244980a9da63a663bf527f` is GREEN, showing
the correction did not invalidate the established integration-status seam.
`php -l tests/Yii2/yii2_bitrix_documentation_status_001_test.php` and
`git diff --check` pass. No full local suite was run.

Gate 3 remains approved for this exact focused-test candidate. The executor may
implement the correction. Production GREEN, final independent review,
exact-source CI, merge and deployment remain outside this approval.

---

## Gate 5 finding test-delta review — 2026-09-25

- Reviewer: `/root/issue267_gate3` (same independent reviewer; no specification,
  test, or production authorship)
- Exact source: `04234b16348e7c00cb6591ce2664a13ad2b10cb7e3ae1d05f56b845acc6112c7`
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T001750Z-047b659dc7/package.json`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T001750Z-047b659dc7/snapshot/source.patch` (SHA-256 `a16b8efb1b7a676c6b00537b7e1c30e8639d2dd36f76ae727ab248d32a40a407`)
- Specification SHA-256: `a3a55317d96fb14d6c3dee659ec36c3b5f557f49ac9fdaaaeb4eaaf723fcf744` (unchanged)
- Focused test SHA-256: `605586d53bd73b6e97b6958c3dad5a15433b7928300b170892cbf1b36c18f0c2`
- Verdict: **APPROVED**

### Delta findings

No blocking findings.

The three added fixtures independently and sensitively encode the Gate 5
findings:

1. A `ready`, attempt-2 job with a claim but no `retry_scheduled` event must be
   unknown and must not be labelled `Ожидает повторной попытки`. This rejects
   inference from the mutable attempt counter alone and requires the durable retry
   fact named by the contract.
2. A `dead` outcome timestamped before the matching claim must yield
   `Результат не зарегистрирован` and must not expose the outcome's
   `TERMINAL_FAILURE`. This rejects correlation by `(job_id, attempt)` alone and
   requires the design's “subsequent event” ordering rule.
3. Twenty-five claimed page pairs are each given a completed outcome, while the
   newest pair receives 24 additional duplicate completions. Requiring exactly 25
   successful rendered rows rejects a globally limited outcome query whose window
   is crowded by duplicates from one pair. This complements the existing page-size
   and bounded-materialization guards by proving that every bounded page pair can
   still obtain its outcome.

The fixtures use explicit durable facts and contract-derived visible results; they
do not copy the proposed correction. They run through the same authenticated Yii
HTTP/document-partial seam and accumulated-failure harness as the previously
approved cases, so one new failure does not mask the others.

### RED and checks

Exact-source record
`1790295444448385000-6c1cbbe92e404f82ba4054a6a24cb714` fails only on the
three intended defects: false retry classification, disclosure of the pre-claim
outcome, and only 2 of 25 successful rows after duplicate-outcome crowding. This
demonstrates sensitivity and full fixture reachability. Adjacent record
`1790295454183411000-70ff0cd26ed9488694140290a184b902` remains GREEN.
`php -l tests/Yii2/yii2_bitrix_documentation_status_001_test.php` and
`git diff --check` pass. No full local suite was run.

Gate 3 approves this exact test delta and authorizes correction of the three Gate
5 findings. This is not production approval: corrected GREEN evidence, refreshed
independent final review, exact-source CI, merge and deployment remain required.

---

## CI ownership correction review — 2026-09-25

- Reviewer: `/root/issue267_gate3` (same independent reviewer; no authorship of
  the specification, tests, production adapter, or policy correction)
- Root-prepared exact source: `32ec0976985a3744b20b15c1ec7515c8871d45359dfc5b6f7afc1e8761c88d47`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T004643Z-a0f93a8283/package.json`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T004643Z-a0f93a8283/snapshot/source.patch` (SHA-256 `e3857e393b8015c176b70a3c345404811c21f65da2b3050264141c2fabc37ef4`)
- Base commit: `050ac3d3cf1922973cd7323dab8eb3df6544e665`
- Verdict: **APPROVED**

### Findings

No blocking findings.

The ownership correction is architecturally coherent and preserves the reviewed
public behavior:

- `MariaDbBitrixDocumentationStatusRead` now resides in `app/Jobs`, the existing
  owner of `fm2_jobs`/`fm2_job_events`, and its name satisfies the canonical
  persistence-owner ratchet. It accepts the Yii-owned `Connection`; it does not
  create a connection lifecycle, invoke a transport, enqueue work, or depend on
  handlers/registry/scheduler.
- `IntegrationStatusController` imports and composes the Jobs reader while
  retaining the canonical authorization, safe-read boundary, page parsing and
  connection close. It no longer owns the new SQL projection.
- `.quality-graph/verification-policy.json` registers the reader under the
  existing `bitrix-order-document-links` capability rather than inventing a
  second capability. That mapping selects the established application, delivery,
  mapping, read, schema, scheduler and console verifier frontier in addition to
  the changed focused acceptance test.
- The OpenSpec design now records the Jobs persistence decision and rejected Yii
  controller placement; verification input and the structural test point to the
  same moved path. The normative stable contract is unchanged, appropriately,
  because this is an ownership/CI correction rather than a public-seam change.

The test-path update remains sensitive: it fails when the canonical Jobs reader
is absent and continues to apply the bounded-event materialization and secret-
column guards to the actual owner. It cannot be satisfied by leaving the former
controller-local reader behind.

### Evidence lineage

The reviewer package is root-prepared because the current moved adapter is already
GREEN and a newly prepared reviewer package cannot manufacture historical RED.
The supplied lineage is nevertheless reconstructible and behavior-specific:

- historical exact record
  `1790296973106357000-9ed8ac2e930349c8a6e9d39d843376ae` reaches the complete
  focused matrix and fails only with `bounded-reader missing` after the test path
  moved to `app/Jobs/MariaDbBitrixDocumentationStatusRead.php`;
- exact root-package source record
  `1790297210434308000-014795908e0d4ae6a2f9a7292a06e3f1` is GREEN for the
  complete primary A1–A8 suite;
- browser/adjacent record
  `1790297258336398000-0a4496721515407880998d42dd056486` is GREEN after the move.
  Its later repository source digest reflects review/delivery metadata movement;
  the executable browser/HTTP acceptance artifacts remain the reviewed ones.

### Independent bounded checks

- PHP syntax: moved reader and controller pass.
- Verification policy JSON parses.
- `python3 tests/Verification/change_verification_001_test.py`: 18/18 pass.
- `python3 tests/Verification/architecture_guard_001_test.py`: pass.
- `git diff --check`: pass.

No full local suite was run. Gate 3 approves the ownership, policy and test-path
correction. The moved adapter remains production code requiring refreshed
independent final review; exact-source CI, publication, merge and deployment are
not approved by this Gate 3 amendment.
