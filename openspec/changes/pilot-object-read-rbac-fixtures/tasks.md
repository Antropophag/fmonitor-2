## 1. Gate 1 — executable fixture contract

- [x] 1.1 Подготовить executable spec `PILOT-OBJECT-READ-RBAC-FIXTURES-001` для exact `GET /pilot/objects` actors/grants, positive representation, per-route negative precedence и cleanup; verification: independent review READY_FOR_OWNER_APPROVAL.
- [x] 1.2 Получить explicit owner approval exact reviewed hash; verification: approval record разрешает Gate 2 без изменения requirements.

## 2. Gates 2–3 — RED и independent test review

- [ ] 2.1 Написать public HTTP RED для exact `GET /pilot/objects` positive grant и legacy-only/inactive/missing/near-match/revoke/unknown-suffix cases; verification: intended failures не setup и list-handler reads/mutations observable.
- [ ] 2.2 Поручить tests fresh independent reviewer; verification: review APPROVED и фиксирует hashes, env isolation, list-representation independence и negative sensitivity.

## 3. Gate 4 — fixture alignment

- [ ] 3.1 Реализовать reusable canonical local-RBAC fixture с explicit actor IDs/unset для каждого case; verification: focused object-list test GREEN без production fallback.
- [ ] 3.2 Синхронизировать list representation только с approved object-list spec; verification: predecessor/raw/security assertions не ослаблены.

## 4. Verification, Gate 5 и Done

- [ ] 4.1 Запустить focused DB tests, local-RBAC characterization, architecture, lint и full verify; verification: owned regressions GREEN, unrelated debt classified.
- [ ] 4.2 Поручить independent code review; verification: APPROVED по Standards/Spec без test changes.
- [ ] 4.3 Обновить operations status и отметить Done только после Gates 1–5, strict OpenSpec и durable GREEN evidence.
