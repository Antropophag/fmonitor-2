# PRODUCTION-RUNTIME-RESTORE-001 — согласованный backup и restore production runtime

Статус: **DRAFT для Gate 1**. Источник: issue #36, ADR0002,
`docs/operations/production-runtime-runbook.md`, `PRODUCTION-HTTP-RUNTIME-001` и
OpenSpec change `restore-production-runtime-contour`.

## 1. Public seam и границы

Единственный операторский seam:

```text
php bin/fmonitor2-runtime-recovery.php backup  --destination ABSOLUTE_PATH \
  --source-commit HEX40 --image-reference IMAGE --image-id sha256:HEX64 \
  --writers-stopped

php bin/fmonitor2-runtime-recovery.php restore --bundle ABSOLUTE_PATH \
  --target-state-root ABSOLUTE_PATH --source-commit HEX40 \
  --image-reference IMAGE --image-id sha256:HEX64
```

Обе команды читают direct `FMONITOR_DB_HOST/PORT/NAME/USER/PASSWORD` и
`FMONITOR_PROCESS_TABLE_PREFIX`. `backup` получает source DB; `restore` получает
explicit target DB. State source задаётся `FMONITOR_SESSION_STATE_ROOT`; restore
target дублируется аргументом и MUST совпадать с этим env. Runtime readiness после
restore использует полный explicit config #33, включая separate private
`FMONITOR_ORIGINAL_DB_PASSWORD_FILE` target.

`--writers-stopped` — явная авторизованная аттестация оператора после
`docker compose stop web php`. Без неё backup MUST вернуть exit 65
`SOURCE_NOT_QUIESCED` до создания destination. Текущий slice не утверждает, что CLI
может независимо доказать состояние произвольного orchestrator. После #34 команда
и runbook MUST добавить остановку worker; до этого background recovery deferred.

Команды не вызываются из HTTP, cron, demo bootstrap или ordinary runtime. Они не
меняют source contour, не выполняют imports и внешние sends, не содержат domain
rules и не принимают relative paths, symlinks или repository paths.

## 2. Stable outcomes

CLI печатает одну JSON line без paths, SQL, credentials и exception text.

| Exit | Result reason |
|---:|---|
| 0 | `BACKUP_CREATED` либо `RESTORE_COMPLETED` |
| 64 | `CONFIGURATION_INVALID` |
| 65 | `SOURCE_NOT_QUIESCED` |
| 66 | `DESTINATION_NOT_EMPTY` либо `TARGET_NOT_EMPTY` |
| 67 | `BUNDLE_INVALID` |
| 69 | `DATABASE_UNAVAILABLE` |
| 70 | `BACKUP_FAILED`, `RESTORE_FAILED` либо `READINESS_FAILED` |

Success stdout содержит ровно `{"ok":true,"reason":"BACKUP_CREATED","formatVersion":"fmonitor-runtime-backup-v1"}`
либо `{"ok":true,"reason":"RESTORE_COMPLETED"}`. Bundle identity вычисляется
оператором как SHA-256 exact bytes `manifest.json`; отдельного неоднозначного digest
directory tree CLI не публикует. Failure MUST NOT сообщать ready/success. Exact повтор backup в существующий path и
restore в уже изменённый target отказывает: merge/overwrite/reseed не выполняются.

## 3. Bundle v1

Новый destination создаётся mode 0700. Команда выбирает fresh sibling
`<destination>.partial-<32 lowercase hex>` в том же parent, не более восьми
попыток. Любой pre-existing partial sibling считается чужим и сохраняется exact;
команда никогда его не открывает, исправляет или удаляет. Если fresh sibling не
найден, возвращается `BACKUP_FAILED`. Final destination появляется одним rename
только после fsync файлов и parent; collision с появившимся final destination даёт
`DESTINATION_NOT_EMPTY`, сохраняет foreign final exact и безопасно удаляет только
собственный task-owned partial. Final bundle
содержит ровно три regular files mode 0600 effective UID, без symlinks:

```text
database.sql
state.tar
manifest.json
```

`manifest.json` публикуется последним и содержит ровно:

```json
{
  "formatVersion": "fmonitor-runtime-backup-v1",
  "createdAtUtc": "RFC3339 seconds Z",
  "sourceCommit": "40 lowercase hex",
  "imageReference": "nonempty non-control string",
  "imageId": "sha256:64 lowercase hex",
  "database": {"name": "configured DB name", "schemaVersion": 22, "tables": [], "byteSize": 1, "sha256": "64 hex", "autoIncrement": []},
  "state": {"byteSize": 1, "sha256": "64 hex", "members": []},
  "deferred": ["jobs-outbox-issue-34"],
  "policyDecisions": ["RETENTION_NEEDS_GRILL", "RPO_NEEDS_GRILL", "RTO_NEEDS_GRILL"]
}
```

`state.members` отсортирован по bytewise relative path и для каждого regular file
содержит ровно `path`, `byteSize`, `sha256`, `mode`. Directories не перечисляются;
их modes сохраняет tar. Absolute, empty, dot, `..`, duplicate, control/backslash
paths, symlinks, devices и hard links запрещены. DB password, original credential
bytes и полный environment не входят в manifest/stdout. Bundle целиком остаётся
private primary evidence вне repository.

DB dump содержит только data и создаётся standard matching client с
`--no-create-info --complete-insert --replace --single-transaction --hex-blob`, без table
locks, routines, events и triggers. Canonical schema не сериализуется и не
переписывается: её recreates exact reviewed source image. `database.autoIncrement`
`database.tables` содержит exact отсортированный inventory поддерживаемых canonical
tables; unknown ambient table заставляет backup отказать до публикации. `database.autoIncrement`
содержит отсортированные записи ровно `table`/`nextValue` для каждой canonical
AUTO_INCREMENT table, включая значения выше `MAX(id)+1` после удалений. State tar
создаётся из canonical state root без dereference links. Любой
symlink в source state даёт `BACKUP_FAILED` до final publication.

## 4. Preflight restore до target mutation

Restore до первого изменяющего DB statement, mkdir target state или extract MUST проверить:

1. bundle directory owner/mode и отсутствие symlink;
2. exact три members, regular files, owner и mode 0600;
3. JSON schema/allowlist, source/image grammar и deferred/policy literals;
4. byte size/SHA-256 dump и tar;
5. tar member type/path/duplicate/mode и exact соответствие manifest inventory;
6. target DB существует, доступна и содержит zero tables;
7. target state path отсутствует; его parent canonical, private и не symlink.

Перед mutation переданные restore source/image identities MUST exact совпадать с
manifest. Это explicit operator/orchestrator attestation: CLI не может изнутри
контейнера независимо доказать собственный registry digest. Drill MUST отдельно
сохранить inspect evidence и доказать запуск из recorded reviewed image/commit;
одно совпадение `schemaVersion` недостаточно.

Missing/extra/corrupt/traversal/symlink/duplicate member даёт `BUNDLE_INVALID`.
Любая непустота target даёт `TARGET_NOT_EMPTY`. Эти outcomes MUST оставлять zero
target tables, отсутствующий state root и все ambient DB/files exact unchanged.

## 5. Restore и terminal admission

После полного preflight команда выполняет canonical catalogue из exact source
image и требует frontier, равный `database.schemaVersion`, импортирует data-only
dump, восстанавливает каждый сохранённый AUTO_INCREMENT next value, materialize state во
временный sibling, проверяет exact inventory/hash/modes и effective UID:GID всех
materialized directories/files, затем
rename в target path. Terminal success требует `RuntimeReadiness::assertReady` на
target explicit config. Ошибка после mutation возвращает `RESTORE_FAILED` либо
`READINESS_FAILED`, не публикует success и оставляет isolated failed target для
диагностики; source и другие contours не меняются. Cleanup failed target — отдельная
явно scoped операторская операция, не автоматическое удаление.

Исполняемый drill сравнивает exact ordered rows, а не counts, минимум для users,
roles/grants, assignment/original history, opening, checklist operations and
attribution, photo metadata, completion facts и audit/events. Сравниваются exact
SHA-256/size PDF, фото, safe log и committed session. Existing cookie после start
читает объект и private PDF; следующая authorized mutation добавляет append-only
history без переписи восстановленных строк.

## 6. Update и rollback

Update drill сохраняет bundle и previous source/image ID, собирает другой reviewed
exact image, запускает отдельные migrations, recreates только web/php и повторяет
readiness/auth/session/private/history/write assertions. Для доказанно additive-
compatible schema допустим возврат previous web/php image без удаления schema или
history. При несовместимости previous image blind rollback запрещён: оператор
восстанавливает исходный bundle в ещё один empty contour и применяет исправленную
forward migration. Произвольный downgrade DB не является capability.

## 7. Evidence и незакрытые решения

Private evidence mode 0600 содержит actual start/end UTC, elapsed seconds, bundle
hash, source/image IDs, команды и exact сравнения. Repository report содержит
только безопасные identities/outcomes. Retention, SLA, RPO, RTO и допустимая потеря
данных остаются `NEEDS_GRILL`; тесты не назначают числовых значений.

#34 является зависимостью отдельного следующего slice: worker quiesce,
pending/leased jobs, retry/dedup и unknown external delivery без реальных sends.
До него base drill обязан печатать/фиксировать `jobs-outbox-issue-34` deferred и не
заявлять background recovery.
