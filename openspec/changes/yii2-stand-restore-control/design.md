## Context

См. `proposal.md`. PR #119 поставил content-addressed bundle из `database.sql`, `artifacts.tar`, `sessions.json`, canonical restore manifest и verified pointer. `RuntimeRecovery` остаётся крупным legacy owner другого backup/restore формата, schema v22–v24 и отдельного CLI. Новый срез должен потребить именно protocol PR #119 и перенести restore control, не копируя монолит.

## Goals / Non-Goals

**Goals:** один application owner restore в `app/RuntimeRestore`; общий минимальный validator для backup/restore bundle protocol; deterministic recording driver для RED/GREEN fault boundaries; Yii2 console adapter; реальный focused roundtrip и consumer inventory.

**Non-Goals:** live driver/deployment, production cutover, общий cleanup recovery, изменение backup format, новые domain facts, migration policy либо harness policy.

## Decisions

1. Bundle admission выделяется из `StandBackupApplication` в один узкий protocol validator/value reader, возвращающий только независимо проверенные immutable bytes/metadata. Альтернатива — вызвать `stand-backup/verify` и затем перечитать bundle — оставляет TOCTOU и разделяет authority; копирование validation в restore создаёт расхождение контрактов.
2. `StandRestoreApplication` владеет operation/lease state machine и вызывает узкий restore driver port после полной preflight validation. Yii2 controller только парсит argv и отображает safe result/exit. Альтернатива — расширить `RuntimeRecovery::restore()` — сохраняет неверного production owner и второй bootstrap.
3. Restore публикует confirmed outcome только после DB import, persistent roots, AUTO_INCREMENT/schema checks и fresh readiness. Любой эффект после которого фактическое состояние нельзя доказать переводит operation в `OUTCOME_UNKNOWN`; lease и evidence сохраняются. Автоматический rollback не заявляется без отдельного доказанного snapshot target.
4. Recording driver использует реальные application/filesystem protocol paths и materialized SQL/artifact/session bytes; fault injection допускается только в test contour. Он не подменяет restore ответ fixture-result.
5. Legacy removal решается после GREEN по `rg` + executable architecture inventory и сравнению оставшихся runtime recovery contracts. Документация/review history не считается production consumer; forward-update/jobs recovery или runbook seam считается responsibility до явной замены.

## Risks / Trade-offs

- [Общий validator может перерасти в общий refactor] → extraction ограничивается exact PR #119 schemas, canonical reads и digest checks.
- [DB и filesystem effects нельзя сделать одной транзакцией] → explicit phase ledger, exclusive lease, no-success-on-ambiguity и post-effect readiness.
- [Recording roundtrip не доказывает live tooling] → в отчёте остаются отдельные operational drill, image tool availability и cutover obligations.
- [Legacy consumers могут требовать старый format/forward migration] → legacy сохраняется до отдельного replacement evidence; чеклист #76 не меняется по намерению.

## Migration Plan

1. Root создаёт normative executable spec/verification input и intended RED; независимый reviewer решает Gate 3.
2. Отдельный executor реализует minimal validator, restore application/driver contour и Yii2 wiring; выполняются только bounded focused checks.
3. Независимый reviewer решает Gate 5 на exact source; затем один Quality Graph CI может подтвердить PR-ready. Live stand не меняется.

Rollback для этого code-only slice — отмена candidate до merge либо последующий revert; никакой target mutation вне disposable test contour не разрешена.
