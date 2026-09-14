## 1. Gate 1–3: classification contract

- [x] 1.1 Создать `specs/DELIVERY-FAST-LANE-118-V1.md`, обновить verification input и проверить traceability F01/F03/F04/F06/F07/F10/minimal F12.
- [x] 1.2 Через public `change-verification` seam добавить intended RED classification tests; F10 обязан падать behavior assertion, не setup failure.
- [x] 1.3 Подготовить frozen Gate 3 package и получить independent `APPROVED`; #118 должен быть CRITICAL.

## 2. Classification implementation

- [x] 2.1 Минимально расширить existing verification policy и planner; focused classification corpus должен стать GREEN до CI wiring.
- [x] 2.2 Зафиксировать F01 exact registered-check argv без whole-category expansion; F03/F04/F06/F07 обязаны escalated fail closed.

## 3. Lifecycle and FAST admission

- [x] 3.1 Добавить one-review FAST package contract без отдельного Gate 3 для FAST consumers; focused harness test должен стать GREEN.
- [x] 3.2 После classification GREEN добавить minimal exact-source FAST admission: selected success/failure/missing-or-skip, policy-unselected neutral, SHA mismatch blocked.
- [x] 3.3 Обновить текущий Quality Graph только minimal routing extension; если требуется новая architecture/router/state machine — STOP.

## 4. Gate 5 and delivery

- [x] 4.1 Проверить implementation LOC: target 220–360, hard stop около 500; tests не должны создавать новый framework.
- [x] 4.2 Подготовить Gate 5 package с RED→GREEN, exact selected plan, excluded jobs и review count; получить independent `APPROVED`.
- [ ] 4.3 Запустить один полный exact-source Quality Graph для CRITICAL изменения #118; UNKNOWN/stale/partial не считать GREEN.
- [x] 4.4 Оставить F02/F05/F08/F09/F11 и расширенный real-CI F12 отдельными changes; #118 целиком не закрывать.
