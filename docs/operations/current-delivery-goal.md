# Текущая цель — №76, документарное закрытие Yii2

Владелец подтвердил продолжение №76 после поставки harness PR89. Текущий срез:
85% монтажа → акт ПТО → декларация → исправления с причиной/историей → возврат
в карточку/очередь. Рабочий checkout `/Users/antropophag/code/fmonitor-2-yii2-documentary-76`,
branch `codex/yii2-documentary-76-20260910`. Фактический source/PR/CI получать
через `python3 tools/delivery/harness.py state`, роль/обязательства — через prepare.

[Контракт](../../specs/YII2-DOCUMENTARY-CLOSURE-001.md),
[OpenSpec](../../openspec/changes/yii2-documentary-closure/),
[delivery record](yii2-documentary-delivery-2026-09-10.md),
[независимый Gate3](../../reviews/tests/YII2-DOCUMENTARY-CLOSURE-001.md).
Root пишет spec/tests; sol/low executor реализует; независимый sol/low reviewer
проверяет Gates3/5. Применяется compact execution protocol #82 и harness PR89.

После целого кандидата, focused GREEN и Gate5 — PR и один full exact-source CI.
№76 включает оставшиеся каталоги/runtime/console и общий cutover; этот срез их
не закрывает. Стенд, чужие worktrees/WIP и все append-only факты сохраняются.
Deployment требует отдельной авторизации после upgrade/rollback evidence для
БД/PDF/фото, пользователей/сессий, offline actions и jobs.

[Предыдущий указатель](current-delivery-goal-before-documentary-2026-09-10.md)
сохранён байт-в-байт как история поручения harness.
