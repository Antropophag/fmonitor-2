## Context

См. `proposal.md`. Действующая цепочка уже имеет единый host parser/publisher, Compose service `prepare` и штатные Yii loaders. Ошибка находится на границе host/container: bind-mounted `0600` сохраняет host ownership, а контейнер исполняется как UID/GID 10001. Exact-mode проверка повторяется в Python helper и legacy PHP loader.

## Goals / Non-Goals

**Goals:**

- Сохранить единственный публичный путь через оба Make-target и штатные Yii import owners.
- Разделить host snapshot и container-readable private snapshot; проверять реальное чтение как UID 10001.
- Сохранить atomic replace, fail-closed path types, redaction и точный importer exit status.

**Non-Goals:**

- Универсальный secret manager, production secret/session policy или изменение image identity.
- Изменение алгоритмов импорта, фильтров, owner provisioning, harness/classifier или architecture baseline.
- Полная OS-матрица; WSL/Docker Desktop остаются отдельно отмечаемой недоступной проверкой, если среда отсутствует.

## Decisions

1. `tools/delivery/local-integration-config` остаётся владельцем грамматики и atomic host publication, но принимает любые фактически доступные regular file/directory без exact mode predicate. Созданные им temporary files остаются private по best effort; корректность не зависит от сохранения mode bits конкретной host filesystem. Альтернатива — полностью убрать path checks — отвергнута из-за symlink/non-regular риска.
2. Make/Compose выполняют привилегированный только этап копирования host snapshot в отдельный tmpfs one-shot container и устанавливают container ownership/mode; сам PHP importer запускается стандартным non-root user. Host `.env` и snapshot не изменяются. Прямой bind mount к importer, постоянный secrets volume и `chmod 0644` отвергнуты.
3. Container delivery использует unique temporary regular file, fsync/complete write и atomic rename в tmpfs. Wrapper запускает child асинхронно с явно восстановленными default INT/QUIT dispositions, пересылает TERM/INT/QUIT, запоминает принятый signal, ждёт child и только затем очищает файлы. Обычный child status сохраняется точно; после принятого signal wrapper возвращает ненулевой interruption status даже при child `exit(0)`. Make задаёт one-shot stop signal, поддерживаемый wrapper, не меняя production PHP-FPM service policy. Secrets не входят в command text или environment values: только paths.
4. `LegacyImportConsole` и `WorkerConfiguration` проверяют absolute/regular/non-symlink/readable input и формат, но не exact mode; container boundary обеспечивает private ownership/mode. Изменение ограничено integration config loaders, не общим production filesystem policy.
5. Root-owned tests охватывают host modes, forbidden paths, replay, no-temp, Make argv/output/rendering, UID 10001 read и synthetic MariaDB/Bitrix flow. Capability ownership регистрируется в существующем verification inventory.

Owning module: local delivery tooling и Yii integration loaders. Persistence owner остаётся существующим legacy/workforce application seam; новая domain persistence отсутствует. `rapid-pilot` не изменяется. Architecture check получает только регистрацию применимых тестов, без baseline edits.

## Risks / Trade-offs

- [Privileged copy увеличивает локальную доверенную поверхность] → команда фиксирована, принимает только проверенный regular source path, создаёт private destination и не исполняет содержимое.
- [Cleanup может скрыть importer failure] → status сохраняется до cleanup и возвращается после него; cleanup failure при успешном importer остаётся ошибкой публикации.
- [Docker Desktop/WSL по-разному отображают mode/ownership] → host exact modes не являются gate, а чтение проверяется внутри Linux container реальным runtime UID.
- [Параллельные запуски могут пересечься] → unique delivered names или сериализация на target scope; атомарная публикация исключает partial read.

## Migration Plan

Изменение применяется только к local Make-targets. Rollback возвращает прежний direct bind mount и exact-mode checks; schema/data migration отсутствует. Перед публикацией выполняются focused checks, независимые Gate 3/5 reviews и один exact-source CI run.
