# Initial Pilot Behavior Inventory

> **Disposition 2026-09-02:** registration-number/`registered` branches below
> are implemented predecessor observations, not target requirements. Target
> starts at `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`; later HTTP/composition/opening
> slices own the remaining replacement. Historical observations stay intact.

Дата среза: 2026-08-31. Это inventory наблюдаемого поведения, а не нормативная спецификация. Статус означает уровень продуктовой приемки: `UNKNOWN` не является требованием.

## PB-01 — сформировать распоряжение о закреплении

- **Mission:** создать неизменяемое основание состава до открытия работ.
- **Status:** `ACCEPTED`.
- **Actor / intent:** сотрудник ФКР формирует одно распоряжение для одного объекта монтажа.
- **Preconditions:** process capability `assignment_order.prepare`; объект допускает подготовку; минимум один трудоустроенный монтажник и один активный инженер.
- **State change / facts:** новая версия распоряжения `prepared`, снимки объекта, состава и кадровых данных, артефакт и process event; существующая версия не переписывается.
- **Observable result:** документ доступен из карточки; дело ожидает регистрации, но работы не открыты.
- **Rejected cases:** нет монтажника/инженера; уволенный монтажник; Акт ПТО; недопустимое состояние; конфликт текущей версии.
- **Authorization:** exact capability `assignment_order.prepare`.
- **Idempotency / concurrency:** не более одной новой актуальной версии; повторная/параллельная команда не должна создавать две версии.
- **Source:** `app/InstallationProcess/InstallationProcess.php`, `app/PilotHttp/PilotHttpApplication.php`, `app/PilotHttp/PrepareFormView.php`.
- **Verifier:** `tests/InstallationProcess/order_prepare_001_test.php` through `order_prepare_010_test.php`, `tests/InstallationProcess/process_command_authorization_001_test.php`.
- **Tables:** `fm2_installation_cases`, `fm2_assignment_orders`, `fm2_order_installers`, `fm2_order_artifacts`, `fm2_process_events`, `fm2_process_tasks`; legacy compatibility projection to `fm_maintable.installator*`.
- **Target context:** Assignment Orders.

## PB-02 — подтвердить регистрацию распоряжения в 1С ДО

- **Mission:** зафиксировать единый статус «Зарегистрировано в 1С ДО» до открытия работ.
- **Status:** `ACCEPTED`.
- **Actor / intent:** сотрудник с полномочием подтверждает номер вручную; будущая интеграция использует тот же application contract.
- **Preconditions:** актуальная версия `prepared`, валидный номер и источник регистрации.
- **State change / facts:** та же версия становится `registered`; добавляются номер, время и actor/source регистрации; документ не пересобирается.
- **Observable result:** объект доступен для отдельной команды открытия.
- **Rejected cases:** неверный capability; неактуальная/неподготовленная версия; повтор с конфликтующими реквизитами.
- **Authorization:** `assignment_order.confirm_registration`.
- **Idempotency / concurrency:** external registration id предусмотрен для интеграционной идемпотентности; принятая версия неизменяема.
- **Source:** `app/InstallationProcess/InstallationProcess.php`, `app/PilotHttp/PilotHttpApplication.php`.
- **Verifier:** `tests/InstallationProcess/registration_confirm_001_test.php`, `tests/InstallationProcess/persistence_registration_001_test.php`, `tests/InstallationProcess/process_command_authorization_001_test.php`.
- **Tables:** `fm2_assignment_orders`, `fm2_process_events`, `fm2_process_tasks`.
- **Target context:** Assignment Orders.

## PB-03 — открыть монтажные работы

- **Mission:** разрешить checklist только после зарегистрированного основания и фактической даты начала.
- **Status:** `ACCEPTED`.
- **Actor / intent:** сотрудник ФКР подтверждает реальное начало работ.
- **Preconditions:** capability `installation.open`; актуальное распоряжение `registered`; состав и кадровый статус допустимы; нет Акта ПТО.
- **State change / facts:** `process_state=working`, `actual_start_date`, отдельные audit timestamp и actor, process event/task transition.
- **Observable result:** карточка показывает работу открытой, checklist доступен.
- **Rejected cases:** дата раньше даты распоряжения или позже today; `prepared`; повторное открытие; отсутствующий состав; Акт ПТО.
- **Authorization:** `installation.open`.
- **Idempotency / concurrency:** второй open запрещён; команда блокирует актуальное состояние.
- **Source:** `app/InstallationProcess/InstallationProcess.php`, `app/PilotHttp/PilotHttpApplication.php`.
- **Verifier:** `tests/InstallationProcess/open_installation_001_test.php`, `tests/InstallationProcess/open_installation_001_integrity_test.php`, `tests/InstallationProcess/process_command_authorization_001_test.php`.
- **Tables:** `fm2_installation_cases`, `fm2_assignment_orders`, `fm2_order_installers`, `fm2_process_events`, `fm2_process_tasks`, `fm2_workforce_catalog` (read).
- **Target context:** Installation Execution.

## PB-04 — зафиксировать выполненный пункт checklist с составом

- **Mission:** хранить датированный прогресс и подтверждённый вклад без переписывания истории.
- **Status:** `ACCEPTED_WITH_CHANGES` — факт/атрибуция согласуются с продуктом, но rapid implementation владеет runtime DDL и смешивает HTTP sync, persistence и правила.
- **Actor / intent:** инженер отмечает пункт и фактически участвовавших монтажников, в том числе offline.
- **Preconditions:** дело открыто; роль/capability checklist; item принадлежит неизменяемому template snapshot; выбранные участники допустимы.
- **State change / facts:** append-only `item_completed` либо `item_installers_changed`, revision, device/server time, actor, template hash и кадровые snapshots участников.
- **Observable result:** revision/projection checklist обновляется; прошлый участник остаётся историческим после смены текущего состава.
- **Rejected cases:** закрытое/неоткрытое дело, stale/invalid operation, неизвестный item/template, недопустимый состав.
- **Authorization:** проверяется Pilot HTTP policy; точный стабильный application capability следует зафиксировать в slice spec.
- **Idempotency / concurrency:** `client_operation_id` unique; optimistic base/accepted revision; повтор должен вернуть уже принятый результат.
- **Source:** `app/PilotHttp/ChecklistSync.php`, `app/PilotHttp/ChecklistView.php`, `app/PilotHttp/checklist.js`.
- **Verifier:** `rapid-pilot/verify-checklist-offline-behavior.mjs`, `rapid-pilot/verify-checklist-current-crew.php`, `rapid-pilot/verify-native-checklist-template-binding.php`.
- **Tables:** `fm2_checklist_revisions`, `fm2_checklist_operations`, `fm2_checklist_operation_installers`, `fm2_checklist_template_snapshots`.
- **Target context:** Inspection Evidence.

## PB-05 — приложить/отозвать фотографию секции checklist

- **Mission:** связать доказательство с конкретной секцией и временем инспекции.
- **Status:** `ACCEPTED_WITH_CHANGES` — upload принят продуктовой спецификацией; revoke остаётся отдельным slice, а exact authorization требует GRILL-003.
- **Actor / intent:** назначенный инженер прикладывает фото-доказательство к immutable checklist section, включая offline queue.
- **Preconditions:** дело существует и `working`; section 1..8; template association валидна; pilot принимает JPEG/PNG/WebP от 1 byte до 5 MiB; корректные hash, metadata и actor. Product minimum size отдельно не задан.
- **State change / facts:** pilot хранит content-addressed blob, append-only `photo_uploaded`, revision и metadata с hash/device+server time, но не dimensions/caption. Dimensions и optional caption — `PRODUCT_ACCEPTED_BUT_NOT_IMPLEMENTED`, а не текущий oracle; section completion требует все items и хотя бы одно active photo.
- **Observable result:** accepted/duplicate + revision; active photo видна в projection и локальная operation помечена принятой.
- **Rejected cases:** malformed/ahead operation, закрытое/неизвестное дело, template mismatch, пустой/слишком большой файл, MIME/hash/length/name mismatch, более 10 active photos в section, storage failure.
- **Authorization:** pilot допускает broad `checklist.edit` либо assigned engineer; target exact policy блокирован GRILL-003.
- **Idempotency / concurrency:** unique `client_operation_id`; active `(case, section, sha256)` duplicate; revision lock сериализует лимит 10. Payload-aware повтор и cleanup после storage/DB failure должны быть target contract.
- **Source:** `app/PilotHttp/ChecklistSync.php`, `app/PilotHttp/ChecklistView.php`.
- **Verifier:** существующие offline verifiers проверяют только cache/prefetch tokens; focused upload characterization отсутствует и обязателен до RED.
- **Tables:** `fm2_checklist_photos`, `fm2_checklist_operations`, `fm2_checklist_revisions`.
- **Target context:** Inspection Evidence.

## PB-06 — запланировать инспекцию

- **Mission:** назначить дату визита инженера без выведения автоматической cadence.
- **Status:** `UNKNOWN` — pilot реализует только добавление даты; weekly cadence
  владельцем отвергнута, а правила произвольного назначения, переноса и отмены
  ещё не утверждены.
- **Actor / intent:** пользователь с `inspection.schedule` назначает дату текущему инженеру.
- **Preconditions:** сегодня/будущее; дело `working|needs_assignment_change`; актуальное распоряжение `registered`; инженер назначен.
- **State change / facts:** schedule + append-only `inspection_scheduled` event.
- **Observable result:** отметка в календаре и приоритет в очереди инженера.
- **Rejected cases:** past/invalid date; нет capability; работы не открыты; нет зарегистрированного распоряжения/инженера.
- **Authorization:** `inspection.schedule`.
- **Idempotency / concurrency:** `UNIQUE(case, engineer, date)`; `INSERT IGNORE` делает точный повтор no-op.
- **Source:** `rapid-pilot/InspectionSchedule.php`, `rapid-pilot/inspection-schedule.js`, `app/PilotHttp/ConstructionControlView.php`.
- **Verifier:** прямого behavioral verifier нет; косвенно `rapid-pilot/verify-calendar-projections.php` проверяет read projection.
- **Tables:** `fm2_pilot_inspection_schedules`, `fm2_pilot_inspection_schedule_events`, reads `fm2_installation_cases`, `fm2_assignment_orders`.
- **Target context:** Inspection Planning.

## PB-07 — закрыть монтаж актом ПТО и декларацией

- **Mission:** отделить фактическое завершение монтажа от checklist progress и сохранить основания закрытия.
- **Status:** `ACCEPTED_WITH_CHANGES` — последовательность ПТО→декларация согласуется с docs, но pilot-правило «41 item = 85%, последние 15% документами» не имеет утверждённой normative spec.
- **Actor / intent:** уполномоченный пользователь последовательно фиксирует дату Акта ПТО и реквизиты декларации.
- **Preconditions:** вычисленный progress не меньше 85%; ПТО один раз; декларация только после ПТО; даты не в будущем.
- **State change / facts:** append-only unique fact `pto_act`, затем `declaration`, с actor/time/details.
- **Observable result:** 85%→стадия документарного закрытия→100%/завершено; legacy item 42 запрещён.
- **Rejected cases:** progress <85; duplicate; декларация без ПТО; future/invalid date; пустые реквизиты.
- **Authorization:** сейчас только authenticated actor + CSRF; отдельное полномочие отсутствует и обязательно до test-user release.
- **Idempotency / concurrency:** unique `(case,fact_type)`; повтор отклоняется.
- **Source:** `rapid-pilot/CompletionFlow.php`, `rapid-pilot/ObjectQueue.php`.
- **Verifier:** `rapid-pilot/verify-completion-flow.php`.
- **Tables:** `fm2_pilot_completion_facts`, reads `fm2_checklist_operations`, `fm2_installation_cases`.
- **Target context:** Installation Completion.

## PB-08 — рассчитать воспроизводимый premium snapshot на дату

- **Mission:** дать ОТиЗ воспроизводимый срез из датированных доказательств с явными блокерами.
- **Status:** `ACCEPTED_WITH_CHANGES` — принцип snapshot принят; точные norms/Kss/КТУ/release scope требуют product approval.
- **Actor / intent:** ОТиЗ запускает расчёт на report date.
- **Preconditions:** OTIZ access; корректная дата; доказаны card operands, checklist template/progress/attribution и previous closures.
- **State change / facts:** immutable draft snapshot, object results, allocations, issues, evidence, content hash and event.
- **Observable result:** deterministic pool/allocation/formula trace либо конечные blocker codes with owners.
- **Rejected cases:** отсутствующие/несовместимые evidence, norm, attribution; недопустимая дата.
- **Authorization:** section-level OTIZ access; отдельный `premium_snapshot.calculate` seam/capability нужен.
- **Idempotency / concurrency:** одинаковые inputs дают одинаковый content; pilot создаёт новый draft и не задаёт operation-id uniqueness.
- **Source:** `rapid-pilot/Otiz.php::calculate`, `rapid-pilot/NativeOperationalPremiumInputs.php`, `rapid-pilot/NativePremiumNorms.php`, `rapid-pilot/legacy-migration/PremiumCalculation.php`.
- **Verifier:** `rapid-pilot/verify-premium-calculation.php`, `rapid-pilot/verify-native-operational-otiz-inputs.php`, `rapid-pilot/verify-native-operational-live-scenario.php`, `rapid-pilot/verify-otiz-workflow.php`.
- **Tables:** `fm2_pilot_otiz_snapshots`, `fm2_pilot_otiz_snapshot_objects`, `fm2_pilot_otiz_snapshot_allocations`, `fm2_pilot_otiz_snapshot_issues`, `fm2_pilot_otiz_snapshot_evidence`, `fm2_pilot_otiz_events`; reads checklist, completion, object-card, workforce and closure tables.
- **Target context:** Premium Decisions.

## PB-09 — принять premium snapshot

- **Mission:** сделать проверенный расчёт неизменяемым основанием дальнейших выплат.
- **Status:** `ACCEPTED_WITH_CHANGES` — immutability и blockers соответствуют принципам; полномочие и meaning of acceptance требуют утверждения.
- **Actor / intent:** ОТиЗ принимает draft без открытых blockers.
- **Preconditions:** snapshot существует, `draft`, нет open blocker.
- **State change / facts:** `accepted`, actor/time и `snapshot_accepted` event; содержимое object/allocation не меняется.
- **Observable result:** срез появляется в accepted history и допускает closure actions.
- **Rejected cases:** missing, already accepted, blockers.
- **Authorization:** сейчас общий OTIZ access; нужен exact capability.
- **Idempotency / concurrency:** row lock; повтор не переписывает accepted snapshot.
- **Source:** `rapid-pilot/Otiz.php::command` route `/snapshots/{id}/accept`.
- **Verifier:** `rapid-pilot/verify-otiz-workflow.php`.
- **Tables:** `fm2_pilot_otiz_snapshots`, `fm2_pilot_otiz_snapshot_issues`, `fm2_pilot_otiz_events`.
- **Target context:** Premium Decisions.

## PB-10 — зафиксировать удержание/закрытие суммы по объекту

- **Mission:** учитывать уже закрытые суммы append-only, не обратным вычислением прогресса.
- **Status:** `UNKNOWN` — pilot route с названием closure принимает только `discipline_cents`; термин, финансовое значение и artifact policy не подтверждены.
- **Actor / intent:** ОТиЗ вводит положительное удержание с основанием после принятия snapshot.
- **Preconditions:** accepted snapshot; object не blocked; cumulative closure не превышает pool.
- **State change / facts:** payment closure row + event; accepted snapshot остаётся неизменным.
- **Observable result:** следующий snapshot вычитает сумму из доступного фонда.
- **Rejected cases:** malformed/nonpositive money, empty/long basis, draft, blocked object, over-closure.
- **Authorization:** общий OTIZ access; exact financial capability отсутствует.
- **Idempotency / concurrency:** transaction/locks ограничивают сумму, но client operation id отсутствует.
- **Source:** `rapid-pilot/Otiz.php::command` route `/snapshots/{id}/closures`.
- **Verifier:** `rapid-pilot/verify-otiz-workflow.php`.
- **Tables:** `fm2_pilot_otiz_payment_closures`, `fm2_pilot_otiz_snapshot_objects`, `fm2_pilot_otiz_events`.
- **Target context:** Payment Closure.

## PB-11 — отметить выплаты snapshot выполненными

- **Mission:** зафиксировать факт выплаты по рассчитанным объектам.
- **Status:** `DEMO_ONLY` — bulk pilot shortcut использует фиксированное основание и не хранит платёжное доказательство; docs не утверждают этот command.
- **Actor / intent:** ОТиЗ одной кнопкой закрывает остатки всех незаблокированных объектов accepted snapshot.
- **Preconditions:** accepted snapshot; есть незакрытые суммы.
- **State change / facts:** closure rows по объектам и events.
- **Observable result:** payment queue показывает completed/no new amount.
- **Rejected cases:** draft/missing snapshot; blocked objects пропускаются.
- **Authorization:** общий OTIZ access.
- **Idempotency / concurrency:** remaining amount prevents double close in serial transaction; operation id отсутствует.
- **Source:** `rapid-pilot/Otiz.php::command` route `/snapshots/{id}/payments/complete`.
- **Verifier:** `rapid-pilot/verify-otiz-workflow.php`.
- **Tables:** `fm2_pilot_otiz_payment_closures`, `fm2_pilot_otiz_snapshot_objects`, `fm2_pilot_otiz_events`.
- **Target context:** Payment Closure.

## PB-12 — реверсировать payment closure

- **Mission:** исправлять ошибку новым фактом, не удаляя финансовую историю.
- **Status:** `ACCEPTED_WITH_CHANGES` — append-only reversal согласуется с принципом истории, но основания/роль/период закрытия требуют утверждения.
- **Actor / intent:** ОТиЗ создаёт отрицательный reversal с причиной.
- **Preconditions:** исходная closure существует и ещё не reversed; валидное основание.
- **State change / facts:** новая closure с `reverses_payment_closure_id`, отрицательными суммами, event.
- **Observable result:** последующие расчёты учитывают reversal; original остаётся.
- **Rejected cases:** missing/already reversed closure, invalid reason.
- **Authorization:** общий OTIZ access; exact reversal capability отсутствует.
- **Idempotency / concurrency:** unique reversal FK-like field обеспечивает один reversal.
- **Source:** `rapid-pilot/Otiz.php::command` reversal route.
- **Verifier:** `rapid-pilot/verify-otiz-workflow.php`.
- **Tables:** `fm2_pilot_otiz_payment_closures`, `fm2_pilot_otiz_events`.
- **Target context:** Payment Closure.

## PB-13 — принять audit decision по migrated evidence/quarantine

- **Mission:** сделать качество legacy evidence видимым и append-only до его допуска.
- **Status:** `DEMO_ONLY` для test-user operational path; это migration-control capability, не штатное изменение монтажного дела.
- **Actor / intent:** ОТиЗ/миграционный оператор фиксирует acknowledge/reject/request correction/map intent.
- **Preconditions:** immutable source identifiers/hashes совпадают с текущей projection; reason and operation id valid.
- **State change / facts:** append-only decision ledger; решение само не импортирует и не очищает quarantine.
- **Observable result:** timeline решения в reconciliation/quarantine screens.
- **Rejected cases:** stale hash, invalid issue/outcome, reused operation id with different payload, forbidden actor.
- **Authorization:** policy ledger + OTIZ access.
- **Idempotency / concurrency:** operation id and immutable hashes.
- **Source:** `rapid-pilot/Otiz.php`, `rapid-pilot/legacy-migration/MigratedEvidenceDecisionLedger.php`, `MigrationQuarantineDecisionLedger.php`.
- **Verifier:** `rapid-pilot/verify-migrated-evidence-decision-ledger.php`, `rapid-pilot/verify-migration-quarantine-registry.php`, `rapid-pilot/verify-otiz-workflow.php`.
- **Tables:** migration decision/registry/projection tables created by the named ledger/store classes.
- **Target context:** Migration Evidence Control (temporary adapter/tooling, not core process ownership).

## Read-only capabilities

Object queue/filtering (`rapid-pilot/ObjectQueue.php`, `verify-object-queue-filters.php`), calendar (`rapid-pilot/Calendar.php`, `verify-calendar-projections.php`), construction-control queue (`app/PilotHttp/ConstructionControlView.php`), installer directory (`app/PilotHttp/InstallerDirectoryView.php`, `verify-installer-directory-pagination.php`) and OTIZ history/export are projections. They consume owned facts; they do not become bounded contexts or own state transitions.
