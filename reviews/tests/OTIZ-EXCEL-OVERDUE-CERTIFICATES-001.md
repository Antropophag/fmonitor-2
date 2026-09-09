# OTIZ-EXCEL-OVERDUE-CERTIFICATES-001 — Gate 1 review

Reviewer: independent specification reviewer (did not author the planning
artifacts, tests, or implementation)

Verdict: **CHANGES_REQUESTED / CONDITIONAL APPROVAL OF SETTLED RULES**

No source or executable test exists or is approved by this review.

## Reviewed artifacts

- `specs/OTIZ-EXCEL-OVERDUE-CERTIFICATES-001.md` —
  `03481a5e43cbea2f6b22fb198c0e247ec2605783cb0c6e57b5e03cf1ff3c6f51`
- proposal — `d10435185cbec16719a48a7c1583e5bf38cee7d6449fe9d4f1e2c67ade0201dc`
- design — `1f5aea03a553ae9cccbbcd1999f9dd5b440c9679a2d882184ba1f79702f9d7d1`
- tasks — `8df9f3e33728391664699ed9cc0fb0d43dc2929d03e090f48607a25181d5835d`
- `otiz/excel-overdue-calculation` delta —
  `6d3b594fed1e927bb6525af82cc2f096b933cd70fa913c260bd549e10ed9a0ed`
- `otiz/deadline-transfer-certificates` delta —
  `2450f094debb4c94952869040bb2aa5755c72e9bd31fbe8e54a9ad5d751913f3`

`openspec validate reproduce-excel-overdue-with-deadline-certificates --strict`
passes. Structural validity does not resolve the pending product decisions below.

## Blocking findings

### 1. The original deadline source is an unresolved owner decision

The candidate commits to reading raw unadjusted `plan_finish_date`, capturing its
date/locator/hash during native selection/application, and blocking existing NULL
applications until a new confirmed basis is created. That is a coherent technical
policy, but the owner is still choosing between this policy and a separately
confirmed FKR deadline. It must remain conditional rather than normative.

After the owner answers, the normative spec, both relevant delta requirements,
proposal, design, tasks, and issue wording must describe the selected owner and
capture time consistently. Tests must not freeze the current raw-card assumption
before that decision.

### 2. The `$EU$2` policy is still pending

The current contract always applies the calculated penalty and excludes a waiver.
The owner is still selecting between no waiver and an explicit reasoned waiver.
The implementation correctly must not gain a hidden global boolean, environment
flag, or unaudited switch in either case. Final Gate 1 nevertheless requires the
owner's choice. If a waiver is selected, its actor, scope, validity, reason,
append-only history, calculation provenance, and interaction with snapshot replay
must be specified before tests. If none is selected, the current no-waiver
requirement can become final.

### 3. Certificate access policy is not exact

The artifacts refer to an “exact certificate upload/correct capability” and a
“read capability” without naming their keys or defining the reader actor set.
“ОТиЗ MAY read” is not an authorization contract. Gate 1 must name the exact
capability or capabilities checked by submit, history read, and PDF download, and
state whether employee FKR, head FKR, and OTIZ receive each one. Authorization
must precede PDF-byte processing and history/private-document disclosure as the
candidate already requires.

### 4. The database frontier is not versioned exactly

Proposal, design, tasks, and release gates use `vNext`. The change adds certificate
roots/revisions/attempts, entitlement events, deadline evidence, capabilities,
private-file inventory, and recovery manifests, so executable forward/restore and
old-tool refusal checks need one literal schema version. Name the new frontier
(expected to be v24 if no intervening migration changes it) and use it consistently
in catalogue, readiness, backup/restore manifests, forward proof, and refusal
outcomes.

## Conditionally approved settled contract

Subject to the four items above, the remaining behavior is specific and testable:

- Certificate initial/correction is one typed application seam with UUID request
  identity, CAS versioning, immutable revisions, mandatory 1-byte-to-20-MiB passive
  PDF, exact metadata, staged private storage, definite/ambiguous outcomes, replay,
  collision, and append-only history/download behavior.
- Current accepted certificate selection deliberately has no report-date gate,
  and effective corrected PTO is used even after report date. Progress, payments,
  and other facts keep their existing report-date cutoffs. This later owner
  clarification supersedes the older issue sentence that says “latest effective
  on report date”; the issue should be updated to avoid two apparent contracts.
- The one-percent-per-calendar-day Kss with floor zero and the after-paid money
  sequence are fully specified in integer cents. The example with 49,725,000
  cents, 9,360,000 cents paid, and nine late days discriminates the approved
  order from the predecessor formula.
- Largest-remainder allocation conserves every cent and has a canonical installer
  tab-identity tie-break independent of input order.
- Snapshot version/provenance and replay preserve prior accepted/paid history.
  A stale old-version draft cannot be accepted or silently rewritten.
- A02 is addressed at the actual object boundary: each acceptance creates a fresh
  entitlement, a newer accepted-unpaid entitlement append-only supersedes the
  older one even for two v2 snapshots, and payment locks installation cases in
  object-id order, validates current entitlement, and totals signed object-wide
  closures across snapshots. Replay/collision/unknown outcomes are explicit.
- HTTP and screens remain adapters to the application seams; primary PDF bytes and
  paths remain private; recovery is forward-only and old tooling fails before
  mutation.

## Gate boundary

At this first checkpoint, this was not final Gate 1 approval. Its four findings
required planning-only correction and the two then-pending owner answers. The
addendum below records the later resolution and newly exposed product question.
Gate 2/3 tests, schema/source work, PR/CI, deployment, and Done must not be
inferred from either conditional approval.

## Gate 1 addendum — revised planning checkpoint

Re-reviewed artifacts:

- normative spec — `29a0f372594dda2fa31491b4e4bbd75fe0328afe965b0213fb423d8e43e17bd1`
- proposal — `6aed740acc9182ad2074a0e68fdc5e24d931ef0eabd93b70bca65e6c96b74188`
- design — `6b27ddf2fa186ccfb9efceb1fe87ebebb9ab1b662182f45e546948ece11c172c`
- tasks — `8e7ee4d3189f0d210411ee44ac75d43b3dd453f6aa1e29c13b915d37baa25cdb`
- certificate delta — `ac423feb22d6df1c87405e70f62e0a6a886b0ef224cc24661a334ac97fac5e8a`
- calculation delta — `8643c0b8d20e99862df076b91ad3b234e679580ea2d11822f83ed2ba4e0a652d`

Strict OpenSpec validation remains PASS.

The earlier technical findings are resolved. The contract now names
`deadline_certificate.write` for active `fkr_operator|manager` and
`deadline_certificate.read` for those roles plus `otiz_specialist`; OTIZ access
alone does not grant writes, and engineer/admin roles receive no implicit access.
Authorization remains before PDF processing or history disclosure. The additive
frontier is exactly v24, with explicit clean install, v23-to-v24 forward,
v24 round-trip, private PDF inventory, and v23-tool fail-before-mutation proofs.

A02 timing is now explicit and coherent across the normative spec, design, tasks,
and calculation delta. Successful atomic publication locks installation cases,
creates fresh candidate entitlement identities, and immediately append-only
supersedes older accepted-unpaid entitlements, including another v2 snapshot or
period. The new candidate still requires separate acceptance; during that gap
neither old nor new entitlement is payable. Payment rechecks the latest active
identity under the same object lock order and uses object-wide net closures.

Three product decisions remain open and are the only Gate 1 blockers:

1. **Original Excel T owner/timing:** choose current raw object-card capture per
   calculation, immutable selection/application capture, or a separate FKR
   confirmation. The revised artifacts correctly refrain from approving any one
   source seam/schema and block missing evidence meanwhile.
2. **Global `$EU$2` analogue:** choose no waiver or a separately specified audited
   waiver with actor, reason, scope, validity, and provenance. No hidden toggle is
   permitted while pending.
3. **Recurring snapshot semantics:** with fund 100, progress 100%, Kss 9000 bp,
   and paid-before 90, literal cumulative Excel yields a new pool of 9 despite no
   new progress, while the accepted interval model may require
   `no_new_amount`. The calculator/publication contract must not select either
   result until the owner resolves this conflict.

Revised verdict: **CHANGES_REQUESTED / CONDITIONAL APPROVAL OF ALL SETTLED
TECHNICAL RULES**. Final Gate 1 approval requires the three owner answers and one
coherent planning update before Gate 2/3 expectations are frozen. No #66 source
or executable test is approved or implied.
