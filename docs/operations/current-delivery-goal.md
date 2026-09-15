# Текущая цель — №153 Slice A, semantic integration closure guard

Поручение владельца 2026-09-15: реализовать только Slice A [№153](https://github.com/Antropophag/fmonitor-2/issues/153) от актуального `main` `3c4dd015` и довести candidate до PR-ready. Scope: существующий change-verification planner консервативно распознаёт repository-owned high-risk semantic surfaces и до публикации требует category-level integration closure из canonical `tools/verification/suites.tsv`.

Не входят Slice B capability→consumer graph, Slice C Gate 3 completeness audit, Slice D CI feedback expansion, новый planner/registry/Gate, FAST changes, blanket integration для STANDARD/CRITICAL, product code, CI performance №136, №107 и rapid-pilot cleanup №141. Merge/deploy/settings не выполнять.

Контракт: [CHANGE-VERIFICATION-SEMANTIC-CLOSURE-001](../../specs/CHANGE-VERIFICATION-SEMANTIC-CLOSURE-001.md). Lifecycle: [require-semantic-integration-closure](../../openspec/changes/require-semantic-integration-closure/). Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён.

Предыдущий указатель №150 сохранён в [истории](current-delivery-goal-history-issue-150-2026-09-15-issue153-transition.md). Фактические source/PR/CI/lane получать через harness state и активный package. После Slice A №153 остаётся открытой для B/C/D.
