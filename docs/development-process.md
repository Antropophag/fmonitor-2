# SSD + TDD delivery process

## Current delivery priority

Read the short [current delivery goal](operations/current-delivery-goal.md).
It owns the work queue; this document owns gates and the execution protocol.
Historical exceptions below apply only to their stated milestone.

## Compact execution protocol — #78, 2026-09-09

One executor owns a vertical slice through its reviewable PR candidate. Give it
only the issue, exact base/worktree, normative specification, relevant boundary
instructions and verification plan. Use a fresh context for each substantial
slice and each independent review; full conversation forks are unnecessary.
Parallel agents remain **gpt-5.6-sol / low**. This is the selected operating mode,
not a measured claim that this model is cheapest; change the model rule explicitly
only after comparable completed-slice evidence supports it.

The coordinating agent resolves scope and decisions, integrates independently
reviewed work and reports outcomes. It reads the complete findings and changed
boundaries; it need not re-read every intermediate artifact. A review returns one
complete findings list, severity, affected locations, required corrections and
verdict. Review fixes as a diff against the reviewed source; broaden only for a
new risk. Keep test authorship and implementation authorship explicit so Gate 3
and Gate 5 retain independence. A reviewer may review both stages if it authored
neither artifact. Batch independent read-only tool calls and inspect all results.

The normative spec owns behavior. OpenSpec owns lifecycle, scope and task state;
link to the spec instead of copying acceptance matrices. Review records own RED,
GREEN and findings evidence. One delivery record links exact source, reviews and
CI; a handoff contains only current state, next action and these links. Historical
records remain available; supersede them with a pointer instead of loading them
at each start. Use [the compact handoff template](../tools/delivery/handoff-template.md).

Before Gate 2, compute the [repository-owned Quality Graph change verification
plan](../tools/delivery/change-verification.md) from the accepted contract and intended boundary changes. The executor reads
its required obligations and executable commands before writing RED tests.
Unresolved coverage blocks Gate 2. A generated plan is not acceptance approval,
RED evidence or a replacement for independent review. Regenerate and review when
scope or bound inputs change. Existing full-CI selection remains authoritative;
focused local checks do not waive integration, E2E or governance categories.

Run meaningful focused checks locally; repeat only after relevant changes,
failures or new risk. One full exact-source CI validates the candidate; avoid a
second local-full run without cause. Report all failed checks before corrections.
Measure tokens, elapsed time and rework separately for completed comparable
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

Write the smallest test that proves one acceptance statement. Run it before implementation and retain the command and relevant failure output in the test-review record.

The test must:

- cite its specification identifier;
- use a public seam rather than private methods or database side channels;
- derive expected values from the specification or a worked example;
- fail for the missing behavior, not for broken setup;
- be deterministic and isolated from production systems.

The gate passes only when the test is demonstrably red for the intended reason.

## Gate 3: independent test review

A reviewer other than the test author reviews the specification and test without relying on planned implementation details. Record the review in `reviews/tests/<spec-id>.md` using the template in that directory.

The reviewer checks traceability, seam choice, sensitivity, expected-value independence, rejected cases, determinism, and the captured red result. `APPROVED` advances the slice. `CHANGES_REQUESTED` returns it to Gate 1 or 2.

## Gate 4: minimal implementation

Write only enough production code to make the independently reviewed test pass. Run the focused test after each change, then the relevant suite. Record the commands and results for code review. Refactoring beyond the slice waits for review or a separately specified slice.

The gate passes when the reviewed test and relevant regression suite are green with no changes to the approved expectation.

## Gate 5: independent code review

A reviewer other than the implementation author reviews the specification, approved tests, production diff, and verification output. Record the review in `reviews/code/<spec-id>.md`.

The reviewer checks specification conformance, invariant enforcement at every entry point, audit/history behavior, security, integration boundaries, maintainability, and whether the test would catch a plausible regression. Test changes discovered here restart at Gate 2 and require a new independent test approval.

The slice is complete only with an `APPROVED` code review and green relevant tests. A review record names the reviewer, reviewed commit, verdict, findings, and verification evidence; approval cannot be inferred from silence.

## Independence

An independent review is performed by a different human or separately tasked agent that did not author the reviewed artifact. The reviewer receives the normative specification and the artifact under review, forms findings independently, and records a verdict. Self-review and a second pass by the same author are useful preparation but do not satisfy either review gate.

## Owner-approved verification matrix — 2026-09-08

The owner approved the [PR matrix](operations/verification-ci-matrix-2026-09-08.md)
and selected `make test` as the canonical full command. `make verify` remains a
compatibility alias. Local focused evidence and independent review precede one
authoritative CI run; local-full followed by CI-full is not a mandatory sequence.
Code, tests, CI, policy/spec and unknown changes require all categories. A narrow
text-only documentation allowlist requires the fast checks and explicitly reports
DOCS_VERIFY_OK, never full VERIFY_OK. Schedule, release and manual CI run full.
This supersedes earlier assumptions requiring duplicate full execution, preserving
Gates 1–5, exact source, authorization, append-only history and fail-closed results.
No branch protection or publisher permission changes are implied.
