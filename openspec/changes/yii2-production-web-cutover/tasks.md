## 1. Gate 1–3: contract и RED

- [x] 1.1 Создать normative `YII2-PRODUCTION-WEB-CUTOVER-001`, `verification-input.json`, выполнить harness `prepare --role root` от exact `origin/main` и проверить generated plan без unresolved coverage.
- [x] 1.2 Root написать public `runtime.php` HTTP/include-boundary RED для health, root, login, representative owner routes, assets, unknown/method/host/failure; выполнить focused plan и сохранить intended RED.
- [x] 1.3 Независимый sol/low reviewer проверить spec, тест, mapping и RED и записать Gate 3 `APPROVED` до production implementation.

## 2. Gate 4: единый runtime

- [x] 2.1 Отдельный sol/low executor сделать `public/runtime.php` тонким Yii2 front controller, добавить Yii root/asset coverage и удалить production reachability legacy router/auth/session; reviewed test должен стать GREEN.
- [x] 2.2 Выполнить generated focused/fast checks, route/asset inventory, architecture-check и соседние auth/FKR/inspection/user/OTIZ tests; записать exact команды и результаты вне checkout.

## 3. Gate 5 и поставка

- [x] 3.1 Root сформировать reconstructible exact-source snapshot; независимый sol/low reviewer записывает Gate 5 verdict по spec/tests/diff/evidence.
- [ ] 3.2 После Gate 5 `APPROVED` создать отдельный PR, выполнить один full exact-source Quality Graph CI, исправить все failures через correction review и merge только при GREEN.
- [ ] 3.3 Обновить delivery record/current goal и #71/#76 фактическими source/PR/CI; deployment и общий cutover/restore rehearsal оставить явно незавершёнными.
