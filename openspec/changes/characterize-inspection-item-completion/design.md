## Context

См. proposal, capability spec и
`docs/operations/inspection-item-completion-behavior-evidence.md`. Existing
verifiers cover template fragments or SQL-seeded crew projection, not a complete
acceptance exchange. Current seam serializes by case but accepts lower stale
base and checks duplicate id before semantic payload/object validation.

## Goals / Non-Goals

**Goals:**

- Compact executable oracle for accepted facts, replay, revision/concurrency and
  four validation boundaries.
- Literal independent expected values and raw-row/projection audit.
- Concurrent-agent-safe DB isolation, deterministic output and bounded cleanup.

**Non-Goals:**

- Exhaustive HTTP authorization matrix or target authorization choice.
- Correction operations, photos/section completion and exhaustive legacy repair.
- Production schema ownership, application seam implementation or target RED.

## Decisions

### 1. Real HTTP is the externally observable seam

Verifier obtains a real session/token and submits same-origin production HTTP.
The actor is fixed as current engineer with no broad editor permission, so this
proves wiring and one observed admission branch without choosing GRILL-003 target
policy. Raw database audit, not response alone, proves mutation.

### 2. Test owns exact schema and never calls runtime ensureSchema

Private fixtures precreate the exact relevant final shape and prove fingerprints
unchanged. HTTP still executes current `ensureSchema`, which remains observed
debt rather than approved ownership. The later canonical migration removes it.

### 3. One serial track proves accepted/replay/stale/ahead history

Literal operations build a small revision history whose full rows are captured
after each action. Same-id changed payload is tested before unrelated rejection
scenarios so duplicate precedence is unmistakable.

### 4. Concurrency uses two loopback servers and clients with a barrier

Each server process opens its own DB connection; each client owns a valid
session/token and waits at the parent barrier before POST. Parent owns readiness,
start coordination, timeout/reaping and final audit. Assertions accept either
winner order but require two consecutive accepted facts; single-server or
thread-like mocks would not exercise MariaDB locks.

### 5. Rejections use independent clean case fixtures

Non-working, template mismatch, wrong item and non-current crew each change only
one precondition. Snapshot after required session GET establishes its current
revision-zero read mutation; post-POST snapshots prove no further revision or
business-fact mutation and prevent result-only fakes/cross-scenario contamination.

### 6. Backfill is one isolated projection scenario

A legacy completion row is setup-only evidence. A real authenticated GET triggers
projection; independent before/after rows prove current-crew attribution was
inserted on read. This closes the integrity evidence gap without mixing target
repair into characterization.

### 7. Verification owns no product module

Harness may depend on current public PilotHttp value/seam and MariaDB test
fixtures only. It adds no application dependency, SQL owner or rapid-pilot
domain logic. Architecture baseline must remain unchanged; canonical stage gains
one verifier entry only after reviewed GREEN.

## Risks / Trade-offs

- [Characterization blesses defects] → every replay/stale/concurrency milestone
  is labelled PILOT_ONLY and target differences remain explicit.
- [Scope repeats previous overgrown concurrency harness] → one two-server race,
  winner-neutral assertions, bounded timeout and no exhaustive auth matrix.
- [Fixture copies production rules] → expected rows come from literal Gate 1
  worked example; test reviewer checks independence before GREEN.
- [Read projection mutates legacy rows] → isolate and characterize one backfill;
  target planning must add an explicit read-only/no-manufactured-history clause
  before calibration Done.
- [HTTP invokes runtime DDL debt] → precreate exact schema and fingerprint
  before/after every behavior; execution is observed without approving ownership.

## Migration Plan

1. Write exact Gate 1 spec with literal operations/facts and obtain owner
   approval as PILOT_ONLY characterization.
2. Fresh RED author creates the smallest accepted-HTTP test; different fresh
   reviewer approves before its GREEN.
3. Implement only the accepted HTTP exchange and turn that reviewed test GREEN.
4. Expand replay/revision/concurrency/rejection/backfill assertions under a new
   intended RED and obtain another fresh independent test approval before their
   implementation.
5. Implement the reviewed expansion and canonical-stage wiring.
6. Run focused test twice, characterization, architecture/lint/regression and
   full verify; fresh code reviewer approves.

Rollback removes only verifier/test registration. Production code, schema and
facts remain unchanged.
