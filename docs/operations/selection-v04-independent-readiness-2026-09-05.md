# ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.4 — independent Gate 1 readiness

Date: 2026-09-05  
Reviewer task: `/root/selection_v04_readiness`  
Repository HEAD: `88a6715f0046b0c4b99d6206ffa3de59fe6c5728`  
Verdict: **CHANGES_REQUESTED — NOT READY FOR RED**

This is a bounded independent technical-readiness review. It does not author or
approve code, tests, migration contracts, external-system work, or a safe-log
mechanism. The owner-approved `replace_pending` policy is accepted as settled
and was not re-questioned.

## Exact reviewed evidence

- Normative candidate `specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md`:
  `91e41ced07c881dfd67596ccafa2ac0246af81200945df5100e7190131d9a00f`.
- Owner decision
  `docs/operations/owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md`:
  `915d4f4ac14906ce3cee9cb2b6fa8dc02fb990a5501063555ec9ec5e3b757e0c`.
- Restart handoff
  `docs/operations/autonomous-restart-handoff-2026-09-05-1842Z.md`:
  `a5a4393c39740310dfe99850503b67e78a09753dbd626170866fd2f631e33478`.
- Storage/identity candidate: `dec84ef86998764fc631a20198d94f41ffb86b2fa294f9f51c69f17b15fee8d3`.
- Result/replay candidate: `2939781b407aa34ee4fa967555ed0e8c7381cc8f4b9362dd7b2689860e8985d8`.
- Audit/exhaustion candidate: `8409e215b35eab42cf7711118ca44d51ac136548aa75abc995ad813298f8eea9`.
- Transaction-ports candidate: `7a9b7e270b24e9a5bdbfd3f1fbde8c073fa4fc9ad77894f5dfa3bb736ef4370e`.
- Prefix correction: `8d1856486d76a6ecb4211ab158af1185eb7edbfef58cb38da0289996f39157a3`.
- OpenSpec design/proposal/delta/tasks respectively:
  `0b0be1d3e8d8692393ee21fd6d8ebdc498d92395ae93d8fbabfaf6608aac5bf8`,
  `a684cf25ff3e583f413704925ce82b9ae2f686679ae403e1fe73932c87249adb`,
  `dd7314f31655ee2f7193b93562690d0c43f2f3b3a75f93ed25c49d64bc10173a`,
  `0b359b92d147f2bd6480d814714a1f833e4712cab7c9236255301cd29590e725`.

Source checks used for compatibility evidence:

- `app/InstallationProcess/MariaDbInstallationProcessEnvironment.php`:
  `5cd931da1ff1bcd356ba2177a3edd0bb56b6aeee1d3f4accb477bd79cbc4a26a`.
- `app/PilotHttp/PilotE2ECoordinator.php`:
  `f6491662738821743976e06086bcb988269c78a4b3d87b9899df4f65575b30b0`.
- `app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php`:
  `4c893c34377546ded04fc094bf5cbfd8dd5647655416ec25a8e6e28c65ef114d`.
- `app/InstallationProcess/ProductionProcessSchemaMigration.php`:
  `5a91a1ce9f4facac9732dd085e8238f419d019056c5ac1dd170c06f36d031660`.

## Blocking findings

### P0 — the candidate expressly depends on an absent executable compatibility contract

Sections 7, 11, 12 and 14 defer the literal migration number, generated
constraint/index names and schema fingerprints to a subsequent migration
contract, and make Gate 1 readiness conditional on an independently approved
compatibility contract. No reviewed artifact supplies that exact contract.
Consequently migration preflight, backfill, durable receipt schema/verification,
frontier installation, rollback readiness, and post-cutover dynamic ownership
checks are prose requirements rather than executable acceptance statements at a
confirmed public seam. The candidate's own section 14 therefore prevents an
`APPROVED` verdict.

Required disposition: add the exact migration/backfill/receipt contract,
including literal table/column/index/constraint names, schema fingerprints,
preflight and failure results, transaction/crash boundaries, receipt identity
and verification seam, frontier update, rerun semantics, and rollback/readiness
rules; then cite its exact approved hash from the normative candidate.

### P0 — all-writer cutover is neither enumerated nor version-compatible

The current production prepare writer in
`MariaDbInstallationProcessEnvironment` computes `version_no` from the physical
`fm2_assignment_orders` table and lets that table allocate `id`. The pilot HTTP
coordinator also mutates that physical order directly during signed-original
upload. The candidate requires every order writer to stop, migrate to the shared
registry allocator, and fail closed in a mixed release, but provides no exact
writer manifest, old/new binary compatibility matrix, deploy ordering, startup
admission result, or proof that an old process cannot resume after backfill.
Fixtures and demo writers further show that identifying writers by a single
class name is insufficient for repository-wide verification.

Required disposition: publish an exhaustive production-writer/read-modifier
manifest and exact N-1/N compatibility and deployment protocol. Define the
observable admission failure for each incompatible binary/schema state and the
mechanical test proving that no implicit `AUTO_INCREMENT` or physical-table
`MAX(version_no)` writer remains reachable.

### P0 — original-reader handoff is described but not an executable amendment

Section 12 names the existing reader and desired registry dispatch, but it does
not amend the original-upload executable contract with exact new DTO/source
types, constructors, lookup status mapping, schema readiness dependency, or
worked outcomes at that command's public seam. Current runtime bytes still read
the physical legacy composition. The selection can therefore produce an
acknowledged ID that the original command cannot consume under the presently
approved contract.

Required disposition: create and independently approve an additive amendment to
the original reader/upload contract that pins registry dispatch, selection and
legacy payload construction, `not_found` versus `unavailable` mapping,
dual/orphan/mismatch behavior, and exact no-fallback tests.

### P0 — same-identity optional render has no public executable contract

The owner-visible promise is that a template may later be rendered from the
same immutable selection identity, while render failure preserves the selection.
Section 12 only says the renderer “later reads” the source, and section 14 makes
the public optional-render path a release dependency. There is no command DTO,
result/status/reason set, authorization rule, idempotency/failure contract,
artifact facts, or acceptance example for this path. A legacy prepare guard
cannot demonstrate this behavior because legacy prepare allocates and persists
a different physical order.

Required disposition: add a separately reviewable executable optional-render
contract consuming an existing selection identity and pin its same-identity,
no-new-composition and renderer-failure observations. Cite its approved exact
hash as a release dependency.

### P1 — the typed persistence model requires IDs before AUTO_INCREMENT storage can create them

`SelectionSelectedEvent` and `SelectionSafeAttemptAudit` constructors require
positive `eventId` and `auditId`, and `SelectionAcceptedPersistence` embeds both
objects before `stageAccepted`. The schema declares both IDs as
`AUTO_INCREMENT`. The transaction port exposes no ID allocation method for
events/audits and no post-insert return payload. Thus a conforming caller cannot
construct the required persistence payload without inventing database-generated
IDs or adding an unstated allocator. The same issue affects terminal-attempt
audits.

Required disposition: choose one coherent ownership model: omit generated IDs
from pre-insert DTOs and return them in a typed stage/commit receipt, or define
explicit bounded allocators and storage semantics. Update constructors, stage
results, schema and acceptance observations together.

### P1 — lookup/result closure is asserted but not fully constructible

The candidate says impossible result combinations are not constructible, yet
only specifies a result interface and serializer, with no closed result value
constructors. Lookup classes are described as accepting `(status, ?payload)`
while also requiring exactly `found(payload)`, `notFound()` and `unavailable()`;
the public constructor shape still admits invalid status/payload combinations
unless it throws, and the exact failure behavior is unstated. Snapshot DTOs also
accept malformed fields that are later classified as dependency unavailability,
without pinning whether construction or application validation owns that
classification.

Required disposition: provide exact closed constructors/factories for every
result and lookup, state constructor validation/exception behavior, and make the
dependency adapter's malformed-data mapping observable. Keep transport-shape
rejection distinct from malformed dependency data.

### P1 — state/status vocabulary is not pinned to legacy physical facts

`LegacyIdentitySummary.physicalStatus` is an unconstrained string, while
precedence depends on “prepared/unregistered”, “registered”, and “accepted
original”. Current storage uses `status` plus separate original-root facts; the
candidate does not give an exact normalization table for every stored status or
contradictory combination. It also does not specify whether an unsupported
legacy status is malformed/unavailable or a terminal business predecessor.
This leaves `pending_selection_exists`, permission to create version N+1, and
`dependency_unavailable` dependent on adapter judgment.

Required disposition: pin a closed legacy-status enum and a complete truth table
over physical status, registry ownership and original-root presence, including
unsupported/null/duplicate combinations and exact result mapping.

### P1 — prefix-25 table length is corrected, but identifier safety is deferred

The replacement base table name is 38 bytes and fits at 63 bytes with a 25-byte
prefix. This corrects the prior table-name defect. It does not establish full
prefix safety because every generated index/constraint name is explicitly left
for the absent migration contract. The existing migration style uses unnamed
keys/foreign keys in places, whose server-generated names also require a pinned
cross-prefix strategy.

Required disposition: the migration contract must enumerate and byte-count
every physical table, index, constraint, receipt and temporary/staging identifier
at prefix lengths 0 and 25, and verify exact schema fingerprints at both bounds.

## Non-blocking coherence observations

- The approved `replace_pending` policy is consistently reflected in mode,
  revision, previous/replaces links, replay precedence and acceptance rows. The
  stale `OWNER_APPROVAL_REQUIRED` prose in sections 1, 14 and 15 is superseded by
  the exact owner record, but should be replaced by an exact approval citation in
  the next candidate so the normative document is self-consistent.
- Canonical intent and composition examples hash correctly, and the dateless
  selection model avoids fabricating document or effective dates.
- Cross-source pending handling and preservation of the applicable legacy crew
  are stated with useful observable examples. They remain blocked by the missing
  reader/writer/migration contracts above.
- The corrected 38-byte member table base name resolves the previously recorded
  66-byte table-name overflow for a 25-byte prefix; it does not resolve generated
  identifier names.

## Gate disposition

Do not write Gate 2 RED for this candidate. A revised exact candidate must close
the P0/P1 findings, cite the owner approval without pending-policy prose, and
receive a fresh independent Gate 1 review at exact hashes. Planning and OpenSpec
validation cannot substitute for that review.
