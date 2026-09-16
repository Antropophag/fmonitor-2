# Текущая цель — №123 slice B, strict intended-RED provenance

Поручение владельца 2026-09-16: продолжить [№123](https://github.com/Antropophag/fmonitor-2/issues/123) отдельным bounded slice B от `main` `11f0fcb446d8fbd0cbfa2c88caf2e0888b9819cf` и довести candidate до PR-ready. Владелец явно подтвердил автономное выполнение этого assignment до PR-ready; merge/deploy/settings не авторизованы.

Scope: запретить ложный `INTENDED_RED`, когда expected marker встречается только в argv, command echo, serialized metadata либо wrapper diagnostic, а launcher/setup не достиг acceptance behavior. Canonical contract: [INTENDED-RED-OBSERVATION-PROVENANCE-001](../../specs/INTENDED-RED-OBSERVATION-PROVENANCE-001.md). Structured evidence существующего runner предпочтительнее post-hoc regex filtering; raw command verdict, exit code и diagnostics сохраняются.

Не входят slice A container dependency visibility, slice C worktree identity guard, evidence redesign, product tests, Gate semantics вне intended-RED admission, arbitrary nonzero admission, FAST/T06/T03, semantic log parser, LLM и `rapid-pilot/`. Параллельные WIP не смешивать. Merge/deploy/settings не выполнять.

Lifecycle: [reject-wrapper-only-intended-red](../../openspec/changes/reject-wrapper-only-intended-red/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз.

Предыдущий указатель №162 сохранён в Git history. Slice A и параллельные №136/T06/T03 не включаются в candidate. Token usage остаётся `UNKNOWN`.
