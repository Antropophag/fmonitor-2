# Independent Gate 1 rereview v3 — PILOT-E2E-RBAC-FIXTURES-001

Date: 2026-09-02  
Prior rereview SHA-256:
`b53c7a4f9a2f6864ac54c650af4bfd9f9674d8b556e03de4ed3827a0d5a69fcd`  
Verdict: **CHANGES_REQUIRED**

## Authoritative current-state finding

The executable spec currently hashes to:

```text
dd32915602225450a0ad1a1213e1cff4a0d5afea62ca1a29a8adee447e1bf48e  specs/PILOT-E2E-RBAC-FIXTURES-001.md
```

This is byte-for-byte the same reviewed hash as the prior rereview, not a new
corrected hash. The requested isolated-versus-main wording correction is not
present in the authoritative file.

Section 3 still states both:

- isolated revoke cleanup finishes before main journey and the main actor-18
  grant remains byte-equivalent at the artifact boundary; and
- “Main branch also asserts exact role5301 permission row before revoke, its
  sole absence afterward”.

Those conditions cannot both hold. The last sentence must say **isolated revoke
branch** observes the sole permission-row deletion. A separate sentence must
say **main branch** observes the permission present both before its first list
request and at the artifact boundary, with its user/role/assignment/grant
manifest fully equal.

## Other findings remain closed

The current hash still correctly contains:

- actor 19 fixed trusted ID 19 with no local row →403 and separate missing ID
  →401;
- process-environment actor boundary not selectable by request input;
- exact transport-header normalization;
- restricted-RBAC-only DB sentinel;
- literal 401/403/503, external opaque correlation and internal safe category;
- task-owned cleanup, command-route exclusions and downstream combined-PDF
  boundary.

Strict OpenSpec validation remains GREEN:

```text
Change 'pilot-e2e-rbac-fixtures' is valid
```

Structural validity does not resolve the contradictory executable assertion.
No owner approval or Gate 2 work is authorized for hash `dd329…`.

## Reviewed hashes

```text
dd32915602225450a0ad1a1213e1cff4a0d5afea62ca1a29a8adee447e1bf48e  specs/PILOT-E2E-RBAC-FIXTURES-001.md
9929249efb3f5f8afbd7f0757ee1681207b19dcea45bb00c90df4f3c2f3d0e5a  openspec/changes/pilot-e2e-rbac-fixtures/proposal.md
bdc5b4fb4f4dfbb62e03d69d7ec6595602b85ec5115fb14366dc3d4be5d0be5c  openspec/changes/pilot-e2e-rbac-fixtures/design.md
d2380bec2e1993d167340644e40a9fa34d8d8b984298bf3073f66cca93bf0e5b  openspec/changes/pilot-e2e-rbac-fixtures/tasks.md
f57bbea09e331d0459e2320a64ef2a59ed73bd71c5cfc186b961df12896aaafb  openspec/changes/pilot-e2e-rbac-fixtures/specs/verification/pilot-e2e-rbac-fixtures/spec.md
```

After the correction actually lands and produces a new executable-spec hash,
request another narrow independent rereview.
