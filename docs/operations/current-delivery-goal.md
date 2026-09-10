# Текущая цель и очередь — 2026-09-10

Владелец поручил ограниченный инкремент системного delivery harness: измерения,
компактный runner, актуальное состояние, подготовленные role/review packages и
узкие подтверждённые consumer obligations. Результат — проверенный PR без merge
и deployment. Продуктовую миграцию #76 и полный архитектурный аудит в этот scope
не включать.

После завершения harness вернуться к следующему согласованному срезу #76:
документарное закрытие — 85% монтажа → акт ПТО → декларация → исправления с
причиной и историей → возврат в карточку/очередь. Этот срез требует собственного
целого кандидата и Gates 1–5; в рамках harness его не начинать. Сохранить
пользовательский WIP, рабочий стенд, историю и авторизацию.

Фактические HEAD/worktree, PR, CI и merge определяет
`python3 tools/delivery/harness.py state` с привязкой к точному source. Текущий
контекст и role package готовит тот же harness; датированные delivery records
остаются историческими снимками и не подменяют live state.

- Контракт: [DELIVERY-HARNESS-001](../../specs/DELIVERY-HARNESS-001.md)
- OpenSpec change: [automatic-delivery-harness](../../openspec/changes/automatic-delivery-harness/)
- Процесс: [development-process.md](../development-process.md)
- Предыдущий точный снимок этой страницы:
  [current-delivery-goal-2026-09-10-before-delivery-harness.md](current-delivery-goal-2026-09-10-before-delivery-harness.md)

Deployment требует отдельной авторизации и свидетельств upgrade/rollback,
сохранности данных, сессий, offline actions и jobs. Harness не меняет стенд.
