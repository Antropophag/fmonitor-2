# Текущая цель — №132, sensitive offline paths не получают FAST

Поручение владельца 2026-09-15: реализовать [№132](https://github.com/Antropophag/fmonitor-2/issues/132) от актуального `main` после merge №135 и №153 Slice A и довести один bounded candidate до PR-ready. Scope: deterministic closed-set classification для sensitive checklist/offline Yii assets, запрещающая FAST и использующая существующий CRITICAL route, canonical inventory и direct oracle.

Не входят runtime/product изменения, service-worker/checklist/storage/replay/auth redesign, AST/function-level classifier, новый planner/registry/inventory/Gate, расширение FAST, №153B/C/D, №136, №141, №107 и №145. `rapid-pilot/` не читать и не изменять. Merge/deploy/settings не выполнять.

Контракт: [SENSITIVE-OFFLINE-VERIFICATION-001](../../specs/SENSITIVE-OFFLINE-VERIFICATION-001.md). Lifecycle: [protect-sensitive-offline-fast](../../openspec/changes/protect-sensitive-offline-fast/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только planner-selected focused checks; full `make test`/`make verify` запрещён.

Предыдущий указатель №153 Slice A сохранён в Git history. Фактические source/PR/CI/lane получать через harness state и активный package. После №132 техдолг-волна останавливается; следующий slice автоматически не начинать.
