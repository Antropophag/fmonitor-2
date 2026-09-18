# Текущая цель — №185, cross-platform local integration staging

Поручение владельца 2026-09-18: реализовать второй bounded slice [№185](https://github.com/Antropophag/fmonitor-2/issues/185) от актуального `main` после merge #189 и довести один candidate до PR-ready. Scope: cross-platform local integration staging и private container-readable delivery для действующих `make import-legacy` и `make sync-workforce`, включая реальные synthetic MariaDB/Bitrix checks, replay и failure-safe cleanup.

Валидный доступный host input/directory не отклоняется из-за exact mode bits; host `.env` не изменяется. Snapshot доставляется штатному container UID 10001 без раскрытия secret и удаляется после success/failure с сохранением importer exit status. Исправление проходит всю цепочку `.env` → staging → Make/Compose → PHP loader → importer.

Не входят VPN, import filters, engineers, chunking, повторная переделка owner provisioning, #182, universal secret manager, production runtime/session policy, harness/classifier/skip rules и architecture baselines. Чужие WIP/worktrees не менять. Production imports, external sends, merge/deploy/settings не выполнять. WSL/Docker Desktop проверяются только при доступности среды.

Контракт: [LOCAL-INTEGRATION-ENV-001](../../specs/LOCAL-INTEGRATION-ENV-001.md). Lifecycle: [cross-platform-local-integration-staging](../../openspec/changes/cross-platform-local-integration-staging/). Root пишет scope/spec/tests; отдельный `gpt-5.6-sol/low` executor реализует; независимые `gpt-5.6-sol/low` reviewers решают planner-required Gates 3/5. Локально только bounded focused checks; full `make test`/`make verify` запрещён. Exact-source GitHub CI выполняется один раз. PR использует `Refs #185`, не закрывает parent issue.

Предыдущий указатель первого slice №185 сохранён в Git history и merge #189; этот candidate содержит только второй slice.
