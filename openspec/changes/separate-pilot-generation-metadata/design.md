## Context

См. `proposal.md` и `docs/operations/pilot-generation-metadata-evidence.md`.
Текущий Compose bootstrap владеет DB sentinel и manifest, но смешивает их с DDL,
rebuild/drop/import. DB и filesystem не имеют общей транзакции; rename даёт
atomic visibility, но текущий код не делает fsync. HTTP и workers валидируют
identity неодинаково. Отдельный `bin/fmonitor2-pilot-demo.php` уже владеет другим
synthetic lifecycle (owner/ready/active manifests и table comments); он не
является test-user Compose contour и остаётся за явной compatibility boundary.

## Goals / Non-Goals

**Goals:**

- Получить один setup owner и одну validated identity для всех consumers.
- Сделать restart read-only относительно generation и пользовательского state.
- Обеспечить сериализацию, crash recovery и scoped cleanup без distributed
  transaction или destructive repair.

**Non-Goals:**

- Помещать sentinel в production migration runner.
- Переписывать все bootstrap/schema slices в этой change.
- Выбирать fixture data, production cutover или долгосрочную retention policy.
- Гарантировать непрерывную доступность во время аварийной publication: fail-closed
  до recovery является допустимым безопасным состоянием.
- Переписывать synthetic standalone demo fixture semantics в этой change;
  collision closure ограничена disjoint physical namespace и runbook routing.

## Decisions

1. **Отдельный Compose-contour owning module `app/PilotEnvironment`.** Он владеет DTO validated
   identity, exact sentinel fingerprint, manifest parser, creator/recovery и
   validator. Allowed dependencies: mysqli, filesystem, secure randomness,
   clock/config; domain/application seams ему недоступны. Compose rapid-pilot
   entrypoint, start/import/workers становятся тонкими adapters. Standalone
   synthetic demo не зависит от этого module. Default host/image topology уже
   различается fingerprint/HOME; для любой поддерживаемой co-location добавляется
   `<state-root>/synthetic-demo/` и prefix discriminator. Альтернатива — новый код в
   `rapid-pilot` — продолжила бы setup ownership debt и нарушила boundary.
2. **Четыре CLI операции: ensure, validate, recover, reset.** `ensure` вызывается
   entrypoint: на полностью пустых owned volumes он исполняет prepare →
   prerequisite bootstrap → finalize; при active generation — только validate;
   при incomplete/ambiguous state — fail-closed. `recover` является отдельной
   явной operator seam. Reset остаётся Compose/operator seam. Автоматический
   repair неоднозначного state отвергнут.
3. **Durable state machine под lock.** Prepare создаёт private
   `generations/<n>/owner.json` с identity/state `preparing`, exact sentinel row
   в пустом namespace и уникальный invocation token. Затем orchestrator запускает
   отдельно gated prerequisites. Finalize повторно проверяет exact canonical
   schemas, required seed/mode invariants и отсутствие запрещённых data, пишет и
   fsync-ит `ready.json` с versioned readiness fingerprint, затем атомарно
   публикует/fsync-ит root `active.json`. Recovery никогда не выводит ready из
   одного совпадения sentinel/owner: он повторяет полный readiness verifier.
   Иное состояние — conflict без cleanup чужого объекта.
4. **Immutable identity after publication.** Create не использует upsert для
   активной generation; restart не вращает nonce. Новая identity требует явного
   reset/new namespace. Это устраняет смешанный old-manifest/new-row restart.
5. **Один strict validator с logical DB identity.** Он проверяет exact schema, единственный key `1`,
   formats/ranges, path-derived fingerprint/prefixes, manifest state/mode,
   logical database name и live server identity. Manifest не делает transport
   host/port глобальной identity: HTTP proxy и worker service endpoint различны.
   Каждый consumer подключается через собственный config и доказывает достигнутую
   logical identity; HTTP listener берётся из active manifest и сверяется только
   startup adapter. State-changing consumers
   дополнительно recheck sentinel под lock/transaction непосредственно перед
   первой записью. Hourly workforce sync не должен создавать started row до
   этого recheck.
6. **Bootstrap orchestration имеет durable prerequisites.** Canonical
   migrations, identity seed, fixture/import и product adapters выполняются
   отдельными идемпотентными командами после prepare и до readiness proof согласно
   их собственным approved slices. Эта change реализуется последней и только
   перестраивает orchestration вокруг уже DDL-free компонентов.
7. **Architecture ratchet.** Новый module не содержит product SQL mutations,
   кроме setup sentinel owner; runtime bootstrap DDL/drop debts уменьшаются без
   baseline exception. `make architecture-check` должен явно запрещать sentinel
   в production migration catalogue и новые generation writes вне owner.

## Risks / Trade-offs

- [Cross-resource publication не атомарна] → readers запускаются только после
  strict validation; lock/staging/recovery закрывают mixed pair.
- [fsync portability в контейнере] → Gate 1 фиксирует поддерживаемую filesystem
  durability primitive и отдельно тестирует kill boundaries; отсутствие
  поддержки даёт setup failure.
- [Существующая активная generation создана старым bootstrap] → одноразовый
  adoption допускается только при exact matching row/manifest и zero mutation;
  иначе требуется явный reset, не repair.
- [Workers стартуют после HTTP healthcheck] → каждый worker всё равно независимо
  валидирует identity перед каждым state-changing run.
- [Release-critical runtime DDL ещё существует] → implementation остаётся
  `BLOCKED_PREDECESSORS`; planning не маскирует эти dependencies.
- [Fixture/reset policy] → отдельный approved decision разрешает только
  deterministic fictional synthetic/native data, preservation на обычном
  restart и ownership-proved destructive cleanup исключительно через explicit
  `make reset`; эта change не присваивает себе fixture semantics.
- [Два исторических lifecycle] → acceptance и runbook ограничены Compose;
  standalone synthetic demo остаётся отдельным harness, но proof всех supported
  topology, co-location discriminator и root README correction входят в эту change. До
  owner выбрал Compose `make up` единственным TEST-USER contour; успех
  standalone не является readiness proof.

## Migration Plan

1. Зафиксировать принятое решение: TEST-USER и будущий рабочий deployment
   используют Compose-подход; закрепить supported topology matrix, co-location
   discriminator и runbook, затем дождаться DDL-free canonical bootstrap
   prerequisites и обновить dependency evidence перед Gate 1. Production
   credentials, backup, scaling и рабочий operational runbook остаются вне scope.
2. Утвердить executable spec, доказать RED и получить независимый test review.
3. Добавить `app/PilotEnvironment` и focused verifier для ensure/restart/recover,
   mismatch, concurrency, crash recovery, permissions, decoys и reset guard.
4. Перевести Compose entrypoint/start/workforce/import consumers на validator и удалить
   sentinel mutation из обычного bootstrap.
5. Выполнить focused/full regression и architecture checks, затем независимый
   code review. Rollback кода сохраняет существующую generation; metadata/data
   не удаляются автоматически.
