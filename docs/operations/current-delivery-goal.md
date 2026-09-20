# Текущая цель — stand-feedback correction Yii ОТиЗ и пагинации

Поручение владельца 2026-09-20: исправить выявленное на локальном стенде
неполное соответствие `refresh-otiz-shlz-ui` от актуального `origin/main` и
довести correction candidate до PR-ready. Base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192`,
branch `codex/fix-otiz-register-pagination`, worktree
`/Users/antropophag/code/fmonitor-2-otiz-correction`.

Scope: полный девятиколоночный Yii object register ОТиЗ; доказанные legacy-коды
Кшах без переписывания snapshot; корректные суммы/unknown presentation;
filters/sort/pageSize/server pagination; единая public `shlz-pagination`
composition для pageable Yii surfaces объектов, монтажников, стройконтроля и ОТиЗ.

Не входят новые финансовые формулы, schema/DDL, изменение RBAC и state-changing
owners, переписывание истории и redesign содержимого соседних экранов. FAST
выбирает только verification planner.

Lifecycle: [refresh-otiz-shlz-ui](../../openspec/changes/refresh-otiz-shlz-ui/).
Contracts: [OTIZ-SHLZ-UI-001](../../specs/OTIZ-SHLZ-UI-001.md),
[OTIZ-OBJECT-REGISTER-PAGING-001](../../specs/OTIZ-OBJECT-REGISTER-PAGING-001.md).
Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует;
независимые reviewers выполняют planner-required reviews.

Локально только bounded focused checks и применимый architecture check; полный `make test`/`make verify` запрещён. Один exact-source GitHub CI через выбранный existing consumer. UNKNOWN не является GREEN/approval.
