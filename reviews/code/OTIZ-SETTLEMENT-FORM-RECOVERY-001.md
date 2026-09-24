# Gate 5 — OTIZ-SETTLEMENT-FORM-RECOVERY-001

## Independent final review

**Verdict: CHANGES_REQUESTED — blocking findings remain.**

I reviewed candidate source `ecc270d2736af4d7b5ae17b16e35d616f5413e35b0bc67b2f51725686f3a9a6a` from exact-source package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T192816Z-9d0b7b514a/package.json` against `specs/OTIZ-SETTLEMENT-FORM-RECOVERY-001.md` (package digest `cc5ce9078449ded92029cc16b6d165c579c88129f987a024b4aa6e806ef698bf`), the approved Gate 3 record, the complete candidate delta from base `d9dddb31f9c6e07092bcf6d4c04df761a1a13ccd`, and all eight retained focused GREEN records. I authored neither the specification/tests nor the implementation.

The server-side implementation conforms in the reviewed areas: strict decimal parsing and the maximum use checked decimal strings; validation and known owner refusals preserve only the allowlisted one-time session state; snapshot/object membership is checked before disclosure; authorization and CSRF remain framework-owned; stale completion returns a no-store HTML 409 without financial writes; values and errors are escaped and described accessibly; and the existing `OtizSettlement` owner, formulas, schema, rights, replay/conflict behavior, and #248 ledger history remain intact. The focused evidence also exercises those paths without an observed false positive. The blocking defects are in the browser UNKNOWN-outcome state and in the browser test's missing success/payment oracles.

### Findings

1. **HIGH — a confirmed successful closure is subsequently presented as an UNKNOWN outcome.** `app/YiiRuntime/Assets/otiz.js:39-45` writes closure input to `sessionStorage` at submit, but clears it only when the destination contains `data-otiz-open-on-error`. A normal successful POST redirects to the same snapshot pathname with `?closed=1`; that page has no recovery drawer, so line 43 restores the submitted values and adds “Результат отправки неизвестен” despite the browser having received the confirmed success redirect and despite the page simultaneously rendering the success status. This violates A05's requirement to use UNKNOWN only when the browser did not receive a server outcome and creates a dangerous contradictory financial result. Clear/resolve pending state for confirmed server outcomes without losing the genuine transport-failure recovery path, and add a browser assertion that an ordinary successful closure shows success and never shows UNKNOWN.

2. **HIGH — payment completion does not implement the required UNKNOWN operation-ID recovery.** The payment form is explicitly marked `data-financial-form` at `app/YiiRuntime/Views/otiz-snapshot.php:15`, so it receives only the generic in-flight disable in `otiz.js:45-46`. Pending state is written and restored exclusively for actions ending in `/closures`, and restoration searches exclusively for a closure form (`otiz.js:43,45`). If `/payments/complete` is sent but its response is not reliably received, navigation/back restoration re-enables a form whose server-rendered `ViewSupport::uuid()` may be new; there is no UNKNOWN notice and no preservation of the submitted operation ID for an explicit replay decision. That violates A05 for one of the two financial public seams and can turn uncertainty into a distinct command rather than an exact replay. Preserve and surface pending payment completion with the same operation ID, without retry/polling, and exercise its aborted-response and explicit replay behavior in the browser/HTTP acceptance flow.

3. **HIGH (test adequacy) — the GREEN browser test cannot catch either A05 regression above.** `tests/Yii2/otiz_settlement_form_recovery_browser.mjs:10-11` checks stale payment and an aborted closure request, but never lets a closure complete successfully and never aborts an ordinary payment-completion request. Its UNKNOWN check also forces a fresh GET after the abort, which proves restoration only for the one closure-specific storage shape. Consequently the test remains GREEN while the implementation emits UNKNOWN after known success and drops UNKNOWN/idempotency state for payment completion. Extend the root-owned test expectations before accepting a correction; because this changes executable acceptance coverage, recompute the verification plan and follow the repository's Gate 2/Gate 3 rules for the resulting exact source.

### Evidence provenance

The prepared package reports GREEN at this exact candidate source for:

- `php tests/Yii2/yii2_otiz_object_ledger_history_001_test.php` — record `1790277915711385000-e94e02c5daa04fecbc8f180dfe1ee81a`;
- `php tests/Yii2/yii2_otiz_settlement_001_test.php` — record `1790277930643197000-dd703bcaeee94211835e97b56b91afdc`;
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php` — record `1790277943761365000-b99ce0caf6e7434a835461c35d59ff3f`;
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_browser_001_test.php` — record `1790277957237395000-76e4ed74d0f54e60ba9acd2124a5f517`;
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py` — record `1790277974080269000-76ff14bed4f748ed9725d1a362ea24b9`;
- `python3 tests/Verification/change_verification_001_test.py` — record `1790278017943916000-e7509892c1fb428dbbd0678a06ba5278`;
- `php tests/Runtime/runtime_storage_001_test.php` — record `1790278051116675000-9b4e8793a66e475fb9fcfd66701e0139`;
- `python3 tests/Verification/architecture_guard_001_test.py` — record `1790278059670895000-c0c336c4a6f744e292972a6943aa5553`.

The mandatory exact-source GitHub `make test` run remains `UNKNOWN` in the package and is not represented as GREEN or approval. Gate 5 is not approved for this source.

## Correction review — exact source `c398117e8071a64ec3d21335a41b7df892b97155ee3aa403ef6f97cf4c16d27a`

**Verdict: APPROVED — no blocking findings remain.**

I independently re-reviewed the complete corrected candidate from exact-source package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T193940Z-a75052f39a/package.json`, including its delta from the previously returned source, the complete production/test diff from base `d9dddb31f9c6e07092bcf6d4c04df761a1a13ccd`, the unchanged normative spec digest `cc5ce9078449ded92029cc16b6d165c579c88129f987a024b4aa6e806ef698bf`, the independently approved Gate 3 correction-test delta, and all eight fresh focused GREEN records. I authored neither specifications/tests nor implementation.

### Prior-finding dispositions

1. **FIXED — confirmed closure success no longer appears UNKNOWN.** `app/YiiRuntime/Assets/otiz.js` now types pending commands and resolves the matching closure entry when the returned snapshot page carries `closed`, `error=closure`, or server recovery state. The browser test first proves the aborted closure remains UNKNOWN with its original operation ID and values and no retry/poll, then explicitly submits that recovered form, awaits `?closed=1`, checks the real success status, and requires the UNKNOWN notice to be absent. This test failed at the intended success-clear assertion before implementation, so the GREEN is sensitive rather than tautological.

2. **FIXED — payment UNKNOWN state preserves the exact operation ID through an explicit decision.** Payment submit now stores a typed pending record containing the snapshot path and rendered operation ID; restoration targets `/payments/complete`, renders the UNKNOWN notice, and reuses that ID. Confirmed `paid`, `error=payment`, and the stale 409 page's server-owned `data-otiz-confirmed-outcome="payment"` marker resolve pending state. The browser scenario aborts exactly one payment request, proves no automatic retry/poll, verifies the notice and recovered form contain the same operation ID, removes interception, explicitly submits, awaits `?paid=1`, and verifies UNKNOWN is cleared.

3. **FIXED — executable coverage catches both former A05 regressions.** The corrected test covers synchronous double-submit suppression, closure transport failure and confirmed success, payment transport failure and confirmed success, same-ID restoration, request counts, and the resulting financial inventory. Its wrapper independently asserts exactly two closures, three events, and two operation receipts: one explicit closure command and one explicit payment command despite the intercepted attempts. Route interception is exact and removed before explicit commands, navigation is awaited, and operation IDs are read from rendered public forms. I found no false-positive path that would let the former implementation pass these assertions.

### Full corrected-diff assessment

No new blocking finding was found. The correction does not introduce a financial writer, calculate available money in HTTP/JS, alter formulas/schema/roles/routes, weaken membership/authentication/CSRF, or change owner replay/conflict semantics. Strict parsing and limits, allowlisted one-time server recovery, safe known-domain errors, stale no-write 409, escaping/accessibility/no-JS behavior, #248 append-only history, and adjacent settlement regressions remain covered and unchanged except for the stale page's bounded confirmed-outcome marker and asset registration needed to clear known payment state.

### Exact-source evidence

- `php tests/Yii2/yii2_otiz_object_ledger_history_001_test.php` — GREEN record `1790278554544881000-faa94258285d4daa9a78aece0a469c57`;
- `php tests/Yii2/yii2_otiz_settlement_001_test.php` — GREEN record `1790278571039895000-79b09083ce1c4c3d8a9e72b765f406ad`;
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_001_test.php` — GREEN record `1790278585292455000-797bf218f09a404ca567287ecc70628c`;
- `php tests/Yii2/yii2_otiz_settlement_form_recovery_browser_001_test.php` — GREEN record `1790278600400595000-5157c34053674430a1b640adc6eb76b5`;
- `python3 tests/Deployment/pilot_jobs_compose_001_test.py` — GREEN record `1790278619514160000-23b77bb862fb45a0a8d776ce0f8f6989`;
- `python3 tests/Verification/change_verification_001_test.py` — GREEN record `1790278704167831000-bfb2c44953364244912df4d6460227b3`;
- `php tests/Runtime/runtime_storage_001_test.php` — GREEN record `1790278736678720000-80ff1a4dc7fa4c308c22c8874dc73972`;
- `python3 tests/Verification/architecture_guard_001_test.py` — GREEN record `1790278744565135000-3408852642f34284b185407f9d700080`.

Gate 5 is **APPROVED** for exact source `c398117e8071a64ec3d21335a41b7df892b97155ee3aa403ef6f97cf4c16d27a`. The mandatory exact-source GitHub `make test`, PR/publication, merge, and deployment remain separate and `UNKNOWN`; this review does not represent them as complete.
