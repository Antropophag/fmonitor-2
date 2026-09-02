# Independent Gate 1 rereview v2 — PILOT-E2E-RBAC-FIXTURES-001

Date: 2026-09-02  
Prior rereview SHA-256:
`28dbf0c436c06621e5c73a5eb9b0d764324fa99747285e215de284d9de579bb6`  
Reviewed executable spec SHA-256:
`dd32915602225450a0ad1a1213e1cff4a0d5afea62ca1a29a8adee447e1bf48e`  
Verdict: **CHANGES_REQUIRED**

## Result

The two substantive corrections requested by the prior rereview are present:

- actor 19 now receives fixed trusted `FMONITOR_AUTH_USER_ID=19` while having no
  local user/role/grant rows, so the production seam can return exact 403; a
  separate absent trusted-ID process proves exact 401;
- the revoke comparison now states that the only intended DB delta is removal
  of `(5301, 'objects.read')`, while every other row, schema, allocator, audit,
  storage and process/artifact observation remains equal.

The previously closed process-environment trust boundary, request
non-selectability, transport-only header normalization, literal failure and
correlation mapping, restricted-DB read sentinel, cleanup order and downstream
artifact boundary remain present and feasible. Strict validation passes:

```text
Change 'pilot-e2e-rbac-fixtures' is valid
```

One contradictory sentence prevents approval of the exact Gate 1 hash.

## Remaining gate blocker

Section 3 correctly says the revoke uses a separate isolated fixture, cleanup
finishes before main journey setup, and the main actor-18 grant remains
byte-equivalent at the artifact boundary. But the snapshot paragraph then says:

> Main branch also asserts exact role5301 permission row before revoke, its sole
> absence afterward, and no other delta.

The main branch cannot both preserve the grant and observe its absence. Replace
this with two explicit comparisons:

1. **Isolated revoke branch:** before contains exactly `(5301,
   'objects.read')`; after equals before minus only that row, with every other
   enumerated observation equal.
2. **Main branch:** before first list and at downstream artifact boundary both
   contain exactly `(5301, 'objects.read')`, the role has no other permission,
   and the complete main grant/role/user assignment manifest is byte-identical.

Make the same isolated-versus-main distinction in the controlling OpenSpec
design/delta/tasks if they restate snapshot verification. No product decision is
needed; this is an executable-spec consistency correction.

## Confirmed review points

- The fixed environment actor is consumed by
  `ProductionLocalObjectListAuthorization`; request input cannot override it.
- Actor 19's positive numeric trusted ID with absent local facts is the correct
  legacy-fallback rejection, while missing/empty/invalid trusted IDs remain the
  authentication-required matrix.
- The restricted DB user makes an accidentally reached list handler observable
  as infrastructure failure, not the expected denial.
- Exact 503 correlation remains an opaque external ID; the safe internal
  category and RBAC/SQL facts are not exposed.
- No local permission is added to command/card/artifact/control routes, and the
  combined-PDF assertion remains a separate downstream dependency.
- Gate order is intact; this rereview does not authorize Gate 2.

## Reviewed hashes

```text
dd32915602225450a0ad1a1213e1cff4a0d5afea62ca1a29a8adee447e1bf48e  specs/PILOT-E2E-RBAC-FIXTURES-001.md
9929249efb3f5f8afbd7f0757ee1681207b19dcea45bb00c90df4f3c2f3d0e5a  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
bdc5b4fb4f4dfbb62e03d69d7ec6595602b85ec5115fb14366dc3d4be5d0be5c  openspec/changes/pilot-e2e-rbac-fixtures/design.md
d2380bec2e1993d167340644e40a9fa34d8d8b984298bf3073f66cca93bf0e5b  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
f57bbea09e331d0459e2320a64ef2a59ed73bd71c5cfc186b961df12896aaafb  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
```

After that single wording correction is applied coherently, a final narrow
independent rereview can issue `READY_FOR_OWNER_APPROVAL`.
