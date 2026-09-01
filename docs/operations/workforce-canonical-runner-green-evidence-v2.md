# WORKFORCE-CANONICAL-RUNNER-001 — corrected GREEN evidence v2

- Дата: `2026-09-02`
- Автор test-fixture correction: свежий отдельный агент
  `workforce_runner_select_failure_fix_20260902r`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, owner-approved
- Публичный seam: `php bin/fmonitor2-migrate.php`
- Verdict автора: `CORRECTED_GREEN_CAPTURED — REQUIRES_FRESH_INDEPENDENT_TEST_REREVIEW`
- Supersedes для post-GREEN запуска сценария §6.6: ранее reviewed test и RED
  evidence остаются историческими и не переписываются

## Причина correction

После minimal GREEN MariaDB 11.4 не показывала
`information_schema.REFERENTIAL_CONSTRAINTS` principal с одним `SELECT`.
Поэтому fixture неожиданного v5 DDL failure останавливалась на exact preflight
v1 и наблюдала `SCHEMA_MIGRATION_CONFLICT` с `schemaVersion:1`, не достигая v5.
Это было ограничение тестового principal, а не поведение public runner §6.6.

Test-only principal теперь получает `SELECT, REFERENCES` на одну
collision-resistant test database. `REFERENCES` даёт необходимую видимость
foreign-key metadata, но principal по-прежнему не имеет `CREATE`, `ALTER` или
`DROP`. Поэтому exact v1–v4 preflight проходит, а первый v5 DDL получает
реальный permission denial через public CLI.

Production code, executable spec и literal assertions не изменялись. Exact
ожидание остаётся exit `70`, stdout
`{"ok":false,"reason":"MIGRATION_FAILED"}\n`, пустой stderr и неизменный
prepared v1–v4 namespace.

## Изолированный GREEN

Использован отдельный Compose project:

```text
$ docker compose -p fmonitor2-wcr-select-fix-20260902r \
    -f compose.test.yaml up --detach --wait test-db
Container fmonitor2-wcr-select-fix-20260902r-test-db-1 Healthy
Host endpoint: 127.0.0.1:23306
```

Полный approved test, включая все preceding cases, corrected §6.6 и embedded
architecture ratchet:

```text
$ FMONITOR_TEST_DB_HOST=127.0.0.1 \
  FMONITOR_TEST_DB_PORT=23306 \
  FMONITOR_TEST_DB_ADMIN_USER=root \
  FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/workforce_canonical_runner_001_test.php
PASS: WORKFORCE-CANONICAL-RUNNER-001 complete public-runner matrix.
```

До final GREEN correction отдельно воспроизвела старую неисправность с exact
actual result `SCHEMA_MIGRATION_CONFLICT`, `schemaVersion:1`; после добавления
только `REFERENCES` тест дошёл за assertion §6.6 и первоначально остановился
лишь на независимом concurrent hotspot ratchet. После production-авторского
выноса readiness без изменения baseline тот же полный тест прошёл.

## Input identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
d7395db426b4744c9373b7856ef0d7f46a8532edd01b0cddecc64739eba2ac94  tests/InstallationProcess/workforce_canonical_runner_001_test.php
3098c845a182440bc0180a1f8e1bba776c87e8eb9465a2533a8f845b788061a5  bin/fmonitor2-migrate.php
6ffa69142493d84879da233a261b87ff4b3494261ad3ebb47bbac70016e3a296  app/InstallationProcess/BitrixWorkforceHistorySchemaMigration.php
```

Нужен новый свежий независимый test rereviewer. Автор correction не ревьюит
собственный тест и не выдаёт Gate 2 approval.
