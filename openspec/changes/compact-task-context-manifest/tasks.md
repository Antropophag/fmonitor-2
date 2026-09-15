## 1. Contract и baseline

- [x] 1.1 Root фиксирует canonical spec, verification input и before-measurement для трёх representative completed changes; verification: deterministic report перечисляет mandatory sources, bytes/chars, full-doc count, unrelated/history notes и `token_usage: UNKNOWN`.
- [x] 1.2 Root добавляет executable RED cases A–L через public `harness.py prepare/package` route; verification: focused test падает только из-за отсутствующего task-context manifest behavior.
- [x] 1.3 Независимый sol/low reviewer проверяет scope/spec/tests/RED и записывает Gate 3 findings/APPROVED; unresolved finding блокирует executor.

## 2. Minimal implementation

- [ ] 2.1 Executor реализует deterministic boundary profiles и safe canonical section index с full-document fallback; verification: UI, persistence, auth, harness и unknown fixtures проходят без изменения planner/FAST/coverage.
- [ ] 2.2 Executor materializes digest-bound manifest и exact required content в existing package directory, связывает их с `package.json` и active binding/state; verification: fresh/repeat/stale/index-invalid/reconstruction tests проходят.
- [ ] 2.3 Executor добавляет deterministic three-replay measurement и delivery record; verification: before/after bytes/chars/full-doc/load-on-demand output воспроизводим, bounded case меньше, sensitive context сохранён, tokens `UNKNOWN`.
- [ ] 2.4 Запустить planner-selected focused checks, OpenSpec strict validation, architecture check и `git diff --check`; canonical full `make test`/`make verify` локально не запускать.

## 3. Independent review и publication

- [ ] 3.1 Независимый sol/low final reviewer проверяет exact candidate, cases A–L, context completeness, no second policy/planner и measurement honesty; все findings разрешены до APPROVED.
- [ ] 3.2 Подготовить PR с exact source, запустить один exact-source GitHub CI consumer и собрать полный failed-job/REGRESSION_FAILURE inventory при failure; merge/deploy/settings не выполнять.
