# Assignment-order original setup — capability publication Gate 1 gap

Date: `2026-09-05`

RED author: separately tasked agent `/root/assignment_original_red2`

Gate 5 review: `c40c0101f46cbbee0187ea48f569999c3a0c49f1`

Reviewed implementation: `32dd3151941f1198e0fdd6ce5ee8ee9e1b851abd`

Outcome: **GATE 1 AMENDMENT REQUIRED**

## Простыми словами

Gate 5 обнаружил реальные дефекты fixture и capability migration. Fixture
sensitivity можно расширить без нового решения. Но тест для capability conflict
и split-publication нельзя написать независимо: утверждённый contract не говорит,
какой typed result должен вернуть public migration seam в этих случаях и как
детерминированно остановить его между DDL phases.

## Exact ambiguity

`AssignmentOrderOriginalSchemaMigrationResult` предоставляет только:

```text
status: APPLIED | UNCHANGED | CONFLICT
schemaVersion: 1
affectedTables: list<string>
```

V12 определяет `CONFLICT` и `affectedTables` только для non-equivalent existing
**owned original table**. Version 1 затем перечисляет ровно семь owned logical
tables и их manifest order. Prerequisite
`fm2_process_user_capabilities` не входит в этот список.

Ни executable spec, ни OpenSpec delta/design не определяет:

- возвращает ли incompatible capability CHECK `CONFLICT` или typed/exceptional
  unavailable outcome;
- если это `CONFLICT`, входит ли
  `fm2_process_user_capabilities` в `affectedTables`, хотя список нормативно
  описан как семь owned original tables;
- exact result для missing capability CHECK, upload-only successor,
  unexpected superset, multiple candidates и unsafe generated name;
- является ли v4 exact predecessor единственным допустимым upgrade source и
  как exact v5 successor представлен в schema result;
- result/exception и authoritative affected set при MariaDB failure после
  одного или нескольких implicit-commit CREATE, но до capability publication;
- recovery result при повторе после такого leading partial;
- exact момент publication capability относительно final schema revalidation.

Поэтому требуемые Gate 2 ожидания `v4→v5`, upload-only, superset, multiple,
unsafe-name и partial-publication нельзя определить из утверждённого result DTO.
Gate 5 формулирует желаемое как “conflict/unavailable”, что само подтверждает
две несовместимые наблюдаемые возможности. Выбор одной тестом был бы новым
public setup contract, а не независимым expected value.

## Deterministic failure seam gap

Public `apply(mysqli,prefix)` не принимает fault injector/lifecycle observer.
Approved conflict preflight обязан обнаружить existing incompatible trailing
table **до DDL** и выполнить zero DDL, поэтому такой table не может доказать
failure между table creation и capability publication. Надёжно создать race
после preflight без named barrier невозможно. Constraint names являются
implementation details, поэтому schema-wide duplicate-name trick также не
является независимым oracle. Permission failure нельзя безопасно привязать к
одному порядковому CREATE стандартными MariaDB grants без кодирования private
DDL names/algorithm.

Gate 1 должен добавить verification-only deterministic migration lifecycle/
fault composition или иной exact public setup mechanism, который останавливает
apply после заданного manifest member и непосредственно перед capability
publication, не доступен runtime consumers и возвращает утверждённый outcome.

## Smallest amendment

Минимальное дополнение должно точно определить:

1. canonical predecessor v4 и successor v5 capability CHECK sets;
2. selection rule при zero/one/multiple candidate constraints и safe-name rule;
3. status, exception policy и exact `affectedTables` для каждого capability
   mismatch;
4. schema-first/capability-last publication order и final revalidation;
5. typed outcome после definite partial DDL failure;
6. deterministic verification-only phase controls и recovery result for repeat;
7. invariant: upload/correct grants не становятся usable, пока семь original
   tables не complete-compatible.

После approval RED author сможет добавить полный capability/failure matrix и
одновременно расширить fixture drift/partial-family/cleanup sensitivity,
которая сама по себе не требует нового product decision.

Tasks 2.2, 2.3 and 3.1 reopened. Existing production remains as unapproved
evidence and must not advance. No production, executable spec or prior review/
evidence bytes were edited by this record.

## Exact inspected hashes

```text
97292b3eae449c70586c91fcba825dbb7eb17df223bebbadd0b892275b5cc4af  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
f4851a33f5bf56c6797c2586791798d8f16d5cfa347162fe84a5b0915cd93a9d  openspec/changes/replace-pilot-registration-with-original-upload/design.md
4ed70498bb554c91c5ddff2d4412a8294d7d9b4a4d9ededb79d919cf8fb5474c  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
9e2c354069c3b62f5d65eea37dd0417b6bbd8ef78d804f870882beda537dbbc1  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
75d1cc01f2e6d94504e9a266098de44ebcd5b2aa6d4ac743537cae75c519b016  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
7dcf210a033ed0c1a4723ab6b399eb22a2bfa33d8d921c53654c55824901fc5f  app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php
b3f14655d3c31ca372966d4a8231fbf5511646554a77c7f06e688922ec1becd3  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
```
