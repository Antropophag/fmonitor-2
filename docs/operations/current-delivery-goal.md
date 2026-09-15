# Текущая цель — №131, точный replay checklist operations

Поручение владельца 2026-09-15: реализовать [№131](https://github.com/Antropophag/fmonitor-2/issues/131) от актуального `main` `0a286f3e` до PR-ready. Scope: минимально исправить replay admission существующих native Yii2 checklist operations, отличных от `item_completed`, используя уже сохраняемый typed context и payload; normal duplicate и integrity/race recovery обязаны применять одну equivalence policy.

Не входят №35/№52/№132/№141, offline/client/service-worker/UI/Vue, общий idempotency/event/command redesign, schema migration, `rapid-pilot` как oracle/compatibility target, повторная реализация #130 и переписывание canonical `item_completed` owner.

Контракт: [CHECKLIST-OPERATION-REPLAY-001](../../specs/CHECKLIST-OPERATION-REPLAY-001.md). Lifecycle: [checklist-operation-replay-equivalence](../../openspec/changes/checklist-operation-replay-equivalence/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-selected Gate 3 и final review. Full local suite запрещён; bounded checks и один exact-source CI run.

Предыдущий указатель №38 сохранён в [истории](current-delivery-goal-history-issue-38-2026-09-15.md). Фактические source/PR/CI/lane получать через harness state и active package.

Delivery record: [issue-131-checklist-operation-replay-delivery.md](issue-131-checklist-operation-replay-delivery.md).
