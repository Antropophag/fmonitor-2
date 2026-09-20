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

## Owner override — 2026-09-20

The owner authorized exactly one additional Gate 3 delta-review for a fixture-only correction. The correction must prepare the complete production runtime contract in isolated test directories, prove a healthy `public/runtime.php` baseline first, and keep each fault isolated to its intended branch. Historical RED and prior review history remain authoritative and are not reset.

The corrected test prepares the full runtime storage contract with `RuntimeStorage::prepare`, proves the healthy canonical bootstrap first, and then runs each isolated fault. Official lineage links historical RED `1789891766777353000-c9162e1d1e55449facca42334ae2455a` to GREEN `1789891790114348000-bad606477b8d4a9180f641b9bacf9d38` with delta SHA-256 `fb95f4f0730cbcade05cf0b9d2d6c42c4b86c17a7e94576391e6c68f4dc96248`. The independent authorized delta review is `APPROVED` with no findings.

Current focused verification:

- container HTTP/log acceptance: GREEN, record `1789891945082992000-364bc58b63404d07bea7fc2120955fdf.json`;
- change-verification governance: GREEN, record `1789891966666880000-fd480891d83c4c8cbddcf2a062f71a77.json`;
- architecture guard: GREEN, record `1789891993865002000-9372c5b2d0f0413ca32280493bd2fae5.json`;
- `make architecture-check`: PASS (7 rules; existing/advisory file-size notices only).

## Owner override after Gate 5 — 2026-09-20

For issue #173 only, the owner replaced the aggregate Gate 3 limit and the consumed fixture-only exception. Production and public HTTP/log regression corrections for findings inside the original #173 contract may proceed autonomously with required Gate 3 delta reviews. Two unsuccessful corrections of the same blocker remain the stop limit; contract, authorization, or scope expansion still requires an owner decision.

Gate 5 found two handled-result omissions in `ExecutionController`: portal `dependency_unavailable` lacked correlation and stable `persistence_failure` fell through to `unexpected`. Root added public HTTP/factual-log regressions; retained RED `1789892548321767000-eeeea7d05911435f935417a4963114ae` fails on the missing portal ID. Independent Gate 3 delta review approved both cases with no findings. Executor corrected both branches and audited the remaining handled 503 returns inside the agreed files without finding another omission.

Post-correction focused evidence:

- container HTTP/log acceptance: GREEN, record `1789892745007367000-6ea283a053464942868c4a411f05dc4c.json`;
- change-verification governance: GREEN, record `1789892766029712000-ba5c1b0ff36143bea19506b69c67fb2f.json`;
- architecture guard: GREEN, record `1789892793020803000-350e6523246643f2aeee4d56040ba4ca.json`;
- `make architecture-check`: PASS (7 rules; advisories only).
