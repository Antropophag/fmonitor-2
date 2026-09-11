## 1. Контракт и RED

- [x] 1.1 Создать `verification-input.json`, выполнить harness `prepare` для root на exact base и прочитать generated plan; проверить отсутствие unresolved coverage до написания тестов.
- [x] 1.2 Root зафиксировать нормативный `YII2-INSTALLER-DIRECTORY-001` и написать полную HTTP/read-model/browser матрицу; intended RED должен падать из-за отсутствующего Yii route, а не setup failure.
- [x] 1.3 Независимому sol/low reviewer проверить spec, tests, RED, authorization/failure/runtime-closure/adjacent-flow mapping и сохранить Gate 3 `APPROVED` до реализации.

## 2. Реализация и focused verification

- [x] 2.1 Отдельному sol/low executor реализовать Yii DAO query, controller, route и view без изменения approved expectations; focused HTTP/read-model tests должны стать GREEN.
- [x] 2.2 Проверить desktop и 390px browser: поиск, комбинированные фильтры, пагинацию, links/navigation, accessibility, escaping, HEAD и отсутствие console/page errors; сохранить harness evidence вне repository.
- [x] 2.3 Выполнить обязательные inventory, architecture/runtime-closure и соседние auth/queue/preopening checks; все применимые команды generated plan должны быть GREEN, а неприменимые группы — обоснованы в delivery record.

## 3. Review и поставка

- [x] 3.1 Root проверить полноту кандидата и сформировать reconstructible exact-source snapshot; независимый sol/low reviewer должен сохранить Gate 5 verdict со всеми findings.
- [ ] 3.2 После Gate 5 `APPROVED` создать PR, выполнить один full exact-source CI и записать PR/CI/source/authors/rework в delivery record; только после merge отметить срез выполненным, не закрывая общий №76 или stand cutover.
