# Issue №136 — Quality Graph performance evidence

## Авторизация и авторство

Прямое поручение владельца в текущей сессии авторизовало bounded issue №136 и немедленный переход OpenSpec propose → apply после strict validation; autonomous authorship не разрешалось. Root/Codex authored scope, executable spec, tests и delivery report. Отдельный `gpt-5.6-sol / low` executor `/root/issue136_executor` authored только `tools/verification/ci.py` и `tools/verification/integration-timings.tsv`. Независимые `gpt-5.6-sol / low` reviewers: `/root/issue136_gate3` (Gate 3) и `/root/issue136_final_review` (Gate 5).

## Fresh comparable baseline

После перебазирования на `main` `82b8b2cc` доступен один comparable successful FULL Quality Graph run актуальной inventory architecture: exact source №157 `59cbb6b9`, уже включённый в текущий `main`. Cancelled, partial, FAST, harness-only и runs до этой architecture не включены в fresh baseline. Ограничение честное: это один run, поэтому median не вычисляется. Wall — от `createdAt` workflow до check `Quality Graph`; execution/setup — timestamps GitHub steps.

| Run / source | Full wall | Shard 1 execution | Shard 2 execution | Runtime setup 1/2 | Dependency setup 1/2 | DB setup 1/2 |
|---|---:|---:|---:|---:|---:|---:|
| [35028248716](https://github.com/Antropophag/fmonitor-2/actions/runs/35028248716) / `59cbb6b9` | 13:39 | 9:34 (573.589s category) | 9:23 (562.373s category) | 0:40 / 0:29 | 1:53 / 2:09 | 0:15 / 0:12 |

Planning weights остаются historical medians трёх сопоставимых runs предыдущего snapshot; они не являются fresh-baseline median или verdict evidence. Top expensive integration tests в fresh run: `quality_graph_ci_setup_001_test.php` 95.277s; `deadline_transfer_certificate_recovery_001_test.php` 37.300s; `runtime_recovery_forward_update_001_test.php` 35.930s; `runtime_jobs_recovery_001_test.php` 35.716s; `bitrix_workforce_delivery_001_test.php` 35.682s; `yii2_case_import_db_001_test.php` 26.460s.

## Shard estimate

На свежем canonical inventory из 273 integration tests и одном и том же median-weight snapshot:

| Allocation | Shard 1 | Shard 2 | Critical shard |
|---|---:|---:|---:|
| прежний sorted round-robin | 696.077s | 541.920s | 696.077s |
| deterministic LPT | 619.005s | 618.992s | 619.005s |

Predicted critical-shard improvement: 77.072s (11.1%). Это estimate, не measured CI improvement. Membership по-прежнему задаёт только `suites.tsv`; weights не являются вторым inventory или GREEN evidence.

## Единственный setup candidate

Declared profile: direct bounded `php tests/Verification/quality_graph_ci_setup_001_test.php` на одном source/environment, три последовательных запуска. Wall: 57.13s cold, 34.83s warm, 32.29s warm; все GREEN. Test намеренно создаёт unique image tag, вызывает public `make test-tools`, проверяет source label/image identity, три locked runtime profiles, отдельную network/DB lifecycle и cleanup. Повторная immutable layer reuse уже обеспечивается Docker warm path, а удаление unique cold build изменило бы сам contract теста. Отдельная простая invalidation-safe reuse seam не подтверждена.

Результат: `NO_SAFE_SETUP_OPTIMIZATION_FOUND`. Setup code не изменён; after measurements неприменимы, потому что optimization не поставлена. Не исследовались другие setup subsystems.

## Exact-source after CI

Будет заполнено после FULL run перебазированного candidate source. Предыдущий pre-rebase source `5285f34d` имел один GREEN FULL run `35027307483`, но повторный run того же SHA `35028861332` упал в e2e `pilot_jobs_compose_001_test.py`, тогда как оба integration shards остались GREEN. Это не final exact-source evidence после rebase и не используется для заявления measured improvement. Runner variance учитывается; historical и after runs не трактуются как контролируемый benchmark.

Token/cost: `UNKNOWN` — supported telemetry отсутствует.
