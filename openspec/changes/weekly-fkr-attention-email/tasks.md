## 1. Gate 1 и verification plan

- [x] 1.1 Root создаёт нормативный `WEEKLY-FKR-ATTENTION-EMAIL-001` с complete acceptance matrix из capability contract, включая точную address-on-retry policy и измеренный предел размера; проверить spec lint/ссылки
- [x] 1.2 Root создаёт `verification-input.json`, запускает harness prepare, читает все обязательства и фиксирует planner lane/required reviews; unresolved obligations должны блокировать Gate 2

## 2. Gate 2 — RED tests

- [x] 2.1 Root добавляет public-seam test недельного календаря и scheduler idempotency для московского понедельника/границ недели; проверить intended RED на отсутствии job type
- [x] 2.2 Root добавляет integration test recipient eligibility и server-side scope для двух руководителей, invalid/disabled recipient и отсутствия cross-scope counts/ids; проверить intended RED
- [x] 2.3 Root добавляет report projection/render test пяти секций, empty/sorting/escaping/links, запрета изображений/внешних ресурсов и Outlook-safe markup; добавить 680px/320px browser captures и проверить intended RED
- [x] 2.4 Root добавляет as-of progress test 85/15, corrections и UNKNOWN boundary, а также overdue/«Обратить внимание» matrix; проверить independently fixed literals и intended RED
- [x] 2.5 Root добавляет jobs/outbox test success/repeat/transient/permanent/concurrent delivery и снимок отсутствия предметных мутаций; проверить intended RED
- [x] 2.6 Root добавляет runtime/config test missing secret, TLS verification, sender compatibility, redaction и non-production test-recipient override; проверить intended RED без сетевой отправки
- [x] 2.7 Обновить verification inventories/manifests для всех новых tests и выполнить полный набор planner-selected bounded RED commands

## 3. Gate 3

- [x] 3.1 Независимый reviewer проверяет spec/tests/RED/obligation mapping на exact prepared source и записывает единый verdict в `reviews/tests/WEEKLY-FKR-ATTENTION-EMAIL-001.md`; продолжать только при APPROVED, если Gate 3 выбран planner

## 4. Gate 4 — минимальная реализация

- [x] 4.1 Executor реализует immutable period/report values и чистую классификацию plans/progress/overdue/attention; соответствующие focused tests проходят
- [x] 4.2 Executor реализует native recipient/scoped report read adapters с as-of/UNKNOWN семантикой; scope/history focused tests проходят
- [x] 4.3 Executor реализует HTML/text renderer с одинаковой view model, image-free Outlook-safe table markup, inline CSS, FMonitor palette, escaping, empty states и trusted absolute links; lint и 680px/320px render tests проходят
- [x] 4.4 Executor добавляет weekly scheduler, generation handler и per-recipient outbox intents в существующий Jobs runtime; scheduler/delivery focused tests проходят
- [x] 4.5 Executor добавляет SMTP transport/runtime configuration/readiness/test override без committed secret и с безопасными typed outcomes; config/redaction tests проходят
- [x] 4.6 Executor добавляет только необходимую additive technical migration и обновляет schema manifests, backup/restore/readiness inventory; schema-focused checks проходят
- [x] 4.7 Executor документирует переменные `FMONITOR_SMTP_*`, безопасный профиль `k2-mailer`, включение/rollback и manual test-send procedure без значения секрета; docs/config checks проходят
- [x] 4.8 Выполнить planner-selected bounded GREEN checks, relevant architecture check и сохранить компактное evidence через harness; локальный полный `make test`/`make verify` не запускать
- [x] 4.9 Выполнить один bounded Impeccable detector pass по email template и независимую визуальную проверку desktop/narrow captures; исправить mechanical findings без добавления изображений

## 5. Gate 5 и delivery

- [x] 5.1 Root проверяет candidate completeness, захватывает reconstructible exact-source snapshot и готовит final reviewer package
- [x] 5.2 Независимый reviewer проверяет spec/tests/Gate 3/code/config/security/evidence и записывает verdict в `reviews/code/WEEKLY-FKR-ATTENTION-EMAIL-001.md`; findings исправляются с повторной проверкой нужного delta
- [ ] 5.3 Выполнить одну exact-source GitHub CI проверку выбранным существующим consumer, собрать полный failure inventory при ошибке и не считать UNKNOWN зелёным
- [ ] 5.4 После APPROVED и GREEN подготовить PR-ready handoff с authorship, elapsed/rework, source/review/CI links и явным статусом live SMTP/test-send; архивирование выполняется отдельно после Done

## 6. Owner-requested compact table revision

- [x] 6.1 Root обновляет spec/tests: четыре раздела, табличные колонки, current native opening status и явная проверка overdue closing
- [x] 6.2 Implementer убирает `attention`, подключает current native opening status и рендерит четыре Outlook-safe таблицы
- [x] 6.3 Выполнить bounded render/source tests, Impeccable detector и independent final review; full local suite не запускать
