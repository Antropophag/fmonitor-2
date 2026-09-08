# PILOT-OBJECT-LIST-001 integration RED correction v11

- Date: `2026-09-04`
- Gate: `2` narrow local-authorization precedence correction
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact correction baseline: `03434e6eb18d66d435dac59ce5c86cbb854c4376`
- Returned review: `reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v10.md`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Mixed-fault missing-actor proof

The v10 positive no-REMOTE path and healthy legacy-only denial did not prove
that a missing local actor wins when CSS and downstream DB are also broken. The
new isolated process combines:

```text
FMONITOR_AUTH_USER_ID absent
REMOTE_USER=legacy.object-list@example.invalid (valid active legacy decoy)
FMONITOR_SHLZ_CSS_PATH missing
FMONITOR_PILOT_CSS_PATH missing
DB password invalid
```

Raw public GET/HEAD must return exact `401 Authentication required.\n`, with
GET/HEAD header parity, empty HEAD, no Retry-After, no correlation ID, no
product/list body, and a byte-equivalent complete DB/filesystem snapshot. This
proves missing local actor precedes both CSS reads and local/downstream DB and
that legacy REMOTE cannot rescue local admission under combined faults.

The healthy-CSS legacy-only case remains separately in the negative actor
matrix. Canonical no-REMOTE success/repeat, local-plus-legacy-decoy parity,
list-read `503+60`, combined authorization-read/CSS precedence, healthy-local
missing-CSS RED, and all prior v10 assertions remain. Task `2.2` stays open for
fresh independent Gate 3 review.

## Verification and genuine RED

```text
2026-09-04T18:05:27+03:00
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

2026-09-04T18:05:29+03:00
$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check -- tests/InstallationProcess/pilot_object_list_001_test.php
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: CSS unavailable after healthy local authorization and before list read status
Expected: 503
Actual: 200
... tests/InstallationProcess/pilot_object_list_001_test.php(298): polError()
exit 255
```

The new combined missing-actor GET/HEAD case passes before the unchanged CSS
bypass RED, after canonical no-REMOTE success/repeat and legacy-decoy parity.
Thus the precedence correction is executed rather than inferred. Origin-query
behavior remains later and unreachable until CSS validation is restored.
Attempt-all cleanup left no task-owned residue.

## Exact input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
bfbf9f1a9ced25873dcb189e384829680033ebf571565f7b0c2c80661bbb7c7a  tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
9151e5b82c6d89122103d381e648c807632bc92dbfb5da52f30acaf8f5676562  tests/InstallationProcess/pilot_object_list_001_test.php
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
14caa204754bed181d8fb41dbd505eabd7197c46f41d74f63daf74c0db67865f  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v10.md
```
