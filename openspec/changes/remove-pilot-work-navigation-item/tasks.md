## 1. Gate 1 — executable contract

- [x] 1.1 Подготовить exact executable spec `PILOT-WORK-NAVIGATION-ITEM-REMOVAL-001`: enumerated configured route families, отсутствие exact label/accessibility name и `/pilot/` navigation destination/current marker, preservation остальных items, root queue/redirect, GET/HEAD, authorization/error headers, repeat и zero-write; verification: spec соответствует owner decision и strict OpenSpec validation проходит.
- [x] 1.2 Поручить fresh independent Gate 1 review и получить explicit owner approval reviewed exact hashes; verification: append-only review имеет `READY_FOR_OWNER_APPROVAL`, отдельное owner decision одобряет hashes только для Gate 2, а superseded restore review/approval не переиспользуются.

## 2. Gates 2–3 — RED и independent test review

- [ ] 2.1 Написать exhaustive shared-renderer DOM RED для всех десяти current states и exact sibling/accessibility/icon bytes; использовать root/object-list как canonical HTTP sentinels и existing route-specific tests как wiring evidence остальных callers; verification: текущий item даёт intended RED без восьми дублирующих DB/server fixtures.
- [ ] 2.2 Доказать через renderer + два HTTP sentinels GET/HEAD, minimal/broad actor parity, repeat/zero business-audit mutation, exact sibling order/attributes/icons и inherited `/pilot` redirect плюс `401/403/404/405/503` preservation; verification: existing route tests сохраняют собственные admission/content assertions.
- [x] 2.3 Обновить только superseded work-navigation predecessor assertion в object-list RBAC verifier на approved absence contract, сохранив RBAC facts/matrix и собственный intended RED; verification: canonical actor проходит setup/identity/authorization и current production даёт exact navigation RED `Expected: 0 / Actual: 2` до неизменённой RBAC matrix.
- [ ] 2.4 Поручить fresh independent test reviewer проверить exhaustive renderer sensitivity, root/object-list HTTP sentinels, existing route-specific wiring evidence и downstream RBAC isolation; verification: exact test hashes получают `APPROVED` до production edit.

## 3. Gate 4 — minimal GREEN

- [ ] 3.1 Удалить item только из единой `app/PilotHttp` shared navigation composition без replacement root item; verification: approved focused RED становится GREEN, остальные navigation items byte-equivalent и `/pilot/` остаётся successful queue route.
- [ ] 3.2 Прогнать object-list/card/prepare/checklist/construction-control/installers/admin/UI-shell и local-RBAC focused regressions; verification: navigation predecessor GREEN, object-list fixture достигает и доказывает собственную RBAC matrix без route/auth/persistence changes.

## 4. Verification, Gate 5 и Done

- [ ] 4.1 Запустить `git diff --check`, `make architecture-check`, lint, focused DB/HTTP tests и `make verify`; verification: architecture 7/7, owned regressions GREEN и полный verify имеет literal `VERIFY_OK`, либо unrelated failures отдельно классифицированы без объявления integration complete.
- [ ] 4.2 Поручить fresh independent code reviewer проверить approved spec/tests, bounded renderer diff, отсутствие rapid-pilot/domain/persistence изменений и verification evidence; verification: verdict `APPROVED`, reviewer не меняет production/tests.
- [ ] 4.3 Обновить append-only operations status и отметить Done только после Gates 1–5; verification: новый removal change является active truth, `restore-pilot-work-navigation` остаётся явно superseded evidence, object-list RBAC predecessor blocker снят.
