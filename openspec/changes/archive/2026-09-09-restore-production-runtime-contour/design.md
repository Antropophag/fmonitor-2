## Context

См. proposal.md и delta spec. #33 дал отдельные production web/php/migrate/prepare
services, persistent MariaDB/state volumes и exact-image evidence; #27 описал ручной
DB dump и platform snapshot, но целого restore seam и drill пока нет. State volume
содержит sessions, PDF/фото и safe log; DB содержит связи и append-only историю,
поэтому одна сторона без другой не является согласованным backup.

## Goals / Non-Goals

**Goals:** один deployment-owned backup/restore интерфейс; preflight до target
mutation; восстановление в новый Compose project; точная DB/file/auth/session
сверка; проверка image update и ограниченного rollback; private evidence.

**Non-Goals:** backup работающего writer, in-place restore, production deployment,
реальные imports/sends, произвольный DB downgrade, выбор retention/RPO/RTO,
восстановление jobs/outbox до #34 и изменение доменных правил.

## Decisions

### 1. Operations владеет orchestration, доменные модули не меняются

Новый `app/RuntimeRestore` владеет проверкой bundle и командами backup/restore;
тонкие CLI в `bin/` принимают explicit paths/config. Модуль может зависеть от
filesystem/process/DB dump adapters и существующей Runtime readiness, но не пишет
доменные таблицы напрямую. Restore загружает полный проверенный dump стандартным
MariaDB client. `rapid-pilot` не участвует и остаётся только behavioral oracle для
последующего browser smoke. Architecture checker запрещает вызов restore из HTTP,
cron и ordinary runtime.

Альтернатива — shell-only runbook — хуже фиксирует fail-closed preflight, stable
outcomes и тестируемую границу. Online application-level export отвергнут: он
создал бы второй неполный владелец истории.

### 2. Writers останавливаются до двухкомпонентного backup

Оператор явно останавливает web/php; после #34 сюда добавится worker. Команда
требует explicit `--writers-stopped` attestation и отказывает без неё, затем делает
standard `mariadb-dump --no-create-info --complete-insert --replace --single-transaction`
из production CLI image и архивирует
state без symlinks. Process adapter передаёт пароль только через child environment;
тестовый real-DB запуск выполняет CLI внутри этого image. Manifest
публикуется последним через rename внутри нового destination. Формат versioned;
в inventory используются только нормализованные относительные пути.

Полной атомарности между MariaDB transaction snapshot и filesystem snapshot без
общего storage coordinator нет. Stop-the-writers делает границу согласованной и
проверяемой для текущей топологии. Snapshot работающего state отвергнут.

### 3. Restore всегда создаёт новый contour

Source contour и backup остаются read-only. Target получает отдельные project name,
DB/state/secrets volumes и непубличный DB port. До импорта проверяются все bytes и
пустота target. Сначала materialize DB/state, затем ownership/modes, Runtime
readiness и browser/read/write checks. Failure сохраняет изолированный target для
диагностики либо удаляется только explicit cleanup командой с project identity.

Canonical schema создаётся catalogue того exact reviewed source image/commit,
которые записаны в bundle и переданы restore command. Затем импортируется data-only
dump и восстанавливаются manifest AUTO_INCREMENT next values, включая gaps после
delete; это избегает нестабильного DDL roundtrip стандартного dump. In-place overwrite отвергнут из-за риска смешать поколения и уничтожить единственную
работающую копию. Source/image args являются operator/orchestrator attestation,
сравниваемой с manifest. CLI не заявляет introspection registry/container identity;
real drill сохраняет отдельный inspect evidence фактически запущенного image ID.

### 4. Manifest связывает backup с source, но не хранит secrets

Manifest содержит format version, UTC timestamps, source commit, immutable image
reference/ID, DB dump hash/size, state archive hash/size и member inventory.
Credential values, environment dump и raw container inspection не включаются.
Restore получает secrets отдельно из private operator configuration и сверяет
доступ/readiness без публикации значений.

### 5. Exact assertions отделены от изменяемого smoke

Test fixture создаёт synthetic populated production contour текущим browser harness.
До backup сохраняется canonical JSON точных отсортированных строк и file inventory.
После restore сравниваются bytes/rows, затем existing cookie выполняет authorized
read, а новая command добавляет историю. Это ловит одинаковые counts с изменённым
содержимым. Corruption/path traversal/nonempty target проверяются отдельными RED до
implementation.

### 6. Update rollback зависит от совместимости schema

Reviewed additive migration запускается отдельным migration service на restored
copy, после чего web/php переключаются на new exact image. Previous image rollback
допустим лишь при доказанной совместимости; schema и history не удаляются. При
несовместимости создаётся ещё один target из исходного backup и применяется
исправленная forward migration.

## Risks / Trade-offs

- [State меняется между DB dump и archive] → оператор останавливает writers и
  передаёт обязательную attestation; фактический orchestrator status сохраняется в
  private drill evidence, CLI не заявляет независимую проверку произвольной среды.
- [Tar path traversal/symlink] → canonical member preflight до extract, запрещены
  absolute/`..`/duplicate/symlink entries.
- [Partial target ошибочно объявлен ready] → manifest публикуется только для backup;
  restore success требует DB, state, ownership и read-only readiness.
- [Backup раскрывает secrets] → manifest allowlist, private mode 0600 и тест на
  отсутствие известных credential values.
- [Update делает previous image несовместимым] → rollback matrix фиксируется до
  migration; иначе только restore-forward.
- [Нет jobs/outbox] → #34 обозначен отдельной зависимостью, базовая сохранность не
  блокируется и не расширяет claim.

## Migration Plan

1. Создать executable RED на backup manifest, corruption/traversal/nonempty отказ и
   zero target mutation; получить независимый Gate 3.
2. Реализовать backup CLI и focused tests без изменения production stand.
3. Создать RED restore exact rows/files/session и реализовать restore в disposable
   Compose target; проверить failure cleanup ownership.
4. Выполнить synthetic restore drill, затем exact-image update и browser write;
   сохранить primary evidence вне repository и безопасный report в docs.
5. Обновить #27 runbook командами backup/restore/update/rollback, выполнить
   независимый Gate 5 и один полный CI по матрице.
6. После #34 открыть отдельный slice для worker quiesce и jobs/outbox recovery.

## Open Questions

- **NEEDS_GRILL:** retention, RPO, RTO и допустимая потеря данных. Они не блокируют
  технический drill, но обязательны до production policy/release claim.
