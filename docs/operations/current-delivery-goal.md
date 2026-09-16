# Текущая цель — №162, minimal FAST maintenance lifecycle

Поручение владельца 2026-09-16: реализовать только T06 parent [№145](https://github.com/Antropophag/fmonitor-2/issues/145) как bounded child [№162](https://github.com/Antropophag/fmonitor-2/issues/162) от `main` после merged T05.1 [PR №161](https://github.com/Antropophag/fmonitor-2/pull/161), base `fa1dfa8dba9b20eeaa1c072c5c351acada29b615`, и довести candidate до PR-ready.

T06 добавляет в public harness deterministic `FAST_MAINTENANCE | OPENSPEC_REQUIRED` routing и compact existing record integration. Shortcut разрешён только после authoritative planner-selected FAST, для восстановления существующего canonical requirement с digest и `semantic_change=false`. Executable RED, один independent final review и exact-source CI сохраняются. Missing/conflicting/semantic/sensitive/unknown и любой non-FAST change используют обычный OpenSpec lifecycle; T06 не классифицирует FAST сама.

Не входят расширение T05.1 classifier, изменения #132/#153A, ослабление STANDARD/CRITICAL, migration historical artifacts, второй registry/docs platform, LLM semantic classifier, T02/#107/#141, product code, merge/deploy/settings.

Контракт: [FAST-MAINTENANCE-LIFECYCLE-001](../../specs/FAST-MAINTENANCE-LIFECYCLE-001.md). Lifecycle: [minimal-fast-maintenance-lifecycle](../../openspec/changes/minimal-fast-maintenance-lifecycle/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз.
