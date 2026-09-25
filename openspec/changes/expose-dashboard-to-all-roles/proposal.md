## Why

На production `GET /pilot/dashboard` возвращает `403` всем активным пользователям: пункт меню показывается по `objects.read`, но маршрут дополнительно требует `installers.read` и запрещает любого actor с ролью стройконтроля. Там же видимая кнопка «Запланировать инспекцию» отправляет команду, которую backend отклоняет `403`, потому что ни одна активная production-роль не имеет `inspection.schedule`. Владелец 2026-09-25 установил продуктовую семантику: дашборд и данные монтажников видят все роли, а видимое действие планирования инспекции должно выполняться, а не завершаться `Access denied`.

## What Changes

- Сделать `/pilot/dashboard` и его `HEAD`-вариант доступными каждому активному аутентифицированному пользователю независимо от ролей и effective permissions.
- Показывать пункт «Дашборд» каждому активному аутентифицированному пользователю на общей Yii2-навигации.
- Сохранить безопасный login/return-path для гостя, read-only семантику и отсутствие предметных записей при чтении.
- Определить безопасную общую область данных дашборда: полный операционный срез, одинаковый для всех ролей; role-specific object scope и `installers.read` не ограничивают агрегаты или переходы дашборда.
- Сделать `/pilot/installers` и `/pilot/installers/{tabId}` доступными каждой активной аутентифицированной роли с одной полной картиной закреплений; удалить construction-control actor scope и `installers.read` gate именно с read-only справочника/карточки.
- Сохранить canonical server-side authorization целевых маршрутов для ссылок и drill-down с дашборда; доступность дашборда не должна обходить права самостоятельных разделов.
- Выдать exact permission `inspection.schedule` ролям `manager` («Руководитель ФКР») и `construction_control_engineer` («Инженер строительного контроля»): закрепить его в каноническом role catalog, а существующие production grants применить точечной идемпотентной операцией владельца без изменения schema frontier.
- Согласовать кнопку «Запланировать инспекцию» и command seam: controls показываются только actor с `inspection.schedule`, а команда сохраняет ту же exact capability-проверку и canonical object scope.
- Сохранить object scope, eligibility, дату, optimistic version, replay/conflict, append-only event audit и guest/inactive запреты планирования.
- Заменить одиночный снимок загрузки монтажников шестинедельным историческим окном с серверной навигацией на предыдущий/следующий период и shlz-ui controls; полный список людей остаётся только в drill-down выбранной даты.
- Не менять права мутаций, административных экранов, selection picker и других самостоятельных разделов.

## Capabilities

### New Capabilities

- `ui/universal-dashboard-access`: доступ всех активных аутентифицированных ролей к read-only дашборду, согласованная навигация и безопасные переходы.
- `inspection/visible-scheduling-action`: согласованная авторизация видимой кнопки и server-side команд планирования инспекции в пределах canonical object scope.

### Modified Capabilities

Отсутствуют: соответствующие dashboard/navigation capabilities ещё не синхронизированы в `openspec/specs/`; новый delta явно перекрывает прежние role-gated требования только для поверхности дашборда.

## Impact

- Actor: любой активный аутентифицированный пользователь FMonitor независимо от роли.
- Source oracle: решение владельца от 2026-09-25 и воспроизведённый production-ответ `GET /pilot/dashboard → 403`.
- Target public seam: Yii2 dashboard, `/pilot/installers`, `/pilot/installers/{tabId}`, их общий `MariaDbInstallerUtilization`, `MainNavigation` и `POST /pilot/construction-control/objects/{id}/inspection-plan`.
- Release value: раздел перестаёт быть недоступным всем production-аккаунтам и становится общей операционной сводкой; динамика загрузки читается на сопоставимом шестинедельном горизонте, а не по одной точке.
- Затрагиваются focused Yii2 HTTP/navigation/dashboard tests и связанные прежние assertions о `403`.
- Не входят изменение role assignments на production, расширение иных mutation capabilities, схема БД, deployment и текущий delivery candidate №157.
