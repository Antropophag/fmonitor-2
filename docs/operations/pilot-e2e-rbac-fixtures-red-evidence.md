# Gate 2 RED evidence — PILOT-E2E-RBAC-FIXTURES-001 v2

Date: 2026-09-02

## Approved seam and exact inputs

- Public seam: configured production raw HTTP `GET /pilot/objects`, backed by an
  isolated real MariaDB schema and task-owned storage.
- Owner-approved amended executable spec:
  `147227bde8b9afe126ee374417a9c7f5a3bac84c5e13b10d7dc1b1d9a525ee1f`
  (`specs/PILOT-E2E-RBAC-FIXTURES-001.md`).
- RED verifier:
  `a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6`
  (`tests/InstallationProcess/pilot_e2e_flow_001_test.php`).

The verifier takes a complete DB/public-process/owned-storage snapshot
immediately around object-list authorization reads. At the later artifact
boundary it compares against an independently captured pre-prepare snapshot.
The post-prepare case, assignment order, installer and event rows are pinned as
complete literal rows from fixture inputs. Artifact metadata is pinned to the
literal type/name/media contract and independently hashed owned bytes, whose
only new paths must be the exact content-addressed directory, shards and blob.
Every other table plus all RBAC facts, schemas and counters remains exact.
Combined-PDF assertions remain unchanged and downstream. Actor19, every
missing/invalid/inactive/near-match actor and authorization-unavailable calls
each have their own immediate full snapshot boundary rather than an aggregate.

## Command and observed RED

```text
$ php tests/InstallationProcess/pilot_e2e_flow_001_test.php
PHP Fatal error: Uncaught TestFailure: nonmigrated card retains predecessor admission after local list RBAC
Expected: 200
Actual: 403
... pilot_e2e_flow_001_test.php(38) ...
... pilot_e2e_flow_001_test.php(157): pefText() ...
```

Exit status: `255`.

## Classification

`INTENDED_RED`: the minimal production WIP now admits fictional actor 18 through
the isolated revoke/repeat branch and the main canonical object list; actor19
and the full negative authority matrix also complete. The canonical object
link is asserted from exact DOM `href`, and the approved configured table is
the exact six-column shape. Production then incorrectly applies the migrated
list authority boundary to the nonmigrated object-card route, returning generic
403 where the approved slice requires inherited predecessor 200. The failure
is therefore an RBAC boundary RED after successful list admission but before
prepare and every combined-PDF assertion, not a setup, stale UI or PDF failure.

No production file was changed. This evidence author does not review or approve
the verifier; Gate 3 requires a fresh independent reviewer.
