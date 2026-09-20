## Purpose

Определяет проверяемый risk-based маршрут delivery для повторяющихся обычных изменений приложения, независимо выбирая число reviews и ширину exact-source CI.

## ADDED Requirements

### Requirement: Обычный класс выбирается по фактической дельте и контракту
Planner SHALL принимать авторскую декларацию риска только вместе с проверяемой дельтой, текущим требованием и существующими ownership/inventory данными. Допустимы классы `PRESENTATION`, `READ`, `APPLICATION_TEST_OR_REFACTOR`: первый ограничен отображением и локальным UI без protected operations; второй — search/filter/sort/pagination/read projection без изменения access scope, денег, readiness/completion, записей или внешних эффектов; третий — regression tests, test helpers и behavior-preserving refactoring без изменения исполнения, admission или достоверности тестов. Имя класса/каталога, размер diff, наличие теста и один флаг `ORDINARY` сами по себе недостаточны.

#### Scenario: Обычная функция с regression
- **WHEN** согласованная небольшая функция относится к одному из трёх классов, имеет current requirement и связанную regression, а фактическая дельта не пересекает чувствительные границы
- **THEN** harness выбирает одного автора теста и реализации и ровно один независимый final review без Gate 3

#### Scenario: Независимый unseen пример
- **WHEN** аналогичная обычная задача из иного модуля соответствует существующим ownership и contract связям, но её путь и issue отсутствуют в policy
- **THEN** она получает тот же compact lifecycle без добавления per-path или per-issue исключения

#### Scenario: Новый прикладной тест
- **WHEN** обычная дельта добавляет или изменяет прикладной regression test, не меняя правила его исполнения, допуска или достоверности
- **THEN** сам факт test diff не запрещает compact lifecycle

#### Scenario: Test admission semantics
- **WHEN** дельта меняет test runner, inventory admission, evidence validity или CI/review policy
- **THEN** она не относится к обычному test/refactor классу и сохраняет чувствительный процесс

### Requirement: Ceremony и CI breadth независимы
Planner SHALL вычислять `required_reviews` отдельно от CI selection. Обычная задача MUST сохранять `required_reviews=["final"]`, даже если неполная связь с тестами или consumers требует FULL CI.

#### Scenario: Ordinary с FULL CI
- **WHEN** дельта доказанно обычная, но полнота сокращённого набора проверок не подтверждена
- **THEN** lifecycle остаётся compact с одним final review, а CI selection становится FULL

#### Scenario: Presentation с полным mapping
- **WHEN** presentation delta и связанный изменённый regression test полностью сопоставлены с public oracle, известными consumers и необходимыми environment checks
- **THEN** planner выбирает FAST, включает все изменённые tests, consumers и environment checks и требует один final review

#### Scenario: Missing mandatory check
- **WHEN** выбранная обязательная проверка отсутствует, failed, cancelled, incomplete либо её exact-source результат неизвестен
- **THEN** harness/CI admission не сообщает общий GREEN или PR-ready

### Requirement: Чувствительные границы fail closed
Planner MUST сохранять Gate 3 + final при изменениях прав/секретов, денег, схемы, записи и сохранности истории, replay/concurrency, offline/sync, внешних эффектов или review/CI admission. Он MUST учитывать чувствительные методы в смешанном файле и не считать неопределённый чувствительный риск обычным.

#### Scenario: Смешанный файл
- **WHEN** изменённый файл содержит presentation/read код и изменённый чувствительный permission/write/schema/sensitive method
- **THEN** planner выбирает чувствительный процесс с Gate 3 + final независимо от обычных частей файла

#### Scenario: Чувствительный diff после prepare
- **WHEN** после prepare exact source получает новую чувствительную дельту
- **THEN** старый package становится stale или новый prepare пересчитывает процесс как чувствительный до reviewer/CI admission

#### Scenario: Неопределённый риск и неполный mapping различаются
- **WHEN** риск чувствительной семантики неопределён
- **THEN** требуется соответствующий review; если риск обычный, но mapping FAST неполон, требуется FULL CI без автоматического Gate 3

### Requirement: Один поддержанный источник требований и delta review
Обычная задача SHALL ссылаться на один краткий источник требований в существующем поддержанном формате и final review; система MUST NOT требовать несколько пересказов одного контракта. Finding correction внутри неизменного контракта SHALL проверяться final delta-review без нового Gate 3, пока дельта не вводит новый существенный риск или изменение исходного контракта. Исторические approvals MUST NOT переписываться под новые bytes.

#### Scenario: Correction regression
- **WHEN** после final finding автор добавляет regression и исправление внутри неизменного контракта
- **THEN** reviewer проверяет полную дельту к последнему source, а Gate 3 не добавляется автоматически

#### Scenario: Существенная correction
- **WHEN** correction меняет исходный контракт или создаёт новый существенный риск
- **THEN** harness требует пересмотра процесса и новый применимый review вместо изменения исторического approval

### Requirement: Сквозная проверка и историческая оценка
Regression SHALL проверять public маршрут `prepare → focused evidence → reviewer package → CI selection`, а не только внутреннюю функцию классификации. Оценка MUST охватывать представителей всех трёх классов из разных модулей, исторические #187, #194 и #209 как fixtures/evidence, а также минимум один ранее не использованный при формулировании правила аналогичный пример. Исторические номера и пути MUST NOT становиться whitelist.

#### Scenario: Историческая матрица
- **WHEN** evaluation читает фактические дельты #187, #194 и #209
- **THEN** отчёт фиксирует изменённые пути, применимость класса, риски и сохраняемые проверки без заявления о повторном lifecycle/CI и без предположения, что любой из них обязан получить FAST

#### Scenario: Реальный маршрут
- **WHEN** qualifying fixture проходит prepare, обязательные focused checks и reviewer preparation
- **THEN** package содержит exact source, выбранные checks, один final review и FAST либо FULL CI независимо от ceremony
