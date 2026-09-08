# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v9

- Date: `2026-09-04T17:55:48+03:00`
- Reviewer: separately tasked agent `/root/object_list_contract_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; reviewer authored none of the specification, test, fixture, production, RED evidence, or prior reviews
- Reviewed exact HEAD: `3d4a92119386802f5c3f95f446e4a374bf8d5401`
- Correction baseline: `b507e05225402a9f9a095bdf17cf632671c9e74a`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **APPROVED**

This approval applies only to the exact reviewed test/fixture and integration
composition. It authorizes minimal Gate 4 GREEN; it is not production approval,
Gate 5, repository-wide GREEN, CI readiness, or release evidence. This reviewer
changed no test or production byte.

## Successor precedence

The v9 ordering follows the owner-approved local-RBAC successor and the v8
contract resolution:

```text
route/method
→ trusted REMOTE_USER transport syntax
→ local actor / active local role / exact objects.read
→ inherited configured CSS
→ inherited object-list read
```

`PILOT-OBJECT-READ-RBAC-FIXTURES-001` lines 9–12, 28–34 and 38–60 replace
legacy admission with the exact local actor manifest, require its denials before
the list handler, retain explicit request identity transport, and inherit the
list/CSS behavior only after successful local admission. Therefore a broken
local-RBAC read wins over a simultaneous CSS failure, while a valid local actor
must still encounter the inherited CSS failure before any list read. No
descriptive legacy lookup is part of this sequence.

## Fresh reproduction

```text
$ date --iso-8601=seconds
2026-09-04T17:55:48+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check \
    b507e05225402a9f9a095bdf17cf632671c9e74a..3d4a92119386802f5c3f95f446e4a374bf8d5401
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: CSS unavailable after healthy local
authorization and before list read status
Expected: 503
Actual: 200
... tests/InstallationProcess/pilot_object_list_001_test.php(255): polError()
exit 255
```

The run reaches line 255 only after the existing header-oracle sensitivity,
canonical revoke/restore, isolated list-read fault and the new combined
DB+CSS-fault assertion pass. Its `finally` completed; direct database inventory
found no task-owned `t_pol_*`/`foreign_pol_*` schema or `pol_*`/`pold_*`/`polf_*`
principal.

## Findings

1. Missing `REMOTE_USER` with both DB and CSS broken remains exact `401` before
   either dependency. Route and method rejection run earlier. This preserves the
   trusted transport syntax boundary before local authorization.
2. With valid transport and actor inputs but both DB credentials and CSS path
   broken, `polUnavailable()` proves exact correlated local-authorization
   `503`: no `Retry-After`, exact application-header manifest, one
   `AUTHORIZATION_READ_FAILED` log with matching 12-hex correlation ID, safe
   redaction, full database/filesystem snapshot preservation and no product
   representation. Because this assertion passes before line 255, it is a
   sensitive executable precedence proof rather than commentary.
3. With healthy local-RBAC and only `FMONITOR_SHLZ_CSS_PATH` missing, public
   GET/HEAD parity expects exact redacted `503`, `Retry-After: 60`, empty HEAD,
   no mutation and no list product body. Current production instead returns
   `200`; this is the earliest genuine missing inherited behavior, not setup
   failure.
4. The least-privilege list-read fault still passes exact `503 + Retry-After:
   60` after direct positive local identity/authority and legacy-object probes
   plus a direct denied process-case read. It cannot be confused with local
   authorization, CSS or a legacy identity lookup.
5. The later origin-query tracer remains byte-for-byte in the test and retains
   the independently reproduced v8 RED. It is correctly not claimed as reached
   in the v9 run because the newly exposed CSS predecessor fails first.
6. The v9 diff adds only the two infrastructure precedence cases, corrects the
   stale ordering comment, reopens task `2.2` and adds append-only evidence.
   Production is unchanged. Searches of the object-list production branch show
   local `authorizeLocalActor`; the test introduces no legacy-user/role fault
   principal or descriptive lookup. The existing active legacy row remains a
   decoy whose differing name/email are excluded from the successful response.
7. All v8 actor/grant/decoy, exact representation, origin/query, classification,
   pagination, header mismatch, integrity, ceiling, snapshot, foreign-decoy and
   attempt-all cleanup assertions remain unchanged.

Expected statuses, headers, correlation/log behavior and ordering derive from
the approved successor and inherited contracts, not current production output.
No remaining Gate 3 finding was identified for these exact bytes.

## Gate decision

**APPROVED.** OpenSpec task `2.2` is satisfied. Minimal Gate 4 may first restore
configured CSS validation after successful local authorization, then implement
the already reviewed query/list behavior without changing this test. Any test,
fixture, specification or integration-composition byte change requires a fresh
independent Gate 3 review.

## Exact reviewed-input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
1335fb641a6e2e1ec23d8158405c8a6eee9bb9eb015f95a6ce923e3c30dd4760  tests/InstallationProcess/pilot_object_list_001_test.php
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
e4034cb56e9080f7ce23126cef27f02e2c27c113a5c44d7d23b6445eb35e4720  docs/operations/pilot-object-list-integration-red-correction-v9-2026-09-04.md
f551df2b26eaef6b46482dd77d008c9fb6c90ab08553743640bf654aefef426a  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v8.md
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v9.md
```
