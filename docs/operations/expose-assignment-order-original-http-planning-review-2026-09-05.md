# Independent planning review — assignment-order original HTTP

- Date: `2026-09-05`
- Reviewer task: `/root/http_planning_review`
- Reviewed commit: `6f2d4c2e88d26b05c1c74919a6cd14c6c7081153`
- Change: `expose-assignment-order-original-http`
- Verdict: **APPROVED_FOR_PLANNING**

This verdict approves only the coherence and readiness of the OpenSpec planning
package. It is not Gate 1 approval: `ASSIGNMENT-ORDER-ORIGINAL-HTTP-001` does not
yet exist as an approved executable contract, its exact routes and transport
mapping are deliberately deferred, and the read-role grants remain unresolved.

## Exact reviewed hashes

Planning artifacts at the reviewed commit:

```text
7555aa06e53ea191a4fd7854f1fcf46ceb785a0f87b18154b84981bfe40bce5e  openspec/changes/expose-assignment-order-original-http/.openspec.yaml
55c3990c47dc45ee8c423227d2e5f792fb98a7650a8a4c5ff4898910239fc2ee  openspec/changes/expose-assignment-order-original-http/proposal.md
0071d75634026a39e92ce09e44846579c686e49b12eeff6bcaf78a61ebc48a96  openspec/changes/expose-assignment-order-original-http/design.md
e1cf6c812b22e38300ee11363550614a51ae23ec19b669de4def4deba6e38f2d  openspec/changes/expose-assignment-order-original-http/specs/pilot/assignment-order-original-http/spec.md
02aa05a92f20d1d0a3156a45489ae815faa46188c8daff30e0f7f717e197a5ae  openspec/changes/expose-assignment-order-original-http/tasks.md
```

Governing inputs read at the reviewed commit:

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
c54abf9d290bbc2653f03a9d7ce4c842167b682b0b4d682bb0fd2ff271abe259  openspec/config.yaml
```

The design's characterization anchor resolves to exact commit
`aecd7bb73d1c8b1f52b73764c79cdb2d7289234a`.

Supplemental read-authority audit, produced independently against the same
planning base after the reviewed commit:

```text
1c5e49991a31c9264f1512ba76e1ddc72dfe9625  evidence commit
36195923ef4872156d905cad2d98e2b9b537a3c20f38f09434689e27b9704e3d  docs/operations/original-upload-http-read-grants-evidence-2026-09-05.md
```

## Assessment

### Coherence and scope

The four artifacts describe one bounded delivery slice: authenticated portal
upload/correction through the approved original command, plus authorized
metadata/history/download through a product read seam. Opening, composition
application, OCR, 1C DO integration, Quality Graph, PR #10, and alteration of
the protected legacy E2E contract are consistently excluded. Proposal value,
delta requirements, design decisions and ordered tasks agree on that boundary.

### Ownership and architecture

State mutation has one owner: the AssignmentOrderOriginal application command
`submitAssignmentOrderOriginal`. The planned HTTP adapter translates transport
input and trusted-session actor identity but does not acquire domain SQL, DDL or
fact ownership. Metadata/history/download use a separate read-only application
seam rather than the diagnostic evidence reader. This separation preserves the
repository's single-mutation-seam rule without pretending that reads are
commands, and the planned architecture check targets the two material leakage
risks: domain persistence in HTTP and verifier factories in runtime.

### Truthful blockers and Gate 1 readiness

The package does not claim executable readiness. It explicitly identifies both
remaining blockers:

- the predecessor command must have complete, exact Gate 5 evidence before this
  slice can receive Gate 1 approval or proceed to RED;
- `assignment_order.original.read` is reserved as the exact local permission,
  but its role grants must be proved by inherited authority or decided by the
  owner before read/download RED.

That distinction is sound. Reserving the permission code does not invent its
grants, and upload/correct grants are not used to infer disclosure authority.
The independent audit at `1c5e49991a31c9264f1512ba76e1ddc72dfe9625`
confirms there is no inherited grant to apply: runtime knows only the older
`assignment_order_artifact.read`, whose role grants and order/appendix scope do
not establish signed-original history/download policy. Therefore this is an
actual owner-decision blocker, not merely missing documentation.
Likewise, deferring exact method/path, DTOs, CSRF/session behavior, multipart
bounds, status/error mapping, download headers and legacy-route disposition to
the named executable specification is appropriate for this planning package.
Task 1.3 enumerates those missing contract elements; task 1.4 requires no
unresolved acceptance semantics and a fresh independent approval. Gate 1 may be
drafted from this plan, but it cannot be approved while either blocker remains.

### History, failure behavior and protected regression

The plan preserves immutable revisions, request-identity replay, correction
conflicts, private storage and the distinction between document bytes/date and
upload time. It also recognizes streaming/storage failure as a Gate 1 contract
question instead of silently promising a partial-success behavior.

`PILOT-E2E-FLOW-001` remains protected and unchanged. The plan requires
characterization and route disposition without editing that executable contract
or deriving new expectations from `PilotE2ECoordinator`; the new HTTP behavior
receives separate executable tests. Migration wiring and regression of both
surfaces are required before release. Any future change to the protected legacy
E2E still requires owner-approved Gate 1 amendment outside this package.

## Verification

```text
$ openspec validate expose-assignment-order-original-http --strict
Change 'expose-assignment-order-original-http' is valid

$ git diff --check
PASS (exit 0 before this append-only review record was added)
```

## Verdict consequence

**APPROVED_FOR_PLANNING.** The exact planning package above is coherent and may
advance to characterization, read-authority investigation, and drafting of
`ASSIGNMENT-ORDER-ORIGINAL-HTTP-001`. This record never approves Gate 1, RED,
tests, implementation, release or archive. Gate 2 remains closed until a fresh
independent Gate 1 approval covers the completed executable contract, resolved
reader grants and exact predecessor Gate 5 evidence.
