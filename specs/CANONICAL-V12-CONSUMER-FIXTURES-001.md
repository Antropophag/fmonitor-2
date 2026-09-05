# CANONICAL-V12-CONSUMER-FIXTURES-001 v0.1

Статус: DRAFT / TECHNICAL_GATE_1_PENDING. Дата: 2026-09-05.
Supporting fixture amendment внутри OpenSpec
`canonicalize-object-detail-snapshot-schema`, tasks 4.1/4.2; новая production
migration или пользовательское поведение не вводятся.

## Простыми словами

Штатная миграция уже создаёт две дополнительные пустые таблицы и сообщает
версию 12. Некоторые проверки всё ещё ожидают версию 11 и останавливаются до
проверки своего поведения. Поправка обновляет только их migration prerequisites
и точный ожидаемый каталог. Проверяемые действия, ошибки, история и права не
упрощаются.

## Actor, public seam and authority

Actor — разработчик/CI. Public seam — запуск каждого existing PHP test/verifier
из точного allowlist ниже, каждый вызывает настоящий
`php bin/fmonitor2-migrate.php` на своей существующей synthetic fixture.
Normative schema authority: approved `OBJECT-DETAIL-SNAPSHOT-SCHEMA-001` v0.4,
owner decision `e8f17b63a3c93e8f4be5664c309b435fde3318e9`, combined engine
review `07f58c7dd10700b3a951f792e94b972839dfe876`.
Cross-cutting invariants наследуются по `docs/development-process.md`:
canonical ownership, exact catalog, append-only facts, isolated verification.
Нового owner policy решения эта test-only поправка не требует; fresh independent
technical Gate 1 должен подтвердить именно эту границу до изменения tests.

## Exact allowed artifacts

В `tests/InstallationProcess/`:

1. `checklist_template_schema_001_test.php`
2. `classification_provenance_schema_001_test.php`
3. `identity_access_schema_001_test.php`
4. `inspection_evidence_schema_001_test.php`
5. `inspection_item_complete_001_mariadb_test.php`
6. `inspection_planning_schema_001_test.php`
7. `installation_completion_schema_001_test.php`
8. `pilot_case_import_001_test.php`
9. `pilot_http_auth_001_test.php`
10. `workforce_canonical_runner_001_test.php`

И existing verifier `rapid-pilot/verify-calendar-projections.php`.

`tests/Verification/harness_otiz_canonical_compat_001_test.php` исключён из
этого ограниченного patch: его existing arbitrary-subset appliedVersions
precondition требует отдельного exact predecessor contract. Его реальный
failure остаётся обязательной незавершённой задачей parent OpenSpec/goal;
исключение из patch не разрешает skip или integration GREEN.

`pilot_demo_bootstrap_001_test.php` только rerun consumer исправленного
pilot_case_import, не edit target. Protected `pilot_e2e_flow_001_test.php`, его
spec, fixtures, dependencies, approvals и registration НЕ меняются этим slice.
Shared `ProductionMigrationRunnerCatalogContract` defaults тоже не меняются.

## Observable result and literal expectation

1. Обычный полный clean production runner имеет exit 0, empty stderr, stdout
   ровно `{"ok":true,"schemaVersion":12,"appliedVersions":[1,2,3,4,5,6,7,8,9,10,11,12]}`
   плюс LF. Полный exact repeat — тот же shape с appliedVersions `[]`.
2. Каждый compatible predecessor запускает только реально missing migrations.
   Если v12 absent, existing exact expected successor list дополняется 12 в конце.
   В частности v5 partial `[5,6,7,8,9,10,11,12]`; v7 predecessor
   `[8,9,10,11,12]`; v8 predecessor `[9,10,11,12]`.
3. Classification race вызывает отдельный approved family-only verification
   worker `tests/Support/classification_provenance_barrier_runner.php`, а не
   полный production CLI. Его winner сохраняет schemaVersion 11,
   appliedVersions `[11]`; loser сохраняет exact exit70/MIGRATION_FAILED.
   Direct v11 conflict тоже остаётся v11. Последующий ordinary production CLI
   repeat, напротив, имеет schemaVersion 12 и appliedVersions `[]`.
4. Literal full table catalogs дополняются ровно
   `fm2_pilot_object_detail_quarantine` и `fm2_pilot_object_details` в binary
   sorted порядке с configured prefix: после `fm2_pilot_invitations` и до
   `fm2_pilot_role_permissions`. IIC count 31 становится 33. Columns и
   indexes двух tables берутся из exact manifest schema v0.4. Если существующий
   caller использует shared catalog, он явно выбирает уже существующие
   `columnsV12()` / `indexesV12()` только для full-runner expectations.
5. Family-local результаты v1…v11, включая conflict на v11/v10/v9/v7, не меняются.
   Version не вычисляется из production output; запрещены `>=11`, ranges
   разрешённых versions, optional table lists и blanket textual replace `11`.
6. Все business acceptance assertions, payload/hash/replay/authorization,
   concurrency protocol, rejected outcome, cleanup и preservation остаются
   byte-identical за исключением точных full-catalog prerequisites/labels выше.
   В pilot_case_import существующее deliberate удаление v10/v11 prerequisites
   сохраняется, v12 tables не удаляются и importer DDL semantics не меняются.
7. Отсутствие/лишняя table, неправильный schemaVersion/appliedVersions, изменённый
   conflict или бизнес-результат остаются failures. Ни один failure не становится
   skip, xfail или допустимым отклонением. Никакое новое data population не вводится.

## Rejected cases, authorization and audit

Существующие exact rejected cases и privilege boundaries всех affected specs
наследуются без изменений. Canonical schema creation не пишет domain audit и не
импортирует source. Test-only correction не создаёт domain facts; reviewer
records/commands/hashes ведутся append-only. Используются только already-owned
synthetic test fixtures, no production/shared demo data и no production secrets.

## RED, review and Done

Первый диагностический full run содержит environment faults и сам по себе не
достаточен для окончательного review. После исправления PATH/vendor отдельный
exact-SHA run либо focused affected commands должны продемонстрировать чистый
отказ старого fixture на version/catalog mismatch. Это RED существующего
fixture prerequisite, не новый RED отсутствующей production migration.

До применения поправки подготовить exact proposed patch как review artifact.
Fresh independent Gate 3 проверяет specification, старый failing execution и
patch: изменяются только разрешённые prerequisites/labels; expectations не
выводятся из output. После APPROVED применить этот patch без дополнительных
изменений, выполнить affected commands и architecture/lint/diff-check.
Это minimal GREEN test-only correction; production не меняется. Новое поведение
или discovered unrelated failure возвращается в отдельный gate, не поглощается.

Fresh independent Gate 5 reviewer проверяет точный applied diff, unchanged
business assertions и focused GREEN; protected E2E hash должен остаться прежним.
Full make verify сохраняется отдельным integration evidence и может оставаться
красным на других известных blockers. Этот ограниченный slice Done не завершает
parent OpenSpec или persistent launch goal.
