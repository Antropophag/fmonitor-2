## Context

См. proposal, capability spec и
`docs/operations/inspection-attribution-correction-behavior-evidence.md`.
Current `item_installers_changed` is append-only in raw storage but overwrites
the visible item projection and contradicts the approved
`completion_retracted` contract. Existing verifiers do not execute it through
the production HTTP/session/CSRF boundary.

## Goals / Non-Goals

**Goals:**

- Small real-HTTP oracle with independent raw-row and projection evidence.
- Exact contrast for history, replay, revision/concurrency, crew drift,
  rejection boundaries and legacy read mutation.
- Deterministic concurrent-agent-safe fixture and bounded cleanup.

**Non-Goals:**

- Designing or implementing target correction/attribution semantics.
- Approving current authorization, response copy or pilot conflict behavior.
- Moving schema ownership, changing runtime code, or expanding the golden E2E.

## Decisions

### 1. Real HTTP/session/CSRF is the observed public seam

The fixture starts production-composed loopback HTTP, performs an authenticated
GET for the real cookie/token and sends same-origin JSON POST. A synthetic actor
is current engineer without broad editor capability. This proves one observed
admission path without selecting target policy. Raw database evidence prevents
response-only fakes.

### 2. Literal fixture precreates the exact schema

The verifier owns private process/identity/card/template/crew/checklist tables
and fingerprints them before/after. HTTP still reaches current runtime DDL, but
that debt is observed rather than adopted; no production SQL owner is added.

### 3. Original completion is an explicit immutable baseline

Nominal scenarios seed or create one independently specified completion with
complete installer rows, then fingerprint it around every correction. This
prevents projection backfill from obscuring whether correction mutated history.
The separate backfill scenario intentionally omits those rows.

### 4. Serial and concurrent tracks are narrow

One serial track proves accepted correction, exact/changed replay, stale and
ahead behavior plus crew drift. One two-server/two-client barrier race proves
both same-base results and winner-neutral final projection. Exhaustive schedules
and same-id races are omitted because they do not unlock the target decision.

### 5. Rejections use isolated cases and exact mutation accounting

Each rejection changes one precondition. Snapshot immediately after session GET
is the POST baseline, so revision-zero initialization and read-side backfill are
not falsely attributed to the rejected command.

### 6. Verification owns no application/domain module

This change adds only test/evidence ownership. Future target command belongs to
`InspectionRecording::changeItemAttribution` only if owner-approved semantics
establish it. The harness may depend on current PilotHttp value types and
MariaDB fixtures; application modules remain independent of HTTP, rapid-pilot
and concrete adapters. Architecture debt baselines must not grow.

## Risks / Trade-offs

- [Pilot command is mistaken for approved correction] → capability title and
  every semantic contrast explicitly say PILOT_ONLY and cite the contradiction.
- [Harness duplicates item-completion setup] → reuse only fixture mechanics at
  implementation time; keep a separate transcript and acceptance contract.
- [Projection backfill contaminates rejection evidence] → seed attributed
  completions normally and isolate one intentional backfill scenario.
- [Concurrency becomes overbuilt] → exactly one two-contender race with bounded
  readiness/start/timeout/reaping and winner-neutral assertions.
- [Fixture copies implementation expectations] → Gate 1 fixes literal examples;
  independent test review occurs before each GREEN increment.

## Migration Plan

1. Create exact Gate 1 executable spec and obtain owner approval for the
   characterization-only scope.
2. Fresh RED author adds the smallest accepted real-HTTP assertion; a different
   fresh reviewer must approve it before GREEN.
3. Implement only that exchange, then add a second reviewed RED expansion for
   replay/revision/concurrency/rejections/backfill.
4. Implement the approved expansion, register once in canonical
   characterization, run focused and full verification, and obtain fresh code
   review.

Rollback removes only verifier/test registration and planning evidence;
production behavior and data are unchanged.
