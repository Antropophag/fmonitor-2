# OTIZ filters and payout decisions — independent final review

Reviewer: `/root/otiz_filters_review`, gpt-5.6-sol / low; not an author of
runtime code or tests. Root authored the contract and regression tests;
`/root/otiz_filters_executor` authored the five runtime files.

Verdict: **APPROVED** for the owner-authorized bounded production UI delivery,
2026-10-29. This is not overall CI or architecture-check GREEN.

## Review findings

Two blocking findings were corrected before approval:

1. Employee natural-name ordering now uses `employeeId` as its stable identity
   tie-break. Object registration-number ordering uses `objectId` as its stable
   tie-break. Legacy amount-sort input cannot change either order.
2. The dismissed-warning link retains a usable no-JavaScript fallback to the
   existing visible `#payment-decisions` section. With JavaScript it opens the
   chooser without navigation and then the selected existing decision editor.

No blocking finding remains. The chooser copy says that the decision does not
change "до предпросмотра и сохранения"; preview is read-only, so clearer wording
would say that only saving changes the decision. This is a non-blocking copy
recommendation and does not change money semantics or behavior.

## Verified behavior

The final real Yii auth/DB browser run passed on the exact hashes below. It covers
the four-checkbox SHLZ popover, Escape and outside dismissal, active count,
10/25/50/100 footer pagination, current/total page information including one-page
results, search/filter/group/page-size retention, page reset, both dismissed
recipients, correct editor selection, and scroll/focus restoration at 1440 and
390 widths. Root inspected the final toolbar, footer, chooser and narrow-layout
captures: controls were unobstructed, checkboxes were 20 px, tables remained
contained, and there was no document overflow. The test also proved that the
financial revision rows were unchanged.

Retained OTIZ feedback browser, settlement real-HTTP, report-filter and report-XLSX
checks passed. Static review passed `git diff --check`, PHP lint for the changed
controller/views, and `node --check` for `otiz.js`. Production
`/pilot/assets/shlz-behaviors.js` was fetched successfully and its export block
contains `enhancePopovers`; the corresponding local public bundle export was also
verified. Existing preview/save validation, CSRF, revision and idempotency paths
were not changed.

Primary evidence:
`/Users/antropophag/.local/share/fmonitor-2/otiz-filters-decisions-20261029/`.
The final browser artifacts are under
`/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/fm2-otiz-v2-browser-8bee1d209d9e/`.

## Exact runtime review binding

- `0b44c0bf67955da1a0f80ad077171da9b039fb183078d87438cc35d2de0430dd` `app/YiiRuntime/Assets/otiz.js`
- `1626fe3b742c281435f941ba916f699c4c4549f304103ae7d207484ba0acdfaa` `app/YiiRuntime/Assets/pilot.css`
- `f40ed8f9c8f4ce4f7d9ceebae32f5b7c5d27dc8e4951785a131c70e6215c89f6` `app/YiiRuntime/Controllers/OtizSettlementV2Controller.php`
- `9f2f87bfb8ce0f8b8b6319f1731811c90b824b63e4d69f491f7a4f9f7b9a2087` `app/YiiRuntime/Views/_otiz-v2-group-table.php`
- `40c6c111d9f249d334a0d5b95f43ce0c7ffd3534f2e7e68f6f5c671a703d473b` `app/YiiRuntime/Views/otiz-calculation-v2.php`

## Deferred and non-GREEN gates

The owner explicitly authorized this short code → independent review → production
cycle and deferred ordinary gates and full CI. No full local `make test` or
`make verify` was run.

`make architecture-check` is RED, not GREEN. Its complete failure inventory is
two `sql_ownership` findings in changed presentation files: the JavaScript
page-size `select` variable and the native PHP `<select>` markup matched the
architecture checker's case-insensitive `SELECT` token expression. The HTTP global
call qualification passed, and inspection found no SQL or persistence behavior in
either file. File-size entries were advisories unrelated to this slice. Under the
owner's explicit waiver, these two reviewed false positives are retained in the
delivery record; checker or baseline policy was not changed for this UI task.
