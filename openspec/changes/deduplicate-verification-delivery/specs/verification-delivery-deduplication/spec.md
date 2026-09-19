## Purpose

Определяет наблюдаемое fail-closed поведение поставки, которое исключает подтверждённые повторные browser/CI/review циклы без ослабления применимости, обязательных результатов и независимых Gates.

## ADDED Requirements

### Requirement: Канонический browser-сценарий исполняется один раз на каждом уровне
Verification inventory и acceptance mapping SHALL выбирать `yii2_preopening_browser_001_test.php` напрямую как канонический witness требований SHLZ operational UI и SHALL NOT одновременно выбирать обёртку, повторно запускающую тот же сценарий. Все существующие browser assertions, fixtures, isolation и классификация intended RED, regression и environment failure MUST сохраниться. Локальный focused запуск и один полный exact-source CI являются разными уровнями и каждый SHALL выполнить сценарий ровно один раз.

#### Scenario: Focused выбор канонического сценария
- **WHEN** verification planner строит focused-команды для acceptance, ранее ссылавшегося на SHLZ browser-обёртку
- **THEN** итоговый список содержит прямой запуск канонического сценария один раз и не содержит исполняющую его обёртку

#### Scenario: Полная матрица
- **WHEN** полный CI consumer читает verification inventory
- **THEN** канонический browser-сценарий выбран один раз, а прежняя обёртка не запускает его вторично

#### Scenario: Ошибка канонического сценария
- **WHEN** browser-сценарий возвращает intended RED, regression failure или environment/setup failure
- **THEN** существующее различение результата сохраняется без переклассификации ошибки обёрткой

### Requirement: Публикация переиспользует только применимый штатный CI-run
После push или создания PR delivery launcher SHALL ограниченно ожидать появления штатного PR-triggered run требуемого workflow для текущего кандидата до ручного dispatch, не обращаясь повторно к модели. Применимость MUST учитывать repository, workflow, exact head, base, trigger/mode и обязательные результаты существующего admission/observer пути; совпадения одного HEAD недостаточно. Один run является неделимой единицей evidence, результаты разных runs MUST NOT объединяться.

#### Scenario: Применимый run ожидает или выполняется
- **WHEN** в пределах ограниченного ожидания найден применимый run со статусом queued или in_progress
- **THEN** launcher возвращает его identity/link существующему observer и выполняет ноль новых dispatch

#### Scenario: Применимый run завершён
- **WHEN** найден завершённый применимый run
- **THEN** launcher передаёт этот run существующей проверке результата и выполняет ноль новых dispatch, не создавая подтверждающий run

#### Scenario: Применимый run отсутствует
- **WHEN** ограниченное ожидание завершилось и API достоверно сообщает отсутствие применимого run
- **THEN** launcher выполняет ровно один поддержанный manual dispatch и далее отслеживает созданный run без нового dispatch на каждом poll

#### Scenario: Run устарел или не совпадает
- **WHEN** доступен run с другим repository, workflow, head, base либо неподходящим trigger/mode
- **THEN** run не принимается как evidence и launcher не сообщает success

#### Scenario: Failure или cancelled
- **WHEN** подходящий по identity run завершён failure или cancelled
- **THEN** результат передаётся существующему полному triage, не преобразуется в success и не вызывает автоматический retry/dispatch без явного допустимого основания

#### Scenario: GitHub API неизвестен
- **WHEN** поиск применимого run завершён API error, incomplete response или UNKNOWN применимостью
- **THEN** launcher возвращает явный UNKNOWN/blocker и не выполняет слепой dispatch

#### Scenario: Изменённая база
- **WHEN** HEAD совпадает, но base кандидата отличается от base найденного run
- **THEN** найденный run не заменяет обязательный CI текущего кандидата

### Requirement: Повторное review передаёт дельту без косметического цикла
Повторный role/review package SHALL содержать ссылку на полный кандидат, delta от последнего проверенного кандидата и полный список прежних открытых findings с результатом исправления или явным blocker. Новые reviewer suggestions SHALL NOT становиться обязательными молча и SHALL различаться с дефектами текущего контракта. Один и тот же необработанный finding MUST NOT повторно передаваться как исправленный; после двух возвратов по одной неустранённой причине root MUST пересмотреть подход текущего среза либо вернуть конкретный blocker.

#### Scenario: Исправленный кандидат
- **WHEN** кандидат возвращается reviewer после коррекции
- **THEN** пакет показывает изменённые байты, каждый прежний finding и его disposition, сохраняя ссылку на исходный scope и полный кандидат

#### Scenario: Новый риск
- **WHEN** delta создаёт новый материальный риск
- **THEN** reviewer может обоснованно расширить проверку, а package явно называет риск и добавленную область

#### Scenario: Неустранённое замечание
- **WHEN** finding остаётся открытым
- **THEN** кандидат не маркируется исправленным или APPROVED и finding остаётся видимым blocker

#### Scenario: Только косметика ledger
- **WHEN** после одобрения меняются только checkbox завершения или исправляется опечатка номера PR без изменения нормативных, исполняемых, source/evidence-binding или authority байтов
- **THEN** новый code-review цикл не требуется, но изменение и его косметическая классификация остаются явно зафиксированы

#### Scenario: Материальные байты изменены
- **WHEN** меняются обязательный executable, normative contract, source/evidence binding, полномочия или статус GREEN
- **THEN** применимые проверки и независимое review нового кандидата остаются обязательными

#### Scenario: Финальный CI неизвестен до push
- **WHEN** versioned candidate готов к заключительному push, а его будущий CI ещё не существует
- **THEN** кандидат не обязан содержать будущий результат собственного CI; итог фиксируется в PR или существующем внешнем delivery record без отдельного commit только ради сообщения о GREEN

### Requirement: Полномочия и история не расширяются
Изменение SHALL NOT давать агенту новые GitHub, merge, deploy, branch-settings или retry полномочия. Исторические review records MUST оставаться неизменными; новый package или superseding record SHALL ссылаться на них. UNKNOWN MUST NOT считаться approval или GREEN.

#### Scenario: Прямой запуск владельцем
- **WHEN** владелец отдельно запускает workflow через Actions API/UI
- **THEN** guard штатного агентского маршрута не обещает и не навязывает глобальную exactly-once семантику

#### Scenario: Недостаточные полномочия или evidence
- **WHEN** launcher/observer не может подтвердить полномочия, применимость или обязательные результаты
- **THEN** поставка остаётся UNKNOWN/blocked без mutation branch settings, merge или deploy
