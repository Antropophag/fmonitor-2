# OTIZ-FILTERS-DECISIONS-20261029

Owner feedback2026-10-29: simplify calculation filters and make payout decisions discoverable without navigating to the page top. Bounded presentation correction; no financial rule/command/schema changes.

- Remove amount sorting control. Object order is stable natural registration-number order; employee order is name with stable identity tie-break. Legacy sort input cannot change this ordering.
- Search and four report filters retain their existing read-only meaning. Place filter checkboxes in an accessible compact SHLZ popover, with visible active count, Escape/outside dismissal and restored focus.
- Put page size10/25/50/100 below each grouping table next to SHLZ pagination and current/total page information, including one-page results. This counts top-level displayed rows; expanding child allocations does not split an object/employee across pages. All grouping/search/report-filter state survives navigation; changing size or filters resets to page1.
- Dismissed warning opens a chooser of all dismissed recipients, then the selected existing individual decision editor; no GET reload, implicit financial POST, search mutation or scroll jump. Closing restores scroll/focus and allows selecting another recipient. Native anchor fallback remains available without JS.
- Existing preview/save validation and CSRF/revision/idempotency behavior remain. Desktop1440 and narrow390 layouts retain usable controls, contained table scroll and no document overflow.

Verification: real Yii auth/DB browser fixture `tests/Yii2/yii2_otiz_filters_decisions_20261029_test.php` + corresponding browser driver; retained `tests/Yii2/yii2_otiz_settlement_v2_001_test.php`, `tests/Otiz/report_filters_20261029_test.php`, existing feedback browser. Primary evidence outside Git.
