# Combined original-command Gate5 v1

Дата2026-09-06. Reviewer `/root/maintenance_review`, отдельно назначенный gpt-5.6-sol low, не автор source/tests. Verdict **CHANGES_REQUESTED**.
Reviewed commit8e3825bc2c672c141f1b2ce869d359458473324e.
Evidence `/Users/antropophag/.local/state/fmonitor2-verification/original-command-combined-green-apd1lr9e/evidence.json`, SHA2563d72656fe0ffb7e4b435a864f2d895999010475b3293598e43c92e5e654f3dbc.
complete=true; exact head/headAfter; clean before/after; все52checks exit0.

## Обязательные исправления

1. Runtime.php command read и BarrierLifecycle blocking fread/fgets/EOF loops не имеют child-side5s monotonic deadlines (parentv74 lines2133–2145/2181). Peer с незакрытым socket может навсегда удержать worker. Parent harness deadline этого не доказывает; WORKER-PORTS исключал framing/FD closure.
2. До command consumption проверяются только простые path strings. Canonical/symlink/owner/mode/file/root metadata checks происходят позже при открытии safe log/password/storage. Parent требует завершить config/DSN/path/FD admission до command input и secret/DB/storage/log/barrier access.
3. Реальный serialized result непосредственно передаётся fwrite без проверки полной line<=16384 до записи. Synthetic RESULT_OVERSIZE branch не доказывает native length guard; oversize serialization обязана писать0bytes.

Нужны bounded executable RED, independent Gate3, minimal correction, affected regressions и новый Gate5. Ранее одобренные command/persistence/audit/parser/storage/maintenance/evidence/fixture/worker-ports сохраняют scoped approvals. Этот review не оценивает готовность portal launch или будущие selection/HTTP/composition/opening.
Reviewer не редактировал artifacts; silent approval не выводится из passing suite.
