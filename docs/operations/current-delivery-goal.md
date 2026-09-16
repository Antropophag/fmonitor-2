# Текущая цель — №164, deterministic known-CI triage

Поручение владельца 2026-09-16: реализовать только T03 parent [№145](https://github.com/Antropophag/fmonitor-2/issues/145) как bounded child [№164](https://github.com/Antropophag/fmonitor-2/issues/164) от `main` после merge PR №163 и довести candidate до PR-ready. Scope: existing public `harness state/wait` возвращает closed machine triage result для максимум двух доказанных signatures, fail-closed unknown neighbors и one same-source retry permission без нового waiter/store/orchestrator.

Initial signatures: exact PR №144 partial-result JSON race и exact existing MariaDB category precondition failure. Общие `JsonException`, timeout, connection reset, exit 1, flaky test или `SETUP_FAILURE` не являются signatures. Product code, test assertions, FAST classifier, #153, T06, publisher semantics, #97, #94 целиком, auto-push/merge/deploy/settings не входят.

Контракт: [DETERMINISTIC-KNOWN-CI-TRIAGE-001](../../specs/DETERMINISTIC-KNOWN-CI-TRIAGE-001.md). Lifecycle: [deterministic-known-ci-triage](../../openspec/changes/deterministic-known-ci-triage/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз. Merge/deploy/settings не выполнять.

Предыдущий указатель №157 сохранён в Git history. Token usage остаётся `UNKNOWN`; измеряются только mandatory log payloads materialized до decision, model-driven triage steps и automatic retry count.
