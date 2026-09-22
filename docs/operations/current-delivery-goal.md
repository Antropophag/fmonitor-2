# Текущая цель — issue #172, контекст и build identity обратной связи

Поручение владельца 2026-09-22: от актуального `main` `bced877a` исправить
существующую обратную связь перед ОПЭ: сохранять точный безопасный исходный
экран и server-owned identity реально работающей сборки. Это bounded follow-up
к #140 с учётом merged #228/#234/#235–#237, без новой системы обращений, release framework,
изменения ОТиЗ, readiness, статусов списка/дашборда, merge или deployment.

Контракт: [`FEEDBACK-001`](../../specs/FEEDBACK-001.md), lifecycle:
[`fix-feedback-context-build-identity`](../../openspec/changes/fix-feedback-context-build-identity/).
Worktree `/Users/antropophag/code/fmonitor-2-issue172`, branch
`codex/issue-172-feedback-context-build`. Root authored scope/spec/tests;
отдельный `gpt-5.6-sol/low` executor реализует production, независимые reviewers
принимают planner-selected Gate 3/final. Локальный full `make test`/`make verify`
запрещён; требуется один exact-source CI. Рабочий стенд и реальные обращения не
изменяются, внешних отправок нет.

Предыдущие цели #236 и bounded regular runtime readiness завершены merge и
сохранены ниже как история.

---

## Исторический указатель — единый этап объекта после checklist retraction

Поручение владельца 2026-09-22: от актуального `main` исправить расхождение карточки, очереди, server-side stage filters/count/pagination и диаграммы этапов после `completion_retracted`, затем довести отдельный candidate до PR-ready с independent review и exact-source CI.

Контракт: [CURRENT-CHECKLIST-STAGE-001](../../specs/CURRENT-CHECKLIST-STAGE-001.md). Lifecycle: [fix-current-checklist-stage](../../openspec/changes/fix-current-checklist-stage/). База аудита: `0504d2589835f2583dc9afdbc47e4694e2573365`; поставлено через merge PR #236.

Не входили checklist writers/offline/history, #171, редактор, фото, ОТиЗ, readiness, документы, сроки/справки, редизайн и остальные находки аудита.

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
