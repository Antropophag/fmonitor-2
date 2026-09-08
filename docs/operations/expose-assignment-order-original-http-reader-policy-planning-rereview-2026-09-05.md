# Independent planning amendment review — original HTTP reader policy

- Date: `2026-09-05`
- Reviewer task: `/root/original_reader_policy_review`
- Reviewed commit: `3ee7bcef1c3cb090c22b773b651c8d512e86e07c`
- Change: `expose-assignment-order-original-http`
- Verdict: **APPROVED_FOR_PLANNING_AMENDMENT**

This verdict is limited to the reader-policy amendment of the OpenSpec planning
package. It is not Gate 1, RED, test, implementation, Gate 5, Done, release or
archive approval.

## Exact reviewed hashes

Amended planning artifacts at the reviewed commit:

```text
7555aa06e53ea191a4fd7854f1fcf46ceb785a0f87b18154b84981bfe40bce5e  openspec/changes/expose-assignment-order-original-http/.openspec.yaml
1baee847d605dfc70cb4fbe3cabe624f7a8124b232dc3ccc63229456af6e3b0e  openspec/changes/expose-assignment-order-original-http/proposal.md
9a609815fb6196718aef598983f6c2b64feaa6b74b23ce6c505be47d4b5da680  openspec/changes/expose-assignment-order-original-http/design.md
e7e48ffe60acc90db39018ac6ac1ff47ed385f567d4ccdee57efbe41de35a904  openspec/changes/expose-assignment-order-original-http/specs/pilot/assignment-order-original-http/spec.md
e15bd9f17540fc4198bd89aaac68a2e1ebf4bb3a1bb4ad88ab4b7a7dc2485816  openspec/changes/expose-assignment-order-original-http/tasks.md
```

Decision and prior-review evidence:

```text
fbe64258f8a49ab32d0068f80092315ada64d604fede31d48cbf7575aa5d5c8b  docs/operations/original-http-reader-owner-decision-2026-09-05.md
36195923ef4872156d905cad2d98e2b9b537a3c20f38f09434689e27b9704e3d  docs/operations/original-upload-http-read-grants-evidence-2026-09-05.md
4b6d42e5164395ed886fc0c034b1914bb5d18627c5ec0fc4b754d4c5ec4a05d5  docs/operations/expose-assignment-order-original-http-planning-review-2026-09-05.md
```

Governing inputs:

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
c54abf9d290bbc2653f03a9d7ce4c842167b682b0b4d682bb0fd2ff271abe259  openspec/config.yaml
```

## Review findings

The prior versions at commit
`6f2d4c2e88d26b05c1c74919a6cd14c6c7081153` correctly left original-evidence
reader grants unresolved. The evidence audit then proposed FKR operator and FKR
manager access within objects available to them, engineer access only after an
explicit object-scope choice, no administrative inheritance, and no inference
from mutation permissions. The recorded owner response approved those proposed
grants and additionally required OTIZ access to every assignment order. The
amendment faithfully resolves the remaining engineer choice as assigned-object
scope and adds unrestricted-by-engineer-assignment OTIZ scope.

The four artifacts are mutually coherent:

- FKR operator and `manager` require an active user/role, the exact
  `assignment_order.original.read` permission, and access to the requested
  object. The exact source and predicate for “accessible objects” remain
  deliberately assigned to executable Gate 1 rather than invented here.
- A construction-control engineer receives the same exact permission only for
  objects assigned to that engineer. The delta spec has an explicit negative
  scenario for an unassigned object, including historical revisions.
- An OTIZ specialist with the exact permission may read every assignment order
  and every prior immutable revision. The design and scenario expressly prevent
  accidental reuse of the engineer-assignment predicate for OTIZ.
- An administrative role alone confers no original-evidence read access.
  Multi-role access is the union only of explicit grants and their applicable
  scopes, not a wildcard or role-name inference.
- Read access covers metadata, history and immutable bytes but implies none of
  upload, correction or opening. Those mutations retain their separate exact
  capabilities and the sole `submitAssignmentOrderOriginal` mutation seam.

Tasks 1.2 and 2.2 carry the policy into the executable-contract and RED stages:
they require an independently reviewed scope source/additive grant policy and
coverage for FKR/manager, assigned and unassigned engineers, global OTIZ,
inactive/revoked/no-grant/admin-only, and multi-role cases. No planning task
marks those grants as already implemented, and no task grants mutation through
the read seam.

No contradiction was found with the previous planning review. Its reader-grant
blocker is resolved by the subsequent owner decision, while its command Gate 5
predecessor and fresh independent Gate 1 requirements remain intact. The
amendment changes no production code, executable contract, tests or runtime
permissions.

## Verification

```text
$ openspec validate expose-assignment-order-original-http --strict
Change 'expose-assignment-order-original-http' is valid

$ git diff --check
PASS (exit 0 with only this append-only review record added)
```

## Verdict consequence

**APPROVED_FOR_PLANNING_AMENDMENT.** The exact amended planning package is
coherent with the owner-approved reader policy and may inform the independent,
data-free executable HTTP schema draft. This record does not approve Gate 1 or
Done. Implementation and RED remain closed until the named executable contract,
exact scope/grant mechanics, independent Gate 1 approval, and full predecessor
command Gate 5 evidence exist.
