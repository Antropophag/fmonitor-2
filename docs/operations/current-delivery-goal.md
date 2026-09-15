# Текущая цель — №157, compact deterministic task-context manifest

Поручение владельца 2026-09-16: реализовать только T02 parent [№145](https://github.com/Antropophag/fmonitor-2/issues/145) как bounded child [№157](https://github.com/Antropophag/fmonitor-2/issues/157) от актуального `main` и довести candidate до PR-ready. Scope: baseline трёх completed changes, deterministic digest-bound manifest в existing harness `prepare/state/package`, public role-package integration, cases A–L и before/after context-byte measurement.

Canonical documents остаются единственным source of truth. Manifest — exact index/package без generative summaries, LLM/NLP selection, второго policy/planner, per-issue metadata или массовой перестройки документации. Unknown applicability и unsafe sectioning fail-safe требуют полный canonical source. Security/auth/persistence instructions нельзя скрывать.

Не входят product code, FAST classifier/coverage/Gates, остальные T03–T14 №145, #107 целиком, root rotation, supervisor #95, CI performance #136, rapid-pilot cleanup #141 и lifecycle redesign. Параллельную №136 не менять. Merge/deploy/settings не выполнять.

Контракт: [TASK-CONTEXT-MANIFEST-001](../../specs/TASK-CONTEXT-MANIFEST-001.md). Lifecycle: [compact-task-context-manifest](../../openspec/changes/compact-task-context-manifest/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз.

Предыдущий указатель №132 сохранён в Git history; параллельный №136 имеет отдельную ветку и не включается в этот candidate. Token usage остаётся `UNKNOWN`; допустимы только measured mandatory context bytes и expected token-pressure reduction.
