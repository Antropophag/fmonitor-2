# Independent test review: YII2-AUTH-001 late persistence

- Reviewer: root agent, not test/support author.
- Author: /root/inventory_review, gpt-5.6-sol/low.
- Reviewed test commit: 5c9c9873.
- Test SHA256: 13b637cae3aac9e3a331f3155f1ea9b9df55b93b66e4cb87bde32fe6769d9568.
- Support SHA256: 10807da119b795f851d6f8c604852e0c285ea7e00eb0aab527b90f52ecbf1760.
- Public seam: production public/yii.php login/logout over actual HTTP.
- RED command: `php tests/Yii2/yii2_session_failure_001_test.php`, exit255.
- Actual pre-fix outcome on in-progress auth implementation:503 retained Location;
  assertion `late session write failure no success redirect`, expectedfalse/actualtrue.
  Root repeated this failure before the fix. This is WIP implementation RED, not
  a claim that auth routes already existed in the test-only HEAD.
- Verdict: `APPROVED`.

## Findings

Normal forms, CSRF and session are established before injecting failure, proving
setup. Test-only auto_prepend_file registers a decorator of documented native
SessionHandler; normal reads/open/other operations delegate to PHP. A private marker
contains operation names only and proves the requested write/destroy fault occurred.
No production fault flags or old storage protocol are introduced.

Assertions reject success redirect, live authenticated cookie, retained success
body, caching and private data in503 responses. Unescaped JSON makes path/hash
redaction assertions sensitive; legitimate cookie expiry is allowed. Callback marker
proves execution without dictating retry counts or PHP implementation internals.
The fixture uses a unique DB and temporary storage, with same-port process restart.

## Required changes

None in the test. Production must cancel success headers/cookies and emit the safe
failure before delivery; auth author may now implement that fix and rerun both suites.
