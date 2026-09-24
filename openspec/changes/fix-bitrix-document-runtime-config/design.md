## Context

См. `proposal.md` и `specs/bitrix-document-runtime-config/spec.md`. Canonical `.env` уже хранит единый `FMONITOR_BITRIX_WEBHOOK_URL`, host bootstrap создаёт private `bitrix-config.json`, а `YiiJobsRuntimeEnvironment` умеет разобрать этот файл, выставить origin/user ID и создать временный private token file. Дефект deployment contract — параллельные прямые env/mount inputs, включая `/dev/null`, которые расходятся с этим существующим owner.

## Goals / Non-Goals

**Goals:**

- `.env` остаётся единственным operator-owned источником Bitrix настроек.
- Один atomic private `bitrix-config.json` является единственным runtime secret source.
- Existing worker bootstrap фактически получает origin/user/token из этого файла и удаляет временный token после execution seam.
- Disabled document integration не требует token mount и не ломает workforce-only worker.

**Non-Goals:**

- Не вводить второй metadata/token staging format или общий secret manager.
- Не менять delivery traversal, publication owner, scheduler/retry semantics, DB schema или UI.
- Не выполнять реальные production Bitrix запросы в локальных тестах.

## Decisions

### 1. Один атомарно публикуемый private config

`local-integration-config` и `stage-runtime-secrets` продолжают валидировать и атомарно публиковать только `bitrix-config.json`. Stager отклоняет symlink/non-regular/non-private input и не создаёт отдельные `bitrix-token`/`bitrix-runtime.env`. Одна rename operation обеспечивает coherent rotation.

Альтернатива с тремя файлами отвергнута: последовательные renames допускают смешанный old/new contract и дублируют секрет.

### 2. Existing worker bootstrap — единственный consumer

`YiiJobsRuntimeEnvironment` читает staged config при старте worker, выставляет только необходимые runtime origin/user/departments внутри процесса и создаёт `0600` temporary token file через existing `WorkerConfiguration::stageToken`. `BitrixOrderDocumentDelivery` получает этот file path; `finally` удаляет файл. Compose не передаёт webhook/token и не монтирует отдельный token path.

Альтернатива с source-able metadata отвергнута: дополнительный файл не был production-consumed и создавал второй source of truth.

### 3. Enabled/disabled определяется root ID без topology split

Worker всегда может обслуживать workforce sync из staged config. Document-links handler применим только при валидном root ID; отсутствие root ID не требует fake token file или отдельного Compose mount. Невалидный configured root/document contract fail-fast в existing handler seam.

### 4. Tests проходят через реальный bootstrap

Root test запускает canonical `.env` staging для plain/single/double quotes, затем вызывает реальный `YiiJobsRuntimeEnvironment` probe и проверяет effective origin/user/token bytes, cleanup и redaction. Failure cases проверяют permissive input, stale-byte preservation и отсутствие partial auxiliary files. Compose assertions запрещают token host variable, `/dev/null` token mount и декоративный runtime metadata contract.

### 5. Owning boundaries

Owning modules — deployment/runtime tooling и existing Jobs bootstrap. Allowed dependencies: existing env parser, `WorkerConfiguration`, POSIX shell/PHP и Compose. Persistence owner не меняется; `rapid-pilot` не участвует. Architecture checks сохраняют secret-leakage и ownership boundaries.

## Risks / Trade-offs

- [Worker temporary token находится в container temp filesystem] → existing owner создаёт `0600`, проверяет ownership/link count и удаляет файл в `finally`.
- [Long-running worker держит token file весь срок процесса] → это runtime execution boundary; token не попадает в environment/argv и удаляется при штатном завершении.
- [Production API/permissions отличаются от synthetic transport] → production fetch и publication остаются `UNKNOWN` до отдельной безопасной проверки.

## Migration Plan

1. Сохранить существующие `.env` keys; никаких новых secret inputs оператору не требуется.
2. Усилить input privacy и focused end-to-end bootstrap tests.
3. Удалить Compose token bind `/dev/null`, прямые origin/user inputs и неиспользуемые staged metadata/token.
4. Повторно stage один private config и перезапустить worker state-preservingly.
5. Rollback возвращает прежний deployment image/Compose; `.env` и private workforce config остаются совместимыми.
