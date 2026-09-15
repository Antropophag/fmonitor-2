## Context

См. `proposal.md` и `specs/runtime/yii-main-navigation/spec.md`. Gap-check пяти routes обнаружил три самостоятельные sidebar-копии (`objects.php`, `users.php`, `roles.php`), общий `ViewSupport` с жёстко active objects для construction-control и controller-built OTIZ HTML только с внутренним меню. Все нужные решения доступа уже предоставляет `Yii::$app->canonicalAccess`.

## Goals / Non-Goals

**Goals:**

- Один маленький Yii2 presentation owner для markup и permission filtering MAIN navigation.
- Пять surfaces передают только current section/context; renderer получает identity из существующего view/request context и использует canonical checker.
- Сохранить текущие shlz-ui classes, визуальный sidebar и отдельную внутреннюю OTIZ navigation.

**Non-Goals:**

- Новый configuration framework, новая access policy или перенос authorization из controllers.
- Подключение остальных Yii2 surfaces, переработка layouts, CSS/mobile и OTIZ domain/application code.

## Decisions

1. **Owner — `app/YiiRuntime` presentation layer.** Общий renderer/component владеет только фиксированным перечнем существующих main destinations и HTML. Он зависит от существующего Yii view context и `canonicalAccess`, не от role names и не от таблиц RBAC. Альтернатива — передавать booleans из каждого controller — оставляет дублирование permission mapping и отвергнута.
2. **Только current-section как вариативный input.** `objects`, `construction-control`, `otiz`, `admin-users`, `admin-roles` выбирают единственный `aria-current`. Effective permissions вычисляются одинаково внутри общего seam. Альтернатива — inference по URL — создаёт скрытую route policy и отвергнута.
3. **Пять surface integrations без layout redesign.** Existing full-shell views переиспользуют renderer; `ViewSupport::begin` принимает/передаёт current section; OTIZ получает общую shell/main-nav обёртку, но existing internal nav HTML сохраняется. Не затрагиваются другие sidebar-копии вне #150.
4. **Нет persistence owner и migration.** Срез read-only, не создаёт факты, audit или schema. Architecture check требует только обычной bounded проверки; `app/PilotHttp` и verification policy не меняются.
5. **Regression через реальный Yii HTTP/browser seam.** Тест разбирает `nav[aria-label="Основная навигация"]`, href/text и `aria-current`, а не сравнивает body. Fixture меняет только test RBAC facts для комбинаций и отдельно подтверждает неизменные route denial и OTIZ internal nav.

## Risks / Trade-offs

- [OTIZ сейчас формирует HTML в controller] → ограничить адаптацию общей shell/presentation seam и не переносить OTIZ business/render logic.
- [Admin permission создаёт две destinations] → обе ссылки фильтруются одним существующим `access.administer`, как и текущие route guards.
- [Существующие tests могут ожидать точный markup] → сохранить shlz-ui classes, canonical hrefs и внутренние nav; выполнять только planner-selected focused checks.
- [Scope creep на прочие views] → подключить ровно пять заданных surfaces; другие меню остаются отдельной будущей работой.

## Migration Plan

Schema/data migration отсутствует. Rollback — вернуть presentation integration и renderer вместе с regression; RBAC и persisted state не затрагиваются.
