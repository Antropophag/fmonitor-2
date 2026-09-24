# Issue #258 stage one delivery

- Base: `d9dddb31f9c6e07092bcf6d4c04df761a1a13ccd` (`origin/main`, includes PR #260).
- Branch/worktree: `codex/issue-258-installer-utilization-stage1` / `/Users/antropophag/code/fmonitor-2-issue-258-stage1`.
- Authorization/authors: normal mode; root authored scope, normative/OpenSpec specs and tests. Production is reserved for a separate `gpt-5.6-sol / low` executor; Gate 3/final are reserved for independent agents.
- Rejected predecessor: `codex/installer-utilization-visibility` was inspected only as reusable evidence. Its old base, dirty source, UX and approvals are not carried forward.
- Planner: CRITICAL, required reviews `gate3` and `final`; package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T184422Z-1fd3582531/package.json`. Scope changes require refresh.

## Gate 2 RED — 2026-09-24

- `php tests/Yii2/yii2_installer_utilization_stage_one_001_test.php` — exit 255, intended behavior failure: installer card returned 404 before confirmed-original/upcoming assertion.
- `php tests/Yii2/yii2_installer_utilization_surfaces_001_test.php` — exit 255, intended behavior failure: server rejected `load=working` with 400 before current-work route assertions.
- `php tests/Yii2/yii2_installer_utilization_browser_001_test.php` — exit 255, intended browser behavior failure: directory utilization filter was unavailable.
- Earlier missing `vendor/autoload.php` was setup failure, not RED. Worktree-local locked Composer dependencies were installed; no full suite was run.

## Current state

Gate 1 and RED candidate are prepared. Gate 3 is pending. Production, focused GREEN, final review, PR and exact-source CI are not started. Stand 8093, merge and deployment remain untouched.
