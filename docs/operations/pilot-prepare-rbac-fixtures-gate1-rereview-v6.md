# Independent Gate 1 rereview v6 — PILOT-PREPARE-RBAC-FIXTURES-001 v3

Date: 2026-09-02  
Reviewer: fresh independently tasked agent `/root/prepare_transport_rereview`  
Gate: Gate 1 rereview after decorator-counter contradiction correction  
Verdict: **READY_FOR_OWNER_APPROVAL**

The reviewer authored neither the reviewed executable/OpenSpec artifacts nor
their tests or production implementation, and did not edit any of them during
this review. This append-only review record is the reviewer's only change.

## Exact reviewed hashes

```text
2736c142c2c4535b6541b08764ef5cfea034434291657935b718945b67b55818  specs/PILOT-PREPARE-RBAC-FIXTURES-001.md
d7a3c0255c7d81432be2e69918449dcd0d8280556e4ce2fde0af5c2f4cfae1b9  openspec/changes/pilot-prepare-rbac-fixtures/proposal.md
816dd64e27d505ef12a6c5660739ef21c4c44874ea45b690e02f910c2838d768  openspec/changes/pilot-prepare-rbac-fixtures/design.md
f6283a3200ee7a39035eb0ab7db6c3d4d48bd2e7990053d62c823960a2ae7425  openspec/changes/pilot-prepare-rbac-fixtures/tasks.md
3e51342ed87eddecb1c30bc4cd4218cf3d6f704d340b2431423b582c9e918beb  openspec/changes/pilot-prepare-rbac-fixtures/specs/verification/pilot-prepare-rbac-fixtures/spec.md
e1610987eb1bc938182d6bb84e3f5816cb3b63e4065e9e9353b2b3804a1236df  docs/operations/pilot-prepare-rbac-fixtures-red-evidence.md
```

## Counter boundary is now coherent

The executable spec, proposal, design, tasks, delta spec and superseded RED
evidence now distinguish the two observable events consistently:

- canonical factory composition creates one real
  `ProductionPrepareFormRenderer` and invokes `decorate()` exactly once;
- an unsupported `PUT|PATCH|DELETE` request retains that one composition-time
  call but invokes the returned wrapped renderer zero times at request time.

Thus the canonical spy can prove both factory wiring and early method rejection
without an impossible zero-decorator assertion. No hidden reset, request-body
probe, alternate graph or replacement renderer is permitted or needed.

## Preserved guarantees

The correction does not weaken the transport contract. Fully delivered bounded
`PUT|PATCH|DELETE` requests still require exact `405`, exact
`Allow: GET, HEAD, POST`, no payload-derived response bytes, admission before
authorization/domain/form work, and byte-equivalent DB/process/artifact/session/
file state. The application correctly makes no claim that PHP's built-in
transport did not buffer or consume the body before invoking PilotHttp.

The prior decorator contract is also intact: normal production uses the
identity decorator; an explicit test decorator receives only the real renderer
created by the canonical factory and delegates its input/output unchanged.
Manual graph reconstruction, reflection, shadowing, replacement renderers and
environment/graph access remain forbidden. GET/HEAD admission ordering, the two
independent authorization gates, inherited outcomes, HEAD parity, redaction,
snapshots and POST exclusion remain unchanged.

## Gate state

The executable spec remains `DRAFT / Gate 1`. Task 1.5 and every Gate 2+ task
remain open. The evidence explicitly marks every prior RED and Gate 3 review as
historical. These reviewed hashes authorize neither test changes nor production
GREEN until the owner explicitly approves this exact batch.

## Verification

```text
openspec validate pilot-prepare-rbac-fixtures --strict
Change 'pilot-prepare-rbac-fixtures' is valid

git diff --check -- specs/PILOT-PREPARE-RBAC-FIXTURES-001.md \
  openspec/changes/pilot-prepare-rbac-fixtures \
  docs/operations/pilot-prepare-rbac-fixtures-red-evidence.md
PASS (no output)

git diff --check
PASS (no output)
```

## Gate decision

Gate 1 technical review is approved. The exact hashes above are
**READY_FOR_OWNER_APPROVAL**. After explicit owner approval, task 1.5 may close
and a fresh Gate 2 RED may be authored and demonstrated; historical RED/review
records must not be reused as approval for the amended v3 contract.
