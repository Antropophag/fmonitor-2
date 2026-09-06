## Purpose

Предоставить проверяемую standalone миграцию dateless selection ledger поверх
завершённого общего registry без включения новых writers или изменения истории.

## ADDED Requirements

### Requirement: Exact additive family with registry prerequisite

Deployment migration SHALL создавать только пять canonical selection tables,
определённых exact executable contract. До первого DDL она MUST доказать
compatible registry schema и completed backfill. Недостаточный доступ,
несовместимый registry или невалидный prefix MUST завершаться fixed failure;
миграция MUST NOT создавать, исправлять или менять registry и legacy facts.

#### Scenario: Clean selection family over completed registry
- **WHEN** deployment actor вызывает standalone seam с approved synthetic registry и отсутствующими пятью selection tables
- **THEN** создаётся exact empty family, public snapshot подтверждает shape и нулевые counts; registry/legacy/evidence bytes сохраняются

#### Scenario: Missing prerequisite
- **WHEN** registry отсутствует, неполон или несовместим
- **THEN** результат — fixed conflict/unavailable согласно exact contract, selection DDL/DML отсутствует

### Requirement: Repeat and interrupted recovery preserve facts

Migration SHALL идемпотентно принимать полную совместимую family с проверенной
целостностью данных и восстанавливать только разрешённые empty partial states.
Несовместимость или nonempty partial family MUST приводить к отказу без repair,
удаления истории, выравнивания counters или принятия неизвестных facts.

#### Scenario: Repeat after complete compatible family
- **WHEN** schema и persisted family проходят exact metadata/data contract
- **THEN** повтор не выполняет DDL/DML и сохраняет history, counts, hashes и counters

#### Scenario: Interruption between table creations
- **WHEN** прошлый процесс остановлен после допустимого DDL prefix и существующие таблицы exact/empty
- **THEN** новый вызов создаёт только missing suffix и подтверждает полную family; неполный nonempty state отклоняется без изменений

### Requirement: Scoped lock and observable unavailable outcome

Concurrent deployment invocations SHALL сериализоваться в ограниченном
prefix/database migration lock. Timeout, denied metadata/DDL и connection failure
MUST оставаться failure; diagnostic/observer exception MUST NOT публиковать
ложную readiness. Verification observer SHALL быть недоступен production config.

#### Scenario: Same family contention
- **WHEN** один verifier удерживает migration phase, а второй вызывает тот же database/prefix
- **THEN** второй либо наблюдает completed repeat после освобождения, либо получает exact bounded unavailable; другой prefix не блокируется этим namespace

### Requirement: Disabled engine is not release readiness

This change SHALL оставаться standalone: canonical runner/version, application
factories, routes, ready manifests и writer enablement MUST NOT изменяться.
Production deployment authority и N−1 exclusion не выводятся из schema readiness.

#### Scenario: Completed standalone verification
- **WHEN** focused migration tests и независимый Gate5 пройдены
- **THEN** canonical runner сохраняет прежний frontier; ни selection, ни legacy allocator не переключается, full selection Gate1/release gates остаются отдельными

## Exact executable contract

ASSIGNMENT-ORDER-SELECTION-SCHEMA-001 v0.2 и normative schema/example JSON fixtures
задают exact public API, fingerprints и populated-data proof. Это draft technical
Gate1 batch; никакое planning approval не разрешает RED до независимого verdict.

Registry public completion `false` означает недоказанный prerequisite и fixed
SCHEMA_MIGRATION_CONFLICT, включая скрытые этим bool API native failures.
Ошибки собственных selection SQL queries остаются unavailable; private registry
proof не дублируется и новый registry API не вводится.
