# Installation completion schema — OpenSpec planning review

Date: 2026-09-02  
Reviewer: fresh independent Gate 1 planning reviewer  
Verdict: **CHANGES_REQUIRED**

## Scope and gate meaning

This review covers only the OpenSpec planning artifacts for
`canonicalize-installation-completion-schema` against product truth, the owner
decision, current pilot oracle/evidence, architecture constraints and the
delivery process. It is not approval of an executable schema specification and
does not authorize Gate 2. OpenSpec planning review cannot replace the required
independent review and explicit owner approval of the future exact executable
spec and its hash.

`openspec validate canonicalize-installation-completion-schema --strict` passes.

## Findings

### Required 1 — do not silently broaden correction semantics from dates to details

The approved owner decision is exact: an incorrectly entered **date** of a PTO
act or declaration is corrected by a new append-only fact with a mandatory
reason, while the original remains forever. The delta spec currently requires
`replacement date/details`, and the design calls this a generic `replacement
payload`. That makes correction of declaration details (and potentially other
future payload fields) part of the planned target without owner approval.

Revise the planning artifacts to keep the approved correction surface limited
to the date, or explicitly defer the corrected-field set to the executable
behavior contract without asserting details correction. Merely having immutable
legacy `details` available for preservation does not approve replacing it.

### Required 2 — expose correction-chain choices as unapproved contract decisions

The design chooses a single linear chain in which every correction must target
the immediately previous effective version, and the schema rejects a second
direct successor. Linear branch protection is a sound concurrency objective,
but “only the current effective version may be corrected” is additional domain
semantics not stated by the owner. The future executable spec must either obtain
owner approval for that rule or distinguish the storage/concurrency invariant
from the behavior policy. Planning must not present the future application seam
obligation as already approved product meaning.

The executable schema contract must also make the database invariant complete:
a previous-correction reference must belong to the same root and represent the
adjacent predecessor. A unique ordinal alone prevents duplicate ordinals but
does not prove a valid, same-root, gapless chain. Tasks 1.1–1.2 should name this
cross-root/gap rejection evidence explicitly.

## Confirmed strengths

- The proposal faithfully preserves roots forever, requires a bounded non-empty
  reason, retains declaration as mandatory for terminal completion, and records
  the target 85/15 model as durable product meaning rather than pilot-only
  behavior.
- Actor, source oracle, deployment actor, public migration/readiness seam,
  release value and non-goals are stated. Authorization and state-changing
  completion commands are correctly excluded rather than inferred from the
  current authenticated-only adapter.
- The slice removes request-time completion DDL without adding domain logic to
  `rapid-pilot`; runtime consumers only receive read-only readiness and existing
  infrastructure failure behavior.
- Predecessor preservation, full-family preflight, partial recovery, collation,
  deterministic conflicts and prefix validation are included. Exact version,
  manifest and prefix ceiling are honestly deferred to Gate 1 evidence after
  the landed v9 catalogue; no invented permanent version appears in planning.
- Gate ordering is correct: exact executable spec and owner approval precede
  fresh RED authorship, independent test review precedes GREEN, and independent
  code review plus `make verify` precede Done.
- The dependency on process v1, inspection evidence and landed planning v9 is
  consistent with the runtime-DDL inventory and the observed ObjectQueue
  failure. Existing completion characterizations remain an oracle, not target
  authorization approval.

## Required rereview condition

After both findings are corrected in proposal/design/spec/tasks, request a fresh
independent planning rereview. A `READY_FOR_EXECUTABLE_SPEC` verdict would only
permit task 1.1 evidence gathering and task 1.2 executable-spec authorship; the
resulting exact spec still requires its own independent Gate 1 review and
explicit owner approval before any RED test edit.

## Reviewed hashes

```text
90bd72fd62ee28b5b26c181af63e617b64fac4e3571b5dfb1bacbd33a25c487b  docs/operations/installation-completion-owner-decision.md
b774fcd662bf5c453584bd55e946c1709b4357c9170d7cb4f07c207e5c3b629c  openspec/changes/canonicalize-installation-completion-schema/proposal.md
49332bd8b08265cce6deb35c24d238c59afafa666b003241cff1b8354dc9cbf9  openspec/changes/canonicalize-installation-completion-schema/design.md
6b8d152f75605b88c3a3081996d68e328d532f8bd6a2a11a39a7d26b467f8838  openspec/changes/canonicalize-installation-completion-schema/tasks.md
31fc8ebe94315e078708751652adf1c76b4d8c1241e2d0c946b0acc17f39f10a  openspec/changes/canonicalize-installation-completion-schema/specs/deployment/canonical-installation-completion-schema/spec.md
a1c1ba68dd36482b316764119d41c0c56843c63815154984bd37233c4596a943  PRODUCT.md
```
