# Publication checkpoint

#33 candidate code d7bac8dd is independently APPROVED FOR CI. Final retained browser run PASS and visual QA passed (screenshot68e5a924); PHP lint and architecture47/47 plus actual7rules all PASS. See production-runtime-delivery-2026-09-09.md. Root is committing review/evidence metadata and the screenshot wait, then creates draft PR/full CI. No production source changed after d7bac8dd.

#27 implementation now active in /Users/antropophag/code/fmonitor-2-operations-20260909, branch codex/runtime-operations-27; runtime_plan owns it, runtime_review independent. Native OTIZ and jobs worktrees have fast-forwarded to d7bac8dd with their own future planning retained. Current root browser/lint sessions are terminal PASS; no test project remains. Goal ACTIVE; main stand8092 preserved. Remaining #33: full CI, image digest and isolated persistent contour, normal merge/integration. Do not mark global goal complete after this PR.

---

# Current checkpoint before candidate publication

Goal ACTIVE; #33 code and focused acceptance complete, final exact-source review/CI/publication remain. Branch codex/production-runtime-33 in /Users/antropophag/code/fmonitor-2-runtime-20260909. All 47 architecture policy fixture tests pass, actual checker remains7 rules/no baseline change. Runtime tests cover configuration/storage, v22 and all process schema families, two real migration runners, FPM/nginx graceful stop, complete41-item/7-photo browser journey, exact restart history/session/files, admin flags and seeded OTIZ. Independent aggregate Gate3 and bounded Gate5 records are under reviews/. Root is staging the candidate; consult git for current SHA rather than historical entries below.

Remaining #33 steps: commit exact candidate, root final browser run with retained private screenshot/log, aggregate exact-SHA Gate5, draft PR/full CI, normal integration, isolated persistent contour. Main manual stand8092 stays untouched. No global production-readiness claim: existing native UI-to-OTIZ eligibility gap is explicitly preserved as next #24 work; financial formulas/A02/A03 unchanged.

Future work is separated:
- #24 native OTIZ planning, normative spec, genuine RED and Gate3: /Users/antropophag/code/fmonitor-2-native-otiz-20260909, branch codex/native-otiz-inputs-24 (currently baseline eb88ed2f; fast-forward onto #33 candidate before implementation).
- #34 jobs planning: /Users/antropophag/code/fmonitor-2-jobs-20260909, branch codex/background-jobs-34 (planning only, baseline eb88ed2f).
- #27 runbook/admin provisioning investigation remains documented here; implementation of initial-admin CLI is not yet delivered.

Agents: runtime_tests frozen after final browser/lifecycle evidence; runtime_review awaits exact candidate for aggregate Gate5; runtime_plan finished architecture guard and future planning. Do not assume a process live from old handles below; final root native migration and architecture sessions are terminal PASS. Original checkout has a pointer to this authoritative checkpoint; its WIP is preserved.

---

# Ночной прогресс — 2026-09-09

Goal ACTIVE без token budget: выполнять #33 → #27 → #34 → #36 → доступные технические срезы до возвращения владельца. Полное поручение: `continue-overnight-2026-09-09.txt`. Промежуточный PR не завершает очередь.

Checkout: `/Users/antropophag/code/fmonitor-2-runtime-20260909`, branch `codex/production-runtime-33`, baseline origin/main `eb88ed2f7edb4ad609662e9268e1d8defcb18ceb` (merge61). Fetch подтверждён; открытых PR нет, #33 OPEN. Старые checkout/WIP сохранены.

Текущая работа: #33, change `production-http-runtime`. Технический выбор: nginx + PHP-FPM, один application image для FPM/CLI, UID10001, отдельный compose в `deploy/runtime`, прямой DB config, canonical migration command под общим DB advisory lock. Production front controller сохраняет composite rapid router. Старый стенд/volumes не менять.

Агенты sol/low:
- runtime_plan: только `openspec/changes/production-http-runtime/`, proposal/design/spec/tasks.
- runtime_tests: только `tests/Runtime/`, RED configuration/package/migration concurrency и подбор browser harness.
- root: интеграция, runtime implementation после RED/test review, deployment verification.

Результаты: fresh main/worktree и активная Goal проверены; implementation/RED/CI ещё не готовы. Работающий пользовательский `fmonitor2-manual-pilot-1` healthy на8092; изолированный test DB healthy на23306. Контуры не изменены.

Далее: завершить исполняемый контракт/RED, независимый test review, применить runtime change, развернуть отдельный контур и проверить реальные маршруты/данные/restart; затем code review/PR/full exact-head CI и следующая задача.

Продуктовые вопросы: новых нет. A02/A03 не закрыты PR61, правила выплат ночью не изобретать. Ограничения: только тестовые внешние транспорты, без реальных отправок/imports/замены основного стенда. Готовность production не заявлена.

## Обновление: блокировка миграций

OpenSpec `production-http-runtime` planning-complete/strict-valid, apply ready (20 задач, ещё не завершены). Бounded независимый Gate3: `reviews/tests/PRODUCTION-RUNTIME-MIGRATION-LOCK-001.md` APPROVED. Root добавил whole-catalogue lock в CanonicalMigrationApplication через MariaDbMigrationLock; до preflight, немедленный отказ75 при contention, release после success/failure. `php tests/Runtime/migration_concurrency_lock_001_test.php` GREEN; PHP lint/diff-check PASS. Независимый code review продолжается, полный runtime Gate3 pending.

Baseline `make architecture-check` PASS7rules + HTTP qualification. PHP-FPM base image pulled: `php:8.5-fpm-bookworm` digest `sha256:81b9c405b013ebda0c9b8cd7a1a61424cf3627ca96348d93752a9b0539ce9a25`. TCPDF local dependency exact revision `fbbaf14cfae8fe646f154f7c530d15ec25764040` подготовлена вне tracked source.

Уточнения: canonical v22 должен добавить существующий legacy projection migration (отсутствовал в21), включая known10-column additive upgrade. Config canonical secret input остаётся FMONITOR_DB_PASSWORD; отдельный existing original reader использует0600 FMONITOR_ORIGINAL_DB_PASSWORD_FILE; prepare создаёт его только при отсутствии и отвергает mismatch. Production services db/migrate/php/web/prepare, nginx→php:9000, наружу8093 по умолчанию, graceful SIGQUIT60s. Агенты runtime_plan доводит normative spec, runtime_tests authored executable compose harness, runtime_review держит независимые gates. Основной стенд/данные не менялись.

## Runtime foundation checkpoint

Implemented app/Runtime config/storage/readiness, public/runtime.php and explicit prepare/check CLI. Native storage/config tests GREEN; v22, production migration runner and OTIZ runtime schema regressions GREEN. Bounded migration lock Gate5 APPROVED. Architecture check after implementation PASS7 + HTTP qualification. Full runtime Gate3 remains CHANGES_REQUESTED pending complete browser/restart/graceful evidence.

Agent runtime_plan finished deploy/runtime packaging and draft runbook; now planning ONLY #34 under openspec/changes/durable-background-jobs/. Root owns app/Runtime/bin/public/integration; runtime_tests extends tests/Runtime browser acceptance; runtime_review stays independent. Nginx has no DB credentials or private storage mounts; source is root-owned. Five Runtime tests registered in suites.tsv/categories.json/run.sh.

Intermediate image pinned as fmonitor2-runtime:foundation-59c4303, ID sha256:59c4303cb3b2a7d54b23e9c880d03bb605df6561e5679f9e0fa9f2aa5b2bc97c. NOT final exact-source candidate: a later lexical configuration validation still needs final rebuild. Compose safe log is /home/fmonitor/.local/state/fmonitor2/log/original-safe.jsonl. First three Compose runs found test path/progress-stderr mismatches only; reviewed corrections applied. Fourth root run handle23412, log /tmp/fmonitor-runtime-compose-fourth.log. Poll authoritative handle/process before any rerun.

Integration risk found by source inspection: PilotE2ECoordinator assumes HTTPS outside trustedDemo (origin and command cookie). runtime_tests prepares actual browser RED; fix must use explicit trusted scheme without demo loopback. Next: finish Compose/browser/FPM scheme, independent reviews, pinned candidate/PR/full CI. Main stand8092 and database volumes preserved and healthy. #27 runbook is validation-pending draft; #34 planning only.

## Lifecycle GREEN and current next step

Root full Compose lifecycle PASS at /tmp/fmonitor-runtime-compose-drain-fixed.log (image4063c7b5 before later schema metadata enhancement). Agent also ran current-source Compose build/lifecycle GREEN. FPM defect minimized outside repository: process_control_timeout=0 escalated graceful quit immediately; 55s completed real five-second deadline handler after5.078s. Added55s (<Docker60s) to global FPM config; independent bounded Gate3/Gate5 records PRODUCTION-RUNTIME-FPM-DRAIN-001 APPROVED. Upstream basis: https://raw.githubusercontent.com/php/php-src/PHP-8.5/sapi/fpm/fpm/fpm_process_ctl.c (timeout/escalation at169-194).

V22 review found real names-only predecessor preflight mutation on wrong-type columns. Root fixed shared metadata validation before ALTER; expanded tests additionally cover missing/wrong PK and MyISAM/latin1. InnoDB, utf8mb4 and id-onlyPK enforced; compatible utf8mb4 collations/nonunique indexes allowed. Expanded RED independently reviewed by runtime_tests (test authored runtime_review); focused preflight/frontier GREEN; bounded Gate5 APPROVED. Both metadata helpers remain under150 lines. Sixth Runtime test registered.

Current agents: runtime_tests MUST complete actual standalone production browser fixture/invocation (not just its unregistered mjs driver); root owns scheme fix after real public-seam RED. Rejected reflection-private test removed. runtime_review reviews core Runtime PHP independently. runtime_plan finished #34 planning and #27 admin provisioning investigation, now idle. #34 planning strict-valid, no implementation yet. Admin investigation path docs/operations/runtime-admin-provisioning-plan-2026-09-09.md. No PR/push/CI/merge yet. Main stand untouched.

Root architecture process handle56771 writes /tmp/fmonitor-runtime-architecture-current.log. Poll that handle or current process inventory before any rerun. Overall goal remains ACTIVE; full runtime Gate3/Gate5 still pending real browser/full persistence coverage.

## Protected browser and integration gap

Root public protected browser segment is GREEN after explicit FPM scheme and transient session-read fixes: login, selection, PDF, original correction/download, distinct opening,41 checklist facts,7 photos,85-to100 progress, zero browser errors. Native transient contention now has an additive WOULD_BLOCK outcome; only start/read can succeed after waiting, mutations retain fail-closed behavior. Old fault/clock tests remain GREEN; bounded scheme/session Gate5 APPROVED. Broader production migration runner and architecture/HTTP qualification all PASS after process metadata extraction; previous agent credential-error report was a missing test password override, not shared DB mutation.

runtime_tests reports expanded production browser GREEN including restart/admin/seeded OTIZ. Independent review requested stronger exact domain rows and reused owner-session hash; author owns that final test/evidence update. No root browser process currently live. Root logs: /tmp/fmonitor-runtime-browser-contention-fixed.log records passed journey followed by stale test observer mysqli (now author fixes reconnection); final author test outcome must be retrieved from its tool/session evidence.

Important existing #24 technical gap, NOT fixed or hidden by #33: newly completed native UI journey has no row in legacy fm2_assignment_orders; MariaDbNativePremiumInputs still requires status=registered there. Current original/selection/application owners persist separate canonical families. OTIZ acceptance tail explicitly seeds the established legacy registered order/installer fixture, and proves transport/runtime parity only. It does NOT prove automatic native UI-to-OTIZ eligibility. Carry this into PR limitations and next #24 technical slice; formulas/payment rules A02/A03 remain separate.

Root authoritative source is still uncommitted codex/production-runtime-33 at baseline eb88ed2f. No PR/push/CI/merge/deployment of main stand. Completed native tests are registered in verification catalogs; supplemental browser drivers are invoked by their harness. Next: final exact persistence/OTIZ test review, preserve visual evidence, commit #33 candidate, full CI and normal integration, then #27/#34/#36 and remaining technical queue. Original checkout has only a pointer to this authoritative checkpoint; WIP and manual stand8092 preserved.

## CI correction publication

PR62 OPEN/DRAFT. First run34295367260 on3cedf5cb terminalFAIL; allfailed job/file
inventories collected. Corrected current terminal-v22 fixtures and demo
compatibility;18 affected integration executables and38 governance cases PASS,
independent CI-correction review APPROVED. Root commits/pushes correction next.
#27 code in operations worktree has full Gate3/Gate5 APPROVED, no commit yet;
merge current #33 corrections into it before its CI because verification fixtures
and inventory changed. Do not repeat the old shared-DB credential speculation:
explicit fmonitor2_test_root_local test override works and shared testDB stayed
healthy. Main stand remains untouched. Goal ACTIVE, more backlog follows.
