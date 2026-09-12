# Yii2 OTIZ workflow delivery — 2026-09-12

Issue: #76. Change: `yii2-otiz-workflow`. Spec: `YII2-OTIZ-WORKFLOW-001`.

Owner/root authored scope, normative spec and tests. Independent sol/low Gate 3
approved the complete test-only source, including the final fixture correction.
A separate sol/low executor implemented the Yii2 adapters and moved reusable
evidence owners to `app/Otiz`; root did not author production implementation.

Candidate behavior: calculate → inspect → accept → XLSX → payment/reverse plus
reconciliation, quarantine, active baselines and historical replay use Yii2 HTTP.
State changes delegate to existing application owners; direct rapid-pilot remains
only a behavioral oracle. Production `public/runtime.php` selects Yii and runtime
include evidence excludes `RapidPilotOtiz` for every OTIZ route.

Focused evidence: mapped application/HTTP/browser tests GREEN; architecture-check
7 rules GREEN; visual contract GREEN; deployment Compose/restart focused check
GREEN. Full local `make test`/`make verify` intentionally not run under owner
decision 2026-09-11. Final independent Gate 5 is `APPROVED` on reviewed source
`3354d333fc0f95a040424b4d3f415cfc439610df37d9b173eb928befb8212721`.
PR and exact-source CI remain pending before merge-ready status.

Historical PR103 run `34702588442` failed uniformly at CI roster preflight:
new OTIZ verifiers were absent from `tools/verification/suites.tsv`; fast, unit,
governance, both integration shards and e2e therefore returned exit 2 before
their categories, and verify rejected the failed aggregate. The complete
inventory was inspected. The correction registers all new tests; local
`ci.py verify-roster` reports GREEN with 389 tests.

Historical correction run `34702874922` passed unit but failed governance/fast
because the explicit e2e composition assertion had not added the newly registered
publication browser test. The full failure was inspected; the expected ordered
list is updated and `verification_ci_001_test.py` plus roster validation pass
locally. Remaining jobs on that obsolete source are not reused as approval.

Deployment: `UNKNOWN` and not authorized.

## PR #103: owner-authorized correction through merge

Владелец 2026-09-12 поручил довести PR #103 до merge. Root сохранил authorship
spec/tests; отдельный sol/low executor `recovery_analysis` исправил только
runbook update sequence. Production code в correction не менялся.

Полный inventory CI `34706549891`, head `6a01015b`, содержит пять failures:
`jobs_runtime_contract_001`, `runtime_schema_001`,
`production_runtime_compose_001`, `runtime_jobs_recovery_001`,
`runtime_recovery_forward_update_001`. Aggregate verify корректно отказал.
Полный журнал: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/stdout/1789233208877639000-2675f21841044e409393c86fc5480e9f.log`.
`gh run view --log` вернул 1 из-за synthetic Quality Graph check без собственного
лога; содержимое всех executable failed jobs сохранено и проверено.

Коррекция `3507c659`: явные тестовые Yii keys в Compose fixtures, DDL scan
реальных перенесённых owners, public prepare в recovery fixtures, непустой
Yii session file в exact backup/restore и prepare/replay после historical
v22/v23 restore с проверкой bytes/modes/rows/AUTO_INCREMENT. Все пять focused
checks и architecture-check GREEN до интеграции main. Полные evidence records
сохраняются вне checkout. Canonical full local suite не запускался.

Main `6680decf` интегрирован merge commit `885a55f1` без конфликтов; повторные
focused checks проверяют новый executable source и обновлённые registries.
Bounded correction input: `openspec/changes/yii2-otiz-workflow/verification-ci-correction-input.json`.
Независимые correction Gates 3/5, final exact-source CI и merge на этом
checkpoint ещё pending; старые failures не заменяются новым статусом.

Повтор после merge выявил race в Compose regression: `restart` завершился до
приёма HTTP nginx (`Connection refused`, record
`1789233423406637000-73206876714b4413b6560454448cdb82`). Проверка теперь ожидает
тот же обязательный HTTP 200 ограниченным polling, не вызывает prepare/migrate
и не ослабляет проверку сохранённого state. Historical forward повтор выполнил
обе версии успешно, но harness пометил evidence UNKNOWN из-за параллельной
правки delivery metadata (`1789233445569869000-1ee0c8ad18de48d0ba4c3034ddefccef`).
Эти записи не используются как GREEN; final checks выполняются при frozen source.
