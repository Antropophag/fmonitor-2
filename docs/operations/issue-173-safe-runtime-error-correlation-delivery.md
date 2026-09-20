# Delivery record — issue #173 safe runtime error correlation

## Assignment and authorship

- Owner assignment: autonomous planning/apply to PR-ready from current `origin/main`, separate worktree, no merge/deploy/settings.
- Base: `e3b39e59b7ba75a3af7dd3d22871c5c9f25d44e9`.
- Branch/worktree: `codex/issue-173-safe-error-correlation`, `/Users/antropophag/code/fmonitor-2-issue-173`.
- Root authored OpenSpec, normative specification, verification input, policy/inventory registration, and executable test.
- Independent Gate 3 reviewer: `gpt-5.6-sol / low`.
- Production implementation: separate `gpt-5.6-sol / low` executor.

## Gate evidence

- Planner lane: `CRITICAL`; required reviews: `gate3`, `final`.
- Gate 2 container RED: missing `X-FMonitor-Error-ID`, retained harness record `1789889136897662000-9b4e319428c248b2b8322b3adeda49e8.json`.
- Gate 3: initial `CHANGES_REQUESTED`; correction round 1 `CHANGES_REQUESTED`; correction round 2 `APPROVED`. Record: `reviews/tests/SAFE-RUNTIME-ERROR-CORRELATION-001.md`.
- Executor added the shared safe reporter and integrations only at the allowed bootstrap/handler/controller boundaries. `git diff --check` is clean.

## Blocker after implementation

The first full container acceptance execution past the initial RED reaches the bootstrap database case and exposes a Gate-3-approved fixture setup defect: `PreopeningFixture::environment()` does not provide required `FMONITOR_SESSION_STATE_ROOT`. `RuntimeConfiguration::fromEnvironment()` therefore correctly fails as `configuration` before database readiness, while the test expects `database`.

- Expected record tuple: `bootstrap / database / unknown`.
- Observed record tuple: `bootstrap / configuration / unknown`.
- Container acceptance exit: `255`.
- Executor-observed executable source: `4bcfd999e82d6299f5b9511910e05cb1df85a572d595669b130102b183008aa8`.

Correcting the fixture changes an approved test. CRITICAL policy requires recomputed Gate 2 and independent Gate 3; the owner-set maximum of two Gate 3 correction rounds is already exhausted. The candidate is therefore preserved but not published, reviewed at Gate 5, or sent to CI. `UNKNOWN` is not represented as GREEN.

## Remaining authorized action

Owner direction is required to allow one additional Gate 3 round for the fixture-only correction. Without that override, PR-ready delivery cannot proceed without either falsifying failure classification or bypassing the required review.
