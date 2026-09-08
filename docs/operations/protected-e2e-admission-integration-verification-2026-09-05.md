# Protected admission integration — exact-SHA verification

Дата: 2026-09-05. Implementation author: `/root`.
Applied exact SHA: `060e880cdff41b8564a005fba95d6ed796c7772f`.
Tracked/untracked worktree clean до и после full verify; HEAD не менялся.

## Gates и exact scope

Owner admission candidate revision3:
`c6adea1cafc5c7fe4dfeb2096b08c053acdefa21e0780be61db24246df3a602e`.
Oracle Gate 3/GREEN/Gate 5 завершены; final independent review:
`reviews/code/PILOT-E2E-ADMISSION-ORACLE-001-v2.md`, APPROVED.
Отдельный protected patch Gate 3:
`reviews/tests/PILOT-E2E-ADMISSION-ASSERTIONS-001-v1.md`, APPROVED.
Patch применён только после обоих approvals.

Applied patch SHA256:
`2fadffb1b9ca4269f03f090a8347155f5b9f005f6d1f3874ac43f758b3e9d33d`.
Protected after SHA256:
`8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b`.
Только 3 stale table assertions заменены двумя вызовами oracle на первом и
повторном actual response; остальные байты protected verifier неизменны.

## Full protected invocation

Command: `php tests/InstallationProcess/pilot_e2e_flow_001_test.php`.
Exit **255**. Оба admission oracle calls, actor19 sentinel, exact grant,
authority matrix и full before/after manifests пройдены. Первый downstream
failure — прежний, не изменённый assertion карточки на line157:

```text
Uncaught TestFailure: launch action visible Сформировать распоряжение
Expected: true
Actual: false
```

Ни remainder journey, ни manual-registration contract не утверждаются этой
поправкой. Verifier завершился собственной ошибкой и выполнил normal finally;
нет early exit/skip/allowed-failure conversion. Reaching admission не является
whole-E2E GREEN.

## Полный make verify на том же SHA

Command: `make verify`, launched private `run-full-verify.py` recorder.
Recorder только сохраняет объединённый stdout/stderr без изменения bytes и
отдельно monotonic timings по уже существующим markers; harness не менялся.
Exit **2**, elapsed **369.515255917 seconds**.

```text
VERIFY_STAGE test-db-reset PASS
VERIFY_STAGE migrate PASS
VERIFY_STAGE architecture-check PASS
VERIFY_STAGE lint PASS
VERIFY_STAGE unit-test PASS
VERIFY_STAGE db-test FAIL
VERIFY_STAGE characterization-test PASS
VERIFY_STAGE e2e-test FAIL
VERIFY_STAGE diff-check PASS
FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test
```

Только failed test identities: `pilot_e2e_flow_001_test.php` и
`pilot_demo_bootstrap_001_test.php`, который вызывает тот же E2E. В каждом
случае достигнут downstream launch-action mismatch после исправленного admission.
Canonical migrations 1–12, architecture7, lint, unit и characterization PASS.
Literal VERIFY_OK отсутствует. QG integration, bootstrap CI PR/publication и
launch readiness по-прежнему не разрешены.

## Primary archive и hashes

Private archive:
`/Users/antropophag/.local/state/fmonitor2-verification/admission-20260905-w7wmv_ye`.

```text
82b7125d68e917a56f36ff877409e6f2635ad39dc69d4f1bbd8bd074ee167d36  patched-e2e.log
0d70070a8974b87a8ce360329bd6954cd98e9bdbbdaf33c1ce6e0850c9857e5a  full-verify.log
cb5d492d805488ee3629c8259a00a122c0e04c00dd3028e5369ed6c4fed0ce28  full-verify-timings.json
cd072944ec1ed9397ea3e9cff3e44bd2fc84213452039341eb44eed789600bea  run-full-verify.py
```

Main test MariaDB осталась healthy. Никакого push/deploy/PR изменения нет.
Scoped integration Gate 5 ещё требуется; parent OpenSpec не Done.
