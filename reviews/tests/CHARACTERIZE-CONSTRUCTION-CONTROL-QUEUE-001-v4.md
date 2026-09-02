# CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001 — fresh independent Gate 1 rereview v4

- Review date: `2026-09-02`
- Reviewer: separately tasked fresh agent `/root/construction_queue_gate1_rereview`
- Scope: final executable characterization specification and coherent OpenSpec
  package after the exact read-only grant correction; no RED, tests, verifier,
  production change, Gate 3, GREEN or code review
- Independence: reviewer did not author or edit the reviewed specification,
  OpenSpec artifacts, tests, test support or production code
- Verdict: `READY_FOR_OWNER_REVIEW`

## Exact reviewed hashes

```text
f0dadc560979970968e265e8e26f0bfed42101a00dcaaed2f8cc7cf311c0243d  specs/CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001.md
de7167bc108bdbf3fbd348434c4818324455eb900935c6c974dd3f4ed93e3a02  openspec/changes/characterize-construction-control-queue/proposal.md
bf0ac6a294aa34ef379237208eb6b765117fdef1564a9cc693fa9c0cc615178b  openspec/changes/characterize-construction-control-queue/design.md
a361549f4ab8920218fc4438877811de971da8695cbc608a2ad153cd98aef412  openspec/changes/characterize-construction-control-queue/specs/verification/construction-control-queue-characterization/spec.md
e0163eefa41f214478de48c291d21c6db1669fbbd00f16dee99064cbdb3e7daa  openspec/changes/characterize-construction-control-queue/tasks.md
cbb78718ec8ecbad3628aece299406c6b7c2b2d6cc2916ce29c4366ff0d15d54  docs/operations/construction-control-queue-planning-review.md
```

## Findings

No blocking finding remains.

1. **The grant contradiction is closed.** Design decision 2 now assigns all
   DDL/DML fixture setup exclusively to the privileged test connection and
   gives every HTTP worker principal only exact `SELECT` grants on owned fixture
   tables. This is coherent with decision 3, proposal, executable spec, delta
   requirement and tasks.
2. **Transient write attempts are independently observable.** Four read-only
   runtime principals prevent persisted writes. A separate privileged observer
   binds each slot to its exact MariaDB thread and reads already-enabled
   `performance_schema.events_statements_history_long`; denied, rolled-back,
   restored, truncated or unclassifiable attempts cannot pass. The `_x`
   sensitivity double must expose its denied literal `INSERT`, independently of
   verifier output and before characterization proceeds.
3. **Concurrent thread binding is deterministic.** `_s` is strictly serial;
   `_a` and `_b` identify concurrent workers independently; `_x` is isolated to
   sensitivity. Both concurrent connections and distinct threads are captured
   before one common dispatch release, both response barriers are reached, and
   both histories are audited before teardown of either worker. Missing, extra,
   duplicate or shared mappings fail as `SETUP_FAILURE`.
4. **The public contract is exact and independently testable.** Real loopback
   `GET`/`HEAD` requests traverse production identity, authorization, MariaDB
   projection, renderer and response composition. Literal actors, fixtures,
   headers, bodies/hashes, working-only selection, ordering, pagination,
   engineer precedence/fallback/absence, activity/PTO projection, escaping,
   links and infrastructure failures are not calculated from future verifier
   output.
5. **Isolation and cleanup fail closed.** Exact prefixed tables, four exact
   accounts, artifact/session children, request log, ambient decoy, deadlines,
   process reaping, grant validation and bounded cleanup are specified. Global
   instrumentation changes, broad kills, wildcard SQL/user cleanup and broad
   recursive deletion are forbidden.
6. **Scope remains characterization-only.** Current permission, broad
   visibility, engineer/activity/completion meanings, ordering, page size,
   browser filters and offline behavior stay explicitly
   `PILOT_ONLY`/`NEEDS_GRILL`; no observed defect is promoted into a target
   product requirement or target read-model API.
7. **Gate discipline is preserved.** This verdict permits owner review of the
   exact hashes only. Gate 2 remains closed until an append-only owner decision
   approves them. RED author, Gate 3 reviewer, GREEN implementer and Gate 5
   reviewer remain separately assigned roles.

## Verification

- `openspec validate characterize-construction-control-queue --strict` — PASS
  (`Change 'characterize-construction-control-queue' is valid`).
- `git diff --check` — PASS (exit 0, no output before this review record was
  added).

## Verdict

**READY_FOR_OWNER_REVIEW**

The executable specification and OpenSpec package are coherent, observable and
bounded. The exact hashes above may be presented for explicit owner approval,
which authorizes Gate 2 only. No RED, test implementation, production change or
GREEN is authorized by this review alone.
