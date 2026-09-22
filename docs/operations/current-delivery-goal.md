# Текущая цель — единый визуальный договор активного Yii2-приложения

Поручение владельца 2026-09-22: взять в работу бриф
`frontend-audit-2026-09-22.md` и применить change `unify-yii2-shlz-ui` от
актуального `origin/main` `bced877aec8a8802e97037749ca4251d3098df1a` как один
согласованный delivery scope. Подтверждённые source findings V01–V09, общий
shell, tables, fields, overlays и feedback states исправляются foundation-first;
все 25 активных Yii2 views проходят общую responsive/accessibility приёмку.

Это более новое owner-решение расширяет исходную #197 в части ОТиЗ и единого
координируемого прохода. #197 остаётся parent/backlog reference; её прежнее
исключение ОТиЗ и требование отдельных PR-slices не ограничивают этот change.
Небольшие внутренние коммиты и проверки сохраняются.

Контракт: [YII2-SHLZ-VISUAL-CONTRACT-001](../../specs/YII2-SHLZ-VISUAL-CONTRACT-001.md).
Lifecycle: [unify-yii2-shlz-ui](../../openspec/changes/unify-yii2-shlz-ui/).
Рабочая ветка `codex/unify-yii2-shlz-ui` и отдельный worktree
`/Users/antropophag/code/fmonitor-2-yii2-shlz-unification` созданы от exact
`origin/main`; незавершённый WIP №157 в исходном checkout не изменяется и не
входит в candidate.

Root пишет scope/spec/tests; отдельный `gpt-5.6-sol / low` executor реализует;
независимые `gpt-5.6-sol / low` reviewers решают planner-required Gates 3/5.
До стабилизации общего `pilot.css`/`ViewSupport.php` один executor владеет shared
foundation. Локально только bounded focused checks и browser sweeps; full
`make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз.

Не входят новая доменная логика, маршруты, права, payloads, формулы, history,
offline/storage/protocol, обновление или fork `shlz-ui`, SPA/grid framework,
`rapid-pilot`, imports, merge и deployment. Непроверенные browser consequences
остаются `UNKNOWN`, а не дефектом или GREEN.

---

## Исторический указатель — единый этап объекта после checklist retraction

Поручение владельца 2026-09-22: от актуального `main` исправить расхождение карточки, очереди, server-side stage filters/count/pagination и диаграммы этапов после `completion_retracted`, затем довести отдельный candidate до PR-ready с independent review и exact-source CI.

Контракт: [CURRENT-CHECKLIST-STAGE-001](../../specs/CURRENT-CHECKLIST-STAGE-001.md). Lifecycle: [fix-current-checklist-stage](../../openspec/changes/fix-current-checklist-stage/). База аудита и актуальный `origin/main`: `0504d2589835f2583dc9afdbc47e4694e2573365`.

Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз.

Не входят checklist writers/offline/history, #171, редактор, фото, ОТиЗ, readiness, документы, сроки/справки, редизайн и остальные находки аудита. Merge/deploy не выполнять.

---

## Исторический указатель — bounded regular runtime readiness

Поручение владельца 2026-09-22: от актуального `main` с merged PR #226 доставить
небольшой PR-ready фикс избыточной idle-нагрузки `/health/ready`. Контракт:
[`RUNTIME-READINESS-LOAD-001`](../../specs/RUNTIME-READINESS-LOAD-001.md), lifecycle:
[`bound-regular-runtime-readiness`](../../openspec/changes/bound-regular-runtime-readiness/).

Scope: existing full schema runtime-check становится обязательным deployment
startup gate; steady readiness ограничивается current DB connect/cheap query,
local resources и exact DB/schema/build-bound startup result. Сохранить liveness,
routes, response/status, canonical migrations and fingerprints. Не входят
business/UI, #171, ОТиЗ, integrations, общий infrastructure tuning или stand.

Работа идёт в отдельном worktree `fmonitor-2-readiness-load` от `3c242f34` (merge
PR #226), с отдельными Compose resources. Root пишет scope/spec/tests; separate
gpt-5.6-sol/low executor implements; independent reviewers решают Gates 3/5.
Локальный full `make test`/`make verify` запрещён; exact-source CI обязателен.

Предыдущая цель #222 завершена merge PR #226; её запись сохранена ниже как история.

---

## Исторический указатель — завершённые #222 и ОТиЗ

### Пошаговая работа ОТиЗ в согласованном интерфейсе

Поручение владельца 2026-09-21: реализовать bounded product slice `otiz-guided-native-interface` от актуального `origin/main` и довести candidate до PR-ready. Актор — сотрудник ОТиЗ; цель — самостоятельно пройти существующий процесс от выбора расчётной даты до проверки, подтверждения, получения XLSX и регистрации уже выполненной выплаты. Обязательная коррекция — черновик, отсутствие blocker и нулевая доступная сумма не могут отображаться как «Выплата по объекту выполнена».

Canonical contract: [`OTIZ-GUIDED-WORKFLOW-001`](../../specs/OTIZ-GUIDED-WORKFLOW-001.md). Lifecycle: [`otiz-guided-native-interface`](../../openspec/changes/otiz-guided-native-interface/). Выбранный visual oracle — вариант B в `/Users/antropophag/.local/share/fmonitor-2/prototypes/otiz-redesign/`; prototype и `rapid-pilot` не задают финансовые правила.

Рабочая ветка `codex/otiz-guided-workflow` в отдельном worktree `/Users/antropophag/code/fmonitor-2-otiz-workflow` была создана от `c8bfc42d774bbe52a6528eb44a371c8a9003e6d3` и 2026-09-22 state-preservingly fast-forwarded до актуального `origin/main` `3c242f34e8f30986f1b8354c4ef947a4c63936dc`. Исходный checkout и WIP #157 не изменяются; прежние цели сохранены в Git history. Root пишет scope/spec/tests; отдельный `gpt-5.6-sol / low` executor реализует, независимые `gpt-5.6-sol / low` reviewers решают planner-required Gates 3/5.

Scope: native views/navigation/assets ОТиЗ, минимальные read-only projection additions и адресные tests. Не входят object card/editor/history и effective-values write/read ownership #222, `MariaDbNativePremiumInputs*`, формулы/нормативы/округление, schema, новые финансовые команды, переписывание snapshots/ledger, SPA, merge, deployment и реальные финансовые действия.

До создания или публикации PR владелец должен увидеть candidate на локальном стенде `http://127.0.0.1:8093`, включая `/pilot/objects/1427` и связанные экраны ОТиЗ. Изменяющие проверки выполняются только на изолированных fixtures, не на пользовательских записях стенда. Локально разрешены только bounded focused checks и Playwright; full `make test`/`make verify` запрещён. После owner checkpoint и независимого Gate 5 выполняется один exact-source CI run.

Текущая стадия на 2026-09-22: dirty UI candidate реализован отдельным executor и
state-preservingly показан на локальном стенде 8093 после серии owner-directed
visual corrections. Полный continuation state, все закреплённые решения,
артефакты и готовый промпт новой сессии:
[`otiz-guided-native-interface-handoff-2026-09-22.md`](otiz-guided-native-interface-handoff-2026-09-22.md).
После интеграции на `3c242f34` planner-listed bounded checks GREEN, включая
root-authored archive-link expectation. Перед PR нужно получить
явный owner checkpoint получен 2026-09-22; далее нужны refreshed exact-source
Gate 3 и Gate 5, PR и один required CI run.
PR/CI/merge/production deployment — `UNKNOWN`; это не approval и не GREEN.

#222 вошёл в `origin/main` через PR #226. Публичный read seam
`MariaDbEffectiveObjectDetails` теперь реализован; этот slice не создаёт
второй effective-values mechanism и не меняет `MariaDbNativePremiumInputs*`.
Пересечения в shared assets/policy интегрированы механически; semantic
совместимость подлежит bounded checks и independent exact-source review.
