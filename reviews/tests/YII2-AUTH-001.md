# Independent test review: YII2-AUTH-001 base increment

- Reviewer: root agent, not test author.
- Test author: /root/auth_plan, gpt-5.6-sol/low.
- Reviewed commit: 4e083473.
- Public seam: real public/yii.php HTTP login/logout/admin roles.
- RED: `php tests/Yii2/yii2_authentication_001_test.php`, exit255, expected
  guest303 /pilot/login, actual404/null; isolated canonical DB setup succeeded.
- Verdict: `APPROVED` for initial auth/roles increment; late session-write fault
  proof remains separately required before completion of task2.2 and the slice.

## Findings

Fixture uses unique canonical DB and temporary sessions, never shared DB reset.
Expected outcomes follow current 10-failures/15-minute throttle, cleared successful
attempt bucket, /pilot/objects default and explicit permission facts. Root's review
corrected accidental policy changes and test defects before approval: threshold,
landing URL, same-port restart, status assertion, session rotation/version-only
revocation, current role label, case-insensitive cookie attributes, invited/disabled
admission, and no eager DB dependency for unauthenticated login GET.

Tests observe actual two-step forms, session identity renewal, current permissions,
role labels changing after DB fixture edit, 403 missing/near-match/inactive grant,
CSRF refusals, logout, restart and safe missing-storage/DB failures. PHP session
cookie encoding and old storage internals are not asserted. Yii400 invalid-CSRF
mapping is explicitly documented, not called byte-compatible with old403.

## Remaining delivery proofs

Late close/write failure before redirect/body requires independent followup RED
and review, as named in OpenSpec tasks. UI/assets parity and browser/FPM proof
must accompany the first real read route; simple HTML marker alone does not prove
visual parity. This approval allows initial implementation, not merge or #71 closure.
