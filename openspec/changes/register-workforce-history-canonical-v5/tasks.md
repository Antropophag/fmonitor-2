## 1. Gate 1 — canonical runner contract

- [x] 1.1 Утвердить executable spec `WORKFORCE-CANONICAL-RUNNER-001`, наследующий exact `BITRIX-WORKFORCE-SCHEMA-001` v0.3; verification: owner approval от 2026-09-02 фиксирует clean/repeat/conflict/runtime-no-DDL outcomes и composed prefix 25/26 при отдельном family-local 37/38.

## 2. Gates 2–3 — RED и independent test review

- [x] 2.1 Свежий test/RED agent добавил runner-level public CLI test; verification: qualifying RED показывает исправный setup, `schemaVersion=4`, `[1,2,3,4]`, восемь v1-v4 tables и отсутствие ровно трёх v5 tables.
- [x] 2.2 Новый fresh independent test-rereview agent записал `APPROVED` в `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-v2.md`; verification: spec/test hashes, complete §6 matrix и independently reproduced RED evidence зафиксированы.

## 3. Gate 4 — minimal implementation

- [x] 3.1 Зарегистрировать approved v5 class после v4 в canonical runner; verification: полный public-CLI matrix clean/repeat/partial/conflict/failure green с exact `schemaVersion=5`.
- [x] 3.2 Удалить direct v5 apply и charset `ALTER` из bootstrap/importer, заменить fail-closed precondition; verification: deployment/auth characterization green, entrypoint запускает canonical runner до bootstrap, runtime только read-only validates v5.
- [x] 3.3 Запустить workforce schema, runner, import/deployment regression и `make architecture-check`; verification: workforce v0.3, catalog, production composition, deployment/auth checks и architecture 6/6 green, baseline не расширен.

## 4. Gate 5 и Done

- [x] 4.1 Fresh independent code rereview v4 проверил spec, reviewed tests, corrected diff и verification и записал `APPROVED` в `reviews/code/WORKFORCE-CANONICAL-RUNNER-001-v4.md`; предыдущие CHANGES_REQUESTED records сохранены append-only.
- [x] 4.2 Done: canonical command reports v5; runtime callers perform no workforce migration/DDL; clean/repeat/partial/conflict/failure/collation tests, absolute architecture rule and relevant regression green; independent test/code reviews approved.
