# Installation completion schema — Gate 1 rereview

Date: 2026-09-02  
Reviewer: fresh independent Gate 1 rereviewer  
Verdict: **CHANGES_REQUIRED**

## Scope

Review-only rereview of `INSTALLATION-COMPLETION-SCHEMA-001`, the owner
decision, evidence and every artifact of
`canonicalize-installation-completion-schema`. No executable spec, OpenSpec
artifact, task, test or production file was edited by this review.

`openspec validate canonicalize-installation-completion-schema --strict`
passes. Structural validity does not resolve the two executable-contract
findings below, so this verdict does not authorize Gate 2.

## Resolution of previous findings

The two findings from
`docs/operations/installation-completion-schema-gate1-review.md` are resolved:

- correction is now limited exactly to `fact_date` plus mandatory bounded
  reason; root and correction `details` are not replaceable and remain
  immutable;
- same-root adjacency, gap protection and branch protection are explicitly
  storage invariants. The artifacts no longer claim that they approve who may
  correct or which version a future command may target. Admission, stale-state
  handling, locking and authorization remain a separate behavior slice.

The proposed composite self-FK, root/version unique key, predecessor unique
key and NULL/version CHECK are internally coherent for one same-root adjacent
chain. They reject cross-root references, missing/skipped predecessors and a
second direct successor without turning those constraints into command policy.

## Required findings

### 1. Make the allowed partial-state inventory internally exact

The executable spec section 4 permits only one one-member recoverable state:
exact root present and corrections absent. It correctly rejects the reverse
state because corrections depend on the root. The delta spec likewise says
only a deterministic subset may be completed, and task 2.1 explicitly calls
for a reverse-partial conflict.

However, `docs/operations/installation-completion-schema-evidence.md` requires
Gate 2 to prove “both compatible partial forms.” There is no second compatible
one-member form in this two-table root-first family. This instruction can make
a RED author either accept the forbidden reverse state or invent an unstated
partial classification.

Reconcile the evidence wording with the exact four-state matrix: both absent,
root-only exact, corrections-only conflict, and both exact; also retain
family-wide zero-mutation rejection when either occupied member conflicts.

### 2. Specify exact observable failure outcomes before RED authorship

The contract gives an exact exit/reason/schema version only for schema
conflicts. Invalid prefix, unsafe/non-applicable database default and runtime
drift are rejected, but their public outcomes are incomplete:

- sections 2 and 5 do not state the migration runner exit code and JSON reason
  for prefix/configuration and database-default failures;
- section 5F specifies ObjectQueue and bootstrap, but leaves card, checklist
  and completion POST at “existing redacted infrastructure outcome” without
  naming the exact status/body/response class or citing a stable normative
  contract that does;
- the delta spec uses the same unspecified “existing infrastructure outcome.”

These are observable results at the declared public seams and determine RED
expectations. Gate 1 requires exact rejected reasons; they cannot be selected
by the test author or inferred later from the planned implementation. State
the exact runner outcomes and the exact fail-closed outcome for every listed
runtime seam, or cite a stable approved executable contract that defines each
one unambiguously.

## Confirmed coverage

- Literal v10 follows the landed v1–v9 catalogue, and the composed prefix
  boundary remains exactly 25 accepted ASCII bytes / 26 rejected before DB
  access.
- Exact root and correction DDL, FK/CHECK/index intent, explicit validated
  utf8mb4 database-default collation, preservation, deterministic conflict
  inventory and root-first recovery are otherwise coherent.
- The migration preserves populated roots, ids, payload, actor, timestamps and
  AUTO_INCREMENT state without synthetic corrections or UPDATE/DELETE.
- Declaration remains mandatory for terminal completion and checklist/documents
  retain target 85/15 meaning; the migration does not calculate or persist that
  projection.
- Runtime DDL removal, read-only readiness, architecture ratchet, focused/full
  verification and Gates 2–5 are present. No new domain logic is assigned to
  `rapid-pilot`.

## Rereview condition

After the two contract inconsistencies are corrected, request another fresh
independent Gate 1 rereview. Only `READY_FOR_OWNER_APPROVAL` followed by explicit
owner approval of the reviewed spec hash may authorize Gate 2.

## Reviewed hashes

```text
90bd72fd62ee28b5b26c181af63e617b64fac4e3571b5dfb1bacbd33a25c487b  docs/operations/installation-completion-owner-decision.md
3db8ffe2533da607ef43e981cee40ede2a25281e0b75ada0098bdfc27c320b6b  docs/operations/installation-completion-schema-evidence.md
5b69e241eb96bfab840b740eb4cc9f55f9d3ab6f28ab7552d82e0e882c1fa088  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
9d03cebaf2c203b3f2fb2ddc14604214a3849fcca5b478c0e042a9196487e05a  openspec/changes/canonicalize-installation-completion-schema/proposal.md
65b506dfb02014d529db86c54b78b4245a28ccbfbc2d5d7e1e49389154d04bef  openspec/changes/canonicalize-installation-completion-schema/design.md
5aa00ae215c6766f1cfb9e1b885d1e0d2ca324adc5a14bef28e37746528ea38f  openspec/changes/canonicalize-installation-completion-schema/tasks.md
2c34703b9601f5225123b930fc22e05e30a57bf2806689ebc83a5a457f5acae1  openspec/changes/canonicalize-installation-completion-schema/specs/deployment/canonical-installation-completion-schema/spec.md
a1c1ba68dd36482b316764119d41c0c56843c63815154984bd37233c4596a943  PRODUCT.md
98c10bf12d606580e420587dd389dda0cbbbbf65b8cf196d20aeb60dd2b11e98  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```
