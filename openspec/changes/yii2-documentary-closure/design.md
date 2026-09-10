## Context

См. proposal.md. Нормативная матрица — YII2-DOCUMENTARY-CLOSURE-001 A1–A7. Legacy CHARACTERIZE DRAFT не является актуальным разрешением broad-user/DDL поведения.

## Goals / Non-Goals

Цель — целый маршрут с existing mutation owner. Нет новой схемы, event store, permission model, файлов ПТО, offline receipt policy или stand cutover.

## Decisions

- InstallationProcess `MariaDbInstallationCompletion.record/correct` сохраняет транзакцию, case lock и authority. Новый Yii adapter получает соединение/prefix из explicit runtime config; controller не пишет таблицы.
- Read-only query и partial карточки показывают effective facts и всю цепочку. Использовать existing shlz-ui/forms/CSP без inline script; глобальный redesign не нужен.
- Не переносить строковый HTML decorator. `CompletionFlow` остаётся oracle временного native router; Yii cohort не включает его. Удаление всего native router зависит от оставшихся срезов #76.
- Schema frontier unchanged v24; completion tables уже входят в canonical migrations, backup/restore и shared fixture inventories. Применение миграций только test/setup или existing deployment. Проверяем отсутствие DDL на DML-only runtime и schema absence.
- Existing `InspectionFixture/PreopeningFixture` обеспечивает isolated canonical DB и реальные opening/identity. Новый completion fixture готовит literal 41-item85 prerequisites независимо от production weight вычисления; commands under test только HTTP. Независимые SQL snapshots проверяют persisted facts, не подменяют команду.
- Boundary consumers: Yii card/queue/checklist, InstallationProcess owner/progress, existing completion/schema manual verifiers. Required plan задаёт focused native/HTTP/browser/architecture checks; inventory подхватывает новые tests существующим способом. Проверить runtime noLegacy includes.
- Restart/session format, PDF/photo bytes, offline store и jobs не меняются; focused adjacent flows подтверждают отсутствие regression. Полная upgrade/rollback и переключение стенда остаются отдельным delivery этапом #76.

## Risks / Trade-offs

- Existing duplicate-as-conflict и correction repeat-as-new-revision сохраняются, новый operation id не вводится.
- Declaration может быть раньше PTO; ужесточение не относится к миграции.
- Existing owner требует mysqli. Дополнительный read adapter использует configured connection, не глобальный native bootstrap.

## Migration Plan

Локальный кандидат → независимые Gates3/5 → exact-source full CI/PR. Ни новых migrations, ни файлов для data rollback нет; возвращается code image при сохранении append-only rows. Стенд не переключается без отдельной авторизации.

## Полная повторная сверка перед Gate3 v3

После двух возвратов root заново сверил A1–A7 с целым кандидатом: A1 — роли/точные grants, revoked read GET/HEAD, status/activation/role revocation, CSRF, ids, методы, массивы всех action families, duplicate/malformed/media/size; A2–A3 —0/84/85, duplicate checklist/retraction, split grants, PTO→declaration, invalid/future/trim/500–501 (500 initial в cross-race), replay precedence, дата declaration раньше PTO, audit/неизменность соседних facts; A4 — два исправления/inheritance, повтор как новая версия, reason/date/details bounds, typo/foreign-type root, same-element history selectors/порядок/имя/id/time, correction при progress84; A5 —root/correction INSERT faults, schema absence, restart и все четыре case-lock races; A6 —все четыре формы/constraints, submit bounds по обеим осям1440/390, browser actions/refresh/return, status projections/item42; A7 — noLegacy/DML-only fixture/current schema/inventory, focused obligations и последующие independent Gate5/full CI. Предыдущие findings и raw failed runs сохранены, code ещё не написан.

## Адаптация существующего допуска

Четыре completion capabilities уже выдаются LocalRoleCatalog и проверяются mutation owner, но отсутствуют в закрытом списке AuthorizeLocalActor::PERMISSIONS. Для UI и предварительного HTTP допуска используется существующий публичный `MariaDbYiiLocalIdentityStore::grants` с точным BINARY grant, active user/activation/role и настроенной Yii DB. SQL авторизации не копируется в controller/query, глобальный registry/unknown-permission contract не меняется. Mutation owner повторно проверяет допуск под транзакцией. Это ограниченный мост текущего среза, не заявление о завершении всей auth migration №76.

Existing queue projection дополнительно отдаёт уже вычисленный completionProgress для карточки, чтобы отобразить реальные84/85/100 без второй формулы. Schema frontier и persisted facts неизменны.
