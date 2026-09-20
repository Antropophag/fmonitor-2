# YII-OPERATIONAL-UI-CONSISTENCY-001 — единый рабочий Yii-интерфейс

## Простыми словами

Основной Yii-интерфейс возвращает проверенное календарное представление, нормально показывает номера объекта, использует полноценные компоненты `shlz-ui` и формирует боковое меню в одном месте. Иконка календаря и предметные правила не меняются.

## Actor и public seam

Actors — аутентифицированные пользователи Yii. Public seams — `GET`/`HEAD /pilot/calendar`, `GET`/`HEAD /pilot/objects`, активные Yii-формы с выбором значения и общий sidebar. Source oracle календаря — `rapid-pilot/Calendar.php`; production runtime от rapid-pilot не зависит.

## A1. Календарь

Одна `shlz-calendar-grid` SHALL иметь ряды «Плановое начало», «Плановое завершение», «Инспекции». Начало читается из `workdatestart`, завершение — `workdatefinish` с fallback `plan_finish_date`, инспекции — из canonical schedule. Отсутствующие/нулевые даты не создают событий. Сохраняются authorization, ordering, bounds, safe failures, HEAD и no-write контракты `YII2-CALENDAR-003`.

## A2. Различимые события

Начало SHALL иметь public tone `accent`, завершение — `warning`, инспекция — `success`; тип также указан текстом в ряду и agenda.

## A3. Номера и адрес

Список SHALL показывать `Рег. № <value>` и `Зав. № <value>`. Пустой либо literal `0` заводской номер — `Зав. № не указан`. Полные фразы не выводятся. Адрес содержит только адрес и подъезд, без инженера. Длинные значения не расширяют всю страницу. Пример: `145091`/`59761.25` → `Рег. № 145091`/`Зав. № 59761.25`.

## A4. Полноценный SHLZ Select

Все применимые select-контролы активных Yii-экранов объектов, монтажников, пользователей и ОТиЗ SHALL использовать общий public SHLZ Select: trigger/listbox/options, `aria-expanded`, `aria-selected`, hidden submitted value и официальный behavior. Selected/open state не выглядит disabled. Form names/values сохраняются; без JavaScript доступен native fallback.

## A5. Навигация

`MainNavigation` SHALL быть единственным владельцем sidebar. Порядок: «Объекты», «Стройконтроль», «Календарь», «ОТиЗ», «Монтажники», «Пользователи», «Роли». Permissions: `objects.read`, `construction_control.read`, `objects.read`, `otiz.manage`, `installers.read`, `access.administer`, `access.administer`. Отсутствие права скрывает пункт без перестановки остальных. Post-render insertion/reordering запрещены.

## A6. Preservation и Done

GET/HEAD не создают domain/audit/schema facts. Иконка, transitions, RBAC grants и ОТиЗ-формулы неизменны. Done требует GREEN focused tests, planner obligations, reviews, exact-source CI и проверенный стенд `8093`.
