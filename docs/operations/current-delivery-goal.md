# Текущая цель — пошаговая работа ОТиЗ в согласованном интерфейсе

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
