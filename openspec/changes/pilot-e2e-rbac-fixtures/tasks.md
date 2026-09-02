## 1. Gate 1 — executable E2E fixture contract

- [x] 1.1 Подготовить `PILOT-E2E-RBAC-FIXTURES-001` spec: actor18 exact `GET /pilot/objects → objects.read`, actor19 legacy-only denial, isolated committed revoke/repeat, main grant preservation, cleanup и combined-PDF boundary; verification: independent review READY_FOR_OWNER_APPROVAL.
- [x] 1.2 Получить explicit owner approval exact hash; verification: Gate 2 отдельно разрешён.
- [x] 1.3 Амендировать snapshot boundary по GRILL-009: full equality вокруг authorization reads, exact RBAC equality и только approved prepare delta у artifact boundary; verification: fresh independent Gate 1 review и new exact-hash owner approval.

## 2. Gates 2–3 — RED и independent review

- [x] 2.1 Переподтвердить E2E RED по amended exact hash: actor18 local list admission, actor19 legacy-only 403, full equality вокруг authorization reads и exact approved prepare delta до artifact boundary; verification: intended failure не PDF/setup failure.
- [x] 2.2 Добавить isolated revoke/repeat/cleanup sensitivity, доказать main actor18 grant unchanged перед downstream PDF и поручить fresh test review; verification: APPROVED exact hashes и downstream PDF dependency visible.

## 3. Gate 4 — minimal fixture GREEN

- [ ] 3.1 Реализовать deterministic canonical role/user/grant seed и real trusted actor propagation; verification: journey до artifact boundary GREEN, cross-route denial сохраняется.
- [ ] 3.2 Удалить ambient identity/session dependence и доказать finally cleanup DB/users/sessions/artifacts; verification: repeat identical, failure leaves no owned residue.

## 4. Verification, Gate 5 и Done

- [ ] 4.1 Запустить demo/golden DB+E2E, architecture, lint и full verify; verification: RBAC failures исчезли, combined-PDF остаётся отдельно классифицирован без ослабления.
- [ ] 4.2 Получить independent code review APPROVED; test changes возвращают Gate 2.
- [ ] 4.3 Обновить operations status и Done только после Gates 1–5, strict OpenSpec и durable evidence.
