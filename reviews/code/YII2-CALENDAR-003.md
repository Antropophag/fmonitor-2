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
