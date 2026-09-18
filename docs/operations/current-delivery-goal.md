# Текущая цель — `refresh-yii2-shlz-ui`

Поручение владельца 2026-09-18: приостановить №157 и привести активный Yii2 UI FMonitor к цельному `shlz-ui`. Первые критичные поверхности — карточка объекта и ОТиЗ; затем тот же action/state vocabulary распространяется на остальные активные экраны.

Source: `origin/main@5bc6a2254bfaa4f5abef283795189d83f98348e3`, branch `codex/ui-shlz-refresh`, worktree `/Users/antropophag/code/fmonitor-2-main-stand`. Жизненный цикл: [refresh-yii2-shlz-ui](../../openspec/changes/refresh-yii2-shlz-ui/). Stable contract: [SHLZ-OPERATIONAL-UI-001](../../specs/SHLZ-OPERATIONAL-UI-001.md). История паузы №157: [transition record](current-delivery-goal-history-issue-157-2026-09-18-ui-transition.md).

Root пишет scope/spec/tests. Отдельный gpt-5.6-sol/low executor реализует. Независимые gpt-5.6-sol/low reviewers решают planner-required Gate 3 и Gate 5. Автономное авторство spec/tests другим агентом не разрешено. Все actual authors и source checkpoints фиксируются в delivery record.

Сохраняются routes, HTTP methods, CSRF, form fields, permissions, domain decisions, append-only facts, idempotency/concurrency и user return paths. Меняются presentation, information hierarchy, responsive behavior, accessibility states и bounded progressive motion. `../shlz-ui` — read-only public component oracle; rapid-pilot не получает новой логики.

Локально только bounded focused checks и `make architecture-check`; full `make test`/`make verify` запрещён. Один exact-source GitHub CI выполняется после focused GREEN и required reviews. UNKNOWN не является GREEN или approval.

Owner exception 2026-09-18: после многократных Gate 3 returns владелец явно поручил начать implementation object-card slice без ещё одного Gate 3 cycle. Gate 3 остаётся `DEFERRED_BY_OWNER`, а не APPROVED; separate executor, focused checks, independent final review и exact-source CI сохраняются.
