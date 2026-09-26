## Purpose

Ограничивает рост Docker storage от локальных focused и disposable workflow FMonitor 2, сохраняя активные контейнеры, данные стенда и ресурсы других проектов.

## ADDED Requirements

### Requirement: Focused build имеет ограниченную identity
Локальный focused runner SHALL использовать стабильную identity, производную только от профиля и полных dependency inputs образа. Source revision SHALL сохраняться как provenance результата запуска и MUST NOT сама по себе создавать новую тяжёлую image identity.

#### Scenario: Повтор на другом source revision
- **WHEN** два запуска используют один профиль и byte-identical dependency inputs, но разные source revisions
- **THEN** оба запуска используют одну image identity, а каждый результат сообщает свой source revision

#### Scenario: Изменение зависимости
- **WHEN** меняется любой dependency input, влияющий на содержимое focused image
- **THEN** runner использует новую deterministic image identity и не принимает прежний образ как соответствующий новым inputs

### Requirement: Тяжёлая сборка защищена бюджетом диска
Единый project-owned build seam MUST перед тяжёлой сборкой измерить доступное место, применить bounded cleanup только при нарушении настроенного порога, повторно измерить место и fail closed без запуска сборки, если минимальный запас не восстановлен. Порог, измеренное значение, предпринятые действия и итог SHALL быть machine-readable; секреты и полный environment MUST NOT выводиться.

#### Scenario: Достаточный запас
- **WHEN** перед focused build доступное место не ниже минимального порога
- **THEN** runner не запускает cleanup и продолжает ровно одну сборку

#### Scenario: Запас восстановлен
- **WHEN** место ниже порога, bounded cleanup завершается успешно и повторное измерение достигает порога
- **THEN** runner сообщает cleanup и продолжает ровно одну сборку

#### Scenario: Запас не восстановлен
- **WHEN** после bounded cleanup место остаётся ниже порога либо измерение/cleanup завершается ошибкой
- **THEN** runner возвращает ненулевой статус до `docker build` и сообщает безопасную причину

### Requirement: Cleanup ограничен ресурсами focused workflow
Автоматический storage-guard cleanup SHALL удалять только неиспользуемые образы с явным project-owned label и cache выделенного project builder в пределах настроенных age/size/free-space правил. Он MUST NOT вызывать глобальный `docker system prune`, удалять containers, networks или volumes, либо выбирать чужие images по отсутствию label. Единственное разрешённое автоматическое удаление volume принадлежит exact-project disposable teardown из отдельного lifecycle requirement; storage guard им не владеет.

#### Scenario: Присутствуют данные локального стенда
- **WHEN** disk guard выполняет cleanup при существующих named и anonymous volumes и активных контейнерах
- **THEN** ни одна cleanup-команда не выбирает volumes или containers

#### Scenario: Присутствуют чужие образы
- **WHEN** Docker daemon содержит неиспользуемые образы без project-owned label
- **THEN** cleanup оставляет их вне области удаления

#### Scenario: Повторный cleanup
- **WHEN** два последовательных запуска не находят новых подходящих ресурсов
- **THEN** второй запуск успешно завершается как no-op с теми же границами безопасности

### Requirement: Disposable lifecycle владеет teardown
Каждый repository-owned disposable Compose workflow SHALL иметь exact project identity и гарантированный teardown собственных ephemeral resources после успеха, ошибки и поддерживаемого сигнала прерывания. Persistent local-stand resources MUST оставаться вне disposable teardown.

#### Scenario: Успешный disposable run
- **WHEN** disposable операция завершается успешно
- **THEN** её teardown выполняется один раз с удалением принадлежащих ей ephemeral volumes и orphans

#### Scenario: Ошибка или прерывание
- **WHEN** disposable операция завершается ошибкой либо получает поддерживаемый сигнал после создания ресурсов
- **THEN** teardown всё равно предпринимается, исходный ненулевой outcome сохраняется, а cleanup failure сообщается отдельно

#### Scenario: Persistent stand
- **WHEN** выполняется обычный local-stand workflow
- **THEN** автоматический disposable teardown не выбирает его project identity или persistent volumes

### Requirement: Одновременные запуски сериализуют destructive maintenance
Project-owned cleanup MUST сериализоваться между одновременными focused runners, повторно проверять состояние после получения lock и не считать занятые ресурсы кандидатами на удаление.

#### Scenario: Два runner требуют cleanup
- **WHEN** два runner одновременно обнаруживают низкий запас диска
- **THEN** destructive maintenance выполняется последовательно, каждый runner повторно измеряет место, и ни один не удаляет ресурс, используемый другим

### Requirement: Оператор получает безопасную диагностику и host guidance
Repository diagnostics SHALL показывать host free space, суммарные Docker images/build-cache/volumes и применённые project limits. Она SHALL отличать project-owned automatic cleanup от manual owner action и MUST NOT автоматически редактировать Docker Desktop settings.

#### Scenario: Проверка doctor
- **WHEN** оператор запускает документированный diagnostic seam
- **THEN** вывод содержит machine-readable измерения и actionable рекомендацию для Docker Desktop GC без изменения host configuration
