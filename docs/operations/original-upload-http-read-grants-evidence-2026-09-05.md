# Original HTTP read grants — evidence and decision boundary

Date: 2026-09-05. Author: `/root`. Inspected base:
`6f2d4c2e88d26b05c1c74919a6cd14c6c7081153`.

## Observed authority

- `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` section 14 (reserved downstream
  scope, searchable at `Reserved read permission`) names
  `assignment_order.original.read` but does not grant it to roles.
- `AuthorizeLocalActor::PERMISSIONS` has `assignment_order_artifact.read`,
  not `assignment_order.original.read`.
- `LocalRoleCatalog` gives artifact-read to `fkr_operator` and
  `construction_control_engineer`. `manager` has only objects/installers/
  management read permissions. These are runtime observations, not authority
  to define original-evidence grants.
- `ARTIFACT-STORE-001` section 6 explicitly limits artifact types to `order`
  or `appendix`. Its predecessor capability cannot silently determine access
  to all immutable signed original revisions.
- The original-upload owner decision expressly authorizes FKR operator and
  FKR manager upload/correction. It does not explicitly establish original
  download/history readers or engineer object scope.

## Exact SHA-256 evidence

```text
16e1ac3b7314a773a87aab2515ac0dd8f69db66119558f759af4cdd50870ea6f  app/RapidPilot/LocalRoleCatalog.php
3e016e4a851bccc19daa469da69f14a426fd3013f6bfa78bfa7166767226b44c  app/IdentityAccess/AuthorizeLocalActor.php
9498bfca1001360f64d6a31dc5588956d8cb4021feb8515d1b017aff913cf1bc  specs/ARTIFACT-STORE-001.md
```

## Candidate decision for owner review (not approved)

Grant `assignment_order.original.read` to active `fkr_operator` and `manager`
for objects they can access, including immutable prior revisions. Permit
engineers only after an explicit choice of object scope (for example, only
their assigned objects). Administration roles do not inherit document access.
Do not infer read from upload/correct. Preserve independently configured role
assignments through additive migration.

This is a material access-policy decision, not a technical route-string
choice. The HTTP planning package correctly leaves read/download NEEDS_GRILL.
No grants, production code, executable tests or protected legacy specs were
changed by this evidence record.
