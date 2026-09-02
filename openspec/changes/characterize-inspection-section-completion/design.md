## Context

See proposal, capability spec and
`docs/operations/inspection-section-completion-behavior-evidence.md`. Current
server appends `section_completed`, while browser auto-enqueues it and no focused
verifier covers readiness/history/replay/concurrency/invalidation.

## Goals / Non-Goals

**Goals:**

- Compact production-HTTP oracle for one complete section lifecycle.
- Independent raw history/projection and zero-extra-progress evidence.
- Narrow replay/repeat/concurrency, rejection and last-photo-revoke contrast.
- Concurrent-agent-safe fixture, deterministic output and bounded cleanup.

**Non-Goals:**

- Target application seam, authorization, UX or invalidation design.
- Exhaustive all-eight-section catalogue verification.
- Production schema ownership or browser/ChecklistSync behavior changes.

## Decisions

### 1. Section 8 minimizes prerequisite facts

Section 8 currently has one hard-coded item (`42`), so one real item operation,
one real tiny-image upload and one completion expose the full public sequence
without copying nine-item setup. This characterizes current map ownership but
does not approve it as target template logic.

### 2. Production HTTP owns behavioral evidence

The fixture obtains a real session/cookie/CSRF and submits same-origin requests
through production-composed loopback HTTP. Raw DB and projection audits prove
effects. Direct calls may support diagnostics but cannot satisfy assertions.

### 3. Serial track establishes ingredients before completion

One serial case records revision zero after GET, accepted item, accepted photo,
accepted section, exact/changed replay, distinct stale/repeat and ahead conflict.
Snapshots around completion prove it adds no progress/photo mutation.

### 4. Concurrency uses two servers and clients

Two separately started servers and POST clients own independent DB connections,
sessions and tokens, released by a parent barrier. Assertions accept either
revision assignment but require both current pilot completions and latest-
revision projection. Single-server pseudo-concurrency is rejected.

### 5. Revoke inconsistency and rejections are isolated

The accepted serial track revokes its only photo after completion and observes
the stale completion projection. Separate clean cases change one readiness,
admission or template precondition. Snapshot after GET distinguishes mutating
read setup from rejected POST behavior.

### 6. Verification owns no product module

Harness depends only on current PilotHttp seams/value types and private MariaDB/
filesystem fixtures. Future command ownership belongs to `InspectionRecording`
only after approval. No new SQL owner, rapid-pilot domain logic, dependency edge
or architecture baseline change is allowed.

## Risks / Trade-offs

- [Characterization blesses repeated/stale behavior] → every divergence is
  labelled PILOT_ONLY and target rules remain NEEDS_GRILL.
- [Prerequisite verifiers are duplicated] → use item/photo facts only as the
  smallest real public setup and audit them, without expanding their contracts.
- [Revocation makes serial history hard to read] → snapshot completion first,
  then isolate revoke and explicitly distinguish readiness from projection.
- [Concurrency harness grows excessively] → exactly one two-contender race with
  bounded readiness/start/execution/reaping.
- [Browser automation is inferred from server output] → record it as static
  source evidence only; server characterization does not claim browser action.

## Migration Plan

1. Write literal Gate 1 executable spec and obtain owner approval solely for
   PILOT_ONLY characterization.
2. Fresh RED author adds smallest accepted HTTP assertion; a different fresh
   reviewer approves before minimal GREEN.
3. Implement the accepted lifecycle only, then add reviewed expansion RED for
   replay/repeat/concurrency/rejections/revoke.
4. Implement the expansion, register once in canonical characterization, run
   focused/full checks and obtain fresh independent code review.

Rollback removes only verifier/test registration; production code/schema/facts
are unchanged.
