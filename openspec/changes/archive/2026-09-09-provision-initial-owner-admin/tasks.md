## 1. Contract and RED

- [x] 1.1 Write normative INITIAL-OWNER-PROVISIONING-001 and public CLI test matrix
- [x] 1.2 Demonstrate RED at absent CLI/application seam and retain evidence
- [x] 1.3 Obtain independent Gate3 approval

## 2. Implementation

- [x] 2.1 Implement native clean-only application operation and exact replay/conflict
- [x] 2.2 Implement direct-config DML-only CLI, lock and stable redacted outcomes
- [x] 2.3 Update production README/runbook with executable clean-install sequence

## 3. Verification and Done

- [x] 3.1 Run focused CLI, pilot bootstrap regression, auth global-call and architecture checks
- [x] 3.2 Obtain independent Gate5 code review
- [x] 3.3 Record full CI pending/completed honestly and archive only after Done

## Итог — 2026-09-09

Полный Actions34300992370 на exact head
`f89e0a2ccc03d27c0083c311c26b5f79e9772c9c`: все8 jobs SUCCESS, literal VERIFY_OK.
PR63 слит штатно, merge `839001b427ccff851dc0332cfe2d731e207f2bee`.
Первый CI34299969447 выявил test-env inheritance; полный inventory собран до
исправления, poison regression + независимый review подтверждают fix.
Production provisioning source не изменялся после исходного code review.
Основной стенд8092 сохранён; clean-install recipe проверен отдельно, восстановление
и jobs продолжаются в #36/#34 без заявления production readiness.
