## 1. Scope и Gate 1

- [x] 1.1 Root создаёт `specs/YII2-CANONICAL-MIGRATIONS-001.md`, связывает его с issue #76 и delta spec, фиксирует actor/public seam, exact JSON/sysexits, fresh/repeat/upgrade, invalid/config/database/software failures, lock/restart/incompatible state, package callers, alias и non-goals; verification: acceptance matrix покрывает все scenarios delta без UNKNOWN как approval.
- [x] 1.2 Root создаёт `verification-input.json`, запускает `python3 tools/delivery/harness.py prepare`, читает обязательства Quality Graph plan и закрывает либо обосновывает каждую boundary category; verification: harness `state` показывает active binding к `YII2-CANONICAL-MIGRATIONS-001` на exact base/source.

## 2. Gate 2 и Gate 3

- [x] 2.1 Root пишет deterministic tests публичного Yii console seam и compatibility alias для успешного JSON/exit contract, invalid inputs, redaction и отсутствия `rapid-pilot`/demo/Yii migration ledger; verification: intended RED возникает только из отсутствующего Yii command/composition.
- [x] 2.2 Root дополняет focused DB/package tests для fresh, repeat, upgrade с сохранением rows/history, restart/incompatible state, concurrent lock и production Make/Compose caller; verification: existing schema oracles используются как независимые expected facts, а RED evidence сохранено вне checkout и связано из review record.
- [x] 2.3 Независимый sol/low reviewer проверяет полный spec/test candidate по Gate 3; verification: `reviews/tests/YII2-CANONICAL-MIGRATIONS-001.md` содержит exact source, полный findings list и явный `APPROVED`, иначе candidate возвращается root.

## 3. Gate 4 — minimal implementation

- [x] 3.1 Executor создаёт shared migration console adapter с закрытой environment validation, одним mysqli lifecycle, существующим `CanonicalMigrationApplication`/catalogue/ledger и redacted outcome mapping; verification: focused unit/CLI tests GREEN без изменения schema classes или catalogue.
- [x] 3.2 Executor регистрирует Yii2 console command и превращает retained `bin/fmonitor2-migrate.php` в тонкий alias к той же composition; verification: direct и alias contract tests GREEN и source/load checks доказывают отсутствие второй migration логики/ledger.
- [x] 3.3 Executor переключает Make и production Compose migration callers на Yii2 command и сохраняет image/runtime dependencies; verification: package/config tests GREEN, migration service не загружает `rapid-pilot` или demo bootstrap.
- [x] 3.4 Executor выполняет bounded focused migration, concurrency, package и architecture checks из prepared plan, не запуская локально `make test`/`make verify`; verification: все применимые команды GREEN, полные логи сохранены вне checkout, tasks отмечены только после полного результата.

## 4. Gate 5 и поставка

- [x] 4.1 Независимый sol/low reviewer проверяет normative spec, approved tests, exact reconstructible production diff и focused evidence; verification: `reviews/code/YII2-CANONICAL-MIGRATIONS-001.md` содержит полный findings list и явный `APPROVED`, либо возвращает correction scope.
- [ ] 4.2 Root подтверждает совпадение reviewed bytes, OpenSpec strict validation и completeness candidate, затем публикует PR и один exact-source GitHub full CI; verification: harness `state` связывает exact commit/source, PR и SUCCESS/VERIFY_OK без локального полного suite.
- [ ] 4.3 После merge root обновляет append-only delivery checkpoint и current pointer, честно оставляя imports/runtime recovery/web cutover/deployment и общий #76 открытыми; verification: merge commit/tree совпадает с reviewed candidate плюс перечисленные review/delivery records, deployment остаётся UNKNOWN без отдельной авторизации.
