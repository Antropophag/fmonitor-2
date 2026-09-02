# Installation completion schema — Gate 1 rereview v2

Date: 2026-09-02  
Reviewer: fresh independent Gate 1 rereviewer v2  
Verdict: **READY_FOR_OWNER_APPROVAL**

## Scope

Review-only rereview of `INSTALLATION-COMPLETION-SCHEMA-001` against both prior
`CHANGES_REQUIRED` records, the approved owner decision, updated evidence, all
OpenSpec artifacts, the canonical runner and the request-reachable completion
runtime sources. This review did not edit the executable spec, OpenSpec
artifacts, tasks, tests or production code.

`openspec validate canonicalize-installation-completion-schema --strict`
passes. This verdict permits explicit owner approval of the exact reviewed spec
hash below; it does not itself authorize Gate 2.

## Closure of prior required findings

### Partial-state inventory is now exact

The evidence and executable contract consistently define the four possible
two-member states:

- both absent: create root and then corrections;
- exact root only: the sole compatible interrupted partial, preserve it and
  create empty corrections;
- corrections only: conflict, because the required root dependency and a
  deterministic root-first interrupted history cannot be established;
- both exact: deterministic no-op repeat.

Any occupied incompatible member is rejected by family-wide read-only
preflight before mutation, so the runner cannot create a missing sibling beside
a conflict. The previous erroneous instruction to test “both compatible
partial forms” has been removed.

### Public failure outcomes are now exact

The executable spec fixes invalid/non-ASCII/26-byte prefix rejection before DB
access at exit `64`, exact `CONFIGURATION_INVALID` JSON and empty stderr. It
fixes unsafe, unknown, non-applicable or non-utf8mb4 database defaults at exit
`70`, exact `MIGRATION_FAILED` JSON, empty stderr and zero mutation.

Missing/drifted runtime family outcomes are explicit at every consuming seam:
ObjectQueue has its redacted 12-hex reference `503`; authenticated card and both
checklist HTML routes have text `503`, `Retry-After: 60`, no-store and defined
GET/HEAD body behavior; authorized completion POST has that same fail-closed
outcome before DML and without redirect; bootstrap exits `70` with exact
`MIGRATION_FAILED` output before publication or fixture/product DML. JSON
checklist operation/sync endpoints are explicitly excluded because they do not
consume this schema. These target outcomes are compatible with the current
HTTP boundary response classes while requiring the planned removal of the two
current enhancement fallbacks that swallow completion-read failures.

## Contract review

- Corrections replace only `fact_date`. Root/correction `details` and arbitrary
  payload are absent from correction storage and remain immutable; this does
  not broaden the approved owner decision.
- Root version 0 plus `version_no >= 1`, the NULL-shape/adjacent-version CHECK,
  `UNIQUE(root_fact_id, version_no)`, unique predecessor and the composite
  self-FK to `(id, root_fact_id, version_no)` jointly reject duplicate ordinal,
  branch, cross-root predecessor, gap/skipped predecessor, missing predecessor
  and malformed first/subsequent versions.
- The root FK, composite self-FK, three normalized CHECK expressions and exact
  named index inventory are stated precisely enough for a metadata fingerprint.
  Server-generated CHECK presentation is correctly normalized semantically
  without accepting duplicate or extra expressions.
- Same-root adjacent gap-free history is a storage invariant only. The spec
  explicitly withholds correction admission, stale-target behavior, locking and
  authorization from this slice and requires a separate approved command seam;
  no unapproved command semantics are inferred.
- Root rows, ids, payload, actor, timestamps and AUTO_INCREMENT state are
  preserved without UPDATE/DELETE, synthetic facts or correction backfill.
  Declaration remains mandatory for terminal completion and checklist/document
  meaning remains target 85/15 without migration-time projection facts.
- Literal v10 follows the landed v1–v9 catalogue. The composed prefix remains
  exact ASCII length 25 accepted / 26 rejected despite the 37-byte family-local
  correction basename. Explicit validated database-default utf8mb4 collation is
  applied to both InnoDB members.
- Gates remain ordered correctly: reviewed exact spec and owner hash approval,
  fresh demonstrated RED, independent test approval, minimal GREEN without
  approved-test edits, verification and architecture check, then independent
  code review. Runtime DDL removal and ObjectQueue DML-only verification are in
  Done; no new domain logic is assigned to `rapid-pilot`.

## Owner approval boundary

The owner may approve executable spec hash
`c63ed10eb22d69ed7e86274a3008e6e991204166e44cb2ad9e8b00d1be686181`.
Gate 2 remains prohibited until that exact reviewed hash is explicitly approved
and recorded. Any normative spec change after this review requires a new hash
and fresh Gate 1 review.

## Reviewed hashes

```text
90bd72fd62ee28b5b26c181af63e617b64fac4e3571b5dfb1bacbd33a25c487b  docs/operations/installation-completion-owner-decision.md
a3b33d5f694f21e5814d854e5a8c5c25662646155d1c5ed8987e6f763a0335df  docs/operations/installation-completion-schema-evidence.md
c63ed10eb22d69ed7e86274a3008e6e991204166e44cb2ad9e8b00d1be686181  specs/INSTALLATION-COMPLETION-SCHEMA-001.md
9d03cebaf2c203b3f2fb2ddc14604214a3849fcca5b478c0e042a9196487e05a  openspec/changes/canonicalize-installation-completion-schema/proposal.md
65b506dfb02014d529db86c54b78b4245a28ccbfbc2d5d7e1e49389154d04bef  openspec/changes/canonicalize-installation-completion-schema/design.md
5aa00ae215c6766f1cfb9e1b885d1e0d2ca324adc5a14bef28e37746528ea38f  openspec/changes/canonicalize-installation-completion-schema/tasks.md
2c34703b9601f5225123b930fc22e05e30a57bf2806689ebc83a5a457f5acae1  openspec/changes/canonicalize-installation-completion-schema/specs/deployment/canonical-installation-completion-schema/spec.md
a1c1ba68dd36482b316764119d41c0c56843c63815154984bd37233c4596a943  PRODUCT.md
98c10bf12d606580e420587dd389dda0cbbbbf65b8cf196d20aeb60dd2b11e98  CONTEXT.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
```
