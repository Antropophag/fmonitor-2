# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v10

- Date: `2026-09-04T18:03:30+03:00`
- Reviewer: separately tasked agent `/root/object_list_local_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; reviewer authored none of the specification, fixture, tests, production, RED evidence, or prior reviews
- Reviewed exact HEAD: `844336c45a3d353d4e0b444956e831c4fc9a3ed1`
- Correction baseline: `3d4a92119386802f5c3f95f446e4a374bf8d5401`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **CHANGES_REQUESTED**

This review applies only to the exact hashes below. It is not GREEN, Gate 5,
repository-wide verification, CI readiness, or release evidence. The reviewer
changed no test or production byte; this append-only record is the only review
change. OpenSpec task `2.2` remains unchecked.

## Controlling contract resolution

`PILOT-OBJECT-READ-RBAC-FIXTURES-001` sections 1–3 replace legacy object-list
admission with the trusted positive local actor ID, active local role and exact
`objects.read`. Section 2 requires an explicit environment per process and says
that `REMOTE_USER`, cookies and previous process environment must not leak from
the positive case. Its section 3 matrix fixes legacy-only `REMOTE_USER` with no
trusted actor key at exact `401`. `LOCAL-RBAC-AUTH-CONTRACT-001` section 2 says
that `REMOTE_USER` is not an input to the authorization seam. Therefore the v9
review's required positive `REMOTE_USER` precursor is superseded and cannot be
resurrected through an inherited descriptive HTTP-auth contract.

The controlling successor order for this migrated route is:

```text
route/method
→ trusted local actor ID / active local role / exact objects.read
→ inherited configured CSS
→ inherited object-list read
```

## Fresh reproduction

```text
$ date --iso-8601=seconds
2026-09-04T18:03:30+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: CSS unavailable after healthy local
authorization and before list read status
Expected: 503
Actual: 200
... tests/InstallationProcess/pilot_object_list_001_test.php(297): polError()
exit 255

$ git diff --check \
    3d4a92119386802f5c3f95f446e4a374bf8d5401..844336c45a3d353d4e0b444956e831c4fc9a3ed1
# exit 0; no output
```

The object-list execution reaches the intended CSS predecessor RED only after
the canonical-grant revoke/restore, least-privilege list-read `503 +
Retry-After: 60`, combined authorization-read/CSS fault, positive no-REMOTE
GET/HEAD representation, repeat request and differing legacy `REMOTE_USER`
decoy parity all pass. The separate approved route-admission test independently
passes a real granted local actor without `REMOTE_USER`. The isolated process
launcher uses `/usr/bin/env -i`, so these observations are not ambient process
inheritance. Cleanup completed; no task-owned `t_pol_*`/`foreign_pol_*` schema
or `pol_*`/`pold_*`/`polf_*` principal remained.

## Finding

1. **The asserted local-authorization-before-CSS order is not sensitive for a
   missing local actor.** The v10 `legacy-only REMOTE_USER` case has healthy
   CSS, while the broken-CSS case has a healthy local actor. The combined-fault
   probe has a valid local actor and a broken DB password, so it proves an
   authorization *read failure* wins over CSS, but not that the no-key
   `AUTHENTICATION_REQUIRED` result wins before CSS. An implementation that
   checks CSS first only when `FMONITOR_AUTH_USER_ID` is absent would still
   satisfy every current v10 assertion: the healthy-CSS legacy-only case would
   return `401`, and the healthy-actor broken-CSS case would return `503`.

   Add one explicit-process public GET/HEAD case with
   `FMONITOR_AUTH_USER_ID` absent, valid legacy decoy
   `REMOTE_USER=legacy.object-list@example.invalid`, and broken configured CSS
   (a simultaneously broken downstream/list credential is also acceptable).
   It must require exact generic `401` and demonstrate no downstream list
   read/mutation. This both proves the successor priority and proves the legacy
   value cannot rescue the missing local authority under the mixed fault.

The positive no-REMOTE, byte-equivalent repeat, differing legacy-decoy parity,
local representation identity, healthy-local missing-CSS intended RED, route
and method priority, and retained later origin/list behavior are otherwise
appropriate and independently derived. No production change is authorized
until the corrected exact test bytes receive a fresh independent Gate 3 review.

## Gate decision

**CHANGES_REQUESTED.** Keep OpenSpec task `2.2` unchecked. Add the one
mixed-fault missing-local-key/legacy-decoy precedence case, record fresh RED
evidence and request a fresh independent review. Do not begin GREEN from this
verdict.

## Exact reviewed-input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
19fcba6f14c73e36b69375737a730f937b1cd7c150e2c7d9fddd567c4ece0b36  tests/InstallationProcess/pilot_object_list_001_test.php
bfbf9f1a9ced25873dcb189e384829680033ebf571565f7b0c2c80661bbb7c7a  tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
9a7d0d554022b433c8417b43327c54301f275c2c73603f2e4d6089f2668de928  docs/operations/pilot-object-list-integration-red-correction-v10-2026-09-04.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v10.md
```
