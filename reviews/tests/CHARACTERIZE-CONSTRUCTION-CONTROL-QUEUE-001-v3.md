# CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001 — fresh independent Gate 1 rereview v3

- Review date: `2026-09-02`
- Reviewer: separately tasked fresh agent `/root/construction_queue_gate1_rereview`
- Scope: corrected executable characterization specification and coherent
  OpenSpec package after four-principal thread mapping; no RED, tests, verifier,
  production change, Gate 3, GREEN or code review
- Independence: reviewer did not author or edit the reviewed specification,
  OpenSpec artifacts, tests, test support or production code
- Verdict: `CHANGES_REQUIRED`

## Exact reviewed hashes

```text
f0dadc560979970968e265e8e26f0bfed42101a00dcaaed2f8cc7cf311c0243d  specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md
de7167bc108bdbf3fbd348434c4818324455eb900935c6c974dd3f4ed93e3a02  openspec/changes/characterize-construction-control-queue/proposal.md
bf8075c6896465a9a46307bd0d373a26c424578b03e288fc1e92f3922d0bd3e6  openspec/changes/characterize-construction-control-queue/design.md
a361549f4ab8920218fc4438877811de971da8695cbc608a2ad153cd98aef412  openspec/changes/characterize-construction-control-queue/specs/verification/construction-control-queue-characterization/spec.md
e0163eefa41f214478de48c291d21c6db1669fbbd00f16dee99064cbdb3e7daa  openspec/changes/characterize-construction-control-queue/tasks.md
cbb78718ec8ecbad3628aece299406c6b7c2b2d6cc2916ce29c4366ff0d15d54  docs/operations/construction-control-queue-planning-review.md
```

## Closed findings

The v2 concurrency/thread-binding finding is closed. The package now derives
four exact runtime accounts from one validated run token:

- `_s` for strictly serialized request groups;
- `_a` and `_b` for concurrent workers A and B;
- `_x` for the denied-DML sensitivity double.

Each active slot permits exactly one connection. The observer independently
maps exact usernames through `PROCESSLIST_ID` to distinct `THREAD_ID` values.
Concurrent A/B threads are both captured before a common dispatch release,
both workers stop after response, and both histories are audited before either
teardown is released. Missing, duplicate, shared or extra connections fail as
`SETUP_FAILURE`. No binding hint may come from the production response or
verifier stdout. This closes the prior ambiguity without timing assumptions or
self-attestation.

The earlier transient-write finding also remains closed: an independent
privileged observer reads already-enabled `performance_schema` statement
history, while before/after fingerprints provide a separate persisted-state
check. The `_x` sensitivity principal must expose a denied literal `INSERT` in
history, and incomplete/truncated history fails closed before characterization.

## Blocking finding

### 1. The design still grants DML to the HTTP worker in one normative decision

`openspec/changes/characterize-construction-control-queue/design.md`, decision
2, still says:

> HTTP worker получает DML/read privileges, соответствующие реальному seam.

That directly contradicts decision 3, the executable spec, proposal, delta
requirement and tasks, all of which require the four runtime accounts to have
only exact `SELECT` grants and no write privileges. It also defeats the stated
first half of the transient-write protection: if interpreted literally by the
Gate 4 implementer, a runtime statement can persist before a later restore,
contrary to the guarantee that least privilege prevents the write while
statement history observes the attempt.

Replace the stale sentence with the same exact read-only grant boundary used by
the rest of the package. Recompute hashes, strict-validate and obtain a fresh
independent review. No behavioral/product decision is needed; this is a
planning-artifact coherence correction.

## Other reviewed boundaries

- The public seam still requires real loopback `GET`/`HEAD` requests through
  production identity, authorization, query, renderer and response composition.
- Literal actors, headers, error bodies/hashes, projections, pagination,
  escaping and canonical href expectations remain independently determined and
  traceable to the current oracle.
- Authorization denial, infrastructure failures, repeat reads, state
  fingerprints, request-log cardinality, clock retry and sensitivity failure
  classification are executable and fail-closed.
- Exact account names/host/grants are validated before bounded `DROP USER`;
  cleanup covers all four accounts, exact tables/files/sessions and children,
  with ambient decoy preservation and no wildcard/broad destructive action.
- Target assignment visibility, inspection/completion meaning, ordering,
  pagination and browser/offline behavior remain explicitly
  `PILOT_ONLY`/`NEEDS_GRILL` and are not promoted into product requirements.
- Gate ordering and independent roles remain correct; Gate 2 is not authorized
  before fresh exact-hash owner approval.

## Verification

- `openspec validate characterize-construction-control-queue --strict` — PASS
  (`Change 'characterize-construction-control-queue' is valid`).
- `git diff --check` — PASS (exit 0, no output before this review record was
  added).

## Verdict

**CHANGES_REQUIRED**

The four-principal mapping now closes concurrent thread ambiguity, and auditing
before teardown is precise. One stale design sentence still contradicts the
package's exact `SELECT`-only runtime boundary. Correct it and obtain another
fresh hash-pinned review; Gate 2 remains closed.
