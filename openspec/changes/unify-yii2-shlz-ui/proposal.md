## Why

Активные Yii2-экраны используют закреплённый `shlz-ui`, но собирают одинаковые таблицы, поля, окна, состояния и оболочку несколькими несовместимыми способами. Аудит среза `bced877aec8a8802e97037749ca4251d3098df1a` зафиксировал V01–V09 и риск того, что очередные локальные overrides усилят расхождение; поэтому работа должна идти как один согласованный визуальный delivery scope поверх #197 с общей приёмкой.

## What Changes

- Зафиксировать единый визуальный договор для активного Yii2-приложения: shell/page, data list, form field, modal/drawer и feedback state на публичных примитивах закреплённого `shlz-ui`.
- Исправить подтверждённые дефекты V01–V09: неполные и местные table-композиции, вложенное поле монтажников, недостижимый mobile selector окна выбора, неполноценное подтверждение выплаты, владение shell из ОТиЗ, конфликтующие responsive-правила, plain secondary forms, местные семейства окон/уведомлений и shell/landmark drift.
- Перевести все 25 активных `app/YiiRuntime/Views` и связанные partials на общий договор одним координируемым проходом с небольшими проверяемыми волнами; после каждой миграции удалять заменённые локальные CSS-правила.
- Проверить все активные экраны на закреплённой owner device matrix: 15-дюймовый ноутбук 1366×768 и 1536×864 CSS px, Redmi Pad 2 Pro 12,1″ в landscape 1280×800 и portrait 800×1280 CSS px, мобильные 360×800 и 390×844 CSS px. Открытый sidebar, touch, overlays, длинные данные и локальные scroll-контейнеры входят в обязательную приёмку.
- Сохранить маршруты, роли, payloads, idempotency, append-only histories, финансовый смысл, offline queue/storage/protocol, no-JS/server fallback и закреплённую компоновку object card.
- Расширить существующий Yii browser-контур общими инвариантами компонентов и точечными regression cases для V02/V03/V04/V06/V09; выполнить просмотренные before/after на согласованной среде и exact-source CI.
- Восстановить baseline authenticated Yii fixture/runtime routes, которые на exact `main` возвращают 503 или падают до UI assertions, ровно настолько, чтобы существующие installers/users/preopening/ОТиЗ journeys снова достигали своих публичных seams; закрепить причину отдельным regression test до визуальной реализации.
- Не обновлять и не форкать `shlz-ui` без отдельного воспроизведения дефекта на закреплённой версии; не создавать второй design system, SPA/grid framework или новый browser framework.

## Capabilities

### New Capabilities

- `ui/yii2-shlz-visual-contract`: единый наблюдаемый договор активных Yii2-экранов для shell, таблиц, форм, overlays, feedback states, responsive layout и доступности при сохранении существующего продуктового поведения.

### Modified Capabilities

Нет.

## Impact

- Акторы: все пользователи активного Yii2-приложения в пределах уже выданных разрешений; административные и финансовые действия не расширяются.
- Source oracle: бриф `frontend-audit-2026-09-22.md` (аудит среза `bced877a`), `PRODUCT.md`, `CONTEXT.md`, действующие executable specs и публичные exports закреплённого `../shlz-ui` commit `9aaedf50eabf5f92e4af1cbc9c0f2a26a171b35b`.
- Target public seam: существующие Yii2 HTTP-маршруты и пользовательские действия, наблюдаемые через серверную разметку и существующий browser runner; новых state-changing seams нет.
- Release value: один предсказуемый интерфейс без скрытых действий, обрезанных финансовых значений, двойных полей и несогласованного modal/focus поведения на поддерживаемых ширинах.
- Основные production targets — 15″ ноутбуки, Redmi Pad 2 Pro 12,1″ и мобильные телефоны; 13″ MacBook Air остаётся дополнительным stress witness, но не подменяет целевую матрицу.
- Затрагиваются `app/YiiRuntime/Views`, `ViewSupport.php`, `Assets/pilot.css`, связанные JS assets, focused PHP/browser tests и минимальная общая runtime/fixture boundary, являющаяся доказанной причиной baseline 503; product/domain writers, persistence semantics, imports, deployment и `rapid-pilot` не затрагиваются.
- Рабочий checkout сейчас содержит незавершённый candidate №157 и конфликт в `tools/verification/ci.py`; реализация этого change должна начаться только из чистого актуального `main`, после явного переключения активной очереди, и не должна включать текущий WIP.
- Owner 2026-09-22 явно разрешил расширение scope на восстановление baseline routes/fixtures после Gate 3 `CHANGES_REQUESTED`. Непроверенные browser consequences остаются гипотезами до baseline и не превращаются в требования о конкретной поломке.
