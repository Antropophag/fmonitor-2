# PERSIST-COMPLETED-INSTALLATION-STATE-001 v0.1

## Простыми словами

После первой допустимой декларации монтажное дело больше не остаётся технически «рабочим»: документ, явное состояние `completed` и аудит завершения сохраняются атомарно. Завершённое дело запрещает новые checklist-операции, но допускает исправления ПТО/декларации и продолжает участвовать в расчётах ОТиЗ, включая финальные 15% и повторные срезы. Legacy-паспорт объекта и формула премии не меняются.

## Основания, актор и публичные seams

Актор — активный сотрудник или Руководитель ФКР с exact capability существующей команды документарного закрытия. Product oracle: `PRODUCT.md`, `CONTEXT.md`, `specs/YII2-DOCUMENTARY-CLOSURE-001.md`, `specs/OTIZ-EXCEL-INPUTS-001.md`. Lifecycle: `openspec/changes/persist-completed-installation-state/`.

State-changing seam остаётся один: POST `/pilot/objects/{id}/completion` через действующий Yii2 application owner. `record_declaration` создаёт завершение; `correct_pto|correct_declaration` исправляют документную историю. Денежный read seam — существующий calculate → snapshot маршрут ОТиЗ.

Root авторит контракт и тесты; отдельный gpt-5.6-sol/low executor реализует; независимый gpt-5.6-sol/low reviewer решает planner-required Gate 3/5. Полный локальный `make test`/`make verify` запрещён; выполняются bounded checks и один exact-source CI.

## A1. Первая декларация и состояние

Для дела `working`, имеющего подтверждённый checklist progress не менее 85% и корневой факт ПТО, допустимая первая `record_declaration` MUST в одной транзакции:

1. добавить ровно один immutable root fact `declaration` с trim-реквизитами, actor и server time;
2. изменить exact case `process_state` с `working` на `completed`, увеличить только предусмотренную техническую revision/updated time, если они принадлежат owner contract;
3. добавить ровно одно append-only process event `installation_completed`, связанное с case, actor декларации и тем же подтверждённым временем.

ПТО само по себе состояние не меняет. Декларация без ПТО, при checklist <85%, для отсутствующего/не-`working` дела либо от неавторизованного actor сохраняет действующие HTTP/domain причины и не меняет ни один факт.

Пример: checklist 85%, ПТО `2026-09-05`, декларация `2026-09-06`, actor 18 → один declaration root, case `completed`, одно событие actor18; исходные 41 checklist items, ПТО и распоряжение byte-equivalent.

## A2. Authorization, transport и отказы

Наследуются exact capability, active identity/role, Yii CSRF, ID/form limits, validation и безопасные ответы `YII2-DOCUMENTARY-CLOSURE-001` A1–A3. Payload actor игнорируется. Не добавляется role-name bypass. Все отказы до commit MUST сохранять case, completion roots/corrections, process events, checklist operations/attribution/revisions, assignment/order/evidence rows неизменными.

Повтор первой декларации после успешного завершения возвращает прежний конфликт уже существующего факта и не создаёт второго transition/event. Недопустимое состояние для новой записи не маскирует authorization или payload validation в обход существующего порядка.

## A3. Concurrency и неопределённый результат

Два параллельных valid `record_declaration` из независимых соединений дают `{303,409}`: ровно один root, `completed` и одно событие. Case lock/conditional transition сериализует решение; уникальность не полагается только на HTTP.

Сбой до commit откатывает root/state/event вместе. При потере ответа после возможного commit система не утверждает отсутствие записи: пользователь перечитывает карточку; повтор не дублирует завершение. Ошибка возвращается sanitized 503/Retry-After по действующему contract. Runtime request не выполняет DDL или repair.

## A4. Команды после завершения

Все item completion, completion retraction и installer-attribution correction commands MUST требовать exact `working`; для `completed` они отклоняются без новых operations, attribution rows, revision, фото или событий.

`correct_pto` и `correct_declaration` MUST допускать `working|completed` при прежней exact capability, валидных root/date/reason и append-only concurrency contract. Correction завершённого дела добавляет только следующую immutable correction revision, сохраняет `process_state=completed`, не добавляет второе `installation_completed` и не открывает дело заново. Исправление даты/реквизитов не удаляет исходные факты.

## A5. Расчёт ОТиЗ

Native premium input MUST выбирать допустимые дела с `process_state IN ('working','completed')`, `actual_start_date <= reportDate`, существующим legacy object и прежним migration predicate `(native_candidate OR no provenance)`. `legacy_active`, `legacy_historical` и quarantine не становятся допустимыми из-за состояния.

Первый срез после ПТО+декларации видит 100% и прирост от прежних 85%, то есть финальные 15%. Повторные выплаты остаются существующим settlement-контрактом. `fm_maintable`, migration predicates, формула, attribution weights и closures не меняются.

## A6. Границы и Done

Historical reconciliation существующих `working` с ПТО+декларацией, deployment CLI/recovery, унификация всех read-проекций и `INSTALLER_ATTRIBUTION_ABSENT` вынесены в GitHub issue #276. Runtime DDL запрещён.

Публичные HTTP/application/concurrency и OTIZ tests MUST доказать A1–A5. Существующие documentary, checklist и OTIZ regressions остаются GREEN.

Done существует только для одного reviewed exact source: planner obligations разрешены, RED одобрен Gate 3 когда требуется, focused GREEN сохранён, независимый final review APPROVED и один exact-source CI GREEN. Merge/deploy не подразумеваются.
