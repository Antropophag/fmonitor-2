## 1. Gate 1–3 preparation

- [x] 1.1 Root создаёт нормативный `YII2-CONSTRUCTION-CONTROL-PREOPENING-001` с полной queue/form/authorization/history/replay matrix и проверяет `openspec validate --strict`
- [x] 1.2 Root создаёт `verification-input.json`, запускает `harness.py prepare`, читает все обязательства Quality Graph plan и устраняет пробелы покрытия до Gate 2
- [x] 1.3 Root добавляет focused Yii2 HTTP/reader/browser RED через реальные public seams и сохраняет intended failure evidence без локального full suite
- [x] 1.4 Независимый sol/low reviewer проверяет полный spec/test candidate и фиксирует Gate 3 `APPROVED` либо полный список corrections в `reviews/tests/`

## 2. Gate 4 implementation

- [x] 2.1 Отдельный sol/low executor расширяет server-side construction-control queue готовыми назначенными объектами и подтверждает actor/read-only/pagination/#39 regressions focused тестами
- [x] 2.2 Executor добавляет preopening state и существующее действие открытия над checklist, не создавая второго writer, и подтверждает authorization/no-facts/return-path focused тестами
- [x] 2.3 Executor подтверждает сквозной путь ready queue → checklist → `open_confirmed` → opened checklist, replay/concurrency и relevant `make architecture-check`

## 3. Gate 5 и поставка

- [x] 3.1 Root фиксирует reconstructible exact-source package; независимый sol/low reviewer проверяет spec, approved tests, production diff и evidence и записывает Gate 5 verdict
- [x] 3.2 После `APPROVED` выполнить все команды свежего focused plan; локальный `make test`/`make verify` не запускать
- [ ] 3.3 Создать commit/PR и выполнить один exact-source Quality Graph CI; merge/закрытие #40 допустимы только при подтверждённом GREEN, deployment остаётся UNKNOWN
