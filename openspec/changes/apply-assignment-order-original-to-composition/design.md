## Context

Selection/original application-reference/history ports уже native и APPROVED.
Case currently has no production application owner; selection read returns no
actual effective order. Owner2026-09-07 разрешил reapplication before opening и
unknown employment start при full confirmed current snapshot.

## Goals / Non-Goals

**Goals:** один deep application interface для initial/sequential/reapplication,
immutable application facts, replay и authoritative current engineer/crew reader.
**Non-Goals:** duplicate original verification, legacy writer migration, automatic
opening, invented employment dates, Bitrix network, preview/remote mutation.

## Decisions

Owning module AssignmentOrderComposition. Original reference/guard потребляется
через public AssignmentOrderOriginal interface; current crew/authority читаются
production adapters под case lock. Application facts принадлежат MariaDb adapters
этого module; DDL — только InstallationProcess schema migration. Ни HTTP, ни
rapid-pilot не пишут facts. Read current/history используют тот же owning storage.

Вместо изменения старых intervals сохраняется последовательность immutable
application snapshots с predecessor links. Correction до opening добавляет новый
fact; после opening snapshot/attribution неизменны. Current view выбирает последний
application; same-day sequential order решается append sequence, а не upload time.
Точные calendar/tie outcomes должны пройти Gate1, не являются implementation defaults.

Accepted request identity хранится вместе с immutable backing и unique fingerprint;
case lock сериализует новые факты и конкуренцию с original/case commands.
Accepted lookup повторяется после case/current authority lock до sequence checks;
pre-lock hint не решает concurrent replay. Global request UUID race разных cases
разрешается только после confirmed rollback fresh authorized lookup. Current IAM и
workforce proof удерживаются native locking reads до commit; metadata перед catalog,
стабильный порядок IDs, READ COMMITTED, без session wait-policy overrides.
Повтор выполняет authorization перед раскрытием old payload. Unknown commit не
повторяется внутри вызова; новый healthy call читает accepted request outcome.

Native test строит реальные selected compositions/originals approved factories.
Production constructor и public command те же, что получит HTTP adapter. Нет
нового абстрактного dependency graph ради in-memory GREEN; native DB boundaries
проверяются synthetic prefix0/25 fixtures и bounded worker races.

## Risks / Trade-offs

Reader payload точно определён в executable contract section6. Schema — prerequisite
с собственными gates на actual canonical frontier15; версия ещё не резервируется.
Unknown employment rule требует coherent completed full snapshot provenance;
publication/selection eligibility integration остаётся последующим gated work.
Runtime does not repair schema, grant users или synthesize missing dates.
New architecture seams потребуют explicit ownership review; baseline не увеличивать
для подавления checker failures. No production source >=150lines.

## Migration Plan

После approval поведения определить exact additive application storage и выполнить
его native schema gates; затем native command/read gates. Canonical runner version
выбирается на актуальном frontier, с preservation checks всех существующих facts.
Rollback deployment не удаляет application history. Fresh bootstrap/HTTP opening
и golden path переключаются на approved application после его GREEN/Gate5.
