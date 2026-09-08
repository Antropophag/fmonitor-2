# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent Gate 5 review v2

- Date: `2026-09-04`
- Reviewer: separately tasked agent `/root/object_list_gate5`
- Implementation/test author: not this reviewer
- Exact production commit: `3191d19cd32385280fd52dac4a04d15d6351906c`
- Production baseline: `aa1a814d80020b12f13bb544a5df5e7f567ae176`
- Documentation/task head reviewed: `6c7133932cc25b69b1291456e7c95170b8468621`
- Corrected test head: `d76c16d866f442e804119484d0b0e87f6dc78258`
- Fresh Gate 3 v12 approval: `561c725eb1c65e94d04f3fa0ee1f86e2ef399dd1`
- Public seam: raw HTTP `GET|HEAD /pilot/objects`
- Verdict: **APPROVED**

This approval is limited to the exact production commit and reviewed hashes
below. The reviewer changed no production or test byte. Repository-wide
verification, CI/release readiness and unrelated integration debt are outside
this approval and are not inferred from the focused GREEN.

## Findings

No blocking code-review finding.

## Standards axis

The production diff is minimal and confined to
`app/PilotHttp/PilotHttp.php`: four added logical statements replace the former
query-driven 50-row collection behavior. It adds no mutation, fallback,
session, audit, legacy-authority or presentation-owned domain fact. The existing
public `read()` seam remains the HTTP collection seam; the pre-existing
`readPage()` compatibility helper remains callable internally but client query
state no longer reaches it.

The collection SQL is bounded at `LIMIT 501`, so the implementation neither
loads an unbounded result nor truncates a successful 500-row response. Existing
row validation, duplicate detection, imported-case membership and canonical
ordering remain unchanged. Architecture check passes all seven rules, lint and
diff-check are clean, and the production file parses under the local PHP
runtime.

## Specification axis

The exact collection route now has the required precedence:

1. local actor syntax and exact local `objects.read` authorization;
2. configured public `shlz.css` validation and configured pilot CSS validation;
3. authorized local actor display-profile lookup;
4. canonical imported-object read and render.

Thus missing/invalid actors and denied grants remain independent of unavailable
CSS and business reads, while healthy authorization with missing CSS fails
closed as `503 Service unavailable` with `Retry-After: 60` before profile/list
reads. `REMOTE_USER` is not consulted for the collection admission or display
identity.

`MariaDbObjectListReader::read()` always invokes `readPage(1, 'all')`; `page`,
`origin`, and every other query parameter are therefore ignored by the public
collection and cannot affect membership, ordering or rendering. The result is
complete for zero through 500 canonical cases. Both the counted total and
materialized row count are checked against the exact ceiling; 501 fails closed
through the inherited redacted `503` response with `Retry-After: 60`.

The corrected predecessor admission test and the approved object-list verifier
both pass freshly against the disposable MariaDB composition. The latter covers
GET/HEAD parity, query byte-equivalence, exact representation and ordering,
CSS/auth/profile/list precedence, repeat/revoke, integrity faults, 500 success,
501 failure, read-only snapshots and cleanup.

## Independent verification

```text
$ php -l app/PilotHttp/PilotHttp.php
No syntax errors detected in app/PilotHttp/PilotHttp.php

$ php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=<compose.test.yaml value> \
  php tests/InstallationProcess/pilot_object_list_001_test.php
PASS: PILOT-OBJECT-LIST-001 public HTTP collection

$ tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

$ bash tools/verification/run.sh lint
# exit 0; empty output

$ git diff --check
# exit 0; empty output

$ git diff --name-status 3191d19^..3191d19
M app/PilotHttp/PilotHttp.php

$ git diff --exit-code d76c16d..3191d19 -- tests
# exit 0; empty output
```

An initial direct object-list invocation used the test's obsolete fallback
password and was rejected by the healthy compose database before test setup.
Re-running with the exact password configured in `compose.test.yaml` produced
the PASS above; this was runner configuration, not product/test failure.

## Exact reviewed hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
b3ea9bf60858e505bd2d12993e8c4120f9fa7f27a2ae9276e2eded00a7b7f74b  app/PilotHttp/PilotHttp.php
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
d6f6731715e4007126541caab75ed6e4099f0192d12783090b0921a3bc0c68da  tests/Support/TaskOwnedArtifactRoot.php
9151e5b82c6d89122103d381e648c807632bc92dbfb5da52f30acaf8f5676562  tests/InstallationProcess/pilot_object_list_001_test.php
5e29a959970faa7b7e8220dc4e114560f3f665e3faca0ab308dec6eac83b4914  tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
568c806911d0a97c2620cf566cab8ee8e6357e447c357fcc07c802c209f5f6e3  docs/operations/pilot-object-list-predecessor-css-red-reproduction-2026-09-04.md
d39087e246b1ce4daa0c004ece39a631f6299e832a504965391652e562bd5131  docs/operations/pilot-object-list-green-verification-2026-09-04.md
244952642929d13333bba70ce4cfd5d7c50f58e25dae9a97fd78f8d6805c9e4e  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v12.md
38e7df7a6b2c6aaa06b12e331a7293b1787b50580756364b6c0a924955202c18  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
```

Gate 5 is **APPROVED** for exact production commit `3191d19`. OpenSpec task
`4.2` may be marked complete by the integration owner; this review does not
itself change task state or declare the whole change Done.
