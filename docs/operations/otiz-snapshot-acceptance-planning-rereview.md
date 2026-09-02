# OTIZ snapshot acceptance — independent planning rereview

- Reviewer: Codex subagent `/root/otiz_planning_rereview_0901b`
- Date: 2026-09-01
- Independence: fresh read-only reviewer; did not author, edit or previously
  review the planning artifacts
- Scope: all four current OpenSpec artifacts, product/context/pilot contracts,
  delivery process, behavior evidence and the preceding planning review

## Prior blockers

1. **Conditional `unique_reversal` repair — closed.** The delta spec now has a
   distinct authorized bad-CSRF scenario whose namespace already contains all
   twelve constructor-owned tables while only `unique_reversal` is absent. It
   requires that exact index to be added before CSRF rejection with no other
   table or business-row mutation. Design decision 3 and task 3.1 preserve this
   as a separately observable branch.
2. **Isolation/failure probes before GREEN — closed.** Task 3.1 puts session
   cleanup, ambient DB/session preservation, bounded process/SQL cleanup,
   repeatable transcript and intentional `SETUP_FAILURE`/`REGRESSION` probes in
   the expanded demonstrated RED. Task 3.2 requires a fresh independent test
   approval before task 3.3 permits their minimal GREEN implementation.
3. **Exact HTTP literals in Gate 1 — closed.** Task 1.1 explicitly requires
   evidence-backed exact status, `Location`, header presence or absence and
   plain UTF-8 body bytes with trailing LF for every case. This correctly
   delegates the literals to the normative executable specification without
   weakening the planning contract.

## Coherence and boundaries

The proposal, delta spec, design and tasks describe one focused public-HTTP
characterization slice. They consistently retain `PILOT_ONLY` status for broad
`otiz.manage`, blocker/warning behavior, replay, runtime DDL and financial
meaning. Formula validation, canonical premium persistence, target authority,
four-eyes/evidence sufficiency and payment consequences remain excluded and
blocked by GRILL-001. No task authorizes new rapid-pilot domain logic or a
persistence redesign.

Task order preserves the mandatory delivery gates: exact owner-approved Gate 1
precedes RED; every accepted and expanded behavior/isolation/failure assertion
is demonstrably RED and independently reviewed before its corresponding GREEN;
regression and architecture checks precede a separately tasked fresh code
review. Literal fixtures, three-field snapshot mutation, single append-only
event, unchanged child rows, independent Moscow clock bounds, sequential replay,
real winner-neutral two-worker concurrency, unique port/cookie/session
ownership, GC-off, exact owned-file cleanup, decoy preservation and canonical
single registration are coherent across artifacts.

## Verification evidence

- `openspec status --change characterize-otiz-snapshot-acceptance --json`:
  planning complete; all four artifacts `done`.
- `openspec instructions proposal|specs|design|tasks --change
  characterize-otiz-snapshot-acceptance --json`: artifacts conform to current
  schema instructions and project rules.
- `openspec validate --strict characterize-otiz-snapshot-acceptance`: passed.
- `git diff --check`: passed.
- `make architecture-check`: `ARCHITECTURE CHECK PASSED (6 rules)`.

## Verdict

READY_FOR_GATE1_DRAFT
