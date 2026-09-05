## Context

См. proposal и independent seam inventory `16ba3e65401ab6b3acb883a96f590bc1e9eb7c3d`. `InstallationProcess::prepareAssignmentOrder` вызывает renderer до persistence. `submitAssignmentOrderOriginal` читает существующий order/composition. Fixtures с direct SQL не доказывают production direct upload.

## Goals / Non-Goals

**Goals:** один production owner выбора состава; immutable identity совместима с original command; отсутствие renderer/storage dependency в selection; atomic replay/concurrency/audit.

**Non-Goals:** менять original PDF parser/storage, применять действующий состав, открывать работы, создавать domain logic в rapid-pilot, выдавать selection права читателям документов.

## Decisions

1. Владелец selection — production application module распоряжений. Candidate seam `selectAssignmentOrderComposition(command)` выражает намерение, HTTP/CLI не пишут process tables.
2. Существующий prepare workflow разделяется на selection и optional render только после executable contract и reviewed regression. Альтернатива «вызвать prepare и скрыть PDF» не удовлетворяет no-template dependency.
3. Persistence order/member snapshots не дублируется новым независимым HTTP writer. При необходимости additive schema change получает exact manifest/version и свои Gates; runtime DDL запрещён.
4. Original command получает уже сохранённую identity. Existing derivation из physical `order_date` и member validity требует явного compatibility решения в Gate 1: дата выбора/шаблона не подменяет documentDate original.
5. Allowed dependencies: caller → selection API → authorization/catalog/repository/clock ports. Selection не получает renderer port; optional rendering — отдельная операция того же owner. Architecture ratchet проверяет отсутствие bypass и renderer calls в selection.
6. Пакет не утверждает конкретные permission code, pre-original correction semantics и mutable draft модель. Эти Gate 1 вопросы сначала разрешаются из inherited approved contracts; новое пользовательское поведение при необходимости имеет NEEDS_GRILL disposition, не выводится из legacy implementation.
7. Existing projection gap доказан в `docs/operations/selection-existing-assignment-projection-gap-2026-09-05.md`: directory фильтрует registered order, но MAX(version) берёт среди всех orders; новая selection скрывает старое назначение. No-application contract охватывает public directory availability/assignments и inspection actor/installer attribution, а не только неизменные interval rows. Изолированный переход на MAX(registered) не является target original applicability. Gate 1 согласует selection visibility с owning lifecycle contract до implementation.

## Risks / Trade-offs

- [Existing prepare одновременно фиксирует даты/интервалы] → exact no-application invariant и regression до переключения HTTP.
- [Manager может upload, но existing prepare grants иные] → coherent selection authority в Gate 1 без implicit inheritance от read.
- [Ошибочный выбор до original] → explicit append-only correction/version contract; не добавлять silent in-place edit.
- [Optional renderer failure ломает direct upload] → independent selection success и preserved identity при failed render.

## Migration Plan

Подготовить executable `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001`, coherent disposition existing prepare/HTTP expectations и independent Gate 1. Затем RED → Gate 3 → minimal owner/persistence → relevant regression/architecture → Gate 5. Подключить direct HTTP flow только после approvals. Existing historical prepared/rendered orders и original revisions не переписываются при deployment или rollback к предыдущему коду.
