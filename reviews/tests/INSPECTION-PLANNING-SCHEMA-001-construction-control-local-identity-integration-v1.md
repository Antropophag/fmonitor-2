# INSPECTION-PLANNING-SCHEMA-001 — construction-control local identity integration Gate 3

Date: 2026-09-04  
Reviewer: `/root/inspection_planning_local_gate3` (independently tasked; did
not author the reviewed test, evidence, specification, or production)  
Reviewed RED evidence commit: `c24ee4e415a48a3097a80b820161fe3747b01194`  
Verdict: `APPROVED`

## Exact reviewed artifacts

```text
464df8d8cdccea4aeb0997d2e397a3d22958f7c8d04a98e556b59d2c055c888c  specs/INSPECTION-PLANNING-SCHEMA-001.md
5da5e8e71b4fc057d08f1fd77cd2771ba969330e768b0fe55cd79851ca854e52  openspec/changes/canonicalize-inspection-planning-schema/specs/deployment/canonical-inspection-planning-schema/spec.md
7bcaf243ed9c98d05ba7a3884f7a325f6d7dec46b955545d72ecaa37b6cb4931  tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
730d554b8b7065dd37deaf8bfacb0f3dccc0e015c0becac98dc05bf26101ee90  tests/Support/inspection_planning_runtime_router.php
5b893a8823ba3c27294f0c2f19f9e47762a6ab9bc232c5826fac307a7f19eda9  docs/operations/inspection-planning-local-identity-integration-red-2026-09-04.md
910fce64bfc23ec73a4b813effabe6ab1b1dfb503963eafd6c402dd06ebbb9ba  app/PilotHttp/LocalAuthenticatedHttpUser.php
4015831ec64c91062dc53caeb969a53cde270abbf93a2432f35543b30b2d7060  app/PilotHttp/PilotE2ECoordinator.php
```

The runtime verifier remains the previously approved public HTTP/Compose test;
this integration cycle changed its fixture, not its acceptance expectation.
No production or test file was edited by this review.

## Independent reproduction

I ran:

```sh
php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
```

The migrated database, DML-only runtime principal, configured CSS exports and
three sibling consumers were healthy. The first failing assertion was exactly:

```text
schedule 303
calendar 200
object queue 200
construction control 503 (expected 200)
```

The command failed only at `DML-only healthy control`; it did not fail during
migration, fixture creation, HTTP startup, CSS reading, scheduling, Calendar or
object-list handling. This is the intended behavioral RED, not setup failure.

I also ran:

```sh
php tests/InstallationProcess/inspection_item_complete_001_endpoint_admission_test.php
php -l tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
php -l tests/Support/inspection_planning_runtime_router.php
openspec validate canonicalize-inspection-planning-schema --strict
git diff --check
```

The established local-auth public-seam regression passed, both lint commands
passed, strict OpenSpec validation passed, and diff-check passed.

## Findings

1. **Traceability and seam.** The verifier sends real HTTP requests through the
   production entrypoint and retains the approved planning-schema outcomes. A
   successful construction-control request is the exact public observation
   required by the healthy DML-only scenario; missing/incompatible planning
   families retain their exact fail-closed response checks and full schema,
   allocator and row-byte before/after snapshots.

2. **Positive identity fixture.** Actor `901` exists as one active local pilot
   user, is joined to one active local role, and that role grants the literal
   `construction_control.read`. `FMONITOR_AUTH_USER_ID=901` is the trusted actor
   input. `REMOTE_USER=inspection-planning@example.invalid` is descriptive
   predecessor input only; no `${legacyPrefix}users` or `users_roles` table/row
   is created, so no legacy account can provide positive authority or display
   identity. The rendered engineer name is independently available from local
   facts and the order snapshot.

3. **RED cause.** The production coordinator currently resolves every ordinary
   route through `dependencies->users()->resolveActiveUser($principal)` before
   checking `construction_control.read`. That legacy directory lookup raises on
   this deliberately absent legacy identity family and is mapped to `503`.
   Thus the test detects the missing local-identity route integration directly.

4. **Sensitivity and bounded GREEN.** This integration test alone is not a new
   complete authorization matrix and must not be read as approving authority
   from an actor ID without a permission check. Its sensitivity composes with
   the already Gate-5-approved `LocalAuthenticatedHttpUser` public-seam
   regression, reproduced GREEN above. That resolver makes a present trusted
   local ID authoritative, requires a unique active local profile and the exact
   supplied permission, derives representation identity from that profile, and
   permits legacy resolution only when the local ID is absent. A minimal and
   acceptable GREEN is therefore to route construction-control through that
   existing resolver with exact `AccessPolicy::CONSTRUCTION_CONTROL_READ`.
   Leaving the legacy-first path fails this RED; supplying a wrong permission
   fails this positive fixture; weakening/reimplementing the resolver is outside
   this integration slice and would fail the inherited local-auth regression or
   Gate 5 review.

5. **Rejected and infrastructure cases.** The current test deliberately reaches
   the healthy assertion before its missing/incompatible loops, so this RED run
   cannot execute those later assertions. Their unchanged code still requires
   exact 503/no redirect/no partial HTML (or opaque queue response), fresh queue
   correlation references and byte-equivalent schema/data/counters after each
   rejected family. Gate 4 must rerun the unchanged verifier GREEN so those
   cases execute. Present-but-invalid/inactive/unauthorized local actor and
   legacy-absence fallback remain inherited behavior of the approved resolver;
   they are not newly specified by the planning-schema slice.

6. **Determinism and isolation.** Names are random private database/user
   namespaces, the runtime has only DML privileges, HTTP uses a private loopback
   port, CSS lives in a private temporary root, and `finally` cleanup removes
   the runtime principal, database and asset root. Repeated reproduction reached
   the same single status mismatch.

## Required Gate 4 / Gate 5 evidence

- Reuse `LocalAuthenticatedHttpUser::resolve(...)` for the construction-control
  route with exact `AccessPolicy::CONSTRUCTION_CONTROL_READ`; do not introduce a
  second local-profile/permission implementation.
- Preserve legacy resolution only when `FMONITOR_AUTH_USER_ID` is absent; a
  present invalid/inactive/unauthorized local identity must not fall back.
- Rerun the unchanged planning runtime verifier to completion, proving healthy
  `303/200/200/200` and every missing/incompatible zero-mutation assertion.
- Rerun the established local-auth endpoint regression and relevant
  construction-control/architecture/lint suites before independent Gate 5.

## Gate decision

Gate 3 is `APPROVED` for the construction-control local-identity integration
RED at commit `c24ee4e415a48a3097a80b820161fe3747b01194`. The failure is genuine,
the expected value remains independently specified, and the allowed GREEN is
constrained to the already reviewed active-local-profile plus exact-permission
resolver without granting legacy data positive local authority.
