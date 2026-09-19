# SSD + TDD delivery process

## Current delivery priority

Read the short [current delivery goal](operations/current-delivery-goal.md).
It owns the work queue; this document owns gates and the execution protocol.
Historical exceptions below apply only to their stated milestone.

## Compact execution protocol — #82, 2026-09-10

The executable entry point is `python3 tools/delivery/harness.py`: `state`
resolves live source/PR/CI, `prepare` refreshes the repository-owned verification
plan and builds a bounded role/review package, and `run` retains full logs while
returning a compact result. Codex project hooks route ordinary implementation and
resume prompts to this process. They do not run checks, approve reviews, or start
delivery work for an ordinary read-only question.

Quality takes precedence over token efficiency, which takes precedence over time.
Keep the most capable model in the primary session. Root owns analysis, scope, the complete acceptance matrix and key decisions.
A declared bounded correction of established behavior may use one author for its
regression test and implementation. New behavior and sensitive work keep root-
authored specifications/tests and a separate executor unless the owner gives a
task-specific exception. Record the route and actual authors in the delivery
record; historical permission is not inherited.
Executors and reviewers use **gpt-5.6-sol / low**, with fresh, bounded contexts.
Root checks candidate completeness before dispatch; reviewers validate prepared
requirements rather than discover missing parts of the assignment.

Before Gate 2, cover the whole bounded slice in the normative spec: public seam,
observable result, persisted facts, rejections with no new facts, permissions,
replay/concurrency, user return path and material adjacent flows. Check schema
frontier consumers (including `rapid-pilot/verify-*`), fixtures/table inventories,
runtime dependencies, deployment/readiness, backup/restore and verification
inventory. Record relevant impacts and briefly justify inapplicable groups in the
design; a tooling-only change does not require a database ceremony.

Use the prepared executor/reviewer package for the issue, exact source, spec,
applicable rules, verification plan, evidence and complete candidate. Review
returns one complete findings list with severity, locations, corrections and
verdict for the agreed scope.
Review corrections against that source; broaden only for changed scope or new
risk. After a second return for foreseeable incompleteness, root rebuilds the
entire matrix and candidate before another dispatch. Assertions, form fields and
editorial corrections belong to their vertical slice, not separate microreviews.
Each correction handoff identifies the full candidate and last reviewed source,
their delta, every prior finding with an explicit `fixed`, `open`, or justified
`not-applicable` disposition, and any new delta risk. An open finding remains a
blocker; a second return for the same unresolved cause requires reconsidering the
approach or reporting the blocker.
Keep planner-required Gate 3/final reviews independent; one reviewer may serve
both if it authored neither.
Batch independent read-only calls and inspect every result.

The verification planner alone selects `verification_lane` and
`required_reviews`, but CI breadth and lifecycle ceremony are separate decisions.
A declared ordinary bounded fix of established behavior requires one independent
final review without Gate 3 even when its lane selects full CI. `FAST` continues
to require that final review. Changes to rights/secrets, money, schema or data
durability, lossy/duplicating replay or concurrency, irreversible operations,
admission/check policy, or materially uncertain sensitive product semantics
retain Gate 3 and final review. Agents do not infer FAST from diff size or prose.
The v1 classifier covers only its supported bounded UI scope; tests/spec changes
can escalate a small change. Classifier expansion is a separate policy change.

Commits mark meaningful stages, with related fixes and review records grouped at
an appropriate checkpoint. There is no per-assertion or per-verdict commit rule
and no mechanical commit quota. Append-only domain history remains mandatory;
it is distinct from Git checkpoint granularity. A review identifies either a
commit or a retained reconstructible source snapshot: base commit plus binary
patch including additions/deletions/modes and a SHA-256 digest. Keep the snapshot
outside the repository, record its location/digest and restore/check it before
review; use [review-source capture/restore](../tools/delivery/review-source.md). A later grouped commit must match the reviewed artifact bytes; enumerate
any additional review/documentation files. Changed code or tests require review
of the delta. CI still runs on the final exact committed candidate.

The normative spec owns behavior. OpenSpec owns lifecycle, scope and task state
for new slices. An ordinary bounded correction may use the existing compact task/
delivery record to link its issue and criteria (or current contract), exact source,
risk rationale, checks and final review without a new OpenSpec or duplicate spec.
Full logs remain outside Git. Review records own RED,
GREEN and findings evidence. One delivery record links exact source, reviews and
CI; a handoff contains only current state, next action and these links. Historical
records remain available; supersede them with a pointer instead of loading them
at each start. Use [the compact handoff template](../tools/delivery/handoff-template.md).

Before Gate 2, run harness `prepare` for the accepted contract and intended
boundary changes; it refreshes the [repository-owned Quality Graph change
verification plan](../tools/delivery/change-verification.md). The test author
reads its required obligations and executable commands before writing RED tests.
Unresolved coverage blocks Gate 2. A generated plan is not acceptance approval,
RED evidence or a replacement for independent review. Regenerate and review when
scope or bound inputs change. Existing CI selection remains authoritative. CI
reconstructs a FAST plan's selected commands from exact source. The separate
text-only docs allowlist is a CI mode, not a delivery-lane decision. Focused local
checks do not waive selected CI obligations.

Run meaningful focused checks locally; repeat only after relevant changes,
failures or new risk. Do not run full `make test` or `make verify` locally in
delivery without a new explicit owner override. One exact-source CI run through
the selected existing consumer validates the candidate. Collect the complete
failed-job and REGRESSION_FAILURE inventory and inspect
every failure before corrections. A same-source retry needs a recorded reason;
retain previous failures rather than treating reruns as diagnosis.
Before a manual Quality Graph dispatch, use `tools/delivery/ci-launch.py` to
boundedly discover an applicable PR-triggered run for the exact repository,
workflow, head, base and mode. Reuse that run when present; dispatch once only
after confirmed absence. Mismatch, failure, cancelled, incomplete or unknown
observations fail closed and do not authorize another dispatch.
Report elapsed time, review/return counts and reasons, repeated checks, delivered
result and remaining blockers. Distinguish implemented code, open PR, green CI
and merge; report material scope growth promptly. Measure tokens and money only
when actual measurements exist. Measure elapsed time and rework for comparable
slices; keep raw journals private and never infer billing from cached-token counts.

## Historical owner-authorized delivery mode —2026-09-07

Read [current delivery goal](operations/current-delivery-goal.md) before choosing
work or verification. For the manual-test pilot due2026-09-07 22:00Europe/Moscow,
that newer owner decision overrides the sequential full-gate prerequisite below.
Implement the working user flow, perform focused checks of ordinary behavior,
authorization and history preservation, deliver the stand, then fix manual feedback.
Rare-case matrices and full architectural migration follow that feedback. Existing
gates remain recorded; deferred work is not approved or complete by implication.
The full process below remains the standard for final production integration.

The following is the normal path from a product decision to production-integrated FMonitor 2.0 code, subject to the explicit owner-authorized manual-pilot mode above. SSD means Specification-Driven Development.

## OpenSpec lifecycle

Every new migration slice starts as one structured change under `openspec/changes/`. Its proposal, delta specification, design, and tasks describe the lifecycle and integration scope. The normative executable behavior remains the stable specification identified below and reviewed through Gates 1–5; OpenSpec artifacts cannot waive, reorder, or self-approve a gate. Archive a change only after its Done definition, regression, architecture check, and independent code review are complete.

## Unit of work

Deliver one vertical behavior slice at a time. A slice crosses the real public seam—from an accepted command or user action to its observable result—and is small enough for one red-green cycle. Each slice has a stable specification identifier such as `ORDER-PREPARE-001`.

Before tests are written, record and confirm the public seam. Tests exercise that seam and remain valid when internals are replaced.

Every executable specification starts with a short non-normative section
`Простыми словами`: in plain language it says what changes, why it matters, and
what the slice deliberately does not do. This summary helps navigation but does
not replace or override the normative acceptance contract below it.

## Gate 1: executable specification

Write or amend the normative specification before code. Each behavior must state:

- specification identifier and user/actor;
- preconditions and input;
- command or action;
- observable result and persisted facts;
- rejected cases and exact business reason;
- authorization and audit requirements;
- examples with independently determined expected values.

The gate passes when ambiguities affecting behavior are resolved and every acceptance statement is observable at the confirmed seam.

Approved cross-cutting invariants are inherited by every slice and are not resubmitted to the product owner as separate behavior for each command. A slice specification cites the inherited invariant and asks for a new decision only when it introduces an exception or a user-visible outcome not already covered by the shared contract.

## Gate 2: red test

Use small deterministic tests to cover the complete agreed slice matrix.
Incremental RED runs are allowed; when Gate 3 is required, submit the complete
candidate there before implementation and retain the command and relevant
failure output in the test-review record.

The test must:

- cite its specification identifier;
- use a public seam rather than private methods or database side channels;
- derive expected values from the specification or a worked example;
- fail for the missing behavior, not for broken setup;
- be deterministic and isolated from production systems.

The gate passes only when the test is demonstrably red for the intended reason.

## Gate 3: independent test review

Gate 3 applies when the planner includes `gate3` in `required_reviews`.
Sensitive routes include it; ordinary compact maintenance and `FAST` proceed to
their required independent final review without adding Gate 3. Full CI alone
does not add Gate 3.

A reviewer other than the test author reviews the specification and test without
relying on planned implementation details. Record the review in
`reviews/tests/<spec-id>.md` using the template in that directory.

The reviewer checks traceability, seam choice, sensitivity, expected-value independence, rejected cases, determinism, and the captured red result. `APPROVED` advances the slice. `CHANGES_REQUESTED` returns it to Gate 1 or 2.

## Gate 4: minimal implementation

Write only enough production code to make the tests required by the selected
plan pass. Run focused tests after a coherent correction affecting behavior or test risk,
then the relevant suite. Record the commands and results for code review. Refactoring beyond the slice waits for review or a separately specified slice.

The gate passes when tests required by the selected plan and the relevant
regression suite are green with no changes to an approved expectation. When
`required_reviews` includes Gate 3, implementation uses that independently
reviewed test.

## Gate 5: independent code review

The planner-selected final review is the Gate 5 decision for the prepared exact
source and is required for every lane, including `FAST`.

A reviewer other than the implementation author reviews the specification,
tests and earlier reviews required by the selected plan, production diff, and
verification output. Record the review in `reviews/code/<spec-id>.md`.

The reviewer checks specification conformance, invariant enforcement at every entry point, audit/history behavior, security, integration boundaries, maintainability, and whether the test would catch a plausible regression. Test changes discovered here require plan recomputation and restart at Gate 2; a new independent test approval is required when the recomputed `required_reviews` includes Gate 3.

The slice is complete only with an `APPROVED` code review and green relevant tests. A review record names the reviewer, reviewed commit or reconstructible source snapshot, verdict, findings, and verification evidence; approval cannot be inferred from silence.

## Independence

An independent review is performed by a different human or separately tasked agent that did not author the reviewed artifact. The reviewer receives the normative specification and the artifact under review, forms findings independently, and records a verdict. Self-review and a second pass by the same author are useful preparation but do not satisfy either review gate.

## Owner-approved verification matrix — 2026-09-08

The owner approved the [PR matrix](operations/verification-ci-matrix-2026-09-08.md)
and selected `make test` as the canonical full command. `make verify` remains a
compatibility alias. Local focused evidence and independent review precede one
authoritative CI run; local-full followed by CI-full is not a mandatory sequence.
Except for planner-supported FAST, code, tests, CI, policy/spec and unknown
changes use the full CI-category fallback. FAST runs the commands selected in its
planner plan, which CI reconstructs from the exact source. Separately, a narrow
text-only documentation allowlist selects docs CI checks and explicitly reports
DOCS_VERIFY_OK, never full VERIFY_OK. Schedule, release and manual CI run full.
This supersedes earlier assumptions requiring duplicate full execution, preserving
planner-required reviews, exact source, authorization, append-only history and
fail-closed results.
No branch protection or publisher permission changes are implied.

Live preflight/review adapters and server-side enforcement remain incomplete
under #107. Their absence is `UNKNOWN`, not approval or GREEN, and does not
justify repeating an already GREEN exact-source run without new failure or risk.
A manual owner merge does not establish autonomous agent admission.

## Owner decision — 2026-09-14: active application CI

The owner explicitly selected mandatory CI for the current Yii2 application only. Retired rapid-pilot launch/UI verifiers are no longer delivery gates. Preserve shared application, migration, security and historical replay checks; a retired adapter used only as a test fixture is not itself a reason to remove a shared contract. The exact retirement inventory and native replacement witness are in `specs/VERIFICATION-ACTIVE-APPLICATION-001.md`. No allow-failure, aggregate bypass, or legacy production compatibility is implied. This decision supersedes earlier requirements to run retired launch/UI entrypoints.
