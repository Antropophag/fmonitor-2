# OTIZ-SETTLEMENT-001 — Yii HTTP commands Gate 3 v1

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/settlement_review`
- Reviewed exact test candidate: `410fb5603e1f2fc7d192d15e243f9c065c45ec7d`
- Test: `tests/Yii2/yii2_otiz_settlement_001_test.php`
- Declared acceptance: `authorized-http-commands`
- Verdict: **CHANGES_REQUESTED**

The current test is a valid public raw-HTTP RED for the retained discipline URL.
It establishes canonical v24 setup, real Yii login, current exact
`otiz.manage`, valid CSRF, a POST to
`/pilot/otiz/snapshots/301/closures`, expected `303` redirect, and one added
closure/event. Reported actual `404` with no Location is the intended missing
route rather than setup failure.

The declared verification acceptance and normative HTTP boundary are broader
than this single happy path. Blocking coverage is missing:

1. Successful HTTP delegation for
   `/snapshots/{id}/payments/complete` and `/closures/{id}/reverse`, including
   their observable redirects/results and facts.
2. Session/current-permission and CSRF denial at the new Yii routes. Core owner
   authorization tests do not prove the adapters cannot bypass admission.
3. HTTP parsing rejection for malformed IDs, UUID, money, basis and artifact as
   applicable, with no facts. The contract assigns parsing to HTTP.
4. Exact translated discipline facts and operation receipt. Closure/event count
   increments alone can pass if cents, basis, artifact, actor, snapshot/object,
   fingerprint, or event payload are wrong.
5. A negative proving arbitrary `paid` or `deadline` form fields cannot alter
   the discipline command, as arbitrary manual paid/deadline input is forbidden.

Add the bounded three-command success and rejection matrix without duplicating
the already approved owner-level financial scenarios. Preserve fresh RED and a
valid exact-candidate verification plan, then request rereview. Gate 4 HTTP
wiring is not authorized by this record.

Reviewed initial test identity:

```text
4c0e56156831b8683d29f77992333eaece1e2bfface9883ea9e98f013b251698  tests/Yii2/yii2_otiz_settlement_001_test.php
```
