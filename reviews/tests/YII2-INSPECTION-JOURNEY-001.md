# YII2-INSPECTION-JOURNEY-001 — Gate3 pending

Author: root; independent reviewer ещё не запускался. Нормативный контракт
[spec](../../specs/YII2-INSPECTION-JOURNEY-001.md), lifecycle/design/input находятся
в openspec/changes/yii2-inspection-journey/. Не считать этот record одобрением.

## Root completeness и RED

Кандидат охватывает общий endpoint cohort, реальный return card/control queue,
photo/correction/section, item42/85%, CSRF/current auth, projection/replay/rollback,
fixture canonical frontier и registration в final CI. Сохранённые native focused
neighbors дополняют, но не заменяют Yii HTTP/browser assertions.

Первый HTTP RED: `php tests/Yii2/yii2_inspection_journey_001_test.php` —
`INTENDED_RED Yii checklist page`, expected200 actual404 после успешного
реального select/upload/open/login. Concurrency command с тем же отсутствующим
GET и browser command с `INTENDED_RED Yii browser checklist` также RED exit255.
Никакой production реализации на момент этих запусков нет.

После дополнения всего кандидата HTTP/browser RED повторён по изменённым inputs;
конкурентный тест не менялся и не повторялся. Setup не является RED.
Generated plan `.local/verification/inspection-plan.json` прочитан до Gate2,
обновлён после добавления tests/inventory. Required focused: три Yii tests,
pilot_http_auth_001_global_calls, verification_ci/inventory, change_verification,
architecture_guard; full `make test` только окончательный CI.

Evidence сохраняется вне repo: ~/.local/state/fmonitor2/deliveries/76-inspection-20260910/.

## Independent verdict

PENDING.

## First independent review (corrected findings)

# YII2-INSPECTION-JOURNEY-001 — independent Gate 3 review

Reviewer: separately tasked `/root/inspection_reviewer`; reviewer authored none of the candidate artifacts.

Reviewed source: base `5822cde3ab327c728db2cc7affb661d0946123cc`, restored checkout `/private/tmp/fmonitor-76-inspection-gate3-ready`, retained patch `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-inspection-gate3-ready/source.patch`, independently verified SHA-256 `63fc00234ac7c09f016712bf0e9bbfb4977e8b643014bb852e6e51275a48d32a`. Generated plan: `/Users/antropophag/.local/state/fmonitor2/deliveries/76-inspection-20260910/gate3-plan.json`.

## Findings

### MEDIUM — the exact item-42 response and selected Yii mapping cases are not asserted

Location: `specs/YII2-INSPECTION-JOURNEY-001.md:45-46,64-74`; inherited `specs/INSPECTION-ITEM-COMPLETE-001.md:198-225`; `tests/Yii2/yii2_inspection_journey_001_test.php:24-32`.

The earlier demand to expose/assert every domain reason at the Yii HTTP seam was invalid and is withdrawn. The inherited contract deliberately maps `STALE_REVISION`/`OPERATION_PAYLOAD_CONFLICT` to `conflict`, all other deterministic domain rejections to `rejected`, and defines the public item-completion result as exactly `{status, revision}`. Unchanged `ChecklistSync::completeItem()` implements that mapping, while `inspection_item_complete_001_rejections_test.php` and `inspection_item_complete_001_http_wiring_test.php` already cover typed domain reasons and their adapter collapse. Duplicating those reason assertions in the Yii cohort would contradict the public API.

A narrower gap remains. A2 explicitly requires item 42 to return the exact documentary-closeout message; the candidate asserts only `status=rejected`. It also does not exercise representative Yii composition mappings for a POST whose external object has no current case or a non-working case, although the migrated controller/resolver wiring owns those translations. These cases need only assert the normative public envelope/status and no mutation, not internal reason constants.

Correction: assert the exact item-42 message and no mutation; add compact real-Yii POST cases for no-current-case and non-working composition while relying on the unchanged native suites for the inherited domain matrix.

### HIGH — HTTP and neighboring photo/section boundaries are materially under-specified by the executable tests

Location: `specs/YII2-INSPECTION-JOURNEY-001.md:76-98`; `tests/Yii2/yii2_inspection_journey_001_test.php:28-42`; `tests/Yii2/InspectionFixture.php:45-51`.

A5 requires missing/invalid CSRF, content type, JSON size >32768 => 413, photo size >5 MiB, and MIME/hash validation. The candidate checks one invalid token, malformed JSON, and bad hash only. A6 requires same-content deduplication across distinct operations, the ten-active-photo limit, persisted bytes/hash/actor in private storage, revoke capability/current-assignee rules, and photo/section actor admission. The test only replays the identical photo operation and rejects revocation of the last photo after section completion; those paths cannot catch violations of the other rules. The fixture's `send()` always supplies a content type and has no oversized request path, confirming these are absent rather than hidden in support code.

Correction: add bounded real-HTTP cases for the stated request limits/content types and distinct-operation photo dedup/limit/persistence/admission/revoke rules, asserting typed results and zero mutation on rejection.

### HIGH — A7 and A8 are mapped to tests that exercise only a fraction of their observable behavior

Location: `specs/YII2-INSPECTION-JOURNEY-001.md:100-118`; `tests/Yii2/inspection_browser.mjs:25-35`; `tests/Yii2/yii2_inspection_journey_001_test.php:15-16,50-54`.

The browser test queues one item operation offline and observes eventual sync. It never queues the required operation -> photo -> section sequence, never proves that conflict/rejected/retryable entries remain queued, never inspects the required `meta`, `operations`, and `photoBlobs` stores or `user:device:object` scope, and never exercises CSRF refresh after login/restart through sync-context. The queue test checks marker presence and that 51 rows paginate 50/1, but does not exercise only-working selection, latest engineer/activity/completion projections, mine/all/search/show-completed behavior, empty results, prescribed sort order, or preservation of device-time ordering. Implementations that discard failed offline work or return a generic paginated list would pass this candidate.

Correction: extend the real-browser oracle with a mixed offline sequence and each retention outcome plus restart/token refresh and store/scope assertions; add literal queue fixtures and requests that distinguish all filters, projections, empty state, ordering, and device-time semantics.

### MEDIUM — route/admission coverage does not cover the declared endpoint cohort

Location: `specs/YII2-INSPECTION-JOURNEY-001.md:18-36`; `tests/Yii2/yii2_inspection_journey_001_test.php:13-19`.

The declared seam includes GET/HEAD queue, both checklist aliases, POST operations/photos on both aliases, and GET/HEAD sync-context with canonical positive decimal IDs. The candidate checks one checklist HEAD, GETs of both aliases, and POST only through the object alias. It does not assert `Allow`, safe return-to after login, queue/sync-context HEAD behavior, noncanonical IDs, construction-control permission denial with no facts, or POST/photo behavior through the construction-control alias. Route wiring can therefore be incomplete while these tests pass.

Correction: add a compact route matrix for methods, aliases, canonical-ID rejection, `Allow`, guest return-to, and queue permission/no-fact behavior, including both write endpoints.

## RED and verification assessment

The retained HTTP, browser, and concurrency logs are qualifying RED for the missing Yii checklist route: their shared fixture completes real select/upload/open/login setup and fails at expected GET 200 versus actual 404. They do not compensate for the coverage gaps above. Registration checks were run once because the candidate changes inventory/CI registration: `verification_inventory_001_test.py` and `verification_ci_001_test.py` both passed. No full suite or candidate test was repeated; no production stand or primary data was touched.

## Unchanged sources inspected

The findings above were checked against unchanged `app/PilotHttp/PilotE2ECoordinator.php` (`checklist`, `syncContext`, request limits and response mapping), `app/PilotHttp/ChecklistSync.php` (`completeItem`, neighboring operation branches, photo storage/dedup/limit), `rapid-pilot/CompletionFlow.php` (item-42 response and completion rendering), `config/yii/web.php`, `app/YiiRuntime/WebResponse.php`, `tests/Yii2/PreopeningFixture.php`, `tests/Yii2/PreopeningConcurrentRequests.php`, `tests/InstallationProcess/inspection_item_complete_001_rejections_test.php`, `inspection_item_complete_001_http_wiring_test.php`, `inspection_item_complete_001_endpoint_admission_test.php`, `control_queue_bulk_protocol_manual_test.php`, and the existing photo-limit verifier. These sources support preserving native owner-level coverage and adding only Yii migration/cohort observations where the new wiring or browser behavior can regress.

## Verdict

**CHANGES_REQUESTED.** Gate 3 does not advance. The RED reaches the intended missing seam, but the complete executable candidate does not cover material Yii-cohort behavior in A1/A2/A5-A8. No API expansion or duplicate owner-level rejection matrix is requested.


## Root correction delta

Исправлены exact item42 message, весь method/alias/HEAD/return admission cohort,
request limits/content types, representative missing/nonworking/template mapping,
photo distinct dedup/10-limit/bytes/revoke/roles, mixed offline queue/retention,
IDB scope/stores, restart native CSRF и queue filters/order/latest engineer.
Typed domain reason exposure demand отозван reviewer по неизменённому контракту.
Native inherited suites явно добавлены в verification plan; HTTP API не расширен.
Delta RED three Yii tests: absent route remains intended404; implementation отсутствует.

# YII2-INSPECTION-JOURNEY-001 — independent Gate 3 correction review

Reviewer: separately tasked `/root/inspection_reviewer`; reviewer authored none of the reviewed artifacts.

Reviewed delta: prior reviewed snapshot SHA-256 `63fc00234ac7c09f016712bf0e9bbfb4977e8b643014bb852e6e51275a48d32a` at `/private/tmp/fmonitor-76-inspection-gate3-ready` to restored correction snapshot `/private/tmp/fmonitor-76-inspection-gate3-delta`, both based on `5822cde3ab327c728db2cc7affb661d0946123cc`. Retained correction source `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-inspection-gate3-delta/source.patch` independently matches SHA-256 `5dd2f1c94de1a18b4f825a16f400383a5c23fdee09815306dbad964baa908871`. Generated plan `gate3-delta-plan.json` binds the amended spec/tests and explicitly maps the unchanged native item-completion suites.

## Findings

No blocking or non-blocking findings in the correction delta.

The amended HTTP test now distinguishes the declared route cohort, canonical IDs, safe login return, HEAD/Allow, queue authorization, CSRF/content-type/body limits, exact item-42 message, public missing-case 404, representative deterministic rejection mapping, mutable-context replay, both write aliases, photo MIME/size/private evidence/distinct-operation dedup/ten-photo limit, revoke authorization/assignment, manager upload, restart/session/sync-token use, schema failure, rollback, and device-time queue ordering. Assertions remain on the public Yii shape; they do not expand the inherited `{status, revision}` item-completion API.

The browser delta checks the exact IndexedDB v2 stores and user/device/object scope, retains the same intended operation through injected `conflict`, `rejected`, and `retryable` results, then proves eventual acceptance. Its offline bulk path observes item operations before photo before automatic section completion and verifies eleven persisted revisions/facts. Its queue fixtures make working-state exclusion, latest application engineer precedence, ownership, completed visibility, search, empty state, and sorting independently distinguishable. The generated one-pixel PNGs have valid PNG chunk structure and distinct hashes for photo-limit sensitivity.

Affected unchanged relationships inspected: `specs/INSPECTION-ITEM-COMPLETE-001.md` HTTP mapping; `app/PilotHttp/PilotE2ECoordinator.php` checklist/sync-context transport and response behavior; `app/PilotHttp/ChecklistSync.php` native-owner mapping, neighboring operations, photo validation/dedup/limit/storage; `app/PilotHttp/MariaDbConstructionControlQueue.php` application/legacy engineer precedence, working filter, completion, device-time sort and pagination; `rapid-pilot/CompletionFlow.php` item-42 contract; `tests/Yii2/PreopeningFixture.php` request/fact/private-root behavior and `PreopeningConcurrentRequests.php`; unchanged item-completion rejection, precedence, replay, MariaDB and HTTP-wiring suites; photo-limit verifier and queue bulk protocol test.

## RED and command accounting

The three supplied correction logs are qualifying RED for the same missing Yii checklist route after successful real fixture setup: HTTP and concurrency fail at expected GET 200 versus actual 404; browser fails at its explicit missing-checklist assertion. The correction changes downstream assertions/support while leaving that intended failure intact. Registration was unchanged and its prior GREEN evidence was accepted without rerun. No RED, passing check, full suite, production stand, or primary data was rerun or touched during this delta review.

## Verdict

**APPROVED.** The coherent correction resolves every prior valid Gate 3 finding. Gate 3 may advance for retained snapshot `5dd2f1c94de1a18b4f825a16f400383a5c23fdee09815306dbad964baa908871`; implementation and Gate 5 remain separate.

## Gate4 browser instrumentation delta — pending independent review

Executor diagnostic `browser-context-probe3.log` showed only first bulk item
being sent, successors/photo/section queued, while async waitForFunction returned.
Root independently reproduced installed Playwright: `waitForFunction(async()=>false,
{}, {timeout:250})` resolved false in33ms, not timeout. Root replaced all five
async IndexedDB predicates with explicit awaited polling of the real stores;
retention, identity, accepted section and request-order assertions remain.
Root also added existing capability-gated shell entry assertion after visually
checking desktop/mobile and unchanged PilotView navigation. No HTTP reason API
or domain expectation changed. This delta requires independent Gate3 approval
before final Gate5; prior approved tests stay approved outside this delta.

# YII2-INSPECTION-JOURNEY-001 — independent Gate 3 browser instrumentation delta

Reviewer: separately tasked `/root/inspection_reviewer`; reviewer did not author the test or production implementation.

Scope: only the `tests/Yii2/inspection_browser.mjs` delta from approved tests checkpoint `752b62a42682f7775697939b0afa353cb89531e9`. Restored source: `/private/tmp/fmonitor-76-inspection-browser-delta`; retained patch `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-inspection-browser-delta/source.patch`, independently verified SHA-256 `8e74075a493ffc42bb753c48b5c868a514e8ca1b3951faec7e539715dd77ae64`. This is not a Gate 5 production review.

## Findings

No findings.

`pollLocal` explicitly awaits fresh IndexedDB transactions until the predicate becomes true or a 30-second deadline expires. On timeout it throws with the last observed operation identities/types/statuses, so the replacement cannot silently convert an unmet condition into success. Each replaced wait retains its original sensitivity: durable item presence; the same `offlineId` in conflict, rejected, retryable and accepted states; durable photo bytes; and accepted section completion. The operation identity, injected response sequence, explicit sync clicks, persisted revision/fact audit, and item -> photo -> section order assertions are unchanged.

The added shell assertion is observable and capability-relevant: unchanged `app/YiiRuntime/ViewSupport.php` renders exactly one `Стройконтроль` link in `Основная навигация` when `canControl` is true, and the browser fixture logs in the authorized construction-control engineer before checking it. It does not inspect implementation-only markup or weaken the subsequent queue assertions.

Affected unchanged relationships inspected: `app/YiiRuntime/Assets/checklist.js` IndexedDB record/status and sync behavior, `app/YiiRuntime/Assets/checklist-sw.js`, `tests/Yii2/yii2_inspection_browser_001_test.php` persisted audit and timeout wrapper, `tests/Yii2/InspectionFixture.php` authorized identity and queue fixtures, and `app/YiiRuntime/ViewSupport.php` shell capability gate. The supplied `browser-context-probe3.log` concretely demonstrates the prior async `waitForFunction` instrumentation returning before its IndexedDB predicate; the separate minimal Playwright probe shows an async false predicate resolving false in about 33 ms despite a 250 ms timeout. No service-worker cause is assumed.

## Evidence and command accounting

`executor-browser-corrected-1.log` is GREEN for the corrected browser test, including the mixed queue and persisted audit. The unchanged HTTP and concurrency cohorts remain GREEN in `executor-http-5.log` and `executor-concurrency-2.log`. `browser-delta-plan.json` binds the exact amended browser source and retains the agreed verification obligations. No passing command was rerun during this review because the supplied evidence resolves the concrete instrumentation risk and introduces no new one.

## Verdict

**APPROVED.** The browser instrumentation correction preserves the approved assertions and makes their asynchronous IndexedDB waits fail closed. Gate 3 remains valid for this test delta. Production implementation still requires its separate Gate 5 review.

## Gate5 correction regression delta — pending Gate3

Root added one boundaries suite for the two confirmed findings and two browser
assertions for the related legacy engineer fallback. Valid injected Yii DB is
opened before poisoning unrelated environment; the owner constructor fails
INTENDED_RED with mysqli_sql_exception. This is not a setup failure. New DAO
assertions observe framework query/transaction events and full rollback facts.
Projection uses a canonical literal registered case and public native completeItem
prerequisite, then workforce drift: expected snapshot employed/display dismissed,
actual employed/employed+NULL. Browser reaches exact legacy-engineer fallback
assertion RED. Two earlier fixture setup errors (required planned date, wrong
association column name) were corrected and are not claimed as projection RED.
Fixture current-crew succession adds a new order and preserves previous records.

# YII2-INSPECTION-JOURNEY-001 — Gate 3 correction regression review

Reviewer: separately tasked `/root/inspection_reviewer`; reviewer authored neither tests nor production.

Reviewed only the coherent regression-test delta from Gate 5 snapshot `e93a1e3be66730397b438dc08a40c43b9240dcbc50c15f3872b0a612c839ba9f`. Frozen checkout `/private/tmp/fmonitor-76-inspection-gate5-correction-tests`, base `752b62a42682f7775697939b0afa353cb89531e9`; retained patch `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-inspection-gate5-correction-tests/source.patch`, independently verified SHA-256 `1084a0bc953c4da411118272e4b0b4d3b944add16ae6142c00c4a78f5bd3c71c`. Plan: `correction-plan.json` in the delivery evidence directory.

## Findings

No findings.

`yii2_inspection_boundaries_001_test.php` supplies a valid observable `yii\db\Connection`, proves it works before constructing the target, corrupts only unrelated `FMONITOR_DB_*` discovery inputs, and then requires owner reads and writes to increment that supplied component's command counter. Yii transaction events distinguish one commit and one rollback, while before/after canonical DB facts prove failed persistence does not commit the photo operation. The fixture uses public `YiiChecklist::projection/accept` behavior and a public native `InspectionRecording::completeItem` prerequisite; it does not bind to a planned internal correction.

The projection scenario fixes the expected values independently: accepted installer rows remain `employed` and keep their recorded name; the current workforce catalog changes installer 7001 to `dismissed`; the HTTP projection must expose historical `employmentStatusSnapshot=employed` with display `employmentStatus=dismissed`, dismissal date, and current membership. A second registered order then detaches 7001 and requires the immutable accepted snapshot plus `currentlyAssigned=false`. Exact operation/personnel inventories before and after reads prevent a passing implementation from rewriting history.

The two browser assertions are also sensitive. Fixture objects 4513/4514 have no application; their distinct legacy engineer IDs 94/73 test fallback, while object 4512 already proves a latest application overrides conflicting legacy data. The assertions therefore catch the frozen `engineer()` implementation that only reads applications and do not duplicate the existing queue/filter matrix.

Affected unchanged relationships inspected: canonical case/order/workforce/checklist schemas in `PreopeningFixture`; `ProductionInspectionEvidenceFactory` public seam; prior `ChecklistSync::crew/projection`; frozen `MariaDbYiiChecklistRead::crew/engineer/projection`; frozen Yii owner constructor/query/transaction paths; unchanged `MariaDbConstructionControlQueue` application → event → legacy fallback; browser queue fixture and PHP audit wrapper.

## RED and command accounting

The final combined RED contains both intended failures after valid setup: environment discovery raises `mysqli_sql_exception` despite the working injected Yii connection, and projection returns stored `employed`/NULL instead of current `dismissed`/date. The projection-only RED confirms the second independently. The browser RED reaches the legacy-engineer assertion only after the complete mixed eleven-revision journey, then fails because the fallback is absent. The two earlier fixture setup failures are excluded from qualifying evidence. Registration evidence is GREEN and was accepted without rerun. No prior passing behavior test, registration test, production check, stand, or primary data was rerun or touched in this review.

## Verdict

**APPROVED.** The regression delta correctly and independently captures both Gate 5 findings and the affected engineer fallback parity. The executor may implement one bounded correction; production remains subject to a fresh Gate 5 delta review. The previously approved two-entry public-seam baseline is unchanged and was not reopened.

## Public-owner authorization regression delta

Source inspection: access read user status/activation but roleAccess omitted
active user conjunction; HTTP guard masked this at transport. Root added blocked
and invited exact replay assertions to the same supplied-DB scenario. RED on
corrected DAO source: expected forbidden, actual duplicate; projection passes.
This preserves existing current authorization at public application seam.
No API or policy expansion; pending independent Gate3 delta approval.

# YII2-INSPECTION-JOURNEY-001 — Gate 3 public-owner authorization delta

Reviewer: separately tasked `/root/inspection_reviewer`; reviewer authored neither tests nor production.

Reviewed only the eight-line authorization delta to the previously approved `yii2_inspection_boundaries_001_test.php`. Frozen checkout `/private/tmp/fmonitor-76-inspection-owner-auth-tests`, base `752b62a42682f7775697939b0afa353cb89531e9`; retained patch `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-inspection-owner-auth-tests/source.patch`, independently verified SHA-256 `4807cee10fd45a0d7169463aec9f34bd29ed83663b6f54394e404b52f47d4789`. Plan: `owner-auth-plan.json` in the delivery evidence directory.

## Findings

No findings.

The delta exercises the public `YiiChecklist::accept` owner directly after a successful first photo, so the repeated payload is a real replay that would otherwise return `duplicate`. Its two fixtures independently invalidate account status (`status=0, activation_state=active`) and activation (`status=1, activation_state=invited`). Each requires `forbidden`, exact full DB-fact equivalence, and unchanged Yii transaction-event counts, then restores the actor in `finally`; a failure in the first case does not contaminate the second.

This is sensitive to the concrete frozen defect. The owner access query selects status/activation but does not enforce them, `roleAccess()` joins role membership without active-account predicates, and non-item `accept()` relies on that role boolean before duplicate detection. The supplied RED therefore reaches the intended public seam and reports expected `forbidden`, actual `duplicate`. The HTTP identity layer does not satisfy the application-owner invariant on its own.

Affected unchanged relationships inspected: `MariaDbYiiChecklistRead::access/permissions/roleAccess`, `MariaDbYiiChecklistMutation::accept`, canonical active-account predicates in `MariaDbChecklistRoleAccess`/Yii identity storage, and `InspectionRecording::completeItem` current-authorization precedence. The delta adds no typed-reason API, does not reopen the approved architecture baseline, and stays within A1/A5/A9 current authorization.

## Evidence and command accounting

The retained patch digest, exact delta, plan, and `inspection-owner-authorization-red.log` were inspected. No prior passing check, registration test, broader matrix, Gate 5 code, stand, or primary data was rerun or touched.

## Verdict

**APPROVED.** The assertion delta correctly captures current active-account authorization at the public owner, including replay and zero transaction/fact mutation. It may join the coherent DAO/projection/fallback correction for fresh Gate 5 review.
