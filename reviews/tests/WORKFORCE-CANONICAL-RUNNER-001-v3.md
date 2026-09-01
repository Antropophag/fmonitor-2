# Test rereview: WORKFORCE-CANONICAL-RUNNER-001 v3

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_runner_test_rereview_20260902t`
- Reviewed worktree: dirty authoritative worktree at
  `932662938837b28309fef2bf0fe3cadb2ce86e41`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`
  (`APPROVED — GATE 1 PASSED`), наследует
  `BITRIX-WORKFORCE-SCHEMA-001 v0.3`
- Предыдущий rereview:
  `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-v2.md`
- Verdict: `APPROVED`

Этот append-only rereview проверяет точечное исправление
unexpected-v5-failure fixture: limited principal теперь имеет
`SELECT, REFERENCES`, но не имеет DDL privileges. Production code и test
expectations reviewer не менял.

## Reviewed identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
d7395db426b4744c9373b7856ef0d7f46a8532edd01b0cddecc64739eba2ac94  tests/InstallationProcess/workforce_canonical_runner_001_test.php
e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
3098c845a182440bc0180a1f8e1bba776c87e8eb9465a2533a8f845b788061a5  bin/fmonitor2-migrate.php
d5e1c7ef15f5ffb1d288f76d7389e7863a55d0b9e3f931747423594787b5324f  reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-v2.md
11bf83a8f1df9e40214ebc1ba8cda9b2d874035a33f31d6543074a2489619319  docs/operations/workforce-canonical-runner-red-evidence-v2.md
```

## Независимая проверка fixture

В свежем isolated Compose project
`fmonitor2-wcr-rereview-20260902t` с MariaDB на host port `27327` полная
executable matrix завершилась:

```text
PASS: WORKFORCE-CANONICAL-RUNNER-001 complete public-runner matrix.
```

Для независимой локализации failure reviewer включил MariaDB
general log только на время повторного изолированного запуска.
Exact query sequence limited principal:

1. `SET NAMES utf8mb4`;
2. read-only database collation query;
3. read-only exact v2 workforce-catalog columns, indexes, checks and foreign-key
   queries;
4. первая mutation attempt —
   `CREATE TABLE wcr_v5_fail_fm2_workforce_sync_runs ...`.

Значит, `SELECT, REFERENCES` достаточны для exact v1–v4/v2
preflight: runner не падает на introspection. Первое отклонённое
действие — именно первый v5 DDL, а public CLI возвращает exact
exit `70`, `{"ok":false,"reason":"MIGRATION_FAILED"}\n`, пустой stderr.
Снимок namespace до/после равен, то есть denial не оставил
DDL/DML.

## Матрица и неослабленные assertions

Исправление меняет только privilege fixture. В executable test
сохранены все обязательные пункты §6:

- clean literal `[1,2,3,4,5]`, exact 11-table catalogue и independent exact
  v5 manifest/initial rows;
- populated repeat `[]` с byte-for-byte fingerprint/row preservation;
- compatible partial recovery `[5]`;
- workforce conflict version `5` и earlier conflict version `1` с zero
  mutation/short-circuit;
- unexpected first-v5-DDL failure как `MIGRATION_FAILED`;
- composed 25-byte success, 26-byte pre-DB rejection и derived 64-byte
  identifier ceiling;
- direct family-local 37/38 boundary;
- runtime DDL/direct-apply ownership scan и repository architecture check.

Полный зелёный прогон показывает, что это не мёртвый fixture:
вся exact matrix достигается и проходит.

## Final verification

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

После проверки general log выключен. Review database, temporary
principal и остальные fixture resources удалены самим тестом.
Compose project/volume удалены reviewer cleanup.

## Verdict

`APPROVED`

Точечное fixture correction закрывает ambiguity прежнего review:
unexpected-failure path доходит через exact read-only preflight до
первого v5 DDL и классифицирует его denial как `MIGRATION_FAILED`.
