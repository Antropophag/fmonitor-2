# YII2-COMPLETION-FORM-RECOVERY-001 — Gate 3 rereview v3

- Reviewer: `/root/completion_gate3` (independent; did not author specification or tests)
- Exact test candidate: `d43513daa4fb8847699edaf78b896515b9143949`
- Prior immutable reviews: `378b28501c57439823257a3accb63c936ed38e82`, `7a36470cc3e9e01a46553924a6570a1feb38ba5a`
- Candidate source: `4482c708d924221fb3b74d7afa8215b2a81ab464a1b048b8b486d7cf58dcc1f7`
- Base: `bced877aec8a8802e97037749ca4251d3098df1a`
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260922T173225Z-6f903651fe/verification-plan.json` (`CRITICAL`; Gate 3 and final review required)
- Server test: `tests/Yii2/yii2_completion_form_recovery_001_test.php` (`git hash-object`: `81490a308c56e6ec14d2585dbfbec63d5ad08b38`)
- Browser runner: `tests/Yii2/yii2_completion_form_recovery_browser_001_test.php` (`git hash-object`: `61a2593c31f0b750f1b1679256152760f6713b2a`)
- Browser script: `tests/Yii2/completion_form_recovery_browser.mjs` (`git hash-object`: `5d6299e5765c1232d5ea1ed72ba628106912844e`)
- Server RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790098213588556000-76a89abbd94c4f3bb17a0ff8358ec371.json`
- Browser RED: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790098346937398000-7115684790a442cc842baed90aa99cc3.json`
- Verdict: **CHANGES_REQUESTED**

## RED validity

Both records are meaningful product REDs.

The server record reaches the authenticated Yii completion POST with an isolated
incomplete-checklist fixture and fails because the existing `409` is plain text
rather than object-card HTML. Its source digest predates the final candidate, but
the later delta changes only the separate browser script's explicit missing-hook
assertion; the server test and production behavior are unchanged.

The browser record is exact to the candidate source. It starts the real HTTP fixture
and Chromium, completes the native two-step login, reaches object 4512, and fails at
the explicit assertion that the pre-change card has no
`data-completion-form="correct_declaration"` hook. That hook is required production
behavior, so this is an intended product RED rather than a setup failure or an
unrelated regression. The harness's generic `REGRESSION_FAILURE` classification does
not match the observed causal failure.

## Prior findings disposition

1. **Executable browser matrix — independently reachable, but not yet deterministic.** The browser family is a separately
   registered acceptance command, so an earlier server RED no longer masks its
   setup or sensitivity. One route observes every completion POST. The scenario
   proves synchronous double-submit suppression, a quiet period with no delayed
   retry, network abort recovery, retained DOM values, `422` card replacement,
   open correction details, field focus, active tab, viewport scroll, `409` form
   retention/general focus, confirmed navigation, and immutable roots. However,
   the non-HTML phase waits for the same warning text that is already visible from
   the preceding network-abort phase. That wait can resolve before POST two has
   completed and before the form is unlocked; POST three may then race the in-flight
   guard. The claimed non-HTML recovery and exact five-request path are therefore
   timing-dependent. The test needs a new observable transition or route-completion/
   unlock handshake before initiating the `422` request. In addition, the `409`
   branch does not assert that correction details remain open, the containing tab is
   active, and the focused general error is in the viewport, although A3 applies
   those behaviors to received `422/409` HTML.

2. **A2 domain/access/transport matrix — acceptable at the server seams.** The server
   test independently reaches all five named conflicts with literal messages,
   original statuses, and no-fact assertions. `FACT_ALREADY_RECORDED` verifies the
   form-level general error and server retention; `FACT_NOT_FOUND` receives the
   connected browser `409` retention/focus check. For checklist/case/order state
   changes, and especially lost capability/session, retaining or recreating an
   unavailable form would present a command the current user/state may no longer
   execute. The tests correctly prefer current authorization and current card facts:
   access responses disclose no document data and create no facts. CSRF, unknown
   action, malformed form, nonexistent object, and expired-session paths remain
   separate non-field results and do not render protected card data.

3. **A1 presentation and isolation — acceptable apart from the browser race above.** All four
   forms have real HTTP validation coverage for exact retained values, accessible
   field association, focus target, visible completion panel, correction disclosure,
   no-fact behavior, and corrected `303` flow. The action-scoped sibling sentinel and
   nonexistent-object marker reject cross-form/object state transport; the browser
   additionally proves multi-field retention across both `422` and `409`. Exact
   implementation structure remains a final code-review concern, not a missing Gate
   3 oracle.

4. **A4 invariants/regressions — resolved in combination with registered owners.**
   The focused server and browser tests now snapshot immutable roots and bound the
   appended correction counts around successful retries. Existing registered owners
   `tests/Yii2/yii2_object_details_editing_001_test.php`,
   `tests/Yii2/yii2_object_details_editing_browser_001_test.php`, and
   `tests/Yii2/yii2_current_checklist_stage_001_test.php` cover the named editor and
   #236 regression surfaces in the required exact-source full CI matrix. Existing
   documentary owners retain exact history-value coverage.

5. **Isolation is acceptable; browser determinism has the synchronization exception
   above.** Both commands create private database
   fixtures and artifact directories and clean them in `finally`; browser execution
   is timeout-bounded, config is mode `0600`, and routed network branches have fixed
   ordinals. Expected statuses, messages, URLs, POST count, DOM state,
   focus state, root snapshot, and correction count are independent literal or
   fixture-derived values. PHP and Node syntax checks pass and the delta is clean
   under `git diff --check`.

## Required changes before rereview

- Synchronize the non-HTML browser phase on a fresh observable result, explicit
  route completion, and/or confirmed form unlock before starting the `422` submit;
  retain the exact all-POST counter and quiet-period assertion.
- Apply the existing open-details, active-tab, and viewport/focus assertions to the
  browser-consumed `409` replacement as required for both HTML rejection statuses.

Production implementation remains unauthorized by this Gate 3 rereview.
