## Purpose

Определяет безопасный setup-only lifecycle локальной pilot generation, чтобы restart сохранял test-user state, а смешанная DB/filesystem identity блокировала любые consumers.

## ADDED Requirements

### Requirement: Compose generation создаётся одной setup orchestration
Система SHALL предоставлять одну локальную Compose operator/container setup
seam. На заведомо пустых volumes `make up` SHALL выполнить prepare, отдельно
gated prerequisite bootstrap и finalize; при существующей ready generation он
SHALL выполнить только validate. Metadata owner SHALL NOT присваивать результат
`ready` до успешного readiness proof и SHALL NOT удалять чужие данные.

#### Scenario: Чистое создание
- **WHEN** авторизованный локальный setup actor создаёт generation в пустом уникальном namespace с валидной конфигурацией
- **THEN** prepare фиксирует candidate identity, prerequisite pipeline завершается, finalize доказывает readiness и только затем публикует одну active identity с нормализованным transcript без секретов

#### Scenario: Fresh make up
- **WHEN** `make up` запускается с пустыми принадлежащими Compose DB/state volumes
- **THEN** entrypoint выбирает clean-create orchestration, завершает ready generation и запускает HTTP либо возвращает конкретный setup failure без частично активной generation

#### Scenario: Namespace уже содержит данные
- **WHEN** startup видит incomplete, непринадлежащий или неоднозначный namespace
- **THEN** он fail-closed до destructive action/HTTP и требует отдельную recovery/reset seam, сохраняя ambient objects

#### Scenario: Недоступный удалённый вызов
- **WHEN** HTTP/test user пытается создать, заменить или reset generation metadata
- **THEN** публичного HTTP mutation route не существует и metadata не меняется

### Requirement: Обычный restart сохраняет поколение
Startup SHALL проверять уже опубликованную identity и SHALL NOT менять nonce,
generation, prefixes, DB schema/rows, artifacts или manifest. Повторный startup
одной согласованной generation MUST быть идемпотентным.

#### Scenario: State-preserving restart
- **WHEN** pilot restart получает согласованные sentinel и manifest
- **THEN** HTTP startup продолжает с теми же байтами metadata и сохраняет sentinel rows, domain rows, counters, artifacts и ambient decoys

#### Scenario: Два последовательных restart
- **WHEN** одна generation успешно запускается дважды без reset/create
- **THEN** оба нормализованных validation transcripts совпадают и не содержат mutation

### Requirement: Все Compose consumers используют единый fail-closed validation
До HTTP startup, worker/import read и особенно до каждого state-changing apply
система MUST проверить exact sentinel schema/singleton и manifest types/formats,
а также равенство generation, fingerprint, nonce, prefixes, logical database
name/server identity, mode и ready state. Каждый consumer SHALL подключаться
через собственный configured transport endpoint и доказывать, что достиг той же
logical DB identity; буквальное равенство host/port между контейнерами SHALL NOT
требоваться. Только HTTP startup MUST согласовать listener port с manifest.

#### Scenario: Полностью согласованная identity
- **WHEN** consumer получает точный manifest, sentinel и live environment
- **THEN** validation через его transport endpoint подтверждает logical database/server identity и возвращает неизменяемую validated generation identity для DB/artifact paths

#### Scenario: Разные Compose transport endpoints
- **WHEN** HTTP contour достигает DB через loopback proxy, а worker через Compose service host
- **THEN** оба проходят validation при одинаковых logical database name/server identity, несмотря на разные host/port

#### Scenario: Любое несовпадение
- **WHEN** отсутствует член пары, schema/row/field невалиден либо любое связанное значение различается
- **THEN** HTTP, worker или import/apply consumer останавливается до внешнего fetch и до DB/filesystem business write с стабильным setup failure

#### Scenario: State-changing worker
- **WHEN** hourly workforce sync начинает новый run либо пишет каталог
- **THEN** он использует validated identity и повторно подтверждает активную DB identity внутри write boundary, не оставляя started row при generation mismatch

### Requirement: Prepare, readiness и publication сериализованы и восстанавливаемы
Одновременно MUST выполняться не более одного creator/recovery для одного
state root. Publication SHALL использовать приватный уникальный staging artifact
и SHALL обеспечивать atomic visibility owner/candidate metadata, readiness proof
и готового active manifest. Recovery MUST повторно проверить prerequisite
readiness; совпадения candidate с DB недостаточно для публикации `ready`.

#### Scenario: Два конкурирующих creator
- **WHEN** два реальных процесса одновременно создают generation в одном state root
- **THEN** ровно один публикует identity, второй получает duplicate/active result либо стабильный conflict, а DB row и manifest относятся к одному winner

#### Scenario: Crash boundaries
- **WHEN** creator прерывается до candidate identity, во время prerequisites, между readiness proof и active publication либо после publication
- **THEN** отдельная recovery seam под lock заново валидирует candidate и все readiness invariants, затем завершает ту же identity либо fail-closed без mixed pair/ambient deletion

#### Scenario: Candidate без readiness
- **WHEN** DB sentinel и owner metadata совпадают, но prerequisite proof отсутствует или не проходит
- **THEN** recovery не публикует `state=ready` и возвращает стабильный incomplete-generation failure

#### Scenario: Staging confidentiality и cleanup
- **WHEN** create/recovery завершается успешно или ошибкой
- **THEN** metadata files имеют приватные permissions, а cleanup удаляет только staging artifacts с доказанным ownership текущего invocation

### Requirement: Sentinel остаётся setup-only exact storage
Sentinel SHALL оставаться вне production migration catalogue и SHALL сохранять
характеризованный four-column InnoDB/utf8mb4 manifest без неутверждённых domain
constraints. Setup validation MUST требовать ровно singleton key `1`, positive
generation и точные hex formats независимо от отсутствия DB CHECK.

#### Scenario: Exact schema
- **WHEN** setup verifier читает `information_schema` и singleton row
- **THEN** columns, nullability/defaults, primary key, engine, resolved collation и отсутствие AUTO_INCREMENT/FK/CHECK совпадают с Gate 1 manifest

#### Scenario: Production runner
- **WHEN** выполняется `bin/fmonitor2-migrate.php` на production schema
- **THEN** он не создаёт generation sentinel и не записывает setup metadata

### Requirement: Reset остаётся отдельной явной destructive seam
Система SHALL отделять restart/create/validate от явно вызванного локальным
operator reset. Reset MUST объявлять точную цель до удаления и SHALL NOT быть
скрытым шагом startup, production migration или HTTP behavior.

#### Scenario: Restart без reset
- **WHEN** operator выполняет обычный stop/start
- **THEN** generation DB volume и pilot-state volume сохраняются

#### Scenario: Явный reset
- **WHEN** локальный operator явно вызывает утверждённую reset seam для точно разрешённого pilot environment
- **THEN** она удаляет только объявленные disposable resources и сообщает, что восстановление требует нового create/bootstrap

#### Scenario: Чужая или неоднозначная цель
- **WHEN** reset не может доказать ownership точного pilot environment
- **THEN** он fail-closed без удаления ресурсов

### Requirement: Fixture semantics остаётся вне slice
Generation metadata SHALL NOT определять содержимое test contour, разрешать
legacy/synthetic data или объявлять reset retention product policy.

#### Scenario: Mode field
- **WHEN** validator читает `native-only` или `test-fixtures`
- **THEN** он проверяет точное согласованное setup значение, но не трактует его как approval fixture content или operational use

### Requirement: Synthetic demo lifecycle явно отделён
Compose generation capability SHALL NOT считать
`bin/fmonitor2-pilot-demo.php` с его owner/ready/active manifests и table-comment
markers своим owner, consumer или acceptance oracle. TEST-USER-READY operator
documentation SHALL направлять владельца продукта только в Compose contour.
Standalone harness MUST иметь доказанно disjoint state root и DB prefixes во
всех поддерживаемых topology либо быть retired; co-located topology MUST
использовать явный contour discriminator.

Успешный standalone synthetic run SHALL NOT считаться доказательством
TEST-USER release readiness. Будущий рабочий deployment SHALL сохранять
Compose-подход к orchestration; production credentials, backup, scaling и
операционный production runbook определяются отдельными изменениями.

#### Scenario: Synthetic demo invocation
- **WHEN** запускается standalone synthetic demo CLI
- **THEN** его metadata и fixtures не читаются и не изменяются Compose generation owner, а его успех не подтверждает Compose readiness

#### Scenario: Owner test-user startup
- **WHEN** владелец следует TEST-USER-READY runbook
- **THEN** он запускает Compose generation seam и не зависит от synthetic demo lifecycle

#### Scenario: Standalone success не доказывает readiness
- **WHEN** standalone synthetic harness завершился успешно
- **THEN** release status остаётся неизменным до успешного Compose `make up` proof

#### Scenario: Направление рабочего deployment
- **WHEN** планируется рабочее развёртывание после TEST-USER
- **THEN** orchestration сохраняет Compose-подход, а production operational details требуют отдельных approved contracts

#### Scenario: Cross-contour co-location
- **WHEN** Compose и standalone harness явно co-located через общий repo realpath fingerprint, HOME/state storage и target DB
- **THEN** они не читают, не перезаписывают и не удаляют state roots, manifests, artifacts или DB tables друг друга

#### Scenario: Default host и image topology
- **WHEN** standalone запускается из документированного host checkout, а Compose из image WORKDIR и named volumes
- **THEN** verifier подтверждает разные fingerprints/state roots/prefixes и отсутствие cross-contour mutation без утверждения ложной коллизии
