# Текущая цель — pre-Gate-3 fixture reachability safeguard

Поручение владельца 2026-09-17: реализовать утверждённый OpenSpec change `require-fixture-reachability-before-gate3` от актуального `origin/main` `764f2c0f2118a8c8f8cdb7b8235fb360e982bb13` и довести отдельный candidate до PR-ready без merge.

Scope: только явно applicable intended-RED tests получают bounded read-only fixture reachability control. Gate-3 preparation требует отдельные exact-source `INTENDED_RED` и `FIXTURE_REACHABLE` evidence; setup/crash/fixture defects блокируются до Gate 3 без product GREEN prerequisite.

Контракт: [INTENDED-RED-FIXTURE-REACHABILITY-001](../../specs/INTENDED-RED-FIXTURE-REACHABILITY-001.md). Lifecycle: [require-fixture-reachability-before-gate3](../../openspec/changes/require-fixture-reachability-before-gate3/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз.

Не входят frozen #20, #153C/D, T07/#107, product changes, generic instrumentation/rewrite test architecture, новый Gate/framework/evidence store, LLM analysis. После отдельного PR-ready остановиться; merge/deploy/settings не выполнять.
