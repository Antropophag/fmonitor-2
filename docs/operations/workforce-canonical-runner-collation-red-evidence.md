# WORKFORCE-CANONICAL-RUNNER-001 — database-default collation RED evidence

- Дата: `2026-09-02`
- Автор теста/RED: отдельный свежий агент
  `workforce_runner_collation_red_20260902x`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, owner-approved
- Унаследованный exact schema contract: `BITRIX-WORKFORCE-SCHEMA-001 v0.3`
- Публичный seam: `php bin/fmonitor2-migrate.php`
- Verdict автора: `QUALIFYING_RED_CAPTURED — AWAITING_FRESH_INDEPENDENT_TEST_REREVIEW`

Этот append-only record фиксирует обнаруженный после исходного GREEN
integration gap. Он не переписывает предыдущие RED/review records и не означает
self-approval теста.

## Усиление executable example

Существующая первая clean-DB проверка в
`tests/InstallationProcess/workforce_canonical_runner_001_test.php` сохраняет
прежние literal expectations полного публичного v1–v5 результата и exact
11-table catalogue. Fixture теперь создаёт database явно как:

```sql
CREATE DATABASE `<isolated>`
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
```

До запуска CLI тест независимо проверяет через `information_schema`, что:

- database default равен literal `utf8mb4_unicode_ci`;
- он отличается от `utf8mb4` charset default в текущей MariaDB;
- следовательно, RED не может быть объяснён неудавшимся setup.

После ожидаемого будущего успеха существующий independent exact-v5 verifier
проверяет каждую textual column всех четырёх workforce tables и требует именно
database-default collation. Production code и остальные assertions теста этим
RED-автором не изменялись.

## Изоляция

Использован отдельный Compose project и отдельный host port:

```text
$ FMONITOR_TEST_DB_PORT=25327 docker compose \
    -p fmonitor2-wcr-collation-20260902x \
    -f compose.test.yaml up --detach --wait test-db
Container fmonitor2-wcr-collation-20260902x-test-db-1 Healthy
```

Тест создаёт collision-resistant database `t_wcr_001_<12 lowercase hex>`, а
`finally` удаляет database и закрывает соединения. Production credentials/data
не используются.

## Fresh qualifying RED

```sh
FMONITOR_TEST_DB_HOST=127.0.0.1 \
FMONITOR_TEST_DB_PORT=25327 \
FMONITOR_TEST_DB_ADMIN_USER=root \
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
bash tools/verification/run.sh red \
  tests/InstallationProcess/workforce_canonical_runner_001_test.php
```

Существенный exact result:

```text
Setup assertions: passed; database default utf8mb4_unicode_ci differs from
utf8mb4 charset default.

Expected CLI result:
  exitCode: 0
  stdout: {"ok":true,"schemaVersion":5,"appliedVersions":[1,2,3,4,5]}
  stderr: <empty>
Expected catalogue: literal 11 tables.

Actual CLI result:
  exitCode: 2
  stdout: {"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":5}
  stderr: <empty>
Actual catalogue: exact eight v1-v4 tables; all three v5 history tables absent.

RED_ASSERTION: expected failing behavior observed in
tests/InstallationProcess/workforce_canonical_runner_001_test.php
Wrapper exit: 0
```

Публичный runner успешно подключился и завершил v1–v4. Ошибка локализована в
стыке: v2 workforce catalog, созданный `DEFAULT CHARSET=utf8mb4`, не наследует
явный database-default collation, после чего strict v5 preflight отвергает
catalog на шаге 5. Это behavior failure, а не DB availability/configuration или
fixture failure.

## Reviewed-input identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
3ab1de71b348c9644de42a0bcc601c5968e9530cf29fe062c19182822f468479  tests/InstallationProcess/workforce_canonical_runner_001_test.php
e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
3098c845a182440bc0180a1f8e1bba776c87e8eb9465a2533a8f845b788061a5  bin/fmonitor2-migrate.php
04b026fcdca896ccda9ccabdbb24f65e90af238a7a11e98fa5b0e990d3801eac  app/InstallationProcess/WorkforceCatalogSchemaMigration.php
6ffa69142493d84879da233a261b87ff4b3494261ad3ebb47bbac70016e3a296  app/InstallationProcess/BitrixWorkforceHistorySchemaMigration.php
```

## Final checks и cleanup

```text
$ php -l tests/InstallationProcess/workforce_canonical_runner_001_test.php
No syntax errors detected in tests/InstallationProcess/workforce_canonical_runner_001_test.php

$ git diff --check
exit 0, no output

$ make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

$ openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid
```

Следующий gate — свежий независимый test rereview этого усиления. Только после
его `APPROVED` production author может минимально исправлять collation handoff.
