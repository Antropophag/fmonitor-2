## Purpose

Фиксирует новую воспроизводимую версию расчёта просрочки по подтверждённой Excel-последовательности и точное распределение всего пула в целых копейках.

## ADDED Requirements

### Requirement: Расчёт использует доказуемые versioned operands
Каждый новый snapshot SHALL сохранять calculation version и operands с source locator/hash для исходного срока, применимой справки, effective native ПТО, report date, premium, shaft coefficient, progress, paid-before и принятого состава. Отсутствующий исходный срок либо неполное/противоречивое evidence SHALL дать явный blocker и нулевой допуск к распределению без выдуманного значения.

#### Scenario: Нет справки
- **WHEN** исходный срок `2026-08-08`, report date `2026-08-17`, ПТО и текущая справка отсутствуют
- **THEN** effective deadline равен `2026-08-08`, comparison date равна `2026-08-17`, days late равны 9 и Kss равен 9100 bp

#### Scenario: Справка заменяет срок
- **WHEN** исходный срок `2026-07-29`, текущая справка задаёт `2026-08-16`, а ПТО равно `2026-07-28`
- **THEN** effective deadline равен `2026-08-16`, days late равны 0 и Kss равен 10000 bp

### Requirement: Дата ПТО следует literal Excel
Comparison date SHALL равняться effective дате `pto_act` из native completion root/correction chain при её наличии независимо от отношения к report date; без ПТО она SHALL равняться report date. Snapshot SHALL сохранять identity/version/hash использованного completion fact.

#### Scenario: ПТО позже отчётной даты
- **WHEN** plan равен `2026-08-10`, report date `2026-08-15`, а effective ПТО равен `2026-08-20`
- **THEN** comparison date равна `2026-08-20`, days late равны 10, а не 5

#### Scenario: Исправленная дата ПТО
- **WHEN** correction chain меняет effective дату ПТО перед новым расчётом
- **THEN** новый snapshot использует correction provenance, а прежний snapshot и исходный completion fact не переписываются

### Requirement: Future-date исключение ограничено document operands новой версии
Новая calculation version SHALL разрешать certificate date и effective PTO date позже report date только для выбора deadline/comparison date по утверждённому Excel-поведению. Progress, payments и остальные факты MUST сохранять существующие report-date cutoffs. Replay опубликованного snapshot прежней version MUST читать сохранённый результат без повторной валидации новыми правилами.

#### Scenario: Будущий документ и будущий checklist fact
- **WHEN** текущая справка и ПТО датированы после report date, а checklist operation также получена после report date
- **THEN** справка и ПТО участвуют в новом расчёте, а checklist operation не участвует

#### Scenario: Replay версии 1
- **WHEN** читается или повторно запрашивается уже опубликованный snapshot прежней calculation version
- **THEN** система возвращает сохранённые version-1 operands/hash/amounts без применения future-document исключения или HALF-UP vNext заново

### Requirement: Просрочка штрафует невыплаченный остаток
Расчёт SHALL вычислять календарные `daysLate=max(0, comparisonDate-deadline)`, `kssBp=max(0,10000-100*daysLate)`, `fund=roundHalfUp(premiumCents*shaftBp/10000)`, `progressAmount=roundHalfUp(fund*progressBp/10000)`, `remainingBeforePenalty=max(0,progressAmount-paidBeforeCents)`, `deadlinePenalty=roundHalfUp(remainingBeforePenalty*(10000-kssBp)/10000)` и `pool=max(0,remainingBeforePenalty-deadlinePenalty)`. На каждой указанной денежной границе неотрицательная дробная копейка SHALL округляться обычным математическим способом HALF-UP; отрицательные деньги или Kss MUST NOT возникать.

#### Scenario: Подтверждённый денежный пример
- **WHEN** progress amount равен 49725000 коп., paid before равен 9360000 коп. и days late равны 9
- **THEN** remaining before penalty равен 40365000, deadline penalty равен 3632850 и pool равен 36732150 коп.; прежний результат 35889750 отклоняется

#### Scenario: Границы Kss
- **WHEN** просрочка равна соответственно 0, 1, 99, 100 и 101 календарному дню
- **THEN** Kss равен соответственно 10000, 9900, 100, 0 и 0 bp, а штраф никогда не превышает remaining before penalty

### Requirement: Все копейки распределяются детерминированно
Для принятого состава система SHALL вычислить идеальные доли уже выбранного целочисленного пула по effective KTU, сначала выдать целые части, затем по одной копейке распределить остаток участникам в порядке убывания дробного остатка. Равные остатки SHALL разрешаться постоянным canonical tie-break по installer tab identity в лексикографическом порядке. Сумма allocations MUST точно равняться pool; один и тот же input MUST давать byte-identical порядок и суммы. Этот conservation pass не заменяет HALF-UP округление денежных границ расчёта.

#### Scenario: Уникальный наибольший остаток
- **WHEN** после целых частей остаётся одна копейка и один участник имеет наибольший дробный остаток
- **THEN** эта копейка назначается ему и conservation выполняется точно

#### Scenario: Равные остатки
- **WHEN** два участника имеют одинаковый дробный остаток
- **THEN** копейка назначается участнику с лексикографически меньшей canonical tab identity независимо от порядка входного массива

### Requirement: Новая версия не переписывает историю
Новая calculation version SHALL применяться ко всем расчётам, создаваемым после deployment. Суммы, operands, hashes и allocations ранее опубликованных snapshots MUST оставаться неизменными и воспроизводимыми. Успешная атомарная publication каждого нового snapshot SHALL под object locks создать fresh candidate-entitlement identity и append-only supersede-ить прежний accepted-unpaid entitlement каждого включённого объекта независимо от report period и formula version. Новый candidate требует отдельной acceptance и до неё не оплачивается; прежний уже не оплачивается. Paid entitlement не переписывается. Draft старой formula version MUST быть пересоздан явно новой операцией и не может быть принят после смены active version.

#### Scenario: Старый выплаченный snapshot
- **WHEN** новая версия развернута после принятия и выплаты старого snapshot
- **THEN** его суммы, hash, allocations и closures остаются byte-identical

#### Scenario: Утверждённый невыплаченный расчёт
- **WHEN** публикуется новый snapshot объекта при существующем accepted-unpaid entitlement, включая другой период или ту же formula version
- **THEN** новый получает fresh candidate identity, старый немедленно получает один superseded event без изменения сумм, выплата по обоим запрещена до отдельной acceptance нового

#### Scenario: Старый невыплаченный draft
- **WHEN** пользователь пытается принять draft прежней version после deployment
- **THEN** система отказывает как stale calculation и предлагает создать новый snapshot без изменения старого

### Requirement: Выплата сериализуется по объектному entitlement
Полная выплата SHALL выполняться одной idempotent application operation. Она MUST блокировать stable installation-case rows в порядке object id, проверять target snapshot и свежую entitlement identity как latest accepted non-superseded основание каждого объекта, считать net closures по object across all snapshots и атомарно добавлять только недостающий остаток. Snapshot-local проверка MUST NOT быть единственной защитой. Одинаковая operation identity SHALL replay прежний result; collision SHALL отказать без новых closures.

#### Scenario: Два snapshot одного объекта
- **WHEN** два v2 snapshots одного объекта приняты последовательно и payment вызывается для старого
- **THEN** операция возвращает obsolete entitlement и не создаёт closure

#### Scenario: Конкурентные выплаты разных snapshots
- **WHEN** payment operations для старого и текущего snapshots одного объекта выполняются конкурентно
- **THEN** object lock сериализует их, только current entitlement может создать одну net closure, а общая выплата объекта не превышает текущий остаток

#### Scenario: Потерянный ответ выплаты
- **WHEN** клиент повторяет completed operation с той же identity и fingerprint
- **THEN** возвращается прежний receipt и ни одна closure/event row не дублируется

### Requirement: Ledger-компоненты не смешиваются молча
`paidBeforeCents` для Excel-последовательности SHALL включать только object-wide net `paid_cents` по подтверждённым payment/reversal facts. Net discipline holds SHALL сохраняться отдельным operand и уменьшать payable после рассчитанного pool; они не считаются выплатой и не возвращаются новым snapshot. Существующие nonzero legacy `deadline_cents` SHALL блокировать v2 publication кодом `LEGACY_DEADLINE_HOLD_REVIEW_REQUIRED` до явного append-only reversal, потому что молчаливое вычитание вместе с новым formula penalty дало бы double penalty, а игнорирование — silent refund.

#### Scenario: Действующее дисциплинарное удержание
- **WHEN** object имеет pool1000 и object-wide net discipline hold100 без payment
- **THEN** paidBefore остаётся0, formula pool остаётся1000, payable равен900 и новый snapshot не возвращает удержанные100

#### Scenario: Старое deadline closure
- **WHEN** до v2 существует nonzero net deadline_cents
- **THEN** object блокируется до отдельного reversal; система не считает сумму paidBefore, не вычитает её второй раз и не игнорирует

### Requirement: Повторный расчёт без нового прогресса требует решения владельца
`NEEDS_GRILL`: cumulative Excel recurrence и approved interval rule пока конфликтуют. При fund100, progress100%, Kss9000 и paidBefore90 literal Excel даёт remaining10, penalty1, pool9 даже без нового progress; interval `(previous,current]` может требовать no-new-amount. До решения система MUST NOT объявлять этот recurring-snapshot case утверждённым; Gate1 calculator recurrence/payment tasks остаются blocked.

#### Scenario: Нет нового прогресса после выплаты
- **WHEN** прежняя выплата90 относится к тем же100% progress, а новый Kss равен9000
- **THEN** ожидаемый result остаётся `NEEDS_GRILL` между literal pool9 и no-new-amount; implementation не выбирает значение самостоятельно

### Requirement: Глобальный waiver не включается скрыто
До отдельного решения владельца система MUST всегда применять рассчитанный deadline penalty. Она MUST NOT переносить Excel `$EU$2` как глобальный boolean, переменную окружения или неаудируемый переключатель.

#### Scenario: Нет утверждённого waiver
- **WHEN** существует просрочка и остальные operands допустимы
- **THEN** penalty применяется по новой формуле и никакая глобальная настройка не обнуляет его
