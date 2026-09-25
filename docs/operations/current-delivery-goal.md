# Текущая цель — #258, этап 2: наблюдения загрузки монтажников

Поручение владельца 2026-09-25: автономно от свежего `origin/main@99bd0974150617a01e195cec28f7d886f1ede761` реализовать и довести до merge второй последовательный этап #258: ежедневные неизменяемые наблюдения, текущую трёхгрупповую сводку, исторический grouped bar chart, first/last comparison и saved drill-down. PR #264 уже влит; справочник, карточку и picker заново не строить. Шестинедельный прогноз и второй installer-блок оставить следующему этапу; #258 не закрывать.

В том же PR read-only диагностировать production symptom: объекты в работе существуют, но официальный read-owner не показывает current/upcoming installers; результат превратить в regression evidence без изменения production facts. Кадровый статус показать штатным label, убрать из пользовательской карточки/справочника источник и время интеграции, исправить только локальные gap/padding карточки, не общий shell/CSS.

Не менять финансовые расчёты, assignment/PTO writers, calendar, ChecklistController, inspection-schedule.js или construction-control list. PR #265 по инспекциям и read-only #29 идут параллельно и не входят в candidate. Deploy/stand mutation/production writes/backfill запрещены. Merge разрешён только после exact-source green CI и planner-required независимых approvals.

Root пишет scope/spec/tests; отдельный gpt-5.6-sol/low executor реализует; независимые gpt-5.6-sol/low reviewers решают planner-required Gates 3/5. Контракт: [INSTALLER-UTILIZATION-OBSERVATIONS-001](../../specs/INSTALLER-UTILIZATION-OBSERVATIONS-001.md). Lifecycle: [add-installer-utilization-observations](../../openspec/changes/add-installer-utilization-observations/). Delivery record: [issue-258-installer-utilization-observations-delivery](issue-258-installer-utilization-observations-delivery.md).

Локально только bounded focused checks; полный `make test`/`make verify` запрещён. Один exact-source GitHub CI run после независимого review.

Интеграция перед merge выполнена с актуальным `origin/main@2372ac2b`; поставленные там #255/#267/#268 и их чужие записи сохранены без расширения scope #258.
