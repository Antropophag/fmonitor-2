## Purpose

Эта capability доказывает безопасный operational backup/restore и rollback нового Yii2 contour на настоящих disposable runtime boundaries до отдельного решения о production cutover.

## ADDED Requirements

### Requirement: Restore требует явную authorization и exact target attestation
Production-shaped restore SHALL принимать эффекты только при наличии отдельного authorization input, связанного с exact operation, verified bundle, target digest, pinned source/image, compose topology, database identity и observed persistent volume identities. Target SHALL быть заранее помечен disposable и attest-нут по фактически наблюдаемым immutable identities; имя compose project, database или каталога само по себе MUST NOT разрешать stop, clear, import, replace, restart либо любой другой destructive effect. Credentials SHALL поступать из явно указанного private runtime source и MUST NOT записываться в ledger, stdout, repository или rehearsal artifact. Authorization SHALL exact bind-ить canonical runtime tuple process table prefix, artifact volume path и Yii session volume path; mismatch с runtime configuration MUST быть отклонён до process calls.

#### Scenario: Полностью attest-нутый disposable target допущен
- **WHEN** уполномоченный operator передаёт неповторимый operation id, verified bundle и authorization package, а все observed identities exact совпадают и target явно disposable
- **THEN** application допускает production driver к эффектам над указанными DB/artifact/session targets и записывает только безопасную authorization/target identity в append-only evidence

#### Scenario: Имя проекта без attestation отклонено
- **WHEN** caller указывает существующее либо похожее project/database name без exact observed identity или disposable authorization
- **THEN** операция fail-closed завершается до первого внешнего или destructive effect и не обнаруживает production target автоматически

#### Scenario: Target изменился после preflight
- **WHEN** любой container, network, database или volume identity отличается между admission и первым либо последующим destructive boundary
- **THEN** driver не продолжает эффекты; outcome не считается success, а потенциально начатый эффект приводит к `OUTCOME_UNKNOWN` с сохранёнными lease/evidence

### Requirement: Один application owner управляет реальными restore effects
Существующий restore application protocol SHALL остаться единственным владельцем admission, immutable bundle validation, operation ledger, exclusive lease, replay/conflict и confirmed outcome. Production driver SHALL быть узким effect adapter этого owner для stop/quiesce, MariaDB import, schema/AUTO_INCREMENT verification, artifact/session restore, restart и fresh readiness; controller, compose wrapper и runbook MUST NOT создавать вторую restore state machine или самостоятельно публиковать success.

#### Scenario: Реальный driver вызывается после immutable preflight
- **WHEN** target и bundle полностью допущены и exclusive lease durable
- **THEN** owner передаёт driver immutable payload references и attested targets, а driver возвращает phase evidence, необходимое owner для confirmed или ambiguous outcome

#### Scenario: Partial либо недоказуемый effect не становится success
- **WHEN** timeout, interrupt, subprocess loss, partial DB/filesystem effect, restart failure или stale readiness не позволяет доказать exact итоговое состояние
- **THEN** owner возвращает `OUTCOME_UNKNOWN`, не публикует confirmed pointer, сохраняет lease и append-only evidence для явного recovery decision

#### Scenario: Replay и conflict сохраняют семантику PR #124
- **WHEN** operation id повторён с теми же аргументами либо с другим bundle/target/authorization digest
- **THEN** exact confirmed result воспроизводится без новых эффектов либо запрос отклоняется как conflict соответственно

### Requirement: Disposable operational roundtrip подтверждает все runtime boundaries
Rehearsal SHALL выполняться только на отдельно созданном disposable stand. Из independently specified known state новый stand-backup SHALL создать bundle, public verify SHALL подтвердить его, затем разрешённый rehearsal SHALL остановить и очистить либо пересоздать exact target, выполнить новый stand-restore, перезапустить runtime и дождаться fresh liveness/readiness. Evidence SHALL независимо проверить literal DB facts и append-only history, schema inventory и AUTO_INCREMENT следующим реальным insert, artifact bytes и modes, session state/оговорённую login policy, jobs/outbox/lease/recovery state где применимо и основные golden smoke flows.

#### Scenario: Успешный roundtrip
- **WHEN** known state создано через публичные seams, backup independently verified, exact disposable target уничтожен и restore/restart/readiness завершены
- **THEN** все заранее перечисленные expected facts совпадают, следующий insert использует ожидаемое AUTO_INCREMENT, golden flows проходят, а owner публикует `RESTORE_VERIFIED` только после fresh post-restart checks

#### Scenario: Проверка bundle не пройдена
- **WHEN** manifest, payload, digest, source/image compatibility или verified pointer не проходит существующий admission contract
- **THEN** никакой target effect, stop, credential use или restart не происходит и результат остаётся fail-closed

### Requirement: Rollback rehearsal возвращает known-good состояние
До rehearsal SHALL быть задан failure predicate: restore/upgrade считается неуспешным при любом неуспешном command outcome, identity drift, missing/mismatched DB/history/schema/AUTO_INCREMENT/artifact/session/job fact, restart/readiness timeout или failed golden smoke. При predicate failure operator SHALL использовать отдельный operation id и известный independently verified backup для возврата exact disposable target, затем повторить restart/readiness и полный обязательный integrity subset. Rollback success MUST быть отдельным доказанным outcome и не может выводиться из запуска команды.

#### Scenario: Инъецированный failed candidate приводит к rollback
- **WHEN** после candidate restore намеренно нарушена одна обязательная readiness/integrity assertion
- **THEN** rehearsal классифицирует candidate как failed, не объявляет cutover readiness и восстанавливает known-good bundle отдельной авторизованной operation

#### Scenario: Rollback подтверждён после restart
- **WHEN** known-good restore завершён и runtime повторно перезапущен
- **THEN** fresh readiness и заранее определённые DB/history/AUTO_INCREMENT/artifact/session/job/golden assertions снова GREEN, а rollback evidence содержит exact bundle, operation, target attestation и timestamps без secrets

### Requirement: Evidence и граница production cutover честны
Repository SHALL содержать воспроизводимый spec/test/runbook inventory и безопасную сводку exact evidence; полные логи, credentials и чувствительные primary artifacts SHALL оставаться вне checkout. Rehearsal MUST NOT выполнять production deployment/cutover и MUST NOT превращать `UNKNOWN` PR/CI/deployment observation в GREEN. По завершении inventory SHALL перечислить устранённые test-only seams, production-shaped driver, exact roundtrip и rollback evidence, оставшиеся responsibilities `RuntimeRecovery`, production-cutover prerequisites и обоснование возможности либо невозможности отдельного legacy-retirement slice.

#### Scenario: Disposable rehearsal не авторизует production
- **WHEN** оба rehearsal outcome подтверждены на disposable target
- **THEN** deployment остаётся `UNKNOWN`/не выполнен, production credentials и targets не использованы, а production cutover требует отдельной owner authorization и exact runbook package

#### Scenario: Legacy responsibility ещё существует
- **WHEN** executable inventory находит хотя бы одну production responsibility `RuntimeRecovery`, не покрытую новым contour
- **THEN** legacy implementation сохраняется, responsibility записывается явно и retirement не объявляется готовым

### Requirement: Jobs readiness использует private canonical config reference
Disposable jobs worker SHALL получать `FMONITOR_BITRIX_CONFIG` только как fixed
`/run/fmonitor-secrets/bitrix-config.json` из exact attest-нутого private secrets
volume. File SHALL быть regular non-symlink mode 0600 и подготовлен без вывода
contents. Caller-provided path/value MUST NOT попадать в rendered service.
Scheduler SHALL не получать Bitrix config, а существующий fail-closed
`jobs/health --interactive=0` healthcheck MUST сохраняться.

#### Scenario: Worker получает private config без ослабления health
- **WHEN** canonical jobs profile rendered для disposable stand
- **THEN** worker ссылается на fixed private-volume file, scheduler не получает config, secret/config contents отсутствуют в render, а оба jobs healthcheck сохраняют exact Yii command
