# Текущая цель — #258 hotfix: один доступный forecast dashboard

Поручение владельца 2026-09-26: исправить production regression после PR #280. Не создавать второй dashboard-блок: существующий «Загрузка монтажников и динамика» SHALL стать шестинедельным live-прогнозом. На production прогноз не должен быть недоступен из-за capability/scope пользователя, если общий dashboard ему доступен.

Scope: заменить historical visualization внутри existing utilization widget прогнозом `busy/free/releasing/conflict/unknown`; удалить отдельный `installer-forecast` widget; унаследовать authenticated dashboard access для forecast и detail; сохранить forecast read-owner, immutable observations/storage и fail-safe source validation. Не менять данные production вручную.

Root пишет spec/tests; отдельный `gpt-5.6-sol/low` executor реализует; независимые reviewers решают Gates 3/5. Base: merged main `708e0a6d`. Контракт и lifecycle остаются `INSTALLER-UTILIZATION-FORECAST-001` / `add-installer-utilization-forecast`, дополненные correction requirements.

Локально только bounded focused checks; полный `make test`/`make verify` запрещён. PR/merge после approvals и exact-source GREEN CI разрешены. Deploy отдельно не разрешён без явного поручения.
