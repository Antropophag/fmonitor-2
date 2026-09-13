## Purpose

Определяет безопасное восстановление disposable stand из ранее подтверждённого Yii2/PHP backup bundle с доказуемым roundtrip и fail-closed исходами.

## ADDED Requirements

### Requirement: Единственный Yii2/application restore seam
Система SHALL предоставлять deployment operator один public seam в существующей Yii2 console composition. Он MUST принимать exact target manifest, bundle digest и UUID operation id, выводить один safe canonical JSON outcome и MUST NOT использовать отдельный bootstrap как production owner.

#### Scenario: Подтверждённый restore command
- **WHEN** оператор вызывает новый restore command с exact admitted target, verified bundle digest и новым UUID
- **THEN** command передаёт действие одному application owner и возвращает success только после подтверждённого restore и readiness

#### Scenario: Неверные аргументы
- **WHEN** аргументы отсутствуют, дублируются, имеют неверную форму либо допускают interactive execution
- **THEN** command возвращает `CONFIGURATION_INVALID` до bundle access и destructive effects

### Requirement: Только verified bundle общего протокола
Restore application SHALL независимо проверить exact target manifest, canonical `verified.json`, content-addressed bundle manifest, все identity поля и bytes/digests каждого allowlisted payload member по неизменённому протоколу PR #119. Синтаксически либо структурно invalid input MUST завершаться до target mutation.

#### Scenario: Corrupt или incompatible input
- **WHEN** manifest/payload повреждён, digest не совпадает, filesystem object не regular/private, либо source/image/database/volume/inventory contract не совпадает с admitted target
- **THEN** restore возвращает `BACKUP_INVALID` либо `TARGET_INVALID` согласно фазе admission и не изменяет DB, persistent state, operation history или readiness facts

#### Scenario: Не тот target
- **WHEN** target пуст формально, но его canonical identity, observed IDs или inventory digest отличаются от backup manifest
- **THEN** restore fail closed до первого destructive effect

### Requirement: Безопасная target admission
До restore effects система MUST доказать, что disposable database и persistent target roots принадлежат exact target и находятся в разрешённом пустом состоянии. Symlink, special object, non-empty или неоднозначно наблюдаемый target MUST быть отклонён без очистки либо усыновления существующих данных.

#### Scenario: Non-empty или unsafe target
- **WHEN** target DB содержит таблицу/данные либо artifact/session root содержит foreign, symlink или special object
- **THEN** restore возвращает `TARGET_NOT_EMPTY` или `TARGET_INVALID`, сохраняет target byte-for-byte и не вызывает restore driver

### Requirement: Полный DB и persistent-state roundtrip
Успешный restore SHALL восстановить DB rows/history, применимый exact schema inventory и AUTO_INCREMENT next values, artifact bytes/modes и session state из одного verified bundle. Success SHALL требовать свежие schema, inventory и runtime readiness assertions.

#### Scenario: Backup → destruction → restore
- **WHEN** исходное disposable state проходит backup через поставленный public seam, target state затем изменён/уничтожен разрешённым fixture driver и новый restore выполнен через production-shaped Yii2/application path
- **THEN** literal DB facts, history, AUTO_INCREMENT, artifacts и sessions совпадают с исходным snapshot, а schema/inventory/readiness assertions GREEN

#### Scenario: Post-restore readiness failure
- **WHEN** payload effects завершились, но свежая readiness либо inventory assertion не подтверждается
- **THEN** command MUST NOT вернуть success и фиксирует ambiguous/failed operation без публикации confirmed restored state

### Requirement: Replay, conflict и ambiguous outcome
Каждый restore SHALL иметь append-only operation record с target, arguments, bundle и outcome digests. Same-id/same-arguments replay MUST не повторять destructive effects; conflicting replay MUST возвращать `OPERATION_CONFLICT`. Timeout, interruption или failure после возможного первого effect MUST возвращать `OUTCOME_UNKNOWN`; partial state MUST не считаться новым confirmed state и автоматический retry MUST быть запрещён без операторского разрешения.

#### Scenario: Interrupted restore
- **WHEN** execution прерывается в любой точке после начала DB или persistent-state effect и отсутствие эффекта нельзя доказать
- **THEN** результат non-zero `OUTCOME_UNKNOWN`, confirmed pointer отсутствует, lease/diagnostic evidence сохраняются и повтор не запускает driver автоматически

#### Scenario: Definite failure до effect
- **WHEN** driver определённо отказал до первого destructive effect
- **THEN** система возвращает definite failure, сохраняет прежнее подтверждённое состояние и допускает byte-identical replay без driver call

#### Scenario: Conflicting operation
- **WHEN** существующий operation id повторён с другим target, bundle либо arguments digest
- **THEN** система возвращает `OPERATION_CONFLICT` до destructive effects

### Requirement: Legacy responsibility определяется evidence
После focused GREEN система SHALL иметь executable production-consumer inventory для `RuntimeRecovery` и legacy CLI. Legacy path MUST быть удалён в этом срезе только если новый owner полностью заменил backup и restore production responsibility; иначе оставшиеся responsibilities MUST быть перечислены и legacy path сохранён.

#### Scenario: Legacy всё ещё нужен
- **WHEN** executable inventory обнаруживает production consumer или contract, не покрытый новым backup/restore owner
- **THEN** legacy code остаётся, а неперенесённая responsibility явно записана без утверждения cutover readiness

#### Scenario: Legacy полностью замещён
- **WHEN** executable inventory доказывает отсутствие production responsibility и все replacement contracts GREEN
- **THEN** legacy class/bootstrap удаляются вместе с обновлением production references, но historical review evidence сохраняется
