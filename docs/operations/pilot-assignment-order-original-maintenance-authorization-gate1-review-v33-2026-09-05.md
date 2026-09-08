# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v33 production maintenance authorization — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_maintenance_auth_gate1`
- Reviewed commit: `4140f75bcede877d32d0f2cc736d64be2e0ef7da`
- Triggering gap: `320b10f1201bdb2eef1a54b747d84eb6665682bc`
- Scope: production maintenance authorization amendment and coherence of the
  current executable specification/OpenSpec package; no tests or production
  implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Findings

### Factory and authorization contract

`ProductionAssignmentOrderOriginalMaintenanceFactory::create()` now takes the
real `mysqli`, the existing production config and one typed
`AssignmentOrderOriginalMaintenanceAuthorization` DTO. The DTO exposes exactly
the two composition facts needed by the existing string-principal authorizer:
`systemPrincipalId` and `capability`. It does not alter the maintenance command,
repository, storage or result signatures and introduces no second mutation
seam.

The principal grammar is closed as `[A-Za-z0-9._:-]{1,160}`. The capability is
the one literal `assignment_order.original.storage.reconcile`. The production
authorizer permits only byte-equality of both the command principal and the
application-requested capability with that configured pair. A principal or
capability mismatch is therefore `DENIED`; there is no prefix, wildcard, list,
case folding, role-name inference or alternate permission fallback.

Invalid principal syntax or any other capability value throws
`InvalidArgumentException` with exactly `Invalid maintenance authorization.`
before factory DB/storage access. This gives Gate 2 a stable pre-resource
failure oracle rather than allowing malformed trusted configuration to become
an ordinary command denial or a later infrastructure failure.

### Trusted deployment boundary and security separation

The DTO is explicitly deployment composition, not request data. Production
bootstrap must construct it from trusted deployment configuration and may not
select it from HTTP, CLI command payload, mutable global, role or display name.
The canonical real-MariaDB acceptance identity is independently fixed as
`test-maintenance-01` plus the exact reconcile capability, so the authorized
path is constructible without inventing a source in the RED or implementation.

The contract expressly forbids storing this system authorization in
`fm2_process_user_capabilities` or creating a user capability row. It therefore
keeps maintenance identity separate from the user schema and preserves the
existing user authorization grammar and capability CHECK. No migration, user
role, public HTTP contract or production-person identity is added.

### Real persistence acceptance sufficiency

The pre-existing contract already requires MariaDB acceptance to use the
production repository and fresh read-only evidence adapter, and exposes exact
atomic maintenance request/audit inventories. V33 supplies the previously
missing authorized production-factory composition. Together these requirements
are sufficient for Gate 2 to prove an authorized real-MariaDB reconciliation,
terminal result plus audit atomicity, replay stability, and the mismatch and
invalid-configuration security branches. An in-memory substitute still cannot
satisfy that acceptance.

The amendment changes no product workflow, end-user capability, role,
composition/opening behavior, HTTP surface, original lineage, runtime-DDL
policy or blocked legacy E2E contract. Task 1.24 may advance; task 4.1 still
requires its independent RED and Gate 3 evidence.

## Verification

```text
$ git rev-parse HEAD
4140f75bcede877d32d0f2cc736d64be2e0ef7da

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff 4140f75^ 4140f75 --check
PASS (no output)
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
0b5050e793191cee26e08a3e2f1c21e7a4a9862d5feefef04354c83b47a3d4e0  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
70d6dba9fb35c2e2aed51c170870c6ec633418c5f63d073dc5ff8a566cfcf1c8  openspec/changes/replace-pilot-registration-with-original-upload/design.md
5cce5a87609f935821a74c0ba9ad071bfcbd0ed279edfb7be1d2c428b4d280cc  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
da1155f9562fc885effa32a24ce2709dac0ef4016b46785ee3c641bf857d64be  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
4a24b9c6d2fdc086cdd8f4e806f746afda0be6cd2b1401d155d6982f3b54b7a1  docs/operations/assignment-order-original-maintenance-authorizer-gate1-gap-2026-09-05.md
```

This review record omits its own circular hash.
