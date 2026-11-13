# MONTH-SESSION-001 — independent final review

Reviewer: `/root/month_session_final_review` (independent; no specification, test, or implementation authorship)

Verdict: **APPROVED for the bounded production-first session configuration change**.

This approval covers the reconstructible candidate against base `6664d5d85dc5e0812b7b4758457087a683f5644d`. It does not claim Gate 3, canonical full GitHub CI, automated admission, merge readiness, deployment, or full-suite GREEN; those remain owner-deferred or `UNKNOWN` under the current delivery record.

## Binding

- Candidate source: `79887c615094e52c278bbcc74ae6e1334ad27187cf2142e8a7a01f4066e549a5`
- Executable source: `14df5746dd0ea1da84c8d96d0dbcbea7dcc7fdaa6514caaa7d375fa141264b28`
- Base: `6664d5d85dc5e0812b7b4758457087a683f5644d`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261113T134550Z-8ee0fc041c/package.json`
- Candidate `config/yii/web.php` SHA-256: `94a3cfeff797c9869289faa8828f155d95105471150b9f12f4aee9c3586f0277`
- Production/base config preflight SHA-256: `652e31e4ca786f5cdabbceb6ebd8f884299b9d23ed10985a90c5e33c98bd2cb1` (host and PHP copy matched before deployment)

## Findings and evidence

No unresolved findings.

The production change is limited to the three contract settings in `config/yii/web.php`: server timeout and cookie lifetime change from 604,800 to 2,592,000 seconds, and `SameSite` changes from `Strict` to `Lax`. Cookie name, storage path, strict session IDs, HTTPS-derived `Secure`, `HttpOnly`, `/pilot` path, identity settings, keys, and disabled auto-login remain unchanged. The implementation does not delete session state, rotate keys, change users or rights, introduce remember-me/sliding renewal, or create a second identity mechanism.

The browser regression uses the real Yii login and protected route, closes and reopens Chromium with the same persistent profile, verifies the same authenticated session survives, and follows a top-level GET from a distinct site. It also proves that an invalid same-origin CSRF logout preserves the authenticated session, a cross-site POST omits the Lax session cookie and is rejected, and a valid CSRF logout terminates the session. The companion configuration assertion covers the 30-day server/client lifetime and retained security flags; the existing session-failure regression covers persistence write/destroy failures. The updated admin browser expectation is consistent with the new Lax contract.

The retained RED evidence fails on the former seven-day cookie lifetime. The supplied focused run is GREEN for `yii2_month_session_001_test.php` and `yii2_session_failure_001_test.php` at executable source `14df5746dd0ea1da84c8d96d0dbcbea7dcc7fdaa6514caaa7d375fa141264b28`; `git diff --check` and PHP lint are clean. The review did not repeat the focused run because the local checkout lacks its container-only dependencies, and it did not run the prohibited local full suite.

Remaining limits: old cookies receive the new lifetime and policy only on the next login; browser privacy policies that suppress cookies are not bypassed. Gate 3 and canonical exact-source full CI were explicitly deferred for this production-first route. The harness therefore continues to report CI/admission as `UNKNOWN`, and this review must not be used as automated admission or a full-matrix GREEN claim.

## PR #292 integration delta review — 13 November 2026

Reviewer: `/root/month_session_final_review` (independent; no authorship of this root-authored test correction)

Verdict: **APPROVED** for candidate source `4f38f82fc8e4b3de5cf86330983ddc262f361cb75cdf640fd45dd7abe222e467` and executable source `718131f4b3d980d149d7f49d9caf1cd1970743b8ba639a6f10e1cd7dbe59d945`, prepared in `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261113T142908Z-01d40309f5/package.json`.

No unresolved findings. Against the previously reviewed commit `dd08855c1da160b3ad855ee6825cc8eeac2903f3`, the executable delta changes only the stale authentication regression expectation from `SameSite=Strict` to the approved `SameSite=Lax` contract. It retains the same real login seam and the adjacent cookie path, `HttpOnly`, HTTP `Secure` omission, session rotation, password-hash, throttling, authorization, and asset assertions. The verification input adds this existing registered test to the candidate boundary, and the delivery record preserves the integration history; neither changes production behavior.

The complete first CI run `43439035233` showed one failed job/category and one `REGRESSION_FAILURE`, at `tests/Yii2/yii2_authentication_001_test.php:84`, because the runtime correctly emitted Lax while the assertion still required Strict. All other categories and the other integration shard passed. The corrected focused authentication test is GREEN in the integration profile with exit 0 at executable source `718131f4b3d980d149d7f49d9caf1cd1970743b8ba639a6f10e1cd7dbe59d945`; `git diff --check` is clean.

This delta approval does not turn the failed first run into GREEN. The replacement exact-source full CI run remains required before merge, and its result must be evaluated independently.

## PR #292 clipboard-fixture delta review — 13 November 2026

Reviewer: `/root/month_session_final_review` (independent; no authorship of this root-authored test correction)

Verdict: **APPROVED** for candidate source `44dcd62e868f912cb722bedc6af29326e7b74fd949cadc1964c67917878b365a` and executable source `65a4923a171acb804bf9ad5330b726127f9ffcca60e44a7793edb9561786d5e0`, prepared in `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20261113T145624Z-34994b3844/package.json`.

No unresolved findings. Against the previously reviewed commit `bfcc68f7d3c8fb7fcb5f993d98e5b6c1d59d9fe0`, the executable delta changes only `tests/Yii2/user_access_browser.mjs`. The former fixture deleted the own `navigator.clipboard` property, which exposed Chromium's inherited native API; the retained probe demonstrates `own=false` with an available function after deletion. The correction defines an explicit own `undefined` value, asserts the API is actually absent, and retains the rejection, exact copied URL, and success-path assertions. It strengthens the keyboard-fallback check by also requiring focus and full selection of the invitation URL. The property remains configurable so the following success mock is installed normally. Production `users.js` is unchanged.

The complete CI run `43443426217` had one failed executable category/job and one `REGRESSION_FAILURE`: `yii2_user_access_browser_001_test.php` timed out waiting for the success message after the inherited native clipboard call asynchronously overwrote state. Fast, unit, governance, and both integration shards passed; the failed aggregate verify/Quality Graph results reflect that same e2e failure. The complete failed-job inventory and logs were inspected. The corrected full focused user-access browser flow is GREEN with exit 0 at executable source `65a4923a171acb804bf9ad5330b726127f9ffcca60e44a7793edb9561786d5e0`; `git diff --check` is clean. No additional test is needed for this fixture-only correction.

This approval does not claim CI GREEN. A new exact-source full CI run remains required before merge, and its result must be evaluated independently.
