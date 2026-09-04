# PILOT-OBJECT-LIST-001 integration RED correction v7

- Date: `2026-09-04`
- Gate: `2` correction after independent Gate 3 v6
- Test author: separately tasked agent `/root/object_list_integration_red`
- Exact correction baseline: `1719e3ee57ad1fdb22160bc4da075323f7b8406d`
- Returned review: `reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v6.md`
- Production changes: none
- Public seam: raw HTTP `GET|HEAD /pilot/objects`

## Isolated infrastructure faults

Two randomized task-owned least-privilege principals now separate the public
object-list stages after canonical local authorization:

1. The list-read-fault principal can resolve exact active local actor `18`, can
   read the active legacy user/role and the valid legacy object sentinel, but
   cannot select `fm2_installation_cases`. Direct grants/read probes establish
   those boundaries. Public GET/HEAD then return exact redacted `503`,
   `Retry-After: 60`, and no partial or empty product representation while the
   complete database/filesystem snapshot stays unchanged.
2. The legacy-identity-fault principal can resolve exact active local actor
   `18` and read all three valid case/list facts, but cannot select either
   `legacy_users` or `legacy_users_roles`. Direct denied selects prove the
   isolated fault. Public GET/HEAD must return the same exact retryable `503`.
   This proves local route authority does not replace the inherited descriptive
   active legacy identity lookup.

Both principals receive the canonical local-RBAC tables needed to reach their
target stage. Their grants omit only the named fault tables. The already
approved local-RBAC unavailable cases remain separate: they still require no
`Retry-After`, an exact correlation ID, and one safe correlated log event.

Both public requests use `polReadOnly`, so all database rows/schema/catalog and
AUTO_INCREMENT state plus protected files remain byte-equivalent. Both fault
principals are included in attempt-all cleanup and final absence assertions.
Task `2.2` remains open for fresh independent Gate 3 review.

## Earliest genuine RED

The new list-query fault executes first and passes exact GET/HEAD `503`/
`Retry-After: 60`, proving the production list-read failure mapping. The next
isolated fault exposes an earlier missing behavior than the previously recorded
origin-query RED:

```text
2026-09-04T17:38:56+03:00
$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ git diff --check -- tests/InstallationProcess/pilot_object_list_001_test.php
# exit 0; no output

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: legacy descriptive identity unavailable after local authority status
Expected: 503
Actual: 200
... tests/InstallationProcess/pilot_object_list_001_test.php(256): polError()
exit 255
```

GET and HEAD agree on the unexpected successful result. The direct probes prove
that local authority and all valid list facts are readable while both exact
legacy identity tables are unavailable. Current production therefore renders
the list from local identity alone rather than failing at the inherited legacy
descriptive-identity seam. This is a public behavior RED, not setup failure.

The unchanged origin-query assertion remains later in the test and is now
unreachable because this newly covered predecessor fails first. Its prior v6
RED remains append-only evidence; it is not claimed as reproduced by this run.
Attempt-all cleanup removed the two new principals and all earlier task-owned
resources; a post-run inventory found no `t_pol_*`, `foreign_pol_*`, `pol_*`,
`pold_*`, `poll_*`, or `polf_*` residue.

## Exact input hashes

```text
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
f459e96cfb359b75dc0c4ad48323f038f60b631aa1b20da171d23a83f533341b  tests/InstallationProcess/pilot_object_list_001_test.php
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
c8dd7a367e258de9aff1287de07e398c0f3058ec32d596ae71d30e4eca00d53c  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001-integration-v6.md
```
