## Context

> **Owner decision / supersession — 2026-09-14.** Этот restore-based design больше не определяет closure issue #76. Его fail-closed semantics и historical evidence сохраняются; никакие reconciliation/rollback effects не разрешены. Replacement planning change — `yii2-clean-stand-cutover`.

См. `proposal.md`. На main `e5a420e0` PR #124 поставил `StandRestoreApplication`: bundle admission общий с backup, ledger/lease/replay и fail-closed outcomes уже принадлежат application owner. Единственный effect path, однако, включается `FMONITOR_STAND_RESTORE_TEST_MODE=1`, читает соседний JSON fixture и создаёт модельные `database.json`, `artifacts/`, `sessions.json`, `readiness.json`; он не соединяется с MariaDB, Docker volumes, runtime stop/restart или HTTP readiness. Existing `RuntimeRecovery` остаётся production-capable legacy contour с собственным bundle/schema/runbook и не должен удаляться этим срезом.

## Goals / Non-Goals

**Goals:**

- Сохранить `StandRestoreApplication` владельцем protocol/state machine и подставить один production driver port рядом с существующим recording fixture adapter.
- Выразить authorization и target attestation как immutable, canonical, digest-bound input до любых credentials/external effects.
- Доказать real boundaries и rollback на disposable compose stand с pinned image/source и изолированными identities.
- Сделать phase evidence достаточным для независимой проверки confirmed/unknown outcome без утечки secrets.

**Non-Goals:**

- Production deployment/cutover или доступ к production credentials/target.
- Вторая restore application, перенос protocol в shell/compose/controller или изменение PR #124 outcomes.
- Общий cleanup, деление `StandRestoreApplication`, изменение harness policy/user workflows.
- Удаление `RuntimeRecovery`; допустим только inventory и запрет новых зависимостей.

## Decisions

### 1. Driver port остаётся под существующим application owner

Application получает injected restore-effect driver и immutable request/config value. Fixture adapter реализует тот же port для deterministic tests; production adapter выполняет DB/filesystem/runtime effects. Ledger, lease, preflight ordering, replay/conflict, terminal record и confirmed pointer остаются в application. Альтернатива — operational shell вокруг fixture или второй recovery command — отвергнута: она раздваивает authorization/outcome authority.

### 2. Отдельный canonical authorization/attestation package

Вход связывает owner-issued authorization id, expiry/scope, operation id, bundle/target digests, source commit, image digest, absolute compose file, service map, DB identity, volume names и observed engine ids, disposable marker и credential references. Adapter повторно наблюдает identities перед каждым destructive phase. Никакого discovery/adoption по одному имени. Альтернатива — разрешить project allowlist/env flag — недостаточна против name reuse и ошибочного окружения.

### 3. Credentials — ссылки, не значения

Package содержит только identifiers private mounted files/runtime secret sources. Adapter открывает credentials после successful admission, передаёт их subprocess через file descriptor/environment минимального lifetime и redacts diagnostics. Ни spec evidence, ни ledger не сохраняют значения. Existing compose secret volume используется как boundary; repository fixtures применяют только disposable credentials.

### 4. Production driver использует native runtime boundaries

MariaDB payload импортируется migration-capable principal в exact empty/recreated disposable database; schema inventory сравнивается с bundle/known expectations, AUTO_INCREMENT доказывается metadata плюс controlled next insert. Artifacts восстанавливаются staging+fsync+rename в exact attested state volume с bytes/modes verification. Sessions восстанавливаются согласно bundle contract в canonical session root. Runtime lifecycle использует pinned compose file/project и explicit services: quiesce writers, restore targets, start migration/HTTP/jobs topology, fresh `/health/live` и `/health/ready`, затем golden checks. Adapter не владеет domain facts и не добавляет logic в `rapid-pilot`.

### 5. Ambiguity сохраняется, rollback является новой operation

До первого эффекта ошибки terminal/fail-closed; после потенциального эффекта любая потеря доказательства даёт `OUTCOME_UNKNOWN`, retained lease и phase evidence. Rollback не маскирует исходный outcome: operator явно классифицирует failure predicate и запускает новый authorized restore operation с known-good bundle после target reconciliation. Это сохраняет append-only history и позволяет независимую проверку.

### 6. Rehearsal evidence разделено на safe summary и external primary logs

В checkout попадают contract tests, generated command inventory, redacted summary с exact commit/image/bundle/operation/attestation digests, timestamps и assertion results. Полные stdout/stderr, DB dumps, artifacts, cookies и secrets хранятся вне checkout в evidence root. Harness только вычисляет exact source/role packages по существующей policy; этот срез её не меняет.

### 7. Verification и authorship проходят Gates 1–5

Root владеет normative spec, expected facts и intended RED. Независимый reviewer решает Gate 3. Отдельный executor реализует adapter и rehearsal tooling. Root готовит complete-candidate exact-source package, отдельный reviewer решает Gate 5. Локально выполняются bounded focused/fast checks; один full exact-source Quality Graph CI нужен до PR-ready. Operational rehearsal выполняется только после reviewed code package и отдельной disposable action authorization, но не является deployment.

## Risks / Trade-offs

- [DB и filesystem effects не атомарны] → phase ledger, quiesced writers, staging, repeated attestation, `OUTCOME_UNKNOWN` и явный rollback.
- [Docker names могут быть переиспользованы] → observed immutable IDs и disposable marker обязательны; name-only admission запрещён.
- [Readiness может быть формально GREEN при неверных данных] → independent literal facts, next insert, job state и golden smoke после restart.
- [Session restore может конфликтовать с owner-approved relogin policy] → заранее фиксировать expected session outcome; не объявлять opaque-cookie portability без доказательства.
- [Operational logs раскрывают secrets/data] → secret references, redaction tests, external evidence root и safe repository summary.
- [Legacy contour кажется лишним после одного rehearsal] → executable responsibility inventory; retirement только отдельным срезом после полного replacement proof.

## Migration Plan

1. Подготовить и независимо утвердить spec, intended RED и exact verification input от main после PR #124.
2. Реализовать driver port/adapters и explicit package validation, сохранив все существующие restore/backup tests.
3. Пройти focused GREEN и независимый Gate 5 на exact candidate source.
4. Создать изолированный disposable stand с новыми credentials/identities; зафиксировать known state и known-good backup.
5. Провести destructive roundtrip, restart/readiness и полный assertion inventory.
6. Инъецировать заранее определённый failure, провести отдельный rollback restore и повторный restart/readiness/integrity proof.
7. Сохранить redacted evidence summary, выполнить exact-source CI и подготовить PR; deployment/cutover оставить не выполненным.
8. После merge отдельно решить production cutover и затем, если inventory пуст, legacy retirement.
