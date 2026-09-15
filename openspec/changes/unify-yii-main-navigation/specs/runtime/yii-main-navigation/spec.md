## Purpose

Обеспечить одинаковую permission-aware основную навигацию на пяти существующих Yii2-разделах без изменения маршрутов, авторизации или внутренней навигации разделов.

## ADDED Requirements

### Requirement: Единый набор основной навигации
Система SHALL на `/pilot/objects`, `/pilot/construction-control`, `/pilot/otiz`, `/pilot/admin/users` и `/pilot/admin/roles` формировать набор основных section links только из effective permissions текущего пользователя: `objects.read` разрешает «Объекты монтажа» → `/pilot/objects`, `construction_control.read` разрешает «Стройконтроль» → `/pilot/construction-control`, `otiz.manage` разрешает «ОТиЗ» → `/pilot/otiz`, а `access.administer` разрешает «Пользователи» → `/pilot/admin/users` и «Роли» → `/pilot/admin/roles`. Существующая доступная аутентифицированному пользователю «Обратная связь» SHALL оставаться одинаково доступной на этих surfaces.

#### Scenario: Все полномочия
- **WHEN** один активный пользователь со всеми четырьмя указанными effective permissions последовательно открывает пять маршрутов
- **THEN** semantic DOM основной навигации содержит на каждом маршруте один и тот же упорядоченный набор canonical links

#### Scenario: Нет полномочия ОТиЗ
- **WHEN** пользователь без `otiz.manage` открывает любую доступную страницу из пяти
- **THEN** ссылка `/pilot/otiz` отсутствует в основной навигации этой страницы

#### Scenario: Нет полномочия стройконтроля
- **WHEN** пользователь без `construction_control.read` открывает любую доступную страницу из пяти
- **THEN** ссылка `/pilot/construction-control` отсутствует в основной навигации этой страницы

#### Scenario: Ограниченная административная комбинация
- **WHEN** пользователь открывает доступный административный route с набором effective permissions, отличным от permissions другого view
- **THEN** основная навигация определяется только effective permissions этого пользователя, включая обе административные ссылки при `access.administer`

### Requirement: Текущий раздел отмечен семантически
Система SHALL ставить `aria-current="page"` ровно на одну section link, соответствующую открытому из пяти разделов, и MUST NOT менять остальные main-navigation links из-за текущего view.

#### Scenario: Переход между пятью разделами
- **WHEN** пользователь со всеми соответствующими permissions открывает каждый из пяти маршрутов
- **THEN** ровно canonical link текущего раздела имеет `aria-current="page"`, а остальные section links не имеют этого значения

### Requirement: Граница presentation-only
Система SHALL сохранить существующие ответы прямого доступа и server-side authorization для пяти routes, MUST NOT создавать domain facts при чтении страниц и SHALL сохранить существующие внутренние ссылки/вкладки ОТиЗ отдельно от основной навигации.

#### Scenario: Прямой доступ без permission
- **WHEN** аутентифицированный пользователь без permission существующего route обращается к нему напрямую
- **THEN** route возвращает тот же authorization outcome, что и до изменения меню, и не создаёт фактов

#### Scenario: Внутренняя навигация ОТиЗ
- **WHEN** пользователь с `otiz.manage` открывает `/pilot/otiz`
- **THEN** страница содержит общую основную навигацию и прежние внутренние переходы ОТиЗ

#### Scenario: Повторный read
- **WHEN** пользователь повторно читает доступные страницы
- **THEN** каждый ответ независимо отражает актуальные effective permissions и не выполняет state-changing, audit или persistence operation
