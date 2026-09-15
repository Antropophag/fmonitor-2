# YII2-MAIN-NAVIGATION-001 — единая основная навигация Yii2

## Простыми словами

Один пользователь должен видеть один и тот же разрешённый набор основного меню на объектах, стройконтроле, ОТиЗ, пользователях и ролях. Меняется только отметка текущего раздела; права, маршруты, внутреннее меню ОТиЗ и внешний вид sidebar не меняются.

## Публичный seam и актор

- Актор: активный аутентифицированный пользователь FMonitor 2.0.
- Публичный seam: реальные Yii HTTP GET requests к `/pilot/objects`, `/pilot/construction-control`, `/pilot/otiz`, `/pilot/admin/users`, `/pilot/admin/roles` и semantic DOM основной навигации в успешном HTML response.
- Источник доступа: существующие effective permissions через canonical access checker.
- Срез read-only: persisted facts, audit facts и side effects отсутствуют.

## Нормативное поведение

1. На каждом из пяти routes состав section links основной навигации определяется одинаково:
   - `objects.read` → «Объекты монтажа», `/pilot/objects`;
   - `construction_control.read` → «Стройконтроль», `/pilot/construction-control`;
   - `otiz.manage` → «ОТиЗ», `/pilot/otiz`;
   - `access.administer` → «Пользователи», `/pilot/admin/users`, и «Роли», `/pilot/admin/roles`.
2. Если соответствующего effective permission нет, section link отсутствует на каждой доступной странице из матрицы.
3. Существующая ссылка «Обратная связь», доступная аутентифицированному пользователю, сохраняется одинаково на этих surfaces и продолжает передавать текущий return path.
4. При всех четырёх permissions упорядоченный набор canonical main links одинаков на пяти routes.
5. Ровно canonical link текущего раздела имеет `aria-current="page"`; смена текущего route не меняет остальные links.
6. Admin menu зависит от `access.administer`, а не от конкретного admin view или названия роли.
7. Прямой route access, login/deny outcome и server-side authorization остаются прежними. Сокрытие пункта не является enforcement.
8. На `/pilot/otiz` общая MAIN navigation добавляется, а существующая внутренняя OTIZ navigation сохраняется.

## Acceptance examples

- Пользователь с четырьмя permissions получает `200` на пяти routes, одинаковый набор main links и ровно один правильный `aria-current` на каждом.
- Без `otiz.manage` `/pilot/otiz` отсутствует в MAIN navigation на всех доступных routes.
- Без `construction_control.read` `/pilot/construction-control` отсутствует в MAIN navigation на всех доступных routes.
- С `access.administer` обе admin links присутствуют вместе с любыми другими links, разрешёнными effective permissions.
- Запрос route без требуемого permission сохраняет прежний `403` (либо прежний login redirect для гостя); меню не меняет этот результат.
- OTIZ internal links «Экономика объектов», «Подготовка выплат» и «Архив расчётов» остаются доступны на OTIZ surface.

## Explicit non-goals

RBAC и semantics permissions, routes, controllers/application authorization, sidebar/mobile redesign, frontend framework, generic navigation platform, внутреннее устройство ОТиЗ и issues #21/#49/#14/#45/#52 не меняются. `rapid-pilot/` не является источником или target этого контракта.
