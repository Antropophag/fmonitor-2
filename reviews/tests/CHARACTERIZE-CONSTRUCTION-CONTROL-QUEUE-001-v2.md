# CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001 — fresh independent Gate 1 rereview v2

- Review date: `2026-09-02`
- Reviewer: separately tasked fresh agent `/root/construction_queue_gate1_rereview`
- Scope: current executable characterization specification and coherent OpenSpec
  package after the transient-DML audit correction; no RED, tests, verifier,
  production change, Gate 3, GREEN or code review
- Independence: reviewer did not author or edit the reviewed specification,
  OpenSpec artifacts, tests, test support or production code
- Verdict: `CHANGES_REQUIRED`

## Exact reviewed hashes

```text
f83d0a2797489c157ab141db283254d641d8e85f854d769fc0b83b00028aecfd  specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md
3bd2241d76feeabede8d5a83ce3003553754cb5316688b2f06b90614af4cc64c  openspec/changes/characterize-construction-control-queue/proposal.md
af58d89a95a884fe6d2cb30815e6524174583030a89d509978840b72c1eb5641  openspec/changes/characterize-construction-control-queue/design.md
349ab2925f66452c85a02f0e45ccf74589d1e272ad34452b4c139d7cc19aaee9  openspec/changes/characterize-construction-control-queue/specs/verification/construction-control-queue-characterization/spec.md
9ee79066e0641d73f567eed16fd6bb817ca7b016f9a9624d7cb9a6884c47f64e  openspec/changes/characterize-construction-control-queue/tasks.md
cbb78718ec8ecbad3628aece299406c6b7c2b2d6cc2916ce29c4366ff0d15d54  docs/operations/construction-control-queue-planning-review.md
```

## Evidence and checks

The rereview traced the contract through the current production composition in
`ProductionPilotHttpEntrypointFactory`, `PilotE2ECoordinator`,
`ProductionConstructionControlRenderer`, `PilotRouteCsp` and
`control-queue.js`. The literal `401`, `403` and `503` bytes, lengths and hashes,
the application-owned headers, successful/error CSP split, `Retry-After`, HEAD
body suppression, working-only projection, ordering, event/fallback precedence,
PTO marker, escaping, canonical checklist links and 50/1 server-pagination
contrast remain traceable to the current oracle. Their target meanings remain
explicitly excluded as `PILOT_ONLY`/`NEEDS_GRILL`.

The previous review's blocking finding is closed. The amended contract no
longer relies on before/after fingerprints to detect a mutate-then-restore
implementation. It requires:

- a distinct privileged observer unavailable to the HTTP worker;
- an exact `SELECT`-only runtime account, preventing a persisted write;
- read-only preflight of already-enabled `performance_schema` statement
  instrumentation and bounded history;
- request barriers retaining the production connection while its exact thread
  history is inspected;
- rejection of denied, rolled-back, restored, truncated or unclassifiable
  DML/DDL attempts;
- a same-principal denied-`INSERT` sensitivity double that must be visible to
  the observer while its sentinel fingerprint remains unchanged.

This is independent observation rather than verifier/production self-report.
Unavailable or incomplete audit correctly fails as `SETUP_FAILURE`, and the
observer is forbidden to enable global instrumentation.

## Blocking finding

### 1. The concurrency scenario cannot satisfy the specified exact-thread binding

The executable spec creates one runtime database account
`fm2_ccq_<token>` at one exact loopback host and says that, for **each** request,
the observer must resolve **exactly one** active connection for that unique
runtime username before binding its `PROCESSLIST_ID` to a `THREAD_ID`.

The same spec separately requires two independently authenticated simultaneous
workers, both to overlap at barriers and traverse production composition. At
that point both production workers can hold an active MariaDB connection under
the one mandated runtime username. The observer therefore sees two active
connections and must classify the run as ambiguous/`SETUP_FAILURE`; choosing
one by timing or an implementation-owned hint would weaken the stated
anti-self-attestation boundary. Serializing the connections would fail the
required overlap/concurrency sensitivity.

Before owner approval, specify a deterministic independent association for
each concurrent request. For example, derive two exact bounded runtime
principals from the run token (same exact `SELECT` grants), bind each worker to
one principal, and require one connection/thread per principal; or specify an
equally independent per-request connection marker visible to the privileged
observer. Reconcile the singular-account cleanup/grant rules, executable spec,
design, delta requirement and task verification. The observer must still fail
closed on missing, extra or ambiguous connections and must audit both request
threads before either connection closes.

## Non-blocking observations

- Literal actors, fixtures and expectations do not derive expected values from
  future verifier output or a production renderer helper.
- Authorization includes unauthenticated, inactive, denied and admitted
  outcomes; denied projection-read absence is required in addition to public
  response observation.
- Infrastructure failures pin page validation, SQL denial, malformed row/event,
  exact body/hash/headers and unchanged state.
- Owned SQL/file/session inventories, ambient decoy, exact account cleanup,
  bounded deadlines, child reaping, two-token determinism and secret/runtime-id
  suppression are fail-closed.
- Browser filters/offline state are source evidence only and are not executed or
  promoted into requirements.
- Gate ordering and role separation remain correct; Gate 2 stays closed without
  fresh exact-hash owner approval.

## Verification

- `openspec validate characterize-construction-control-queue --strict` — PASS
  (`Change 'characterize-construction-control-queue' is valid`).
- `git diff --check` — PASS (exit 0, no output before this review record was
  added).

## Verdict

**CHANGES_REQUIRED**

The transient-write observation gap is genuinely resolved without
self-attestation, and the response/projection/isolation/PILOT_ONLY contract is
otherwise ready. Gate 1 cannot advance while the required two-worker overlap
contradicts the singular-username exact-thread binding. Amend that association,
strict-validate the coherent package and obtain another fresh independent
review over new exact hashes. Gate 2 remains closed.
