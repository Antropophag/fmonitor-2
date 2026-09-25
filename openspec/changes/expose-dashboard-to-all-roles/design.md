## Context

См. `proposal.md` и `specs/ui/universal-dashboard-access/spec.md`. Сейчас Yii2-навигация показывает дашборд по `objects.read`, контроллер последовательно требует `objects.read` и `installers.read`, а затем запрещает любого actor с активной ролью `construction_control_engineer`. На production эти независимые условия исключают все активные аккаунты. Сам dashboard owner повторно проверяет `objects.read`.

Вторая production-characterization показывает `POST /pilot/construction-control/objects/510/inspection-plan → 403`: UI публикует кнопку, а command owner отдельно требует `inspection.schedule`, которого нет ни у одной активной production-роли. Изменение пересекает presentation, HTTP authorization, read-model и существующий inspection-planning command boundary, поэтому требует согласованных policies и focused regression matrix. Схема БД не затрагивается.

## Goals / Non-Goals

**Goals:**

- Один источник решения «активный аутентифицированный пользователь может читать дашборд» для прямого маршрута и навигации.
- Полный общий dashboard DTO, включая installer utilization, для каждой активной роли.
- Полный role-invariant справочник и карточка монтажника для каждой активной роли.
- Сохранение guest redirect, fail-safe read errors, `HEAD` и read-only свойств.
- Шестинедельная динамика загрузки с предсказуемой навигацией по историческим окнам.
- Выполнимое видимое действие планирования инспекции для ролей руководителя ФКР и стройконтроля через exact `inspection.schedule` и canonical object scope.

**Non-Goals:**

- Не менять permissions других самостоятельных routes и mutation commands.
- Не менять роли/permissions в production-БД и не добавлять миграцию.
- Не переносить логику в `rapid-pilot` и не создавать новый persistence owner.
- Не выполнять deployment в рамках реализации change.

## Decisions

### 1. Универсальный read-доступ задаётся на публичном Yii2 seam

`DashboardController` сохраняет обязательную аутентификацию через существующий access filter, но удаляет capability checks и role-based full-scope rejection для `index` и dashboard observation reads. Альтернатива — выдать `objects.read`/`installers.read` всем ролям — отвергнута: это неявно расширило бы самостоятельные разделы и другие commands.

### 2. Dashboard owner не выполняет вторичную capability-проверку

`YiiOperationalDashboard`/его store не должны повторно запрещать actor по `objects.read`, иначе public seam останется логически противоречивым. Read owner сохраняет валидацию входов и DTO, bounded aggregation и fail-safe ошибки. Альтернатива — передавать synthetic privileged actor — отвергнута как ложная идентичность и риск аудита.

### 3. Installer utilization является общей read-only проекцией

`MariaDbInstallerUtilization` возвращает одну global projection независимо от ролей actor: dashboard, `/pilot/installers` и `/pilot/installers/{tabId}` не требуют `installers.read` и не применяют `construction_control_engineer` scope. Actor identity остаётся только для authentication/public seam, но не меняет строки, counts, assignments или history. Selection picker сохраняет собственную authorization/scope policy. Альтернатива «ограничивать только чистого инженера» отвергнута владельцем: справочник должен быть одинаковым для всех ролей.

### 4. Навигация использует безусловный authenticated item

`MainNavigation` должен уметь включить один dashboard item для любого активного identity, не связывая его с произвольной capability. Остальные элементы остаются permission-aware. Альтернатива — привязать ссылку к базовой permission, которой «обычно» обладают все роли, — отвергнута как повторение production-дефекта.

### 5. Владелец данных и зависимости не меняются

Owning modules остаются `YiiRuntime` для HTTP/presentation и существующие `InstallationProcess`/`Workforce` read owners для агрегатов. Разрешены только их текущие DB adapters; новых writers, таблиц, кэшей или внешних зависимостей нет. `rapid-pilot` остаётся oracle/adapter и не изменяется. Architecture checks не должны получать новых исключений; изменение `app/PilotHttp/*.php` не ожидается.

### 6. Регрессия проверяется матрицей actor states

Root-authored tests должны покрыть guest, inactive/invalid identity, минимальную активную роль без `installers.read`, стройконтроль, multi-role actor и privileged actor; для каждой разрешённой строки проверяются `GET=200`, `HEAD=200` с пустым телом, полный installer widget, navigation item и отсутствие writes. Прежние assertions о `403` для дашборда заменяются, но запреты целевых standalone routes сохраняются отдельными assertions.

### 7. Exact permission выдаётся двум ролям канонически

`LocalRoleCatalog` добавляет `inspection.schedule` ровно ролям `manager` и `construction_control_engineer`; новые установки получают grants из каталога. Для существующего production владелец явно авторизовал точечную транзакционную `INSERT IGNORE ... SELECT role_id` по этим двум exact role codes; операция выполнена 2026-09-25, добавила две строки, не изменила schema frontier, custom grants или назначения пользователей. Существующий inspection-planning application owner сохраняет capability check, транзакционную/replay логику и `actorHasObjectScope`: руководитель ФКР получает global manager scope, инженер — только актуально закреплённые объекты. UI публикует controls только при effective `inspection.schedule`.

### 8. Динамика использует фиксированные календарные недели

Презентация агрегирует окно в шесть последовательных семидневных групп. Значение группы — последнее сохранённое наблюдение внутри недели. Это сохраняет весь период в одном viewport и повторяет грамматику существующей диаграммы «Плановая нагрузка на 6 недель».

Dashboard вычисляет понедельник текущей недели в Europe/Moscow и читает диапазон от `currentMonday - 14 days` до `currentMonday + 27 days`. URL не управляет периодом. Шесть групп повторяют exact DOM/CSS диаграммы плановой нагрузки. Первый ряд dashboard charts — две равные колонки: этапы слева, загрузка справа; на narrow viewport они складываются в этом же порядке. Виджет загрузки содержит только заголовок, легенду и plot; summary-плашки и controls периода отсутствуют.

## Risks / Trade-offs

- [Агрегаты монтажников становятся доступны всем активным ролям] → это явное решение владельца; ФИО не сериализуются в общий дашборд, гостевой и inactive доступ остаются закрытыми, а standalone permissions не расширяются.
- [В периоде нет наблюдений] → показывать честное empty-state с границами окна, не синтетические нули и не список людей.
- [Удаление scope-filter показывает инженеру общий срез] → закрепить отдельными role-matrix assertions, чтобы будущая «оптимизация» не вернула персональный scope.
- [Общий справочник расширяет кадровое read-представление] → это явное решение владельца; guest/inactive закрыты, mutation и picker permissions не расширяются.
- [Навигация и маршрут снова разойдутся] → тестировать их одной actor matrix и по возможности использовать один универсальный policy helper.
- [Старые active-change tests требуют `403`] → инвентаризировать все dashboard authorization assertions и обновить только те, которые конфликтуют с новым owner decision; не ослаблять unrelated route tests.
- [Новая permission ошибочно попадёт другим ролям] → production operation выбирает ровно binary role codes `manager` и `construction_control_engineer`, а тест фиксирует exact canonical catalogue; фактический post-check подтвердил эти grants вместе с прежним coordinator grant.

## Migration Plan

1. Подготовить RED focused tests на текущем production-equivalent behavior.
2. Реализовать минимальное изменение policy, owner и navigation без schema migration; применить два явно авторизованных production role grants транзакционно и проверить результат.
3. Прогнать planner-selected bounded checks и независимые reviews на exact source.
4. После отдельного разрешения на публикацию доставить обычным deployment path; проверить `GET|HEAD /pilot/dashboard` под представителями production-ролей.
5. Rollback application source не отзывает owner-approved grants. Если владелец отдельно решит отменить право планирования, выполнить точечный `DELETE` только для `inspection.schedule` у exact role codes `manager` и `construction_control_engineer` после проверки, что grant не используется; по текущему решению grants сохраняются.
