# WORKFORCE-CANONICAL-RUNNER-001 — collation GREEN evidence

- Дата: `2026-09-02`
- Supersedes implementation state reviewed in
  `reviews/code/WORKFORCE-CANONICAL-RUNNER-001.md` (`CHANGES_REQUESTED`)
- Reviewed RED: `docs/operations/workforce-canonical-runner-collation-red-evidence.md`
- Test review: `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-collation.md`
- Verdict: `GREEN — AWAITING_FRESH_CODE_REREVIEW`

Minimal correction keeps schema ownership in canonical migrations. Clean v2
catalog creation now uses the validated database-default `utf8mb4` collation;
v5 creates its three new tables with that same collation. Runtime bootstrap and
importer remain read-only consumers: no workforce `ALTER` or direct migration
invocation was restored.

The full public-runner matrix passes on an isolated MariaDB database explicitly
using `utf8mb4_unicode_ci`, including clean v1–v5, repeat, partial recovery,
conflicts, unexpected DDL failure and prefix boundaries. Workforce schema v0.3,
catalog and production composition tests pass. `make verify` now passes setup,
migrate, architecture, lint and unit stages and reports only the established
eight DB regressions plus the same duplicated E2E artifact failure; no new
workforce/canonical migration regression remains.

Checks:

- `git diff --check` — PASS
- `make architecture-check` — PASS (7 rules, including absolute workforce migration ownership)
- `openspec validate register-workforce-history-canonical-v5 --strict` — PASS
- `make verify` — expected baseline only: DB 8, E2E duplicate 1
