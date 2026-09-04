# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent integration Gate 3 v8

- Date: `2026-09-04T17:48:53+03:00`
- Reviewer: separately tasked agent `/root/object_list_contract_gate3`
- Test author: separately tasked agent `/root/object_list_integration_red`; reviewer authored none of the specification, test, fixture, production, RED evidence, or prior reviews
- Reviewed exact HEAD: `bc1bb08357ea15ef29950044ba3b1c36d6f78c0e`
- Correction baseline: `9f5539ecff3f99f397a99a823be754207d29d72e`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **APPROVED**

This approval applies only to the exact reviewed test/fixture and integration
composition. It authorizes minimal Gate 4 GREEN; it is not production approval,
Gate 5, repository-wide GREEN, CI readiness, or release evidence. This reviewer
changed no test or production byte.

## Controlling-contract resolution

The v7 review incorrectly required a descriptive legacy-user/role lookup after
successful local authorization. That inference is superseded by the later,
more specific, owner-approved `PILOT-OBJECT-READ-RBAC-FIXTURES-001` contract:

1. Its plain-language lines 9–12 say the list enters through local user ID,
   active local role and exact `objects.read`, and that an old email/legacy-role
   match no longer makes a positive fixture.
2. Section 1 lines 16–30 fixes actor `18`, its local tuple and the exact target
   authority `authorizeLocalActor(18, 'objects.read')`.
3. Section 2 lines 38–46 requires the canonical landed local identity/access
   manifest and says legacy rows may exist only as decoys and must not grant
   authority. It separately requires explicit request environments.
4. Section 3 lines 49–60 makes an active legacy user/role supplied through
   `REMOTE_USER`, without the trusted local actor key, exact `401`; the exact
   local manifest is what advances to the inherited list handler.
5. The phrase “inherited unchanged after successful admission” in section 1
   line 33–34 preserves the list/data/HTTP behavior after this successor
   admission. It cannot reinstate the predecessor's legacy authority or
   descriptive identity because that would contradict the successor's explicit
   actor, target-authority, decoy and outcome clauses.

Accordingly `REMOTE_USER` remains only the explicitly retained, syntactically
validated transport input in this composition. A legacy descriptive lookup is
neither required nor authorized. The append-only v7 record remains history but
must not authorize such production behavior.

## Fresh reproduction

```text
$ date --iso-8601=seconds
2026-09-04T17:48:53+03:00

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check \
    9f5539ecff3f99f397a99a823be754207d29d72e..bc1bb08357ea15ef29950044ba3b1c36d6f78c0e
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: query is byte-identical and ignored
/pilot/objects?origin=migration
Expected: status 200, Content-Length 3361, canonical three-item body
Actual:   status 200, Content-Length 2439, empty-state body
... tests/InstallationProcess/pilot_object_list_001_test.php(293): assertSameValue()
exit 255
```

The first two query variants (`?sort=regnumber` and
`?origin=demo_fixture`) pass before the intended `origin=migration` failure.
The run reaches that assertion only after helper mutation probes, canonical
revoke/restore, isolated list-read failure, canonical success GET/HEAD, local
identity rendering, legacy-decoy exclusion, UI-shell/CSP, exact semantic list,
classification/pagination and snapshot checks. The test's `finally` completed;
a direct post-run inventory found no `pol_*` task users, `t_pol_*` databases or
`foreign_pol_*` databases.

## Findings

1. The positive request deliberately combines local actor `18` with the
   differing active legacy decoy `Legacy identity decoy /
   legacy.object-list@example.invalid`. Direct least-privilege lookup fixes the
   full local tuple (`18`, `Сотрудник ФКР (тест)`,
   `fkr.object-list@example.invalid`); public HTML requires the local name and
   excludes both legacy decoy name and email.
2. With the same active legacy decoy transport identity and the local actor key
   absent, the negative matrix returns exact `401` before list access. The
   additional legacy-only case, malformed/empty IDs, missing/inactive local
   actors, inactive/missing assignments/grants, near-match permissions and
   committed revoke retain exact denial and no-handler-read sensitivity.
3. The list-fault principal can read the exact local authority/identity tuple
   and an approved legacy object fact, has no legacy user/role grant, and is
   directly denied `fm2_installation_cases`. Public GET/HEAD therefore proves
   the list-read branch returns exact redacted `503`, `Retry-After: 60`, empty
   HEAD, no mutation and no partial product representation.
4. Local-RBAC schema/read failures remain on their distinct exact correlated
   `503` contract without `Retry-After`. CSS, integrity and 501-ceiling failures
   retain required retry headers. Positive/inverse helper probes reject both an
   unexpected and a missing retry header.
5. Exact facts, association, numeric tie order, non-imported exclusion,
   query-byte identity, 500/501 completeness, route/method priority, configured
   shell/assets, classification/pagination sensitivity, full DB/schema/counter
   and filesystem snapshots, foreign decoys and attempt-all cleanup remain
   intact. Expected tuples and response values derive from approved contracts,
   not production output.
6. The correction removes only the invalid legacy-descriptive fault oracle and
   associated principal/grants/cleanup. It changes no production byte, does not
   weaken the genuine list-fault tracer, and returns the earliest failure to the
   already approved origin-query behavior.

No remaining Gate 3 finding was identified for these exact bytes.

## Gate decision

**APPROVED.** OpenSpec task `2.2` is satisfied. Minimal Gate 4 may implement the
reviewed query/list-ceiling behavior without changing this test. Any test,
fixture, specification, or integration-composition byte change requires a new
independent Gate 3 review.

## Exact reviewed-input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
49cc76cfcc72db0461a19d92326ca98473acec506de8999d4ba23a0c09fda6d1  tests/InstallationProcess/pilot_object_list_001_test.php
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
ceb60883f6ad4cdb8344012e04b5aa9c057210d54582417e27cb82474d8da82b  docs/operations/pilot-object-list-integration-red-correction-v8-2026-09-04.md
e63c2d49b43c1305ff3bb0dfff4d6efd9506289b9393c4e60f8efbacfe077058  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v7.md
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md

METADATA  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v8.md
```
