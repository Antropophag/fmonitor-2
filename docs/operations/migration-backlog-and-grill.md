# Migration backlog and initial GRILL package

## Recently completed

- `INSPECTION-PLANNING-SCHEMA-001` — DONE: canonical v9 exact family,
  read-only DML-runtime consumers, preservation/conflict/prefix/CHECK coverage,
  architecture and independent Gates 1–5 approved; product scheduling semantics
  remain in separate behavior decisions.

- `INSTALLATION-COMPLETION-SCHEMA-001` — DONE: canonical literal v10 owns the
  two-member append-only completion family; runtime completion DDL removed;
  DML-only HTTP/bootstrap, preservation, conflict, prefix/collation,
  architecture and independent Gates 1–5 approved. Remaining RBAC/login and
  combined-PDF failures belong to their existing separate slices.

## Backlog

### READY_FOR_GATE1_REVIEW — local RBAC fixture slices

- `PILOT-OBJECT-READ-RBAC-FIXTURES-001` — OpenSpec
  `pilot-object-read-rbac-fixtures`, 4/4 planning artifacts, strict-valid.
- `PILOT-PREPARE-RBAC-FIXTURES-001` — OpenSpec
  `pilot-prepare-rbac-fixtures`, 4/4 planning artifacts, strict-valid.
- `PILOT-E2E-RBAC-FIXTURES-001` — OpenSpec
  `pilot-e2e-rbac-fixtures`, 4/4 planning artifacts, strict-valid; combined-PDF
  остаётся отдельной downstream dependency.

Все три наследуют stable `LOCAL-RBAC-AUTH-CONTRACT-001` и требуют собственных
Gate 1 review/owner approval до RED или fixture implementation.
Final planning rereviews: `APPROVED_FOR_GATE1_DRAFT`.

### READY_FOR_GATE1_REVIEW — remaining verify blockers

- `PILOT-E2E-COMBINED-PDF-001`: OpenSpec `pilot-e2e-combined-pdf`, 4/4,
  strict-valid; owner decision already establishes one combined PDF, но exact
  executable spec/review/approval остаются отдельными gates.
- `PILOT-SESSION-STORAGE-001`: OpenSpec
  `define-pilot-session-storage-contract`, 4/4, strict-valid; configurable
  ownership-checked filesystem session root заменит hardcoded OS-home only after
  security Gate 1 approval.

Final independent planning rereviews для combined-PDF и session storage:
`APPROVED_FOR_GATE1_DRAFT`.

### READY_FOR_OWNER_APPROVAL — classification provenance

- `CLASSIFICATION-PROVENANCE-SCHEMA-001` — exact reviewed SHA-256
  `a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed`;
  explicit owner approval разблокирует Gate 2. OpenSpec package strict-valid и
  синхронизирован с no-ledger v11/runtime/source-sentinel contract.

Every implementation slice requires an OpenSpec change, accepted executable spec, RED evidence, independent test review, minimal implementation, rapid-pilot adapter, architecture/regression verification and independent code review.

### READY candidates

| Slice | Oracle / target seam | Characterization | Done emphasis |
|---|---|---|---|
| `INSPECTION-ITEM-COMPLETE-001` Complete one checklist item with immutable installer attribution | `ChecklistSync::accept`; target `InspectionRecording::completeItem` | `rapid-pilot/verify-checklist-offline-behavior.mjs`, `verify-checklist-current-crew.php` | no runtime DDL; operation idempotency and historical crew preserved |
| `INSPECTION-PHOTO-UPLOAD-001` Attach one photo to a checklist section | `ChecklistSync`; target `InspectionRecording::uploadPhoto` | focused cases from `verify-checklist-offline-behavior.mjs` and `verify-checklist-offline-prefetch.php` | canonical migration, content hash, duplicate behavior, storage failure contract |
| `INSPECTION-ATTRIBUTION-CORRECT-001` Correct installers by append-only operation | `item_installers_changed`; target `InspectionRecording::changeItemAttribution` | checklist projection/current-crew verifier | original completion fact unchanged; exact authorization |
| `COMPLETION-PTO-001` Record PTO fact after verified threshold | `CompletionFlow::handle`; target `InstallationCompletion::recordPtoAct` | `verify-completion-flow.php` | only after owner confirms threshold semantics; exact capability and migration |
| `PREMIUM-SNAPSHOT-DETERMINISM-001` Calculate a draft from accepted operands | `PremiumCalculation::calculate`; target `PremiumDecisions::calculatePremiumSnapshot` | `verify-premium-calculation.php` | safe if limited to pure versioned calculation; no financial acceptance implied |

Recommended calibration: `INSPECTION-ITEM-COMPLETE-001`. It is a real state-changing seam, has deterministic verifiers, exercises append-only history/idempotency, and does not require deciding the financial model. Gate 1 must explicitly confirm its stable authorization name and accepted operation contract before RED.

### NEEDS_GRILL

| Slice | Blocked decision |
|---|---|
| `INSPECTION-SCHEDULE-001` Schedule first/next inspection | cadence, owners, due-date rules, reschedule/cancel semantics |
| `COMPLETION-PTO-001` and `COMPLETION-DECLARATION-001` | whether 85% + documents = 100%, authorization and declaration requirements |
| `PREMIUM-OPERANDS-001` | approved norm version, Kss/КТУ and attribution rules |
| `PREMIUM-ACCEPT-001` | acceptance authority and legal/process meaning |
| `PAYMENT-CLOSURE-001`, `PAYMENT-REVERSAL-001` | closure terminology, evidence, roles, dates, reversal controls |
| `INSPECTION-PHOTO-UPLOAD-001` and related evidence mutations | exact capability plus current-assignment authorization; queued upload after reassignment |

### DEMO / migration tooling, not release-critical until promoted

- bulk `PAYMENTS-COMPLETE` shortcut;
- reconciliation/quarantine UI decisions;
- historical replay and active-baseline screens;
- generic calendar/read-model polish.

## GRILL-001 — inspection, completion and financial decision semantics

### Topic

Какие rapid-pilot transitions становятся stakeholder-approved state-changing paths первого test-user release.

### Why this decision is needed

Код уже позволяет планировать инспекцию, превращать 85% checklist в 100% через ПТО/декларацию, принимать premium snapshot и закрывать деньги. Без решения владельца нельзя безопасно превратить эти shortcuts в application contracts и authorization policy.

### What repository/pilot currently does

- `RapidPilotInspectionSchedule` добавляет любую дату today/future для текущего инженера; exact duplicate is no-op; переноса/отмены нет.
- `RapidPilotCompletionFlow` суммирует hard-coded weights до 85%, затем требует ПТО и декларацию; legacy item 42 блокируется.
- `RapidPilotOtiz` рассчитывает immutable draft, принимает его без blockers, записывает discipline-only closure, умеет bulk-complete и reversal.
- Все три области создают schema at runtime; completion/OTIZ mutations используют слишком широкую авторизацию.

### What existing specs/docs imply

- При состоявшейся инспекции фиксируются наблюдаемый состав и прогресс.
  Weekly cadence полностью отвергнута владельцем 02.09.2026.
- После 100% и Акта ПТО дело закрывается; premium snapshot должен быть воспроизводим (`docs/fmonitor-2-discovery.md`).
- Точные premium formulas должны быть версионированы; автоматический расчёт ранее предлагалось не включать до стабилизации распоряжений/инспекций.
- Append-only history и exact application capabilities обязательны.

### Questions, recommendations and consequences

1. **RESOLVED: недельная cadence отсутствует.**
   - Owner decision 02.09.2026: полностью отвергнуть weekly requirement и
     автоматические `+7 days` due/overdue rules.
   - Remaining question: кто и в каких состояниях может назначить, перенести или
     отменить произвольную дату визита — отдельный behavior contract.

2. **Подтверждаем ли модель `checklist = 85%`, ПТО = доступ к последнему этапу, декларация = 100%/completed? Какие документы обязательны?**
   - Recommendation: не утверждать hard-coded 85/15 как доменное правило без нормативного источника. Для test release зафиксировать checklist progress отдельно; completed требует 100% утверждённого template + ПТО, а декларацию хранить отдельным обязательным/необязательным основанием по ответу владельца.
   - Alternative consequence: сохранение pilot 85/15 быстро, но напрямую влияет на premium и может закрепить недоказанную формулу; удаление declaration может потерять обязательное evidence.

3. **Кто может фиксировать ПТО/декларацию и может ли исправлять ошибочную дату?**
   - Recommendation: отдельные capabilities `installation_completion.record_pto` и `.record_declaration`; исправление только superseding/correction fact с причиной, никогда UPDATE/DELETE.
   - Alternative consequence: общий authenticated доступ небезопасен; запрет любых corrections приведёт к ручным DB-правкам.

4. **Входит ли автоматический premium calculation/acceptance в scope тестовых пользователей 2026-09-09?**
   - Recommendation: включить read-only deterministic preview + blocker list, но не production-significant acceptance/payment до утверждения norms и ролей; characterization продолжать.
   - Alternative consequence: полный scope требует немедленного утверждения formulas, КТУ, financial authorization и evidence; исключение всего ОТиЗ не даст проверить целевой workflow.

5. **Что именно означает pilot `payment closure`: фактическая выплата, удержание, передача в payroll или закрытие расчётного обязательства? Какое доказательство обязательно?**
   - Recommendation: разделить `recordActualPayment` и `recordDeduction`; требовать effective date, external reference/artifact hash, сумму, основание и actor; bulk-complete не продвигать.
   - Alternative consequence: единая closure теряет семантику и позволяет фиксированной кнопке создать финансовые факты без источника.

6. **Кто может calculate, accept, record payment/deduction и reverse? Допустимо ли одному человеку выполнить все действия?**
   - Recommendation: четыре exact capabilities; calculate доступен ОТиЗ, accept — руководителю/назначенному approver, payment evidence — ОТиЗ/payroll role, reversal — отдельное повышенное право с обязательной причиной. Для pilot separation-of-duties можно сделать warning, но audit обязателен.
   - Alternative consequence: единый OTIZ access ускоряет demo, но не соответствует критической authorization готовности; жёсткое two-person rule может заблокировать малую тестовую группу.

### Exactly blocked slices

`INSPECTION-SCHEDULE-001`, `INSPECTION-RESCHEDULE-001`, `COMPLETION-PTO-001`, `COMPLETION-DECLARATION-001`, `PREMIUM-OPERANDS-001`, `PREMIUM-ACCEPT-001`, `PAYMENT-CLOSURE-001`, `PAYMENT-REVERSAL-001`. Checklist item/photo/attribution slices и pure premium calculation characterization не заблокированы.

## GRILL-003 — inspection evidence authorization

### Topic

Кто вправе создавать inspection evidence и что происходит с offline upload после смены назначенного инженера.

### Why this decision is needed

Pilot разрешает upload пользователю с broad `checklist.edit` **или** текущему инженеру карточки, а application object вообще не проверяет authorization. Product spec требует активного инженера строительного контроля из текущего зарегистрированного распоряжения. Target seam нельзя сделать fail-closed без выбора policy.

### What repository/pilot currently does

- HTTP policy принимает broad checklist editor либо совпадение с текущим engineer.
- `ChecklistSync` напрямую вызываем и не имеет application authorization guard.
- Offline operation содержит device time, но принимается по состоянию и actor на момент server receipt.

### What existing specs/docs imply

- Evidence создаёт активный назначенный инженер; при смене назначения доступ прежнего инженера прекращается.
- State-changing capability должна принадлежать одному application module и иметь exact public seam.
- История append-only; supervisor/correction exceptions должны быть отдельными командами, а не обходом upload policy.

### Questions, recommendations and consequences

1. **Upload требует одновременно exact capability `inspection.photo.upload` и текущее назначение инженером?**
   - Recommendation: да — capability даёт право действия, assignment ограничивает object scope.
   - Alternative consequence: broad `checklist.edit` допускает чужие объекты; assignment-only не даёт явной политики отзыва/admin access.

2. **Принимаем ли queued upload прежнего инженера после смены назначения, если device time был раньше?**
   - Recommendation: нет; повторно проверить current assignment при server receipt, сохранить bytes локально и вернуть deterministic rejection.
   - Alternative consequence: приём по device time требует отдельной historical-assignment и anti-backdating policy и сохраняет доступ после revoke.

### Exactly blocked slices

Только authorization clauses/Gate 1 для `INSPECTION-PHOTO-UPLOAD-001`, `INSPECTION-ITEM-COMPLETE-001`, `INSPECTION-ATTRIBUTION-CORRECT-001` и будущего `INSPECTION-PHOTO-REVOKE-001`. Upload characterization, canonical schema ownership, validation и storage-failure contracts остаются READY.

## GRILL-002 — security boundary and assignment-order artifact contract

### Topic

Подтвердить две уже реализованные, но не прошедшие SSD/TDD смены публичного контракта: local RBAC, route-specific JavaScript CSP и объединённый PDF распоряжения.

### Why this decision is needed

Утверждённые specs всё ещё требуют legacy-role authorization, CSP без `script-src` и два HTML-артефакта. Runtime уже использует local RBAC, same-origin JavaScript и один PDF. Поэтому восемь DB-verifiers красные, а механическая правка tests закрепила бы неутверждённую security/product semantics.

### What repository/pilot currently does

- `AccessPolicy` авторизует только active local user с `activation_state=active`, active role и exact permission; наличие local tables отключает legacy fallback.
- Общий HTTP responder добавляет `script-src 'self'` всем ответам; часть успешных screens действительно загружает external JS. `CompletionFlow` при этом вставляет inline script, который текущая CSP блокирует.
- Production renderer создаёт один combined PDF `order`; старый E2E verifier всё ещё скачивает `order` и `appendix` как HTML.

### What existing specs/docs imply

- `PILOT-HTTP-AUTH-001` v0.12 и `PILOT-UI-SHELL-001` требуют CSP без JavaScript и описывают inherited legacy identity/roles.
- `ARTIFACT-STORE-001` уже утверждает один combined PDF без appendix.
- `PILOT-E2E-FLOW-001`, `PRODUCT.md` и pilot docs всё ещё описывают два артефакта.
- Exact authorization, fail-closed security и independent review обязательны.

### Questions, recommendations and consequences

1. **Подтверждаем local RBAC как authoritative модель для test-user release?**
   - Recommendation: да; active user + active activation + active role + exact permission, без name-based или implicit authenticated-user access. Legacy directory остаётся только импортируемым evidence.
   - Alternative consequence: возврат к legacy fallback ослабляет уже построенную boundary; одновременная поддержка двух источников создаёт неоднозначную авторизацию.

2. **Разрешаем same-origin external JavaScript только на явно script-enabled успешных routes?**
   - Recommendation: да; `script-src 'self'` только для allowlisted 2xx HTML, без inline/eval. Ошибки, redirects, assets и script-free HTML сохраняют строгую CSP без `script-src`; inline fragment вынести во внешний asset или удалить.
   - Alternative consequence: полный запрет JS ломает текущие queue/scheduling interactions; глобальное разрешение расширяет attack surface и нарушает утверждённый контракт.

3. **Подтверждаем один объединённый PDF распоряжения вместо отдельных order + appendix HTML?**
   - Recommendation: да; синхронизировать E2E/product/pilot docs с уже утверждённым `ARTIFACT-STORE-001`, оставить один versioned PDF artifact.
   - Alternative consequence: возврат к двум HTML artifacts противоречит production renderer и recent artifact-store contract; поддержка обоих удваивает surface без release-value.

### Exactly blocked slices

`LOCAL-RBAC-AUTH-CONTRACT-001`, `PILOT-OBJECT-READ-RBAC-FIXTURES-001`, `PILOT-PREPARE-RBAC-FIXTURES-001`, `PILOT-E2E-RBAC-FIXTURES-001`, `PILOT-ROUTE-CSP-001`, `PILOT-E2E-COMBINED-PDF-001`. Остальные READY ownership/discovery задачи продолжаются.

## GRILL-004 — source of fresh test-user data

### Topic

Чем заполняется fresh test environment к первому входу тестовых пользователей: стабильным synthetic/native набором или sanitised legacy cutover.

### Why this decision is needed

Оба варианта позволяют тестировать основной workflow, но только legacy cutover делает migration provenance/quarantine schemas и доступ к реальным legacy evidence release-critical. Без выбора нельзя честно определить минимальный deployment scope и reproducibility contract.

### What repository/pilot currently does

- `bin/fmonitor2-pilot-demo.php` создаёт deterministic synthetic legacy-shaped source fixture, canonical process rows и demo identity/configuration.
- Existing golden E2E использует fixed production-shaped MariaDB fixtures и проходит prepare → register → open без реального historical cutover.
- Rapid-pilot также содержит active-baseline classification, provenance и quarantine tooling; inventory классифицирует его как `DEMO_ONLY` migration-control capability.
- Generation bootstrap всё ещё владеет destructive seed/reset DDL и не является допустимым production schema owner.

### What existing specs/docs imply

- TEST-USER-READY требует stable fixtures и воспроизводимый fresh deployment, но не требует переноса реальной истории до 2026-09-09.
- Primary dumps/evidence остаются вне repository; в git допустимы только derived redacted contracts.
- Runtime-DDL migration plan оставляет active-baseline/provenance schemas post-release debt, если test contour использует native fixtures.

### Questions, recommendations and consequences

1. **Для первого test-user contour используем deterministic synthetic/native fixture или sanitised slice реальных legacy данных?**
   - Recommendation: deterministic synthetic/native fixture с 2–4 ролями и несколькими cases, покрывающими golden journey и rejection paths; реальный cutover вынести в отдельный post-test migration rehearsal.
   - Alternative consequence: sanitised legacy slice повышает реалистичность, но немедленно требует source-selection/redaction policy, provenance/quarantine migrations, controlled evidence transfer и дополнительную release verification.

2. **Если выбран synthetic fixture, должен ли reset всегда возвращать один exact named scenario set без сохранения действий предыдущего test run?**
   - Recommendation: да; destructive reset — явная operator-only test-environment команда, обычный restart сохраняет состояние, а scenario version/hash видимы в setup result.
   - Alternative consequence: mutable/ad-hoc seed снижает воспроизводимость дефектов; reset при каждом restart уничтожает работу тестировщиков.

3. **Нужны ли тестовым пользователям реальные персональные данные до отдельного cutover approval?**
   - Recommendation: нет; только вымышленные ФИО, адреса, email и identifiers, явно маркированные как тестовые.
   - Alternative consequence: реальные/псевдонимизированные данные требуют отдельного security/privacy решения, access controls и retention/deletion procedure.

### Exactly blocked slices

Только `TEST-USER-FIXTURE-SEED-001` Gate 1 и решение, продвигать ли до release `CANONICALIZE-ACTIVE-BASELINE-PROVENANCE-SCHEMA`/quarantine tooling. Canonical workforce, identity/access, checklist/evidence ownership и behavior characterization не заблокированы.

## GRILL-005 — identity/access interrupted-migration recovery

### Topic

Как canonical identity/access migration должна обрабатывать частично созданное,
но exact-compatible семейство из девяти таблиц.

### Why this decision is needed

MariaDB schema DDL commit-ится по statements. Процесс может оборваться после
создания нескольких таблиц, поэтому повторный запуск неизбежно увидит partial
family. Текущий OpenSpec требует fail-closed conflict для любого partial state,
а подготовленная executable spec разрешает создать только отсутствующие таблицы
после полного read-only preflight всех существующих members.

### What repository/pilot currently does

Bootstrap создаёт восемь таблиц отдельными `CREATE TABLE IF NOT EXISTS`, а
девятая status-events table создаётся lazy request path. Поэтому partial family
уже является нормальным наблюдаемым состоянием, хотя canonical ownership ещё не
реализовано.

### What existing specs/docs imply

Canonical migrations должны быть restartable, не изменять populated compatible
tables и выполнять zero mutation при любом несовместимом member. OpenSpec change
сейчас формулирует более строгий all-or-none rule; `IDENTITY-ACCESS-SCHEMA-001`
задаёт exact-compatible partial recovery. Два контракта нельзя утверждать
одновременно.

### Questions, recommendations and consequences

1. **Обновить OpenSpec под exact-compatible partial recovery?**
   - Recommendation: да; сначала проверить всё существующее семейство без DDL,
     при полном exact match создать только missing members в dependency order,
     затем повторно проверить полный fingerprint и зарегистрировать version.
   - Alternative consequence: all-or-none fail-closed требует отдельной ручной
     repair procedure после любого interrupted DDL; автоматический rollback
     невозможен, а удаление уже созданных таблиц небезопасно.

2. **Считать ли любой incompatible или extra member безусловным conflict с zero mutation?**
   - Recommendation: да; recovery разрешена только для отсутствия tables, не для
     исправления, преобразования или удаления существующей schema/data.
   - Alternative consequence: schema repair внутри migration делает security
     ownership destructive и затрудняет доказательство сохранности данных.

### Exactly blocked slices

Только approval, RED и implementation `CANONICALIZE-IDENTITY-ACCESS-SCHEMA`.
Checklist/evidence schema discovery, characterization и остальные READY slices
продолжаются.

## GRILL-006 — inspection photo limit and replay semantics

### Topic

Определить target policy для максимального числа фото, overflow в offline queue
и повторного использования client operation id с другим payload.

### Why this decision is needed

Pilot жёстко ограничивает 10 active photos на section и принимает stale base
revision. Эти детали не утверждены как продуктовый контракт. Кроме того, race с
одинаковым operation id и разными bytes может вернуть payload-unaware duplicate
и оставить orphan blob после DB rollback.

### What repository/pilot currently does

Server под case-row lock сначала проверяет active same-content duplicate, затем
считает non-revoked rows `(case, section)` и отклоняет новый hash при count >= 10.
Browser заранее обрезает очередь по локальному projected count. Operation replay
сравнивается только по id, не по canonical payload fingerprint.

### What existing specs/docs imply

Evidence должна быть неизменяемо связана с inspection section и accepted command
identity должна быть idempotent. Точный numeric cap, scope и overflow UX не
утверждены. Authorization/current assignment остаются отдельно в GRILL-003.

### Questions, recommendations and consequences

1. **Какой limit и scope нужны для test-user release?**
   - Recommendation: максимум 10 active photos на section; revoked photos не
     считаются, но их history сохраняется.
   - Alternative consequence: limit на visit/case требует другого aggregate и
     меняет конкуренцию между sections; unlimited повышает storage/UX risk.

2. **Что делать с queued overflow после server rejection?**
   - Recommendation: deterministic non-retryable `PHOTO_LIMIT_REACHED`, item
     остаётся локально видимым и removable/replacable, без автоматического retry.
   - Alternative consequence: бесконечный retry создаёт шум и не может пройти,
     silent drop теряет пользовательское evidence без объяснения.

3. **Одинаковый client operation id с другим canonical payload — duplicate или conflict?**
   - Recommendation: `OPERATION_PAYLOAD_CONFLICT`, zero DB/file mutation; exact
     replay остаётся duplicate/idempotent.
   - Alternative consequence: payload-unaware duplicate скрывает client bugs и
     может ошибочно подтвердить несохранённые bytes.

### Exactly blocked slices

Target acceptance clauses/RED `INSPECTION-PHOTO-UPLOAD-001` и будущий offline
queue UX slice. PILOT_ONLY `CHARACTERIZE-INSPECTION-PHOTO-LIMIT-CONCURRENCY-001`
не заблокирован; revoke/re-upload policy остаётся отдельным slice.

## GRILL-007 — photo revoke, correction and retention

### Topic

Определить target semantics отзыва inspection photo без стирания historical
evidence и без нарушения уже завершённого section.

### Why this decision is needed

Pilot изменяет `revoked_at`, оставляет blob/upload history, не требует причины и
не отменяет `section_completed`. Одновременно schema запрещает identical-content
re-upload после revoke, хотя active duplicate lookup его не видит.

### What repository/pilot currently does

Public revoke проходит через checklist operation: один active photo row получает
`revoked_at`, append-ится `photo_revoked`, revision растёт. Blob остаётся. Wrong
section/already revoked отклоняются. Exact sequential replay duplicate, но
concurrent replay может дать rejected. Same-content re-upload падает на unique
key; different content принимается. Browser скрывает фото optimistic и не
восстанавливает его после rejection.

### What existing specs/docs imply

История append-only и state-changing capability должна иметь один application
seam. Current evidence не должно участвовать в readiness после revoke, но точные
correction/retention/authorization rules не утверждены. GRILL-003 уже задаёт
assignment boundary для upload и логично применяется к revoke.

### Questions, recommendations and consequences

1. **Кто может отзывать photo?**
   - Recommendation: `inspection.photo.revoke` плюс current assignment; supervisor
     override — отдельная audited correction command.
   - Alternative consequence: broad checklist edit позволяет отзывать чужое
     evidence; author-only мешает исправлять случаи после смены назначения.

2. **Нужны confirmation и reason?**
   - Recommendation: явное подтверждение и обязательная bounded reason,
     сохранённая в append-only revoke fact.
   - Alternative consequence: revoke без причины ухудшает audit; свободный
     неограниченный текст создаёт privacy/size risk.

3. **Что делать с последним active photo уже completed section?**
   - Recommendation: ordinary revoke отклонять до replacement; elevated
     correction отдельно append-ит readiness correction.
   - Alternative consequence: оставить completion при нуле evidence создаёт
     противоречивый state; молча снять completion стирает утверждённый факт.

4. **Можно ли повторно загрузить identical bytes после revoke?**
   - Recommendation: да, как новый evidence fact с новой identity/history; blob
     content deduplication может переиспользовать storage object.
   - Alternative consequence: вечный запрет по hash мешает законному повторному
     предъявлению; resurrection старой row смешивает разные факты.

5. **Как долго хранить revoked blobs?**
   - Recommendation: не удалять в test-user release; deletion/retention вынести
     в отдельную policy с legal/security approval.
   - Alternative consequence: немедленное удаление разрушает audit; бессрочное
     хранение без policy создаёт будущий compliance debt.

### Exactly blocked slices

Target `INSPECTION-PHOTO-REVOKE-001`, elevated correction command и offline
revoke/re-upload UX. PILOT_ONLY characterization текущего revoke oracle READY.
