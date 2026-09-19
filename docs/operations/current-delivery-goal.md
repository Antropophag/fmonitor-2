# Текущая цель — №196, `refresh-otiz-shlz-ui`

Поручение владельца 2026-09-18: реализовать №196 — привести активный Yii2-раздел ОТиЗ к цельному `shlz-ui`. Карточка объекта доставлена predecessor PR #195; этот bounded child охватывает только `/pilot/otiz/**`.

Base: `origin/main@af4e2ddb72a194ecfb114820f4134a34b20fdb39`; branch `codex/issue-196-shlz-ui`; worktree `/Users/antropophag/code/fmonitor-2-issue196`. Жизненный цикл: [refresh-otiz-shlz-ui](../../openspec/changes/refresh-otiz-shlz-ui/). Stable contract: [OTIZ-SHLZ-UI-001](../../specs/OTIZ-SHLZ-UI-001.md). Mutable source/PR/CI state принадлежит delivery harness.

Root пишет scope/spec/tests. Отдельный gpt-5.6-sol/low executor реализует. Независимые gpt-5.6-sol/low reviewers решают planner-required Gate 3 и Gate 5. Автономное авторство spec/tests другим агентом не разрешено. Все actual authors и source checkpoints фиксируются в delivery record.

Сохраняются routes, HTTP methods, CSRF, form fields, permissions, domain decisions, append-only facts, idempotency/concurrency и user return paths. Меняются presentation, information hierarchy, responsive behavior, accessibility states и bounded progressive motion. `../shlz-ui` — read-only public component oracle; rapid-pilot не получает новой логики.

Локально только bounded focused checks и `make architecture-check`; full `make test`/`make verify` запрещён. Один exact-source GitHub CI выполняется после focused GREEN и required reviews. UNKNOWN не является GREEN или approval.

Scope: workflow header; register/snapshot/history; object/evidence/violation/settlement regions; action hierarchy; labelled-row/contained-scroll strategies; 320/768/1024/1440, keyboard, coarse pointer, reduced motion и JS-off. Формулы, permissions, routes, payloads, idempotency/concurrency и append-only facts неизменны. Merge/deploy/settings не выполнять.
