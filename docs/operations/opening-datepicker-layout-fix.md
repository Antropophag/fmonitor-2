# Opening date picker layout — 2026-09-28

Owner reported a broken picker on the ready-to-open object card and supplied Desktop screenshot2026-09-28 00:07. The continuing direct-production/no-gates authorization applies. Root authored the bounded presentation fix and focused browser regression.

Cause: `.fm2-next-action .fm2-inline-form button` assigned grid-column2/grid-row2 to every nested calendar navigation/day button, making navigation overlap. The generic inline-form input skin also leaked into the nested SHLZ date field. The opening field lacked the standard SHLZ field/control composition, so enhancement nested the picker inside its original label.

Corrected the form rules to target direct children; restricted action-panel heading/paragraph rules to their own heading group; restored the opening date's public SHLZ field/control wrapper. Field names, IDs, CSRF, request/revision binding, action and date semantics are unchanged.

Verification: real isolated `yii2_preopening_browser_001_test.php` passes original upload/correction/retry/application/opening with durable date/history assertions, plus new calendar-arrow columns/title geometry and unskinned date-input checks at1440/390px. Live isolated Playwright on object1347 verified the same geometry and selected2026-09-28 into the ISO hidden field; all live POSTs were blocked and the opening form was not submitted. Desktop/mobile screenshots were visually inspected. Private fixture evidence: `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-preopening-d25e4f1a82d9`. Source is preserved in Git and the production image; no review/CI approval is implied by this emergency delivery.
