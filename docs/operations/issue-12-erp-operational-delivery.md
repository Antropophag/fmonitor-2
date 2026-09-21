# Delivery — issue #12, ERP equipment facts operational contour

## Authorization and authors

- Owner authorization: 2026-09-21, новая delivery-задача от свежего `origin/main`; разрешено state-preserving обновить только локальный стенд `127.0.0.1:8093`.
- Root author: scope, OpenSpec artifacts, normative specification amendment, verification input, RED tests и orchestration.
- Executor: отдельный `gpt-5.6-sol/low`, production implementation после применимого Gate 3.
- Independent reviewers: отдельные `gpt-5.6-sol/low` для planner-required Gates 3/5; фактические agent ids и verdicts будут записаны после выполнения.
- Initial baseline: `45fa7dee3ae8703295546142c4ce08c7f1d662de` (merged PR #216); pre-publication rebase baseline: `6e6ccbdbd4fa4d676fe94e9244e44aaaf411a36d` (`origin/main`, includes PR #217).
- Preserved WIP: исходный `/Users/antropophag/code/fmonitor-2` остаётся на `codex/issue-157-task-context-manifest` с независимыми dirty/conflicted изменениями; ERP candidate ведётся в `/Users/antropophag/code/fmonitor-2-erp-operational` на `codex/issue-12-erp-operational`.

## Outcome and boundaries

Цель — canonical `scheduler/manual → durable job → worker → bounded read-only ERP → EquipmentFactsApplication → projection/card` с direct `.env`, truthful readiness и реальным successful hourly run на сохраняемом стенде. Source oracle — только `../fmonitor/application/controllers/Integration.php::shlz_prodorders`; legacy persistence/controller logic не переносится.

Не входят merge, внешний deployment, reset/delete volumes, полный ERP catalogue scan, приблизительное matching, изменение installation process facts, новый integration framework или новые реальные secrets в tracked files/evidence.

## Delivery state

- OpenSpec: `openspec/changes/operationalize-erp-equipment-sync/`.
- Normative contract: `specs/ERP-EQUIPMENT-FACTS-001.md`.
- Verification input: `openspec/changes/operationalize-erp-equipment-sync/verification-input.json`; planner lane `CRITICAL`, required reviews `gate3` and `final`; root package `20260921T090514Z-b761d07382` was refreshed after test registration/scope expansion before review.
- Gate 2 RED: worker handler exits at exact intended claim rejection (`CONFIGURATION_INVALID` before ERP); bounded source returns `failed` instead of accepting local candidates; direct env, complete startup/readiness and canonical manual composition fail at their intended missing behavior. Existing application owner mapping/change/diagnostic regressions remain GREEN, proving setup and inherited authoritative semantics.
- Gate 3: pending/UNKNOWN.
- Implementation/focused GREEN: pending/UNKNOWN.
- Local stand 8093 qualification: state-preserving update completed; db/php/web/jobs-worker/jobs-scheduler are healthy through process readiness. Automatic hourly ERP job `7` completed at `2026-09-21T10:30:04.251122Z`, safe run `5d8fd71e-c7f0-4469-9503-394dfbf84ad0`, matched `4`. Protected cards 1226, 1427, 2238 and 2239 return 200 with ERP block and last-success; 1318 has no projection/last-success. Repeated identical run kept history at 9 rows. Pre/post object count remained 334 and sessions/artifacts were preserved.
- PR/exact commit/CI: pending/UNKNOWN.
- Gate 5: pending/UNKNOWN.

UNKNOWN не является approval или GREEN. Raw credentials, DSN, SQL, source rows и raw `zavnumber` не записываются в этот record.
