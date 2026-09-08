# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v11

- Date: `2026-09-04T18:07:01+03:00`
- Reviewer: separately tasked agent `/root/object_list_local_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; reviewer authored none of the specification, fixture, tests, production, RED evidence, or prior reviews
- Reviewed exact HEAD: `513d00debbf547e74bb35be9656627405fc913e0`
- Correction baseline: `03434e6eb18d66d435dac59ce5c86cbb854c4376`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **APPROVED**

This approval applies only to the exact reviewed test, fixture and integration
composition below. It authorizes minimal Gate 4 GREEN. It is not production
approval, Gate 5, repository-wide GREEN, CI readiness or release evidence. The
reviewer changed no test or production byte.

## v10 finding closure

The sole v10 finding is closed. The added public mixed-fault process has:

```text
FMONITOR_AUTH_USER_ID absent
REMOTE_USER=legacy.object-list@example.invalid
FMONITOR_SHLZ_CSS_PATH missing
FMONITOR_PILOT_CSS_PATH missing
FMONITOR_DB_PASSWORD wrong
```

It executes raw GET and HEAD through `polParity()` and requires exact `401
Authentication required.\n`. `polAuthorizationError()` additionally requires
the complete singleton application-header allowlist, no `Retry-After`, no
correlation ID, no cookies/location/auth challenge/server disclosure and the
generic redacted body. `polReadOnly()` compares the complete ordered database
schema/rows/AUTO_INCREMENT snapshot, while the filesystem guard observes the
request. Thus a conditional CSS-first or DB-first implementation for an absent
local actor cannot satisfy the test, and legacy `REMOTE_USER` cannot rescue the
missing trusted local key.

The separately retained healthy-CSS legacy-only case still requires `401` with
the least-privilege denial principal. The canonical positive environment omits
`REMOTE_USER`; its GET/HEAD, representation identity, repeat, and a separate
differing legacy-decoy parity request pass before the intended RED. The
independently approved `local_rbac_objects_route_admission_001_test.php` also
passes a real exact `objects.read` grant without `REMOTE_USER`.

The resulting controlling order is now executable:

```text
route/method
→ trusted local actor ID / active local role / exact objects.read
→ configured CSS
→ object-list read
```

No old positive `REMOTE_USER` requirement is resurrected. Route and method
priority, authorization-read-before-CSS mixed fault, isolated list-read
`503 + Retry-After: 60`, later origin-query RED, exact representation,
classification/pagination exclusions, revoke/repeat, foreign-decoy preservation
and attempt-all cleanup remain unchanged from the reviewed v10 bytes.

## Fresh reproduction

```text
$ date --iso-8601=seconds
2026-09-04T18:07:01+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check \
    03434e6eb18d66d435dac59ce5c86cbb854c4376..513d00debbf547e74bb35be9656627405fc913e0
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: CSS unavailable after healthy local
authorization and before list read status
Expected: 503
Actual: 200
... tests/InstallationProcess/pilot_object_list_001_test.php(298): polError()
exit 255
```

The mixed-fault correction executes and passes before line 298. The failure is
the same honest missing configured-CSS behavior recorded by v10, not setup or
authorization failure. The launcher uses `/usr/bin/env -i`, and the finally
path completed without task-owned database, principal or filesystem residue.

## Gate decision

**APPROVED.** OpenSpec task `2.2` may be marked complete. Minimal Gate 4 may
restore configured CSS validation after successful local authorization, then
implement the already reviewed later object-list behavior. Any test, fixture,
specification or integration-composition byte change requires fresh independent
Gate 3 review.

## Exact reviewed-input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
9151e5b82c6d89122103d381e648c807632bc92dbfb5da52f30acaf8f5676562  tests/InstallationProcess/pilot_object_list_001_test.php
bfbf9f1a9ced25873dcb189e384829680033ebf571565f7b0c2c80661bbb7c7a  tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
54508b7e5d578acbd10de080edfe0a6dffa11b77c7e7ce515f6989e5dd1fdb23  docs/operations/pilot-object-list-integration-red-correction-v11-2026-09-04.md
14caa204754bed181d8fb41dbd505eabd7197c46f41d74f63daf74c0db67865f  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v10.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v11.md
```
