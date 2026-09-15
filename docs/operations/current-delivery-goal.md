# Текущая цель — №52, самостоятельное закрепление инженера стройконтроля

Поручение владельца 2026-09-15: реализовать [№52](https://github.com/Antropophag/fmonitor-2/issues/52) от актуального `main` `3d213666` до PR-ready. Текущее закрепление инженера — самостоятельный native append-only fact; распоряжение хранит immutable historical snapshot. До первого standalone fact разрешён только read-only bootstrap из последнего подтверждённого native application.

Контракт: [YII2-CONTROL-ENGINEER-ASSIGNMENT-001](../../specs/YII2-CONTROL-ENGINEER-ASSIGNMENT-001.md). Lifecycle: [standalone-control-engineer-assignment](../../openspec/changes/standalone-control-engineer-assignment/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-selected gates. Full local suite запрещён; используются bounded focused checks и один exact-source CI run.

Не входят rapid-pilot, legacy assignment migration, district rules, #20/#45/#14/#131/#141, checklist/offline, generic assignment/event framework, redesign order/application и unrelated refactor. №40 переключается только в current-engineer reads; №38 сохраняет application ownership installer composition.

Предыдущий pointer №131 сохранён в [истории](current-delivery-goal-history-issue-131-2026-09-15-issue52-transition.md).

Delivery record: [issue-52-control-engineer-assignment-delivery.md](issue-52-control-engineer-assignment-delivery.md). Live source/PR/CI/lane получать через harness state и активный package.
