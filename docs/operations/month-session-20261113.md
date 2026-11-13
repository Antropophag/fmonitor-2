# Исправление внешнего входа и сессия30дней

Owner13.11.2026: «Давай исправим и сессию сделаем месяц». Контракт: [MONTH-SESSION-001](../../specs/MONTH-SESSION-001.md).

Диагностика на prod: runtime config совпадает с Git; Yii timeout604800 и persistent cookie Max-Age604800, SameSite=Strict. Auth/absolute timeout не заданы, remember-me отключён. После закрытия/запуска Chromium прямой вход сохраняется; внешний top-level link заменяет authenticated cookie гостевой. Изолированный тест с единственным изменением SameSite=Lax сохраняет вход. Primary probes/logs вне Git: `~/.local/share/fmonitor-2/session-diagnosis-20261113/`. Прод при диагностике не менялся; cookie/session tokens не публиковались.

Scope: bounded correction session config, lifetime/timeout2592000, SameSite=Lax; CSRF/Secure/HttpOnly/strict IDs и native storage остаются. Для старой cookie новые параметры выдаются при следующем входе. Никакого массового logout, смены ключей, удаления state или DB migration.

Режим: ранее заданный owner production-first, обычные gates/fullCI отложены и не считаются GREEN. Root пишет specification/tests; separate sol/low executor — config; независимый final reviewer. Эта ветка основана на main6664d5d8, но на prod меняется только config, поэтому ранее выложенное automatic order opening не откатывается.


## Выполнено

Root authored spec и новый реальный browser regression; `/root/month_session_executor` (gpt-5.6-sol/low) изменил только3 строки `config/yii/web.php`; `/root/month_session_final_review` независимо APPROVED без замечаний. [Review](../../reviews/code/MONTH-SESSION-001.md): candidate79887c615094e52c278bbcc74ae6e1334ad27187cf2142e8a7a01f4066e549a5, executable14df5746dd0ea1da84c8d96d0dbcbea7dcc7fdaa6514caaa7d375fa141264b28.

RED: настоящий login выдавал cookie7дней и новый regression остановился на ожидаемом сроке30дней. GREEN на точных candidate bytes: `yii2_month_session_001_test.php` (expiry, full persistent-profile restart, external GET, same-origin invalid CSRF, cross-site POST cookie exclusion, explicit logout) и `yii2_session_failure_001_test.php` (write/destroy failures). PHP lint и diff-check прошли. Existing admin browser expectation приведён к новой SameSite policy без удаления иных security assertions. Полный suite локально не запускался; fullCI и formal Gate3/automated admission owner-deferred/UNKNOWN.

Выкладка13.11.2026 16:49МСК: только `config/yii/web.php` на host и PHP-container, CAS по старому SHA256652e31e4ca786f5cdabbceb6ebd8f884299b9d23ed10985a90c5e33c98bd2cb1; новый reviewed SHA25694a3cfeff797c9869289faa8828f155d95105471150b9f12f4aee9c3586f0277. Private rollback copies0600: `/home/antropophag/.local/share/fmonitor-2/month-session-20261113/before-20261113T134917Z/`. PHP-FPM graceful USR2 reload, без остановки web/jobs/db, смены ключей или очистки session files. Предыдущее automatic order opening остаётся на prod.

Live effective Yii: timeout/lifetime2592000, SameSiteLax, Secure/HttpOnly/strict IDs true, autoLoginfalse. Внешний `/pilot/login` HTTP200 выдаёт `Max-Age=2592000; path=/pilot; secure; HttpOnly; SameSite=Lax`, `/health/ready`200. PHP/web/db/оба jobs healthy. Cookie values/session payloads не опубликованы. Старые cookie обновляются при следующем входе; пользователь уведомлён об однократном повторном входе.

Primary evidence вне Git: `~/.local/share/fmonitor-2/month-session-20261113/` (`red.log`, `focused.log`, `preflight.json`, `deploy-result.json`, `live-verification.json`). Post-review additions — только review/delivery/current-goal records; reviewed config/test bytes не менялись. Source сохранён отдельной веткой `codex/persistent-month-session`, merge/fullCI не выполнялись.

## Git integration 13 November 2026

Owner explicitly requested PRs through merge. PR292 initial head dd08855c1da160b3ad855ee6825cc8eeac2903f3, full CI43439035233: fast, unit, governance, e2e and integration2 passed; integration1 had exactly one regression, yii2_authentication_001_test.php expected obsolete SameSite Strict. Complete failed jobs/regression logs retained outside Git at ~/.local/share/fmonitor-2/pr292-293-20261113/. Root updates that expectation to contract Lax; no production code delta. Independent delta review and exact-source CI remain required before merge. Previous deferred-gate record is historical, not current CI approval.
