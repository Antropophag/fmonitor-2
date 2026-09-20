## Context

См. `proposal.md` — Why и `specs/ui/minimal-operational-dashboard/spec.md`. Текущий Yii shell ведёт корневой маршрут в реестр объектов, а `MainNavigation` строит RBAC-скрытую навигацию. `MariaDbYiiObjectQueue` уже владеет канонической нормализацией текущего статуса, но её paginated seam не подходит для агрегирования всего набора. В соседнем read-only `../shlz-ui` публично выпущены CSS contracts `shlz-dashboard` и `shlz-chart-widget`; само приложение уже закрепляет `shlz.css` как локальный asset.

## Goals / Non-Goals

**Goals:**

- Один read-only public seam с тем же `objects.read`, что и реестр.
- Один bounded SQL read model для агрегатов и двух списков, использующий canonical tables/current facts.
- Визуальное продолжение существующего Yii shell и публичных `shlz-ui` contracts, без собственного chart runtime.
- Детерминированные HTTP, data, browser/responsive и read-only witnesses.

**Non-Goals:**

- Новый persistence owner, DDL, snapshot/cache, background job или state-changing application seam.
- Унификация внутренних status projections вне нужного read model.
- Графики, тренды, произвольные даты, персонализация и сохранение раскладки.
- Изменение `rapid-pilot/`, ОТиЗ-формул, существующей объектной очереди или row-level access policy.

## Decisions

### 1. Новый read model внутри InstallationProcess

Владельцем данных будет новый интерфейс/реализация операционной сводки в `app/InstallationProcess`, создаваемый через `InstallationProcessFactory`. Он принимает actor ID и явную дату среза, сам проверяет `objects.read`, fail-closed проверяет требуемую схему и возвращает только четыре числа и до десяти строк.

Агрегаты рассчитываются SQL conditional aggregation, а два top-5 списка — отдельными `ORDER BY ... LIMIT 5` запросами. Запросы используют существующие installation cases, current order/application/completion/checklist facts; статус определяется теми же правилами, что и очередь, но без materialization всех объектов. Допустимо вынести общий SQL status expression/малый чистый классификатор, если это уменьшает риск расхождения без расширения публичной поверхности.

Альтернатива — вызвать очередь по страницам и сложить результаты — отвергнута: она нарушает bounded memory/query требования и создаёт N+page поведение. Отдельная summary table отвергнута как новый persistence owner и преждевременный кэш.

### 2. Новый `/pilot/dashboard`, но без замены стартового редиректа в первом срезе

`DashboardController` обслуживает только `GET|HEAD`, применяет authenticated access и явную canonical permission check. Пункт «Дашборд» добавляется первым в группу «Монтаж» для пользователей с `objects.read`; относительный порядок существующих ссылок остаётся прежним. Корневой `/` пока продолжает вести в `/pilot/objects`, чтобы демонстрационный срез не менял привычную landing semantics без отдельного решения владельца.

Альтернатива — немедленно сделать дашборд главной страницей — отклонена: issue требует доступный пример, но не содержит решения о смене стартового маршрута для всех ролей.

### 3. Серверный HTML поверх публичного shlz-ui

View продолжает существующий Yii shell и использует публичные классы `shlz-dashboard`, `shlz-dashboard__section`, `shlz-dashboard__grid`, `shlz-chart-widget` и их semantic slots, а также уже публичные status/link/button/empty-state primitives. Нужный upstream CSS переносится в закреплённый локальный `app/YiiRuntime/Assets/shlz.css` с source/version evidence, как и остальные consumed exports. Локальный CSS допускается только для application composition/semantic data rows, а не для копирования примитивов.

Диаграммы не рисуются: четыре компактных виджета содержат число, формулу/основание и ссылку. Это честнее текущих данных и соответствует запрету сторонней chart-библиотеки.

Impeccable mode — Operate, established-world extension: сохраняются существующие typography, shell, navigation и restrained semantic color. Проверка включает desktop 1440 и mobile 390 в одной bounded visual pass, затем не более одной correction pass; механический detector запускается один раз после UI-изменений.

### 4. Ошибка всего среза вместо частичных чисел

Read model возвращает единый успешный DTO либо бросает infrastructure failure. Controller показывает безопасное общее error state и не смешивает частично рассчитанные показатели. Пустой набор является успешным нулевым результатом с единым empty state.

Альтернатива — показывать доступные виджеты при падении части запросов — отклонена: несогласованные числа на одном срезе выглядят достоверными и вредят демонстрации.

### 5. Фиксированная дата запроса и read-only доказательство

Controller вычисляет одну дату `Europe/Moscow` и передаёт её read model; все границы запроса используют это значение. Focused test подменяет clock/дату через публичную factory seam или тестовую конфигурацию, не через production query parameter. До/после fingerprint correctness-bearing tables доказывает отсутствие записей при GET, HEAD, repeat и concurrent reads.

## Risks / Trade-offs

- [Дублирование сложных правил текущего статуса] → переиспользовать общий чистый классификатор/SQL expression и добавить parity fixtures с объектной очередью для всех canonical состояний.
- [Агрегация может сканировать большой набор] → один bounded aggregate query, индексы/`EXPLAIN` на существующей схеме и measured 30k fixture; новый индекс/DDL не добавлять в этот срез, а производственный bottleneck оформить отдельно.
- [Upstream `shlz-ui` checkout может опережать закреплённый consumer asset] → копировать только публичный выпущенный contract, записать source path/hash/version и проверить отсутствие private imports.
- [Понятие «доступный объект» сейчас совпадает с глобальным `objects.read`] → сохранить действующую policy; будущий row-level scope должен войти в один read predicate и отдельный gated slice.
- [Пункт навигации увеличивает плотность sidebar] → desktop/mobile browser witness проверяет порядок, wrapping/collapse и отсутствие горизонтальной прокрутки.

## Migration Plan

1. Добавить read model, controller, route, view, navigation item и закреплённые публичные CSS contracts без DDL.
2. Запустить planner-selected focused data/HTTP/browser/architecture checks и OpenSpec strict validation; полный локальный suite не запускать.
3. После независимого Gate 5 опубликовать candidate и выполнить один exact-source CI consumer.
4. Rollback — удалить маршрут/пункт/view/read model и вернуть asset bytes; предметные данные и схема не требуют отката.

## Open Questions

- Какие дополнительные решения заказчик хочет принимать по дашборду: управление сроками, загрузкой бригад или документарным закрытием?
- Нужны ли в следующем срезе периоды, организационные разрезы или role-specific dashboards? Ответы собираются на демонстрации и не меняют текущий контракт.
