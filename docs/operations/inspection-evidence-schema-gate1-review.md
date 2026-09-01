# INSPECTION-EVIDENCE-SCHEMA-001 — independent Gate 1 technical review

- Date: `2026-09-01`
- Reviewer: separately tasked Codex agent `/root/inspection_v8_gate1_review`
- Independence: reviewer did not author the executable specification or OpenSpec artifacts
- Fixed point: worktree reviewed at `HEAD 6c6b605d44f03dee5bbd743c20f1fd3246cb850b`
- Verdict: `READY_FOR_OWNER_APPROVAL`

## Reviewed-file SHA-256 manifest

The planning artifacts are uncommitted at the fixed point, so the hashes below
identify the exact worktree bytes reviewed against that `HEAD`.

```text
201885dc684287c1526c4657e5a9dd71f23d7dca74423fb5f329169e03fea358  PRODUCT.md
98c10bf12d606580e420587dd389dda0cbbbbf65b8cf196d20aeb60dd2b11e98  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
dcd6ce032d0815c0672a38afade5ec504979f4db445fc33cfc0b029f67720f53  docs/fmonitor-2-pilot-spec.md
59d2643200f6649c20f5ce6ea104d88591bf057a0afa64ab056ddd6562162886  docs/fmonitor-2-pilot-data-model.md
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php
b92369d7a36efe107ba3c312d79c7eb71af81c16f2d594c5384cce5cc931728c  app/PilotHttp/ChecklistSync.php
050d4dc24d7fc95f354275df4ba9b946d5ea6dfdebe2e0d198709df16b806143  app/PilotHttp/PilotE2ECoordinator.php
fe7e2fab97faefbea6ea890c5b44a2de136590ea54b12c03a5824a5907922e44  app/InstallationProcess/ChecklistTemplateSchemaMigration.php
f3fd143860c903139e484e8d04ea151185260bbbe60ff0e629cb88d5cd397f0d  tests/InstallationProcess/production_migration_runner_001_test.php
fac6039a99379488fc8f85dca7e16a5262561c5d7095614df2b2b78667d14324  specs/INSPECTION-EVIDENCE-SCHEMA-001.md
6ad3cad4d132e08ad79422918a5c2f9c82ff7811b1f689058ad5fa74f97021c9  openspec/changes/canonicalize-inspection-evidence-schema/proposal.md
57336dc20461afca35cdf90514a419885efd28dd6ede5dd1cb6f528955d3345f  openspec/changes/canonicalize-inspection-evidence-schema/design.md
494b082e64ca34b51a91022f0ed52d152c3ce2eb6b12636bbfa0ae2826eeda16  openspec/changes/canonicalize-inspection-evidence-schema/tasks.md
69676a9d16dd2d380de13b76f2f50d35d4c085998b01a109a4a9a6cc49b0cdb7  openspec/changes/canonicalize-inspection-evidence-schema/specs/deployment/canonical-inspection-evidence-schema/spec.md
4ec0cb4c5f4aabd151558f2cad455a78c242acdeb79efe6a8db069077fdf2214  docs/operations/checklist-template-schema-gate1-review.md
b029fe7ac990e62fe532360396786c4fb4e09e078b1c0adc9765931f61282bad  docs/operations/checklist-template-schema-green-verification.md
c7b6d267e2d067a8a2aef51e651e0c62d5230b43e00edabf87a17a3a4e133e00  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md
4048003e371ae730f4bebbf222d468577f4c2cc35f7f6d2e3572e30bce5ac103  reviews/code/CHECKLIST-TEMPLATE-SCHEMA-001.md
```

## Findings

No blocking findings.

1. **Literal ordering is exact and current.** The landed runner at the fixed
   point registers exactly v1 process, v2 workforce catalogue, v3 process-user
   capabilities, v4 command capabilities, v5 Bitrix workforce history, v6
   identity/access and v7 checklist-template schema. The proposed evidence
   migration is consistently literal v8, immediately after v7, and the spec
   sends any catalogue renumbering/reordering back through Gate 1.
2. **Fingerprints are evidence-backed and sufficiently exact.** The four final
   shapes reproduce the request-reachable `ChecklistSync::ensureSchema()`
   family, including MariaDB-generated index names, unsigned widths, column
   ordering and the two historical additions. The target deliberately removes
   the legacy backfill default from final `assignment_source`: existing rows
   receive exact `pilot_backfill_current_order`, while new evidence must state
   its source explicitly. This is specified and tested rather than silently
   inferred.
3. **Compatible upgrades and zero-mutation conflict behavior are closed.** The
   allowed state product is exactly `2 * 2 * 3 * 3 = 36`: revisions/photos are
   absent or final, and operations/installers are absent, exact predecessor or
   final. Partial template-column subsets, malformed assignment-source forms,
   extra keys/constraints and mixed conflicts are rejected after whole-family
   metadata preflight and before owned DDL/DML. Conflict ordering, prefix
   isolation, row preservation and both AUTO_INCREMENT states are observable.
4. **Collation/prefix policy matches landed precedent.** The contract inherits
   v6/v7 exact database-default `utf8mb4` validation, ASCII identifier allowlist,
   documented MariaDB UCA alias normalization and safe trial application before
   target DDL. It keeps the catalogue-wide 25-byte prefix ceiling even though
   this family alone could fit 30 bytes, and requires explicit validated
   collation on every table and character-column addition.
5. **Runtime ownership is fail-closed.** The reviewed source confirms both
   `ChecklistSync::ensureSchema()` and its coordinator calls are currently
   request reachable. The acceptance contract removes that DDL/repair surface,
   requires exact schema preconditions before evidence DML or photo-file
   persistence, and preserves the existing infrastructure mapping:
   `PilotHttpInfrastructureUnavailable`, HTTP 503 and JSON `retryable`.
6. **Authorization, audit and preservation remain scoped.** Deployment access,
   rather than a new user capability, authorizes migration. No domain event or
   historical actor/time/payload/photo/installer fact may be rewritten. Existing
   checklist authorization, append-only outcomes, template identity,
   attribution, duplicate/conflict, photo revoke and downstream completion/OTIZ
   observations remain regression contracts; unresolved correction/revocation
   semantics are explicitly not promoted into requirements.
7. **Gate 2 is sensitive to the intended behavior.** The mandatory matrix tests
   all 36 compatible combinations, populated and empty upgrades, every partial
   additive-column subset, metadata mutations across columns/indexes/constraints,
   multiple simultaneous conflicts, prefix boundaries/isolation, invalid
   database defaults, sequence corruption, DDL-denied successful runtime and
   per-table missing/incompatible fail-closed runtime. It also requires setup
   proof and an assertion failure caused by missing v8 behavior, preventing
   fixture, connection or predecessor failures from being recorded as RED.

Gate 2 test review should specifically verify that expected fingerprints are
literal test-owned values rather than values imported from the implementation,
and that photo failure fixtures prove the filesystem is unchanged as well as
the database. These are already normative requirements, not requested spec
changes.

## Validation evidence

Fresh command at the fixed point:

```text
openspec validate canonicalize-inspection-evidence-schema --strict
Change 'canonicalize-inspection-evidence-schema' is valid
```

The executable spec, proposal, design, delta spec and tasks are coherent. Task
`1.1` remains unchecked, owner decision remains `PENDING`, and no Gate 2 test or
production implementation was authored or changed by this review.

## Gate boundary

`READY_FOR_OWNER_APPROVAL` is an independent technical Gate 1 verdict only. It
does not constitute owner approval and does not authorize Gate 2 until the owner
explicitly approves the exact reviewed artifact identified by this manifest.
