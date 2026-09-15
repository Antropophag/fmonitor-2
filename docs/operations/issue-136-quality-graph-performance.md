# Issue №136 — Quality Graph performance evidence

## Fresh comparable baseline

Выборка: три последних successful FULL Quality Graph runs после merge №135 с обеими integration jobs и итоговым fail-closed `verify`. Cancelled, partial, FAST и harness-only runs не использованы. Wall — от `created_at` workflow до завершения check `Quality Graph`; execution/setup — timestamps соответствующих GitHub steps.

| Run / source | Full wall | Shard 1 execution | Shard 2 execution | Runtime setup 1/2 | Dependency setup 1/2 | DB setup 1/2 |
|---|---:|---:|---:|---:|---:|---:|
| [35022305566](https://github.com/Antropophag/fmonitor-2/actions/runs/35022305566) / `f4933a64` | 13:19 | 9:33 | 9:10 | 0:27 / 0:37 | 1:52 / 2:10 | 0:13 / 0:11 |
| [35017913916](https://github.com/Antropophag/fmonitor-2/actions/runs/35017913916) / `d65a82eb` | 15:44 | 11:33 | 7:26 | 0:23 / 0:34 | 1:38 / 1:44 | 0:10 / 0:14 |
| [34981392888](https://github.com/Antropophag/fmonitor-2/actions/runs/34981392888) / `854a8dc5` | 16:05 | 11:44 | 9:23 | 0:26 / 0:25 | 1:44 / 1:43 | 0:14 / 0:13 |

Медианные `VERIFY_TIMING` по этим runs сохранены только как planning weights. Top expensive integration tests: `quality_graph_ci_setup_001_test.php` 82.308s; `bitrix_workforce_delivery_001_test.php` 37.491s; `runtime_recovery_forward_update_001_test.php` 36.117s; `yii2_case_import_db_001_test.php` 35.836s; `deadline_transfer_certificate_recovery_001_test.php` 35.278s; `runtime_jobs_recovery_001_test.php` 34.197s.

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

Будет заполнено после единственного FULL run candidate source. Runner variance учитывается; historical и after runs не трактуются как контролируемый benchmark.

Token/cost: `UNKNOWN` — supported telemetry отсутствует.
