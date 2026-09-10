## Context

См. `proposal.md`. Действующий route реализован в `PilotE2ECoordinator`, читает через mysqli `MariaDbInstallerDirectoryReader` и рендерит `ProductionInstallerDirectoryRenderer`. Yii2 уже владеет session/auth/access, shell/assets и соседними process routes, но отдельного installer-directory controller/read model/view нет.

## Goals / Non-Goals

**Goals:**

- Добавить Yii2-owned GET/HEAD transport и read-only workforce projection с прежними публичными результатами.
- Сохранить один явно configured Yii DB connection, существующие table prefixes и отсутствие writes.
- Доказать browser parity, HTTP failures, authorization и route-level runtime closure.

**Non-Goals:**

- Не переносить workforce sync/import, не менять schema и не вводить кадровые команды.
- Не реализовывать INS-02, новые availability/conflict/staleness semantics или контекстный selection workflow.
- Не удалять общий rapid-pilot stand/router до общего cutover №76.

## Decisions

1. Owning module — read-side Workforce projection в `app/Workforce` либо ближайшем существующем domain package; Yii controller только валидирует transport, проверяет `installers.read` и вызывает query. Persistence owner использует `yii\db\Connection`/Query Builder с process и legacy prefixes. Альтернатива — повторно вызвать mysqli reader — сохраняет временную runtime composition и не продвигает DB boundary, поэтому отклонена.
2. HTML переносится в отдельный Yii view с общими Yii shell/assets. Значения строятся из нормализованной query projection и экранируются Yii helpers. Копирование `ProductionInstallerDirectoryRenderer` в controller отклонено из-за дублирования shell и transport concerns.
3. Фильтры остаются server-side и ограниченными действующим контрактом; page size 50 и сортировка не меняются. Новые product semantics из aspirational INS-01/INS-02 требуют отдельного решения и не выводятся из текущей реализации.
4. Старый rapid-pilot route и renderer остаются oracle/stand adapter до cutover, но Yii route не включает их и не вызывает `rapid-pilot`. Architecture checks дополняются route/load-boundary и controller hotspot проверками; verification inventory включает focused HTTP и browser tests.
5. Изменений schema frontier, backup/restore, files/PDF/photos, jobs, offline actions и domain history нет. Поэтому отдельная DB migration/rehearsal ceremony для этого среза неприменима; общий upgrade/rollback gate №76 остаётся открытым.

## Risks / Trade-offs

- [Различия mysqli и Yii DAO в LIKE/числовом поиске] → Зафиксировать литеральные примеры и сравнить публичный HTML/result counts с oracle.
- [Shell/navigation drift между уже перенесёнными Yii views] → Использовать существующие asset bundles и проверить разрешённые nav items для нескольких ролей.
- [Большой каталог или N+1 по закреплениям] → Сохранить bounded двухэтапный query и проверить 125-record/50-row pagination, число запросов и отсутствие per-row query.
- [Старый stand продолжает обслуживать тот же URL] → Явно считать это временным adapter; удаление возможно только после общего cutover и не входит в Done данного среза.

## Migration Plan

1. Добавить independently reviewed RED на Yii HTTP/browser seam и verification inventory.
2. Реализовать query/controller/view/route минимально до focused GREEN.
3. Получить независимый Gate 5 на exact snapshot, создать PR и запустить один full exact-source CI.
4. Rollback этого среза — откат route/code до предыдущего Yii image; schema и данные не меняются. Рабочий rapid-pilot stand не переключается.
