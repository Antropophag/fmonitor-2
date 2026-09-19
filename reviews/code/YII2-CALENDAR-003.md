# Gate 5 review — YII2-CALENDAR-003

- Reviewer: independent Codex reviewer `/root/calendar_gate5`; authored none of the specification, tests, or implementation.
- Review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T203931Z-99b7f7b325/package.json`.
- Exact source: `7ea57c585284de555c11571fe22bd17502ea29c2dc0a8b67a9f5b52f57fc50c0` over base `fa930ed7bd308ede3ab1b083f7e366ba451ecb98`.
- Contract: `specs/YII2-CALENDAR-003.md`.
- Verdict: **CHANGES_REQUESTED**.

## Findings

### P1 — Repeated scalar `date` is accepted instead of rejected

Locations: `app/YiiRuntime/Controllers/CalendarController.php:47-64`, `tests/Yii2/yii2_calendar_003_test.php:49-53`.

A3 explicitly requires a repeated `date` query to return `400`. The controller validates `Yii::$app->request->queryParams`, after PHP/Yii has normalized the raw query. Standard PHP parsing collapses `date=2026-10-15&date=2026-11-03` to the last scalar value, so the controller sees one valid string and returns a calendar instead of rejecting the ambiguous request. The test covers an array spelling (`date[]=...`) but not a repeated scalar key, leaving the requirement falsely GREEN.

Correction: validate multiplicity from the raw query string before consuming normalized query parameters (without weakening unknown-key or array rejection), and add an HTTP regression assertion for repeated scalar `date` returning `400` with no partial calendar HTML and no writes.

### P1 — Required fail-closed `503` behavior is not proven by the focused acceptance test

Locations: `tests/Yii2/yii2_calendar_003_test.php:1-61`; implementation paths at `app/InstallationProcess/MariaDbYiiObjectQueue.php:28-38` and `app/YiiRuntime/Controllers/CalendarController.php:66-75`.

The stable contract and OpenSpec verification impact require missing/incompatible planning schema and bounded-source overflow to return safe `503` responses without partial HTML, DDL/repair, or writes. The focused HTTP test exercises neither schema failure nor overflow. Although the implementation has plausible guards, the Gate 4 evidence cannot establish their HTTP status, opacity, or no-write behavior and is insensitive to regressions in those branches.

Correction: extend the focused HTTP test with (1) an incompatible or absent schedule-schema fixture and (2) `CALENDAR_ROW_LIMIT + 1` in-range rows; assert `503`, safe/opaque response text, absence of `data-calendar-page`, and byte-equivalent facts/schema. Keep the checks bounded and do not run the forbidden local full suite.

## Reviewed areas with no additional blocking findings

- Authorization and navigation use `objects.read`; the calendar entry is ordered after `Объекты монтажа`, permission-filtered by the shared navigation renderer, and the calendar route marks it current.
- Projection is bounded to 5,000 rows plus an overflow sentinel and orders by date, numeric `legacy_object_id`, then schedule `id`; presentation preserves that order and escapes display strings.
- GET/HEAD introduce no mutation command or runtime repair path. The exact-source package records GREEN focused calendar HTTP/browser checks, adjacent object-card/object-queue checks, planner obligations, and architecture checks. Exact-source CI remains `UNKNOWN`/pending and is not treated as GREEN.
- Object links target the existing `/pilot/objects/<id>` read route rather than bypassing its authorization.
- The supplied desktop and 390px mobile captures preserve the incumbent shell and shlz-ui Calendar Grid language. Heading/count/period/grid/agenda hierarchy is legible; selected-day and event states are clear; the grid owns horizontal overflow without viewport overflow; the mobile agenda is readable and the fixed navigation does not obscure it. Existing shlz-ui focus-visible styling applies to grid controls. No cosmetic issue warrants blocking this bounded restoration.
- Minor finish note: server-rendered `autofocus` on the selected day intentionally restores the horizontal position but also moves keyboard/screen-reader focus away from the page heading after every date navigation. This is defensible for the current interaction, but a future polish pass should verify the announcement/focus experience with assistive technology and prefer explicit client-side focus management if it is disruptive.

## Evidence assessment

The package binds all recorded local GREEN results to the reviewed exact source. The browser test checks desktop/mobile rendering, contained overflow, selected state, today-state presence, agenda ordering, and a visible object link. It does not actually follow the object link despite the design record saying it verifies a return to object detail; this is non-blocking for production correctness because the href uses the established route, but the evidence wording should not overclaim navigation coverage.

Gate 5 cannot approve while a normative request-boundary case is known to violate A3 and mandatory safe-failure branches remain untested.

## Rereview — 2026-09-19

- Reviewer: the same independent reviewer; no production, test, or specification authorship.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260919T204600Z-5391b117aa/package.json`.
- Corrected exact source: `0b023d324dc72919cf697eeef85c6f212faaf5580991d68afcfdc8bb00b509bf`.
- Verdict: **APPROVED**.

Both P1 findings are resolved:

1. `CalendarController` now counts raw, URL-decoded query keys before reading Yii's normalized parameters and rejects more than one scalar `date`. The focused HTTP test includes `date=2026-10-15&date=2026-11-03` and verifies `400` plus absence of partial calendar HTML. Existing malformed, array, out-of-range, and unknown-key checks remain intact.
2. The focused HTTP test now drives both fail-closed branches: 5,001 in-range rows prove bounded overflow returns safe `503`, and a deliberately incompatible schedule schema proves readiness failure returns safe `503`. Both cases assert no partial calendar markup, no leaked SQL/table details, and unchanged planning facts/schema across the request.

The correction delta is confined to request multiplicity validation, focused failure coverage, delivery evidence, and this review record. The raw-query check does not broaden accepted keys or bypass the existing normalized type/range validation. The overflow fixture reaches the sentinel deterministically (three original in-range rows plus 4,998 inserted rows), and the schema fixture records its intentionally incompatible baseline before the request, so its no-repair comparison is meaningful.

The corrected package records GREEN exact-source results for the calendar HTTP/browser checks, object-card/object-queue consumer frontier, jobs composition, change verification, and architecture guard. No correction-delta regression or remaining Gate 5 finding was identified. Exact-source CI remains a separate pending/UNKNOWN publication gate and is not represented as GREEN by this approval.

## Post-CI delta review — 2026-09-20

- Reviewed commit: `c45a95e872a2c5070f7981d0af8b10897e4638c2` (`Align calendar navigation verification`).
- CI failure inventory reviewed: run `35468465778`; `governance` failed on two `registered_yii2_focused_bootstrap` assertions, `Integration (2/2)` failed on one `yii2_main_navigation` assertion, and only their aggregate jobs failed. All other jobs passed.
- Verdict: **APPROVED**.

No findings remain in the post-CI delta.

The shared-navigation test correction adds `/pilot/calendar` consistently to the route/label matrix, canonical order, `Монтаж` child hierarchy, pinned icon expectations, and both permission phases that retain `objects.read`. It does not relax exact membership, exact order, current-route uniqueness, group structure, icon geometry, permission-negative behavior, or repeated-read no-write assertions. This directly repairs the single Integration failure while increasing coverage of the newly shipped navigation item.

The governance-oracle correction preserves the two mandatory direct rationales as a required subset and permits exactly one additional rationale, `semantic integration closure`; any other augmentation still fails. This matches the planner's protected-capability closure for the registered shared-navigation consumer and does not turn the assertion into an unconstrained containment check. Adding both corrected tests to `verification-input.json` also keeps their selection and future changes within the declared change boundary.

The delivery record accurately preserves the complete failed-job and `REGRESSION_FAILURE` inventories, identifies aggregate failures as downstream, records the two root causes, and requires a fresh exact-source CI run because the source changed. Reported bounded reruns are GREEN: the focused main-navigation profile and all eight governance-oracle tests. No production behavior, stable contract, or acceptance expectation changed, and no test weakening or unrelated scope expansion was found.
