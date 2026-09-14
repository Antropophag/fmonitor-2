## 1. Gate 1–3 — scope, behavioral RED и review

- [x] 1.1 Создать `specs/DELIVERY-PROFILE-NETWORK-110.md`, связать issue #110 в `verification-input.json`, сгенерировать/прочитать mandatory plan и проверить strict OpenSpec; STOP при запрещённом scope или >100 infrastructure LOC.
- [x] 1.2 Root расширяет существующий зарегистрированный `quality_graph_ci_setup_001_test.php`: внешний lifecycle поднимает `test-db`, оба profiles выполняют реальный `mysqli SELECT 1`, а `finally` всегда выполняет `make test-env-down`; сохранить intended RED без изменения inventory.
- [x] 1.3 Передать complete spec/test/RED exact-source package независимому Gate 3 reviewer; implementation разрешена только при `APPROVED`.

## 2. Gate 4 — минимальная networking implementation

- [x] 2.1 Отдельный executor меняет только `tools/delivery/run-in-profile`: читает объявленное default network name из canonical Compose JSON, присоединяет `integration`/`browser` только к существующей network и передаёт `test-db:3306`, не владея lifecycle.
- [x] 2.2 Выполнить behavioral `mysqli SELECT 1` contract через `integration` и `browser`, strict OpenSpec, `git diff --check` и scope/LOC guard; подтвердить, что governance и все запрещённые файлы неизменны.
- [x] 2.3 Зафиксировать результат consumer verification: blanket category adoption отклонён; Docker CLI/socket/tooling expansion, full integration/e2e category acceptance и PR B не выполняются.

## 3. Gate 5 и отдельная поставка

- [x] 3.1 Передать exact source, approved tests, полный focused evidence и scope/LOC diff независимому Gate 5 reviewer; исправления возвращаются через применимые gates.
- [ ] 3.2 После `APPROVED` создать отдельный prerequisite PR, дождаться authoritative exact-source Quality Graph GREEN и не merge самостоятельно.
- [ ] 3.3 Не создавать PR B и не менять Quality Graph; после merge и authoritative GREEN prerequisite передать issue #110 consumer conclusion и отдельно перейти к #118.
