# Independent Gate 1 rereview v5 — PILOT-PREPARE-RBAC-FIXTURES-001 v3

Date: 2026-09-02  
Reviewer: fresh independently tasked agent `/root/prepare_transport_review`  
Gate: Gate 1 rereview after PHP built-in transport-boundary amendment  
Verdict: **CHANGES_REQUIRED**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation, and did not edit any of them during
this review. This append-only review record is the reviewer's only change.

## Exact reviewed hashes

```text
e154577ff2e4f702ea752d2810f07161acd4d26fb78200e3d583ed53e39f2ef8  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
ba32b84f02c595ec4ac05e79bd7aaa87f62e582b254f94885a8677baa05c01fc  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
00179e32296631af5b899ca3960de61eef41a38053327655bea1db08e288e7f0  openspec/changes/pilot-prepare-rbac-fixtures/design.md
6ca53a5cf17781150f7f59f5f1eed430ee1e9c71eb3d8d8ad40217a0e54772df  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
784c04b0f73e0cbc5b51b0e7ebed574920da65d85776ec6770849eab3df2a473  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
```

## Satisfactory transport clarification

The impossible transport-level unread-body claim has been removed coherently
from the executable spec, proposal, design and delta spec. Fully delivered
bounded `PUT|PATCH|DELETE` requests still require the exact public `405` and
`Allow: GET, HEAD, POST`, no payload-derived response bytes, no authorization,
domain or form work, and byte-equivalent DB/process/artifact/session/file state.
The package correctly acknowledges that PHP's built-in transport may have
buffered or consumed the body before application invocation and introduces no
request-body probe or other hidden production/test seam.

The previously approved public decorator/factory surface otherwise remains
intact: the canonical factory creates one real renderer, calls `decorate()` once,
and uses its returned renderer throughout the canonical graph; normal production
uses the identity decorator, while a test decorator receives only the real
renderer. Manual graph reconstruction, replacement renderers, reflection and
graph/environment access remain forbidden. Existing GET/HEAD admission order,
two independent authorization gates, exact inherited outcomes, POST exclusion,
HEAD parity, snapshots and redaction are preserved.

The gate reset is also correct: task 1.5 and every Gate 2+ task are open, the
executable spec remains DRAFT, and earlier approvals and Gate 2/3 records are
explicitly historical. No amended RED is authorized yet.

## Blocking finding: decorator-call guarantee contradicts canonical composition

Design decision 5 requires the canonical factory to call `decorate()` exactly
once while constructing the entrypoint. Design decision 6 then requires an
unsupported request to prove `zero renderer-decorator calls`. Read literally,
both guarantees cannot hold for a canonical entrypoint built with the test spy:
the required composition-time `decorate()` call has already occurred before any
request, including a `PUT`, `PATCH` or `DELETE`, can be delivered.

The executable spec and delta spec use the implementable narrower statement:
unsupported methods produce zero renderer invocations. Tasks 1.5 and 2.1 also
say `zero render`, while the proposal says `zero decorator/render`. The package
therefore does not give a RED author one exact counter boundary.

Correct the transport language across the artifacts to distinguish the required
single composition-time `decorate()` call from request-time invocation of the
decorated/wrapped renderer. The 405 case should require the former exactly once
for canonical spy composition and the latter zero times, together with zero
authorization/domain/form work and zero mutation. This is a wording correction,
not permission to remove the canonical decorator call, reset a hidden counter,
add a request-body observation seam, or weaken any public 405/snapshot guarantee.

## Verification

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

git diff --check -- specs/PILOT-PREPARE-RBAC-FIXTURES-001.md \
  openspec/changes/pilot-prepare-rbac-fixtures
PASS (no output)

git diff --check
PASS (no output)
```

## Gate decision

Gate 1 remains closed for v3. Task 1.5 stays open and no replacement Gate 2 RED
may begin from these hashes. After the counter boundary is corrected coherently,
the changed exact hashes require another fresh independent Gate 1 rereview and
explicit owner approval.
