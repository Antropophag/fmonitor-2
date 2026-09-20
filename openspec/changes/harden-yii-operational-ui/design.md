## Context

См. `proposal.md` и `specs/yii-operational-ui-consistency/spec.md`. Рабочий стенд обслуживает Yii controllers/views; `rapid-pilot/Calendar.php` содержит полезный oracle трёхрядного календаря, но не входит в runtime image. В Yii одновременно присутствуют нативные `select`, локальные композиции и общий `MainNavigation`, тогда как старый Shell дополнительно декорирует HTML. Срез основан на `93094fd25fcd`, чтобы сохранить уже собранную коррекцию ОТиЗ/пагинации.

## Goals / Non-Goals

**Goals:**

- Один read owner календаря возвращает нормализованные события трёх типов без N+1 и без зависимости от view.
- Один переиспользуемый Yii helper/view composition формирует SHLZ Select и подключает официальный behavior.
- `MainNavigation` полностью владеет порядком и RBAC-фильтрацией.
- Focused HTTP/DOM/browser checks ловят повторную деградацию на реальном Yii seam.

**Non-Goals:**

- Новая иконка календаря, новые доменные факты инспекций, DDL или изменение прав.
- Перенос новой логики в rapid-pilot либо runtime import оттуда.
- Общий визуальный redesign вне устранения деградации компонентов и переполнения.

## Decisions

### 1. Расширить canonical Yii read projection, а не подключать rapid-pilot

`MariaDbYiiObjectQueue::readCalendar()` (либо выделенный рядом read owner при сохранении той же public factory seam) одним bounded чтением возвращает события `planned_start`, `planned_end`, `inspection` с общей формой. Legacy planned-start берётся из `workdatestart`; planned-finish — из принятого fallback `workdatefinish` → `plan_finish_date`. Нулевые/некорректные даты отбрасываются. View только группирует и рендерит.

Альтернатива — вызвать `RapidPilotCalendar` — отклонена: временный адаптер отсутствует в runtime image и не должен владеть production behavior.

### 2. Сохранить одну calendar-grid composition с тремя рядами

Yii view повторяет таблицу и доступные состояния публичного `shlz-calendar-grid`, но данные приходят из Yii read owner. Тоны выбираются из уже экспортируемого набора: `accent` для начала, `warning` для завершения, `success` для инспекции. Подписи и agenda сохраняют текстовый тип события.

Альтернатива — три независимых календаря — отклонена из-за потери сравнения дат и утроения навигации.

### 3. Ввести общий серверный SHLZ Select renderer

Общий helper принимает name, label, current value, options и id; выдаёт documented trigger/listbox/options, hidden form value и доступный native fallback. Один официальный behavior bundle выполняет progressive enhancement. Активные Yii views переводятся на helper; локальные обработчики выбора удаляются или делегируют официальному behavior.

Альтернатива — только перекрасить native `<select>` — отклонена: это сохраняет платформенно-серый popup и не выполняет требование полноценного компонента.

### 4. Сделать MainNavigation единственной декларативной таблицей пунктов

`MainNavigation` содержит единый фиксированный ordered manifest: «Объекты» (`objects.read`), «Стройконтроль» (`construction_control.read`), «Календарь» (`objects.read`), «ОТиЗ» (`otiz.manage`), «Монтажники» (`installers.read`), «Пользователи» и «Роли» (`access.administer`). Views вызывают только `MainNavigation::render`; старое runtime-декорирование не участвует в Yii и покрывается запретительным тестом. Canonical access check удаляет недоступные элементы, но manifest всегда сохраняет относительный порядок оставшихся.

Альтернатива — продолжить вставки/regex-перестановки — отклонена как источник расхождения между экранами.

### 5. Нормализовать display реквизитов на границе view model

Queue projection сохраняет сырые registration/factory values; общий presentation helper трактует пустое и literal `0` как отсутствие заводского номера и формирует две короткие строки. Инженер остаётся доступен на предназначенных экранах/колонках, но не в адресе списка объектов. CSS использует logical properties, `min-inline-size: 0` и controlled wrapping.

## Risks / Trade-offs

- [Расширенный calendar query может стать тяжёлым на полном legacy-наборе] → сохранить существующие date predicates, source/event limits, deterministic ordering и тест переполнения.
- [Custom select без JS может потерять отправляемое значение] → hidden value плюс доступный native fallback, HTTP-тесты GET/POST без выполнения JS.
- [Глобальная замена select затронет формы изменения данных] → инвентарный тест активных Yii views и focused tests каждого changed form; семантика name/value не меняется.
- [RBAC-навигация может раскрыть ссылку без права] → matrix-тест точных сочетаний capabilities и HTTP authorization на целевых routes.
- [Смешение с родительской OTИЗ-коррекцией] → отдельная child-ветка от exact `93094fd25fcd`, без изменения её истории; итоговый PR явно указывает stacked base либо переносится после интеграции родителя.

## Migration Plan

1. Зафиксировать RED HTTP/DOM tests на exact child base и подготовить verification plan.
2. Реализовать read projection, calendar view, общий select helper/behavior, object presentation и navigation manifest.
3. Выполнить planner-selected focused checks, independent review и visual inspection desktop/mobile в одном batched проходе.
4. Пересобрать локальный runtime без удаления named volumes; проверить `/health/ready` и авторизованные `/pilot/calendar`, `/pilot/objects`.
5. Rollback: вернуть предыдущий image tag/commit; схема и данные не меняются.
