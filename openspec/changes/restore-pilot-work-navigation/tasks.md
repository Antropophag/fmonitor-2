## 1. Gate 1 — executable regression contract

- [ ] 1.1 Подготовить exact executable spec `PILOT-WORK-NAVIGATION-001` против approved `PILOT-OBJECT-LIST-001`, `PILOT-UI-SHELL-001`, diagnosis/history и public shell; verification: spec фиксирует exact configured shared-shell route families, root non-link `aria-current="page"` без href, non-root exact link без `aria-current`, minimal/broad actor parity, inherited `/pilot` redirect и `401/403/404/405/503` body/header preservation, zero-write/repeat и explicit non-goals.
- [ ] 1.2 Поручить fresh independent Gate 1 review и получить exact owner approval reviewed hash; verification: review `READY_FOR_OWNER_APPROVAL`, append-only approval record отдельно разрешает Gate 2 без code/test approval.

## 2. Gates 2–3 — RED и independent test review

- [ ] 2.1 Написать минимальный renderer/public HTTP RED: root и по одному успешному configured representation каждого spec-enumerated route family доказывают exact count/element/href/`aria-current`; minimal/broad permission parity, repeat и zero business/audit mutation доказаны отдельно; verification: current unconditional disabled helper даёт intended assertion failure, не setup/RBAC failure.
- [ ] 2.2 Подтвердить inherited HTTP regression evidence для exact `/pilot` redirect и `401/403/404/405/503` status/body/application-controlled headers, включая `Allow`, `Location`, `Retry-After` и correlation header where applicable; verification: existing approved route suites остаются GREEN, а navigation verifier не переопределяет RBAC semantics.
- [ ] 2.3 Поручить fresh independent test reviewer проверить public-seam traceability, route-family coverage, sensitivity к missing/duplicate/wrong element/href/current и отсутствие authorization weakening; verification: exact test hashes получают `APPROVED` до production edit.

## 3. Gate 4 — minimal GREEN

- [ ] 3.1 Восстановить один `/pilot/` destination и exact current-state только в `app/PilotHttp` shell renderer; verification: approved focused RED становится GREEN без route/auth/persistence changes и без rapid-pilot edits.
- [ ] 3.2 Запустить object-list/card/UI-shell и локальные RBAC regression suites; verification: predecessor navigation failures исчезают, object-list fixture test достигает собственного RBAC matrix.

## 4. Verification, Gate 5 и Done

- [ ] 4.1 Запустить `make architecture-check`, lint, focused DB tests и `make verify`; verification: architecture 7/7, owned regressions GREEN, unrelated failures отдельно классифицированы без assertion weakening.
- [ ] 4.2 Поручить fresh independent code reviewer проверить approved spec/tests, bounded renderer diff и verification evidence; verification: `APPROVED`, reviewer не меняет production/tests.
- [ ] 4.3 Обновить operations status и отметить Done только после strict OpenSpec, Gates 1–5 и durable GREEN; verification: work navigation contract закрыт, object-list RBAC predecessor blocker снят.
