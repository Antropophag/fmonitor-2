## Purpose

Гарантирует, что состав основной Yii2-навигации определяется только effective permissions пользователя и не меняется из-за открытого экрана.

## ADDED Requirements

### Requirement: Видимость пунктов не зависит от текущего экрана
На каждом успешном аутентифицированном Yii2 HTML-экране с общим sidebar система SHALL формировать упорядоченный набор section links основной навигации исключительно из effective permissions текущего пользователя. Текущий route SHALL влиять только на отметку `aria-current="page"` и return path ссылки обратной связи, но MUST NOT добавлять или скрывать section links.

#### Scenario: Переход между корневым и вложенным экраном
- **WHEN** один пользователь открывает доступный корневой экран и затем доступный вложенный экран без изменения своих effective permissions
- **THEN** labels, destinations и порядок section links основной навигации на обоих экранах одинаковы
- **AND** различаться могут только `aria-current` и return path обратной связи

#### Scenario: Вложенный экран без собственного пункта меню
- **WHEN** пользователь открывает доступный вложенный экран, которому не соответствует отдельный canonical navigation item
- **THEN** система сохраняет полный разрешённый ролью набор section links
- **AND** отсутствие совпадающего current section не изменяет видимость остальных links

### Requirement: Permission mapping остаётся единым
Система SHALL применять один canonical permission mapping ко всем экранам с общим sidebar: `objects.read` для объектов монтажа, `installers.read` для монтажников, `construction_control.read` для стройконтроля, `otiz.manage` для ОТиЗ и `access.administer` для пользователей и ролей. Отсутствующий effective permission SHALL скрывать соответствующий пункт на каждом таком экране.

#### Scenario: Ограниченная роль на разных экранах
- **WHEN** пользователь без одного из canonical permissions открывает два доступных экрана с общим sidebar
- **THEN** соответствующий запрещённый пункт отсутствует на обоих экранах
- **AND** остальные разрешённые пункты совпадают на обоих экранах

### Requirement: Меню не заменяет авторизацию и не создаёт фактов
Формирование общей навигации SHALL оставаться read-only presentation behavior. Server-side route authorization SHALL сохранять прежний allow/deny outcome независимо от видимости пункта; повторные GET-запросы MUST NOT создавать или изменять persisted facts.

#### Scenario: Прямой переход на запрещённый route
- **WHEN** пользователь без требуемого permission запрашивает route напрямую
- **THEN** система возвращает прежний server-side denial outcome
- **AND** отсутствие пункта в sidebar не считается механизмом авторизации

#### Scenario: Повторное чтение
- **WHEN** пользователь повторяет GET-запросы экранов с общей навигацией
- **THEN** состав меню остаётся детерминированным
- **AND** persisted facts не изменяются
