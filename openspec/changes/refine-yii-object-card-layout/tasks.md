## 1. Gate 1 и план проверки

- [x] 1.1 Создать нормативную спецификацию `YII2-OBJECT-CARD-PRESENTATION-001` с полной матрицей карточки, смежных экранов, документов, authorization/no-write и responsive/accessibility; проверить traceability с delta spec.
- [x] 1.2 Создать `verification-input.json`, подготовить обязательный Quality Graph plan через delivery harness и подтвердить отсутствие unresolved obligations до Gate 2.

## 2. Gate 2 — RED

- [x] 2.1 Расширить focused HTTP-тест карточки проверками табов, регистрационного номера, отсутствия должности/provenance и document-row markup; зафиксировать RED по отсутствующему поведению.
- [x] 2.2 Добавить focused browser-тест keyboard tabs, desktop/mobile layout и overflow; зафиксировать RED по отсутствующему поведению.
- [x] 2.3 Добавить focused проверки смежных экранов состава и справочника на запрет должностей вне справочника и запрет пользовательского происхождения данных; зафиксировать RED.
- [x] 2.4 Передать полный RED-кандидат независимому Gate 3 reviewer, устранить полный findings list и получить `APPROVED`, если planner требует Gate 3.

## 3. Gate 4 — реализация

- [x] 3.1 Реализовать серверную таб-композицию карточки и подключить публичный tabs behavior; проверить focused HTTP/browser tests.
- [x] 3.2 Реализовать паспорт, сроки/готовность/оборудование и компактную команду без должностей/provenance; проверить unknown/empty/read-only cases.
- [x] 3.3 Перевести распоряжения и технические ссылки на публичные document rows с безопасным file-type fallback; проверить форматы и сохранение URL/permissions.
- [x] 3.4 Удалить должности монтажников со смежных экранов и происхождение данных со всех пользовательских views, сохранив должность в справочнике; проверить focused inventory.
- [x] 3.5 Выполнить bounded visual pass Playwright на desktop/mobile, `impeccable detect`, syntax/diff/architecture focused checks и записать результаты без полного локального suite.

## 4. Gate 5 и Done

- [x] 4.1 Подготовить reconstructible exact-source review package и получить независимый final `APPROVED` по спецификации, тестам, production diff и evidence.
- [ ] 4.2 Запустить один exact-source GitHub CI через выбранный planner consumer; собрать полный failure inventory при ошибке и не считать `UNKNOWN` зелёным.
- [ ] 4.3 Зафиксировать delivery record: фактические авторы, scope, проверки, review verdicts, CI и отсутствие изменений persistence/authorization; Done — карточка и смежные экраны соответствуют спецификации без изменений доменных фактов.
