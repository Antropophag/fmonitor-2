# Pilot object/prepare RBAC fixtures — independent planning rereview v2

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **APPROVED_FOR_GATE1_DRAFT**

The reviewer did not author or edit OpenSpec artifacts, specifications, code or
tests. This verdict supersedes the prior two `CHANGES_REQUESTED` planning
records for the exact packages below. It authorizes preparation and independent
review of executable Gate 1 drafts only; it does not authorize owner approval,
test edits or implementation.

## Reviewed hashes

### `pilot-object-read-rbac-fixtures`

```text
a534ca9cf726c01c1dbd0d3faeb3c4560197c23d6f2fc1b654afe49741c6e4cc  proposal.md
bd2cb1b9e48e2b8d8959d88d67b4591297d447f94856179ebaf8a1f18a7e891a  design.md
3128529b18a6226a6f66ebce2159bdf48ffb194f396869132cab179df99aabc2  specs/verification/pilot-object-read-rbac-fixtures/spec.md
34b0246214a3cea8b38d63a81613f3e972dd36f35cd1be0c0a46f871f80ccce6  tasks.md
```

### `pilot-prepare-rbac-fixtures`

```text
b5a13a7ee05f65f8caac33d1e55e0c89fa9040d3a224bfbbd9c36a1726fe18e3  proposal.md
9a7237e3352969a8329dc538d45bde3bd75eb36d786fd2f516d43df541eb8f09  design.md
972b38eb140dfad5c34507f7bae1d78bfaecc2261d571924349d97f52532557e  specs/verification/pilot-prepare-rbac-fixtures/spec.md
d40d54d18676b7d93433e358c5f7eb3dd66b808cb57ffc9ab81bcf2927f4536e  tasks.md
```

Prior reviews:

```text
be9fcf6b0720dcbf5d355a201a9714c991407e0f9d49fcbea3ab2e68c7daec95  pilot-object-prepare-rbac-fixtures-planning-review.md
389c7ca8a6ededec1613b5612b174c6d402c56e2d1b6fd736d7b6bee62060208  pilot-object-prepare-rbac-fixtures-planning-rereview.md
```

## Object-list package

The scope is now exact and coherent:

- only `GET /pilot/objects` uses the already approved local authorization seam
  and byte-exact `objects.read`;
- the actor comes from the trusted positive local actor ID, while `REMOTE_USER`,
  legacy email/role and authenticated-only state cannot grant admission;
- object card, prepare and generic UI shell authorization/fixtures are explicit
  non-goals;
- source oracle, target public seam, Impact, design, delta and tasks now all name
  the object-list verifier only.

Positive admission must reach the real handler/read and then independently
assert the approved object-list representation/security/asset landmarks.
Negative planning retains missing/malformed identity, legacy-only authority,
inactive user/activation/role, missing/near-match permission, unavailable
authorization, committed revoke, deterministic repeat and unknown suffix.
Gate 1 must make exact result/body/header and handler-read/mutation sentinels
executable per case, as tasks already require.

Fixture ownership remains tests/support only. Canonical schema/manifest setup,
explicit actor env/unset, isolated namespace/finally cleanup and no production
fallback are appropriate. No list-route production behavior or permission is
changed by this package.

## Prepare GET vertical package

The package now honestly includes a production security change rather than
hiding it as fixture-only:

- exact `GET /pilot/objects/{positive-id}/assignment-order/prepare` is migrated
  to the stable local authorization seam with exact local route permission
  `assignment_order.prepare`;
- the existing downstream process capability with the same literal remains a
  separate fact and gate;
- production ownership is explicitly PilotHttp route composition; tasks include
  route wiring before process-capability/form reads, while InstallationProcess
  domain behavior remains unchanged;
- Impact, migration/rollback plan and fixture tasks consistently cover the
  route+fixture vertical.

The two one-sided cases are exact and security-sensitive: local permission
without process capability denies before form read; process capability without
local permission denies before the capability/handler read. `objects.read`,
legacy role, authentication, wildcard/prefix/case variants and either gate alone
cannot imply prepare. A positive actor with both exact gates prevents an
always-deny implementation.

Normative method scope is GET only. PUT retains the existing 405 result before
authorization reads. POST/media/CSRF and the state-changing prepare command are
explicitly excluded in proposal, design, delta and tasks; revoke now applies
only to a new GET invocation. Full process/artifact/audit snapshots preserve
zero mutation on denied GETs, and the successful form representation remains
read-only.

## Cross-package observability and delivery gates

Both future executable drafts must instantiate a fresh explicit environment for
each case, never inherit an ambient positive actor/grant, and distinguish
authorization failure from fixture/DOM/setup failure. Expected representation
must come from approved route/UI specs, not production-derived snapshots.
Handler/read sentinels and complete before/after persistence state are required
for denial precedence; committed revoke must be observed on a new invocation.

The packages preserve the required sequence: executable Gate 1 spec and fresh
review, explicit owner approval exact hash, demonstrated public-seam RED,
independent test review, minimal fixture/route GREEN, focused/full verification,
architecture/lint, independent code review and Done evidence. No task is marked
complete and no current artifact authorizes code/test changes.

## Verification

```text
openspec validate pilot-object-read-rbac-fixtures --strict
Change 'pilot-object-read-rbac-fixtures' is valid

openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

git diff --check -- <both reviewed OpenSpec packages>
exit 0, empty output
```

## Verdict

**APPROVED_FOR_GATE1_DRAFT.** Object list is a bounded fixture-only restoration
of the stable local-RBAC route. Prepare is a separately explicit GET
route-migration vertical with two independent authorization/capability gates and
untouched POST command semantics. The packages may now produce owner-facing
executable specs; those specs still require fresh independent Gate 1 review and
explicit owner approval before Gate 2.
