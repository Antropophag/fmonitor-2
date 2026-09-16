# Текущая цель — №160, T05.1 bounded server-rendered presentation FAST

Поручение владельца 2026-09-16: реализовать только T05.1 parent [№145](https://github.com/Antropophag/fmonitor-2/issues/145) как bounded child [№160](https://github.com/Antropophag/fmonitor-2/issues/160) от актуального `main` и довести candidate до PR-ready. Scope: ровно один mechanically proven FAST class `bounded-server-rendered-presentation`, public oracle, cases A–O и before/after independent-review-dispatch proxy.

FAST разрешён только для closed repository-owned server-rendered presentation owners с реально выбранным registered public oracle. Path/name, намерение агента и diff size не являются доказательством. Mixed/unknown, #132 sensitive и #153A semantic surfaces fail closed; более строгие механизмы имеют приоритет.

Не входят product code, test coverage, следующие T05.x, T06, #107, #141, CI performance, lifecycle redesign, новый planner/registry/Gate, AST/LLM classifier или dependency graph. STANDARD/CRITICAL Gates 3/5 и независимый FAST final review не меняются. Merge/deploy/settings не выполнять.

Контракт: [FAST-SERVER-RENDERED-PRESENTATION-001](../../specs/FAST-SERVER-RENDERED-PRESENTATION-001.md). Lifecycle: [fast-bounded-server-rendered-presentation](../../openspec/changes/fast-bounded-server-rendered-presentation/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз.

Незавершённый checkout №157 сохранён без изменений в исходном worktree; №160 ведётся в отдельном worktree от `origin/main`. Token usage остаётся `UNKNOWN`; измеряется только требуемое policy число independent review dispatches.
