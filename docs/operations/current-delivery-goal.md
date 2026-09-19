# Текущая цель — №198, убрать дубли CI/E2E и лишние циклы review

Поручение владельца 2026-09-19: реализовать bounded issue №198 от актуального `origin/main`, довести до PR-ready без merge/deploy/settings. Base: `62d027d54af7a01a1da300eda2901ba68b2bab20`; branch `codex/issue-198-deduplicate-verification`; worktree `/Users/antropophag/code/fmonitor-2-issue198`.

Scope: удалить конкретный повторный запуск `yii2_preopening_browser_001_test.php` через SHLZ wrapper; добавить fail-closed reuse штатного PR-triggered Quality Graph run перед одним fallback dispatch; адресно сократить correction/review handoff. Не начинать аудит/переписывание harness, общий граф dedup, глобальную exactly-once семантику, telemetry или изменение workflow/branch settings/FAST/admission schemas.

Lifecycle: [deduplicate-verification-delivery](../../openspec/changes/deduplicate-verification-delivery/). Stable contract: [VERIFICATION-DELIVERY-DEDUPLICATION-001](../../specs/VERIFICATION-DELIVERY-DEDUPLICATION-001.md). Mutable source/PR/CI state принадлежит delivery harness.

Root авторит scope/spec/tests. Отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают Gate 3 и Gate 5. Фактических авторов и source checkpoints фиксировать в delivery record.

Локально только bounded focused checks и применимый architecture check; полный `make test`/`make verify` запрещён. Один exact-source GitHub CI выполняется штатным PR-trigger либо единственным fallback dispatch через новый guard. UNKNOWN не является GREEN/approval. Чужой WIP №157 и параллельные UI changes не менять.
