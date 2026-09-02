# CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001 — independent Gate 1 review

- Review date: `2026-09-02`
- Reviewer: separately tasked fresh agent `/root/navigation_planning_update`
- Scope: executable characterization specification and testability only; no RED,
  test implementation, production change, Gate 3, GREEN or code review
- Independence: reviewer did not author or edit the reviewed executable spec,
  OpenSpec artifacts, tests, test support or production code
- Verdict: `CHANGES_REQUIRED`

## Exact reviewed hashes

```text
aebff690011680e3ac703a2408dd782c6b583d8bb26d86dc881a3dd15db4067a  specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md
1b1d5795af057ca8a44fb1fe6b0246d78ada16aea9ef1eb4bfa8e29a645b6313  openspec/changes/characterize-construction-control-queue/proposal.md
61b0861f58d74347a528d9424fbfe2640de8ed129f1ac72d96687b93ede68748  openspec/changes/characterize-construction-control-queue/design.md
f7bc52208d6a538ff018de589ae6bce949aea4313770218d16ba1c2799d07fae  openspec/changes/characterize-construction-control-queue/specs/verification/construction-control-queue-characterization/spec.md
4db8b651926f29b4a66b552d87f23c82e1e32f625eb1d0dee4d0eb024ac74644  openspec/changes/characterize-construction-control-queue/tasks.md
cbb78718ec8ecbad3628aece299406c6b7c2b2d6cc2916ce29c4366ff0d15d54  docs/operations/construction-control-queue-planning-review.md
```

The task hash differs from the earlier planning review's recorded
`0f39e0...` because executable-spec authoring task 1.1 is now checked. The
current task content and hash above were reviewed directly.

## Evidence and checks

The review traced the matrix through the real configured composition in
`ProductionPilotHttpEntrypointFactory`, `PilotE2ECoordinator`,
`ProductionConstructionControlRenderer`, `PilotRouteCsp` and
`control-queue.js`, and compared its isolation/gate shape with existing
characterization contracts.

The literal error bytes are correct for current production response code:

```text
Authentication required.\n  length=25  sha256=971cfaaf421c7874bc205759bc6e2d771706b39358770928952ba2b8ea580dd5
Access denied.\n             length=15  sha256=5a96ae11555504787da4b5f09ca3175a006392cff7c2c7df1a57f08ca2ebda02
Service unavailable.\n       length=21  sha256=38c9439b9ab2abf40304675451d0fae7069809a7e3c8fe0ef96274c8680f21eb
```

The application-owned security/cache headers, route-specific successful CSP,
base error CSP, `Retry-After`, `Content-Length` and HEAD empty-body behavior
match `PilotE2ECoordinator::response()` and `PilotRouteCsp`. The projection
fixtures distinguish working-only selection, null-first/activity ordering,
event precedence, legacy fallback, absence, maximum operation device time,
PTO marker, escaping, canonical links and the current 50-row pagination before
browser filtering. Current browser-only behavior is correctly excluded.

## Blocking finding

### 1. Required mutate-then-restore sensitivity is not observable by the mandated harness

The executable spec requires the Gate 2 meta-test to reject a plausible
implementation that "mutate[s] then restore[s] DB state". Its mandated state
observation, however, is only a complete fingerprint immediately before and
after each request group. A request can insert/update/delete and restore the
same definitions, rows and auto-increment metadata before returning; both
fingerprints then compare equal. The request log proves HTTP exchange identity,
not absence of SQL writes. The only SQL audit mentioned by the spec is optional
and scoped to proving that denied cases do not query queue-owned facts.

This is an anti-fake sensitivity requirement that no conforming Gate 2 test can
reliably satisfy using the approved observation seams. A future test would have
to self-attest the impossible sensitivity, weaken it silently, or add an
unapproved database-write observation mechanism.

Required correction: choose and specify one executable mechanism before owner
approval. Either require an independent test-owned write audit/guard that
observes every runtime-principal DML attempt (including rolled-back/restored
writes) without replacing public HTTP observation, with exact setup/failure and
cleanup semantics; or narrow the sensitivity claim to mutations visible in the
required before/after schema/row/auto-increment fingerprints. Reconcile the
executable spec, delta requirement/design and task verification so they promise
the same observable boundary.

## Non-blocking observations

- The public-seam rule is strong: real loopback requests must traverse identity,
  capability lookup, MariaDB projection, renderer and response headers; direct
  renderer calls and fabricated DTOs cannot qualify.
- Literal actor/case/page data and request-log cardinality make static expected
  HTML, skipped authorization, wrong selection/order/source/escaping/link and
  serialized fake concurrency independently detectable.
- Unique token/prefix/root preflight, least-privilege runtime identity, exact
  inventory, bounded deadlines, PID reaping, ambient decoy and two-run
  normalized transcript provide a credible isolation and cleanup contract once
  the transient-write gap is resolved.
- `PILOT_ONLY`, `NEEDS_GRILL` and non-goals consistently avoid promoting broad
  visibility, activity/completion meaning, page size/order, browser filters or
  a target read-model API into product requirements.
- Gate discipline is correct: this review and exact owner approval precede RED;
  Gate 3 and Gate 5 require different fresh reviewers, and no production change
  is authorized by this slice.

## Verdict

**CHANGES_REQUIRED**

The exact response and projection matrix is traceable and otherwise testable,
but the mandatory mutate-then-restore anti-fake claim cannot be demonstrated by
the specified before/after-only observation boundary. The executable spec hash
`aebff690...` is therefore not ready for owner approval. Revise that boundary,
strict-validate the coherent artifacts, and obtain a fresh independent Gate 1
review over the new exact hashes. Gate 2 remains closed.
