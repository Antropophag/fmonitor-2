# Test review: PILOT-OBJECT-READ-RBAC-FIXTURES-001

- Reviewer: separately tasked agent `/root/object_list_test_review`
- Test author: repository checkpoint author; reviewer did not author the reviewed specification, fixture, or test
- Reviewed commit: `25eb3370defe31e2d5234660c8021f1cc441f6d4`
- Specification: `PILOT-OBJECT-READ-RBAC-FIXTURES-001` v1, owner-approved SHA-256 `e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828`
- Public seam: production HTTP `GET /pilot/objects`
- Red command and intended failure: see evidence below; the current command stops on the navigation predecessor and does not demonstrate this slice's intended fixture RED
- Verdict: `CHANGES_REQUESTED`

## Reviewed hashes

```text
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
a534ca9cf726c01c1dbd0d3faeb3c4560197c23d6f2fc1b654afe49741c6e4cc  openspec/changes/pilot-object-read-rbac-fixtures/proposal.md
bd2cb1b9e48e2b8d8959d88d67b4591297d447f94856179ebaf8a1f18a7e891a  openspec/changes/pilot-object-read-rbac-fixtures/design.md
3128529b18a6226a6f66ebce2159bdf48ffb194f396869132cab179df99aabc2  openspec/changes/pilot-object-read-rbac-fixtures/specs/verification/pilot-object-read-rbac-fixtures/spec.md
0b10887a9fd4c1cce5d02c10bc76bd2ebb79f6094303a05754110de62592ce59  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
6ab9b7fcc4e65e7f87fb8a46a39ef4c5c2ee7aec4ca98fefd14d25fd0a1d0616  tests/Support/PilotObjectReadRbacFixture.php
861462feb34df7eb107167c314f5605ab1c5e554bb88c1706c0240c05e624f9a  tests/InstallationProcess/pilot_object_list_001_test.php
```

Gate 1 is valid for the exact spec hash: the independent rereview returned
`READY_FOR_OWNER_APPROVAL`, and the owner decision of 2026-09-02 records that
exact hash. `openspec validate pilot-object-read-rbac-fixtures --strict`, both
PHP lint commands, and scoped `git diff --check` pass at the reviewed commit.

## RED evidence

Fresh exact command:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_list_001_test.php

PHP Fatal error: Uncaught TestFailure: approved removal predecessor: no work item or root navigation destination
Expected: 0
Actual: 2
```

This is a real public-seam RED, but it belongs to
`PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001`, not to the reviewed fixture slice.
A predecessor RED may coexist with an independently reachable and durably
captured slice RED; it cannot substitute for one.

The only stored fixture RED record,
`docs/operations/pilot-object-read-rbac-fixtures-red-attempt.md`, is stale for
the reviewed blobs. It records test SHA-256
`7fb8bcb665bf673fc4d83b1fdc0ae345e47a4cca1c573e079b37203fa91079e7`
and fixture SHA-256
`2b07fdc6c3d4ee04cce9150ef9b0dbb20aec40417011393cb5a82ac16e61345f`,
whereas this review covers `861462...` and `6ab9b7...`. Its intended failure
(`canonical fixture actor` absent) is no longer sensitive: the reviewed fixture
already supplies the expected full name and the fresh run passes that assertion
before reaching navigation. Therefore the reviewed test has no durable Gate 2
evidence that it fails for its own missing behavior.

## Findings

1. **Blocking — intended public RED is not demonstrated.** The current test
   fails first on an unrelated navigation predecessor. The old own-slice RED
   concerns different blobs and an oracle that now passes. The later manifest
   assertion would observe role `900018` rather than required `5101`, but that
   is a database-side fixture assertion after the HTTP behavior, not evidence
   that the confirmed public seam fails for missing behavior. Gate 2 therefore
   has not passed for the reviewed hashes.

2. **Blocking — the 503 oracle contradicts and under-covers the approved
   contract.** `polError()` requires `Retry-After: 60` for every 503, while the
   owner-approved spec explicitly forbids `Retry-After`. The test does not pin
   exact singleton security headers, the 12-hex correlation ID, correlation/log
   equality, one safe logger category, or redaction/cardinality for schema/read
   unavailable branches.

3. **Blocking — required actor-input matrix is incomplete.** There are no
   explicit raw-process cases for absent/empty, `0`, `-1`, `abc`, leading-space
   ` 18`, or trailing-space `18 `. The existing `malformed identity` case
   changes `REMOTE_USER` while retaining valid `FMONITOR_AUTH_USER_ID=18`, so it
   does not test malformed trusted actor input and presently expects 401 for a
   request that should remain authorized by the trusted key.

4. **Blocking — no-handler-read isolation does not meet the specified
   mechanism.** All requests use the same reader, which is granted access to
   legacy object and process tables. A dangling case can make selected bypasses
   visible, but it is not the required denial process DB user restricted to the
   four canonical authorization tables and cannot prove zero downstream reads
   for every rejection path.

5. **Blocking — snapshot and cleanup evidence are incomplete.** `polSnapshot()`
   captures table names, AUTO_INCREMENT and rows, but not `SHOW CREATE`; the
   test does not establish the specified complete schema snapshot. It also does
   not create and compare foreign decoy database/file bytes plus metadata or
   record attempt-once cleanup inventory for server PID/pipes, DB resources,
   exact user/database and mutable root.

6. **Blocking — fixture is knowingly non-canonical.** The wrapper delegates to
   the generic fixture, producing role `900018`, generic role metadata and one
   role per actor rather than installing the exact independently approved
   canonical manifest with positive role `5101`. Keeping that mismatch is valid
   for RED only if a public-seam test is demonstrably sensitive to the missing
   behavior; the reviewed suite currently is not.

Traceability to the two named specifications and the production HTTP entrypoint
is otherwise clear. Positive list values are literal and independently grounded
in the approved object-list contract. The near-match, inactive, unassigned,
legacy-only, revoke, route isolation, repeated invocation, database snapshot and
task-owned filesystem guards provide useful partial sensitivity, but they do
not close the blockers above.

## Required changes

- Establish a current, reproducible public-seam RED for this slice's missing
  fixture behavior, independently of the navigation predecessor, and retain its
  exact command/output against the reviewed test and fixture hashes.
- Correct the 503 expectations to the exact approved no-`Retry-After` contract
  and add correlation, logging, singleton-header and redaction assertions.
- Add every exact trusted actor string case with explicit per-process
  set/unset behavior.
- Use a denial reader restricted to canonical authorization tables so any
  object/process/legacy read fails observably before the expected denial.
- Complete schema/filesystem/foreign-decoy snapshots and attempt-all cleanup
  evidence required by section 5.
- Request a fresh independent Gate 3 review after test-only corrections and
  fresh intended RED evidence. Production/fixture GREEN remains unauthorized.

---

# Independent Gate 3 rereview v2

- Reviewer: separately tasked agent `/root/object_list_rereview`
- Test author: separately tasked agent `/root/object_list_red_correction`; reviewer did not author the specification, fixture, test, production, or prior review
- Reviewed commit: `c9419b0aa6f170dbf82a6035e9ec4145f20f72c4`
- Specification: `PILOT-OBJECT-READ-RBAC-FIXTURES-001` v1, owner-approved SHA-256 `e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828`
- Public seam: production HTTP `GET /pilot/objects`
- Red command and intended failure: focused command below fails on canonical role `5101` revocation remaining ineffective through the public route because the pre-GREEN fixture still grants actor 18 through generic role `900018`
- Verdict: `APPROVED`

## Reviewed hashes

```text
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
a534ca9cf726c01c1dbd0d3faeb3c4560197c23d6f2fc1b654afe49741c6e4cc  openspec/changes/pilot-object-read-rbac-fixtures/proposal.md
bd2cb1b9e48e2b8d8959d88d67b4591297d447f94856179ebaf8a1f18a7e891a  openspec/changes/pilot-object-read-rbac-fixtures/design.md
3128529b18a6226a6f66ebce2159bdf48ffb194f396869132cab179df99aabc2  openspec/changes/pilot-object-read-rbac-fixtures/specs/verification/pilot-object-read-rbac-fixtures/spec.md
71ab6211e1beb90e4af42ddcb6776b5008257d5202aec3e2a8e2bf2d4d0e921d  openspec/changes/pilot-object-read-rbac-fixtures/tasks.md
6ab9b7fcc4e65e7f87fb8a46a39ef4c5c2ee7aec4ca98fefd14d25fd0a1d0616  tests/Support/PilotObjectReadRbacFixture.php
42e8c066638f41de4ca0486f489273d0e58ed45fa0467fcd56cfd7809d238c4c  tests/InstallationProcess/pilot_object_list_001_test.php
a7f69dc47d81aed886f18e6f53cbb3488f532a7d9c3e358cecffc646f0b8850f  docs/operations/pilot-object-read-rbac-fixtures-red-correction-evidence-v2.md
```

## Fresh verification

```text
php -l tests/Support/PilotObjectReadRbacFixture.php
No syntax errors detected in tests/Support/PilotObjectReadRbacFixture.php

php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

openspec validate pilot-object-read-rbac-fixtures --strict
Change 'pilot-object-read-rbac-fixtures' is valid

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: canonical fixture revoke controls public list before navigation status
Expected: 403
Actual: 200
```

The focused RED exited `255`. A post-failure inspection found no task or foreign
decoy databases, no `pol_*` or `pold_*` principals, and no `pol-*` or
`foreign-*` entries under `.test-artifacts`.

## Findings

All blockers from the prior `CHANGES_REQUESTED` review are closed for the exact
reviewed blobs:

1. The first behavioral assertion now commits the specified fixture-admin
   revocation and observes its result through exact `GET /pilot/objects`. It
   fails `200` versus expected exact `403` because role `5101` is absent while
   generic role `900018` continues to authorize. This is the missing canonical
   fixture behavior, not setup or an unrelated route. The navigation-removal
   assertion remains later in the test and neither supplies nor substitutes for
   this RED.
2. Both authorization-unavailable branches now pin the no-`Retry-After` 503
   contract, exact singleton header inventory and values, 12-hex correlation
   identity, exactly one safe logger event, response/log identity equality,
   category, and response/log redaction.
3. Independent server processes cover absent, empty, `0`, `-1`, `abc`, ` 18`,
   `18 `, unknown and inactive actors, inactive/unassigned roles, and all four
   near-match permissions. Cookie, synthetic identity header, and
   `REMOTE_USER` decoys cannot replace the trusted environment key.
4. Every RBAC denial uses the separate `pold_*` principal with SELECT grants
   only on the four canonical authorization tables. A downstream object,
   process, session, audit, or legacy read cannot silently satisfy a denial.
5. Database snapshots cover ordered complete rows, `AUTO_INCREMENT`, and exact
   `SHOW CREATE`; filesystem guards cover bytes and metadata. The attempt-all
   cleanup inventory addresses server resources, DB resource, both principals,
   the exact task database and task-owned root once each, then proves foreign
   database/file preservation before removing the test decoys. Fresh RED cleanup
   left no inspected residue.
6. The temporary generic fixture remains deliberately non-canonical and is now
   precisely what makes the leading public-seam tracer RED. The later exact
   manifest assertion and committed revoke remain sensitive to the required
   positive role `5101` after GREEN.

Traceability, expected-value independence, rejected cases, repeat/revoke
sensitivity, route isolation, deterministic task-owned inputs, and public-seam
choice satisfy Gate 3. This approval authorizes minimal Gate 4 fixture GREEN; it
does not approve production implementation or waive later navigation and full
regression gates.

## Required changes

None.
