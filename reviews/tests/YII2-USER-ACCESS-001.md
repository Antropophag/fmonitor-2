# Test review: YII2-USER-ACCESS-001

- Reviewer: Codex independent sol/low reviewer `/root/review76_access`; authored neither specification nor tests
- Test author: root agent under the owner-authorized autonomous #82 then #76 assignment; source commit authored/committed by Timofey Grishin
- Reviewed source: commit `2cd9ffa03a6359b41dd598fae741801be5585d76`, base `8c4468738a96953055d35fe6c05bd0c5d26539e4`
- Agreed review scope / prior findings disposition (for rereview): first whole-candidate Gate 3 review; no prior findings
- Specification: `specs/YII2-USER-ACCESS-001.md`
- Public seam: `YiiUserAccess` and real Yii HTTP/browser routes
- Verification plan: `.local/verification/76-plan.json`, regenerated from the reviewed source, SHA-256 `e4c42424b96329023a61e502ca6177a38e11a8b54b46635e12a114239ce1510a`; `CHANGE_VERIFICATION_OK`; required categories `e2e`, `governance`, `integration`, `unit`
- Red command and intended failure: `php -d display_errors=0 tests/Yii2/yii2_user_access_001_test.php` reaches the isolated migrated database and working Yii login, then gets intended missing admin route `404` instead of `303`; `php -d display_errors=0 tests/Yii2/yii2_user_access_concurrency_001_test.php` reaches fixture setup, then finds the intended absent `YiiUserAccess`; `php -d display_errors=0 tests/Yii2/yii2_user_access_browser_001_test.php` completes real browser login, then gets intended users-route `404` instead of `200`. Evidence: `/tmp/76-user-access-final-red.log`, `/tmp/76-concurrency-final-red.log`, `/tmp/76-browser-final-red.log`.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — the directory/read contract is mostly insensitive.** `tests/Yii2/yii2_user_access_001_test.php:9-11` checks status, a few action strings, alias and HEAD, while `tests/Yii2/user_access_browser.mjs:39-45` checks only client-side search and two filters. An implementation can omit or leak phone, normalized roles, effective permissions, invitation-valid state, inactive-role permission exclusion, hashes/tokens, or mutate audit/identity during an authorized read and still pass. The normative `read` row explicitly requires each of these. Add owner-result and rendered-HTML assertions for representative active/inactive roles and invitation states, absence of credential/hash/raw-token material, exact effective permissions, and an authorized-read before/after facts assertion.

2. **High — admission, request, and safe infrastructure behavior leave required branches open.** `tests/Yii2/yii2_user_access_001_test.php:8,12-17,43` covers guest redirect, one unprivileged actor, inactive role, near-match permission, spoofing, GET, bad CSRF, malformed invite input and one mutation fault. It does not prove safe guest return, inactive actor, revoked/absent permission, HEAD on command routes, missing versus stale CSRF, schema/read failure `503`, or absence of protected content on every denial. It also does not exercise the explicitly required neighboring login/logout/OTIZ CSRF flows after the shared Yii Request change. Add those cases with no-facts and no-disclosure assertions, and register/run the neighboring regressions in the focused plan or acceptance mapping.

3. **High — several command rejection and persistence clauses have no sensitive oracle.** In `tests/Yii2/yii2_user_access_001_test.php:23-41`, reissue does not cover blocked or missing targets, new-link creator/24-hour expiry, or revocation of multiple earlier usable invitations; activation does not submit a revoked token or cover an active identity that still has an otherwise usable token; role does not cover missing target, malformed action, detach of an unassigned non-default role, HTTP `400` for invalid or HTTP `403` for superadministrator authorization, nor event timestamps; status does not cover invited target, malformed action, HTTP self-denial, or event timestamps. These omissions permit foreseeable partial implementations while the suite passes. Add direct-owner and HTTP cases with exact result/status and unchanged-facts assertions, plus literal persisted time/actor/lineage checks.

4. **High — invitation serialization omits concurrent reissue/reissue.** `tests/Yii2/yii2_user_access_concurrency_001_test.php:16-22` races duplicate invite, activation/activation, activation/reissue and last-superadministrator mutations. The `serialization` row also requires at most one current invitation. Two concurrent reissues can both revoke the old row and each insert a new usable row unless this exact race is locked; none of the existing races catches it. Add a distinct-process reissue/reissue race asserting accepted outcomes, preserved rows, and exactly one current unrevoked invitation.

5. **Medium — raw invitation-token non-disclosure and session-delivery failure are not verified.** `tests/Yii2/yii2_user_access_001_test.php:20-25` proves one-time HTML flash and hashed DB storage, but never inspects server/application logs, and the rollback clause's session-delivery failure behavior is absent. Add a hostile token/log assertion and a deterministic session write/delivery fault proving the response does not claim success and that a possibly committed invitation remains recoverable through reissue without raw-token persistence.

The fixtures otherwise use isolated migrated databases, random database/users and artifact directories, a DML-only application credential, bounded process barriers, and independent SQL observation. The three retained REDs fail for the intended absent production seams after successful setup. PHP/JS syntax checks and `python3 tests/Verification/verification_inventory_001_test.py` (15/15) pass on the reviewed source.

## Required changes

Resolve findings 1-5 and resubmit the complete corrected Gate 2 candidate with fresh intended RED evidence and a regenerated verification plan. No implementation should begin from this candidate.

## Rereview 1 — corrected whole candidate

- Reviewer: Codex independent sol/low reviewer `/root/review76_access`; authored neither specification nor tests
- Test author: root agent under the owner-authorized autonomous #82 then #76 assignment
- Reviewed source: base commit `2cd9ffa03a6359b41dd598fae741801be5585d76` plus retained snapshot `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-access-gate3-rereview`, patch SHA-256 `c20574b5fda4f44c9a3859d8f45073e427b211325d1af1ba30df544c12f31ea5`
- Prior findings disposition: findings 2-4 are resolved. Finding 1 is substantially corrected but remains incomplete. Finding 5 adds both requested paths, but its log oracle is not valid as written.
- Verification plan: `.local/verification/76-plan.json`, SHA-256 `1584dc7623fe92ddb5cb6cdd8adfffd39f169f30bdb64f52ad5ab854e1f39006`; `CHANGE_VERIFICATION_OK`; focused commands now include existing Yii authentication, session-failure, OTIZ HTTP and OTIZ browser regressions.
- RED evidence: `/tmp/76-http-rereview-red.log`, `/tmp/76-edges-rereview-red.log`, `/tmp/76-concurrency-rereview-red.log`, `/tmp/76-browser-rereview-red.log`; all fail at the intended absent route/owner after successful isolated setup.
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **High — the raw-token log assertion is guaranteed to observe transport logging rather than application leakage.** `tests/Yii2/yii2_user_access_edges_001_test.php:49` sends `GET /pilot/activate?token=<hostile>` to PHP's built-in server and then requires that same value be absent from `server.log`. The fixture sends both the built-in server's stdout and stderr to that file (`tests/Yii2/UserAccessFixture.php:53` in the snapshot). PHP's development server records request targets, including query strings, before Yii can sanitize them, so a conforming application cannot make this assertion pass. Separate application logs from the web-server access log and assert against logs owned by the application, or change the normative transport so raw tokens never enter logged request targets and test that boundary explicitly.

2. **Medium — the explicit directory result shape remains only partially asserted.** The corrected specification now requires user `updatedAt`, membership `id/name/active`, and global-role `userCount/updatedAt`. `tests/Yii2/yii2_user_access_edges_001_test.php:18-21` validates user name/email/phone/active/invited, membership active state, and global role code/name/active/permissions, but an implementation can omit or corrupt those remaining fields and still pass. Add exact representative assertions for the full newly normative result shape. This completes prior finding 1 rather than broadening the review.

The corrected candidate otherwise covers safe saved return and external-return rejection; inactive/revoked admission; command HEAD and missing/stale CSRF; safe schema failure; the requested owner/HTTP rejection matrix; timestamps, issuer and invitation lineage; concurrent reissue/reissue with one usable leaf; native session-write failure and reissue recovery; and the neighboring shared-CSRF regressions in the verification plan.

### Required changes

Correct the two findings above, capture a new reconstructible source snapshot, regenerate the plan, and retain fresh intended RED evidence. Implementation remains blocked at Gate 3.

## Rereview 2 — complete corrected candidate

- Reviewer: Codex independent sol/low reviewer `/root/review76_access`; authored neither specification nor tests
- Test author: root agent under the owner-authorized autonomous #82 then #76 assignment
- Reviewed source: base commit `2cd9ffa03a6359b41dd598fae741801be5585d76` plus retained snapshot `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-access-gate3-complete`, patch SHA-256 `18f2aca6f23131cd4f869b697e012459306486d1efbc7871ccee880625df18d7`
- Prior findings disposition: all first-review findings 1-5 and rereview-1 findings 1-2 are resolved for the agreed Yii application slice
- Verification plan: `.local/verification/76-plan.json`, SHA-256 `317bad54a9bae405a7ae1f636212edd1427c044f8dcbba3170bfa7421ed6150d`; `CHANGE_VERIFICATION_OK`
- Supplemental boundary evidence: `/tmp/76-log-boundary-probe.txt`, SHA-256 `822f8bef33c37c7e75c7b07dc457dec318350516e749d890ae729f25edd28987`
- Fresh corrected RED: `/tmp/76-edges-complete-red.log`, intended absent `YiiUserAccess` after successful isolated fixture setup; unchanged HTTP/concurrency/browser RED evidence remains applicable
- Verdict: `APPROVED`

### Findings

None.

### Disposition

The final correction asserts the complete pinned directory shape: user source timestamp, membership IDs/names/active states, and global-role user count/source timestamp, in addition to the previously reviewed projection, secrecy, and no-write oracles. It also proves that an already-created owner rereads a committed permission revocation.

The prior raw-token log finding is withdrawn on the concrete harness evidence. The fixture router always handles the request through `public/yii.php`; the retained real-request probe shows the combined captured server/Yii log contains no query token even for the handled Yii `404` path. The assertion remains sensitive to application-owned disclosure in this slice. The separate nginx request-target logging concern is recorded as a required dependency before final Yii cutover and does not invalidate this pre-cutover application candidate.

Gate 3 is approved for implementation from the exact retained snapshot above. Any test or specification change after this source requires review of the changed delta.

## Gate 5 authorization-precedence delta review

- Reviewer: Codex independent sol/low reviewer `/root/review76_access`; authored neither specification nor tests
- Test author: root agent under the owner-authorized autonomous #82 then #76 assignment
- Reviewed source: base commit `2cd9ffa03a6359b41dd598fae741801be5585d76` plus retained snapshot `/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-access-gate3-auth-precedence`, patch SHA-256 `d2aad32fb91f72806dd2fa6d6a5a59197c4122ede6b6fe2ee5abab4c2777f2db`
- Agreed review scope: specification clarification and seven direct-owner assertions added solely for the authorization-precedence defect found at first Gate 5
- Verification plan: `.local/verification/76-plan.json`, SHA-256 `b6a12651fab330061ff1e49e15b772b931e638bd3c737b33639e1d458cff3833`; `CHANGE_VERIFICATION_OK`
- RED command/evidence: `php -d display_errors=0 tests/Yii2/yii2_user_access_edges_001_test.php`; `/tmp/76-auth-precedence-red.log` reports all seven intended failures together after successful isolated database/owner setup
- Verdict: `APPROVED`

### Findings

None.

### Disposition

The clarified admission rule makes the required precedence observable without changing authorized-invalid outcomes. The test exercises the public `YiiUserAccess` seam and proves two independent properties: unauthorized malformed invite/role/status commands return `access_denied`, and an unavailable authorization source throws before malformed-field or self-target rejection. It restores the renamed authorization table in `finally` and compares the complete identity/access fact set before and after, so setup and rejection paths are isolated and no partial write can pass unnoticed.

The corrected implementation may now proceed. Gate 5 rereview must cover the production delta against this approved expectation.
