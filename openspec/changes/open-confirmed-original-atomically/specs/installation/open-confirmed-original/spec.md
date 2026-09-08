## Purpose

Позволяет явно открыть работы одним действием после подтверждения оригинала, сохраняя применение состава и opening как атомарную append-only операцию.

## ADDED Requirements

### Requirement: Атомарное открытие по current confirmed original
Система SHALL по одной команде активного пользователя с `installation.open` проверить exact current accepted original, выбранный состав, eligibility, expected application sequence и actual start date, затем атомарно добавить application facts и opening fact. Upload MUST оставаться отдельным чистым действием.

#### Scenario: Первый application и opening
- **WHEN** current accepted original ещё не применён и валидная команда содержит canonical requestId, exact order/revision, expected sequence 0 и допустимую actual date
- **THEN** система добавляет ровно один application и открывает дело в той же транзакции

#### Scenario: Уже применён тот же original
- **WHEN** exact current original уже является current application
- **THEN** система использует его и открывает дело без duplicate application

#### Scenario: Исправленный original до opening
- **WHEN** current accepted correction отличается от current application и expected sequence совпадает
- **THEN** система добавляет append-only reapplication и opening атомарно, сохраняя прежние facts

### Requirement: Отказ без partial facts
Система MUST не сохранять application/assignments/opening при invalid date, stale original/sequence, failed eligibility/template или отсутствии exact `installation.open`. Standalone apply MUST сохранить permission `assignment_order.composition.apply`.

#### Scenario: Отказ открытия
- **WHEN** любая проверка compound command отклонена
- **THEN** application, assignments, process events и opening остаются неизменными

### Requirement: Replay
Система SHALL связать canonical requestId с normalized payload и возвращать прежний success для exact replay; другой payload с тем же id MUST быть отклонён без mutation.

#### Scenario: Exact replay
- **WHEN** принятая команда повторена с тем же normalized payload
- **THEN** возвращается тот же application/opening result без новых facts

### Requirement: HTTP action
POST execution SHALL принимать только `csrfToken`, `action=open_confirmed`, `requestId`, `orderId`, `revisionId`, `sequence`, `actualStartDate`; actor и object берутся из trusted server/route. Success SHALL вернуть 303 на `/pilot/objects/{id}`.

#### Scenario: Успешная форма
- **WHEN** trusted actor отправляет валидную форму
- **THEN** HTTP вызывает compound seam один раз и перенаправляет в карточку
