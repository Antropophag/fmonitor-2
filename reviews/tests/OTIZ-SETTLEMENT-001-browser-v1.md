# OTIZ-SETTLEMENT-001 — full Yii browser Gate 3 v1

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Specification/test author: root
- Reviewed test/spec candidate: `915cfe0a`
- Production WIP: explicitly excluded from review
- Verdict: **CHANGES_REQUESTED**

The browser harness is otherwise sound. Real Chromium follows the protected URL
through both Yii login steps and uses native clicks on rendered forms. It records
distinct rendered UUIDs, uses rendered CSRF, runs the server with DML-only
credentials, bounds browser/server execution, avoids pipe deadlocks with files,
and preserves diagnostic artifacts. The independent money oracle requires
discipline10000, paid90000 and linked reversal-10000, exactly three receipts and
four events, and byte-equivalent accepted snapshot/object rows.

The captured RED is valid: Chromium completes login but returns to
`/pilot/objects` instead of the requested snapshot, browser exit1 / parent exit255.
Setup, browser launch and authentication succeeded. The required plan check
returned `CHANGE_VERIFICATION_OK`; inventory reports all15 PASS.

Two blocking traceability gaps remain:

1. The new normative supplement requires retained calculation details,
   allocations and issues. The fixture creates no representative allocation or
   issue and supplies no calculation trace; the browser asserts only one object
   row and summary values. Seed representative trace/allocation/issue data,
   assert their rendered semantics, and include their source rows in the
   unchanged accepted-facts oracle as applicable.
2. The supplement states that an invalid browser command shows the retained
   error without facts. The browser performs only successful submissions. The
   HTTP supplement checks crafted requests/query messages, while the new browser
   acceptance mapping lists only the browser PHP test. Add one native invalid
   rendered-form submission that asserts the retained error and unchanged
   closure/event/receipt counts, or explicitly narrow the normative browser
   requirement and bind the HTTP test into the acceptance if invalid behavior is
   intentionally HTTP-only.

No other finding was found for scope, expected-value independence, admission,
rendered form use, persistence sensitivity, determinism or cleanup.

Reviewed identities:

```text
36597bab4286898ccb5bb65a3775fdbcd6fededec036bc624b9300810fc42f79  specs/OTIZ-SETTLEMENT-001.md
0c2b2d8f552691e7ba77be4c23d97769bacd97701cf9cc428fa2ba890697cd22  tests/Yii2/yii2_otiz_settlement_browser_001_test.php
2d4518ed3532fe4c0bb7195e146b67bff82610573b2767603a7493e0594c9147  tests/Yii2/otiz_settlement_browser.mjs
36faacd587b8b14b70a3d74e71ad096ba5b4fe6bf6e6cc4bbe498e01de1cbcda  docs/operations/otiz-settlement-browser-red-2026-09-10.md
```

Browser Gate 3 remains **CHANGES_REQUESTED**. Existing core, POST and bounded GET
approvals remain valid. No production correction is authorized from this record.
