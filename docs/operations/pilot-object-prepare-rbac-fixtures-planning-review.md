# Pilot object/prepare RBAC fixtures — independent planning review

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/completion_test_review`  
Verdict: **CHANGES_REQUESTED**

This is planning review only. The reviewer did not edit OpenSpec artifacts,
specifications, code or tests. Neither package authorizes implementation, test
edits or production authorization changes.

## Reviewed hashes

### `pilot-object-read-rbac-fixtures`

```text
6cdbec70b9711975e510f244becac51f45f4e6c695f41672c20aca2cdeeabbad  proposal.md
d1cbdd35e9435e04d10d9fdcd72ec5cca622c648c4ada1ca44e3099fc6cd0c9a  design.md
bd1b1bb7977d32939e3a3aada1f2ed17649464fe4b8c4162a1925a66d6a7dd1e  specs/verification/pilot-object-read-rbac-fixtures/spec.md
fd55ec9e78f04aca30e5f7bcd912c811a8d1fda8a61c64cd7e0f15c11dc421ab  tasks.md
```

### `pilot-prepare-rbac-fixtures`

```text
9e390f976ca55107b51272c11b1e45d0a5d1bbff2e687f6954fa3cdf8de76020  proposal.md
5bc0cf61c5d49b91a6247e9a00befd310f6efa0cc28018a9a678b7eee1d1a7ca  design.md
03a6b543ca2428c22b2ca6dfe823322c2232b022281147d29664a0f9275f8ee2  specs/verification/pilot-prepare-rbac-fixtures/spec.md
214bb9877e69277d474bb3012368dbe175a4a1b51d26e6072d51dc5022caaa44  tasks.md
```

Stable references:

```text
f13c27c2ee0d706954f5eee081bb717612abeac5e0386f0881a875c229bc1392  specs/LOCAL-RBAC-AUTH-CONTRACT-001.md
8c5e703a4429092ee3087a30d250b946468e10d007fefb5e4b57ee7f9eca44ee  reviews/tests/LOCAL-RBAC-AUTH-CONTRACT-001.md
d9a85d0335480bbba626cd4b4f49262f47ad9c441316ca4cde7a5e2abbb90c3c  reviews/code/LOCAL-RBAC-AUTH-CONTRACT-001.md
a1c1ba68dd36482b316764119d41c0c56843c63815154984bd37233c4596a943  PRODUCT.md
98c10bf12d606580e420587dd389dda0cbbbbf65b8cf196d20aeb60dd2b11e98  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```

## Required findings

### R1 — object package treats non-migrated card/shell routes as local-RBAC routes

The stable owner-approved `LOCAL-RBAC-AUTH-CONTRACT-001` migrates exactly one
vertical consumer: `GET /pilot/objects → objects.read`. It explicitly leaves
other routes for separate slices and names card-route authorization migration as
outside that change.

The object fixture proposal nevertheless targets list, object card and
configured shell “through local authorization boundary”, while its delta says
every positive object-read verifier must supply a trusted local actor ID and
exact `objects.read`, and that legacy identity/email must never suffice. Current
card and shell paths do not use the migrated `LocalObjectListAuthorization`
route seam; they still resolve the established identity/directory path. Adding
local tables/grants to their fixtures can be a necessary compatibility setup,
but it does not prove those routes enforce `objects.read` or reject legacy/email
authority.

This contradicts the package's own non-goal “card-route authorization policy”
and risks turning a fixture change into an unapproved route-permission migration.
Split the contract precisely:

- for exact `/pilot/objects`, require trusted local actor ID, exact
  `objects.read`, all stable negative branches and no fallback;
- for card and configured shell, state only the currently approved admission
  seam and exact fixture prerequisites, without claiming local-RBAC enforcement
  or permission denial that production does not own;
- alternatively create separately approved route-migration changes before
  requiring those routes to use the common local seam.

The future executable draft must map every included verifier/path to its actual
actor input (`FMONITOR_AUTH_USER_ID`, `REMOTE_USER` or another approved boundary)
and permission owner. “Object-read” is too broad to substitute for an exact
route mapping.

### R2 — prepare package confuses local role permissions with process capability

The current prepare form admission checks
`PrepareFormReaderProvider::hasCapability(userId, 'assignment_order.prepare')`,
backed by the process capability store. It is not the sole migrated
`LocalObjectListAuthorization` consumer and does not derive prepare admission
from an `objects.read` local role or the new shared local-RBAC application seam.

The proposal/delta instead describe an active local role with exact
`assignment_order.prepare`, frame `objects.read` as a separate local read grant,
and claim a read-only local actor must receive 403 before the prepare reader.
That is a new route authority model unless it is limited to existing process
capability facts. Stable LOCAL-RBAC uses the prepare literal only as a
cross-route denial example; it explicitly does not connect the prepare route.

Revise the package to name both distinct facts and their current owners:

- identity/directory prerequisite required to resolve the actor;
- exact production process capability row required by the current prepare form
  reader;
- any local role permissions needed merely by surrounding list/card setup, not
  as an implied prepare grant.

If the intent is to migrate prepare to the common local-RBAC seam, that is a
separate behavior/security slice requiring exact route mapping, Gate 1 owner
approval and production changes—not fixture-only alignment.

### R3 — prepare GET representation and state-changing POST command are mixed

The failing named verifier is `pilot_prepare_form_001_test.php`, whose principal
surface is GET/HEAD form composition and process-capability admission. The
proposal, delta and tasks additionally promise real GET/POST prepare, CSRF,
malformed media/body and command zero-mutation behavior without identifying the
exact POST controller/application seam or its separately approved authorization
contract.

Fixture planning must not infer that form-read authorization, command
authorization and CSRF are one boundary merely because they share a route-like
name. Pin every method/path, required authority and expected status/body. Either:

- scope this change to GET/HEAD prepare-form fixture restoration and preserve
  existing command tests only as unaffected regression; or
- cite the approved executable command spec/public seam and independently state
  POST actor/capability/CSRF/persistence snapshots without changing semantics.

Until then, “malformed command after authorization” is not sufficiently
traceable for an executable fixture Gate 1 contract and can accidentally expand
command scope despite the proposal's command non-goal.

### R4 — exact negative branches and observability need route-specific inventory

Both designs correctly demand explicit actor IDs/unset, isolated process/env and
no ambient positive grant, but the delta specs collapse negative behavior into
broad lists. The Gate 1 drafts must enumerate for each exact route/method:

- trusted identity field and explicit absence/malformed value;
- canonical user/activation/role/grant or process-capability facts;
- exact 401/403/503 (and applicable 400/404/405/409) result/body/header;
- a handler/read sentinel proving denial occurred before the protected read or
  command, not merely a final status;
- complete domain/audit/artifact before/after snapshot for rejected cases;
- committed revoke on a new invocation and an independently successful positive
  control preventing always-deny tests;
- legacy-only, near-match, inactive and unavailable cases only where the exact
  route's stable authority contract owns them.

DOM assertions must cite exact approved UI-shell/object/prepare specs and remain
separate from authorization assertions. Expected DOM may not be copied from
current production output. This inventory belongs in the executable specs
requested by task 1.1, but the OpenSpec packages must first stop implying one
authority model across different routes.

## Confirmed planning properties

- Both changes correctly identify fixture/test-support ownership and prohibit
  production fallback, login/session redesign, permission invention and
  architecture-baseline growth.
- Canonical schema/manifest setup rather than reduced ad-hoc RBAC tables is the
  right direction; reusable fixture code must remain test-owned and not call a
  production schema creator as its expected-value oracle.
- Explicit per-case environment construction and unset are appropriate defenses
  against ambient actor contamination.
- Positive control before denial matrices, committed revoke, current snapshot,
  no-handler-read and full persistence snapshots are appropriate observable
  requirements once bound to exact routes.
- Object configured DOM may be aligned only to already approved UI/CSP
  contracts; prepare command/domain/PDF behavior remains outside fixture
  ownership.
- Tasks preserve Gate order: executable spec, explicit owner approval,
  demonstrated RED, independent test review, fixture-only GREEN, verification
  and independent code review. No task is marked done and no implementation is
  authorized by these proposals.

## Verification

```text
openspec validate pilot-object-read-rbac-fixtures --strict
Change 'pilot-object-read-rbac-fixtures' is valid

openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid
```

Strict validation proves artifact structure, not correctness of the conflated
route authority assumptions above.

## Verdict

**CHANGES_REQUESTED.** The fixture-only objective is valid, but the object
package must separate the one migrated list route from card/shell compatibility,
and the prepare package must distinguish local identity/role facts, process
capability admission and GET-form versus POST-command seams. After revising the
OpenSpec packages, request a fresh planning review before drafting owner-facing
Gate 1 executable specs. No code or test edits are authorized by this verdict.
