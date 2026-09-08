## Context

См. `proposal.md`. Native seams для selection/original/application/opening,
checklist/photo/completion, workforce и restart-safe startup уже реализованы и
проверены focused fixtures. Read-only Bitrix delivery получен в private evidence;
deployment нового стенда, publication batch, hourly schedule и live smoke ещё не доказаны.

## Goals / Non-Goals

**Goals:** собрать один чистый generation, сохранить append-only facts и server-side
RBAC, опубликовать разрешённый workforce batch, выполнить реальный golden path и
передать владельцу URL/маршрут/ограничения.

**Non-Goals:** перенос legacy users или production objects, Bitrix writes, подключение
старых volumes, удаление резервных volumes, PR/CI publication и full production claim.

## Decisions

Owning modules остаются разделёнными по public seams: AssignmentOrderComposition
владеет selection/application/opening reference, AssignmentOrderOriginal — original,
InspectionEvidence — checklist/photo, InstallationProcess — completion/workforce и
schema/startup. HTTP и rapid-pilot только вызывают owners и строят read projections.

Новый Docker generation использует один prefix для process и временного legacy-object
read adapter, потому что fresh-order resources требуют совпадения. Контур объектов
создаётся пустым; owner-admin bootstrap выполняется один раз, manifest/sentinel и
приглашённые пользователи повторно используются при restart.

Private original/workforce evidence и secrets остаются вне репозитория с режимами
0700/0600. Bitrix batch публикуется через native synchronization owner; альтернативой
был старый truncate/import cron, но он нарушает append-only provenance и исключён.

Architecture check не должен получать новые baseline debts. Startup-only DDL остаётся
в `*SchemaMigration`, SQL — в MariaDB adapters; canonical versions18 уже restart-safe.

## Risks / Trade-offs

- [Новый стенд не поднимается из clean DB] → preflight migrations, container health и login smoke до передачи URL.
- [Private batch устареет] → показать timestamp/warning и настроить hourly read-only schedule; возраст сам не блокирует.
- [Реальный flow выявит integration defect] → остановить readiness claim, исправить конкретный сценарий и повторить его.
- [Старый preview нужен для расследования] → volumes сохраняются отключённым резервом и не смешиваются с новым generation.

## Migration Plan

1. Зафиксировать source/image и сохранить идентификаторы старых volumes.
2. Остановить и удалить прежний preview-контур без удаления volumes.
3. Поднять clean DB/application generation, выполнить canonical и startup migrations.
4. Создать только owner-admin, опубликовать private workforce batch, не импортировать users/objects.
5. Настроить hourly workforce schedule и выполнить login → restart → golden-path smoke.
6. Передать URL, вход без пароля, маршрут и известные ограничения. При rollback
   остановить новый контур; резервные старые volumes остаются неизменными.
