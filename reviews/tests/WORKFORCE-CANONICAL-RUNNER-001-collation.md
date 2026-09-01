# Test review: WORKFORCE-CANONICAL-RUNNER-001 database-default collation RED

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_runner_collation_test_review_20260902y`
- Reviewed worktree: dirty authoritative worktree at
  `932662938837b28309fef2bf0fe3cadb2ce86e41`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`
  (`APPROVED — GATE 1 PASSED`), наследует
  `BITRIX-WORKFORCE-SCHEMA-001 v0.3`
- Причина rereview: code review обнаружил несовместимость clean compatible
  database при несовпадении database-default и charset-default collation
- Verdict: `APPROVED`

Этот append-only review проверяет только новое collation-усиление executable
test и соответствующий RED evidence. Reviewer не менял production code или
test.

## Reviewed identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
3ab1de71b348c9644de42a0bcc601c5968e9530cf29fe062c19182822f468479  tests/InstallationProcess/workforce_canonical_runner_001_test.php
e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
3098c845a182440bc0180a1f8e1bba776c87e8eb9465a2533a8f845b788061a5  bin/fmonitor2-migrate.php
04b026fcdca896ccda9ccabdbb24f65e90af238a7a11e98fa5b0e990d3801eac  app/InstallationProcess/WorkforceCatalogSchemaMigration.php
6ffa69142493d84879da233a261b87ff4b3494261ad3ebb47bbac70016e3a296  app/InstallationProcess/BitrixWorkforceHistorySchemaMigration.php
96c439b07e9245503afee0644c52714f3e2348e481125868ff90a7d38b88ea34  docs/operations/workforce-canonical-runner-collation-red-evidence.md
83615fbd3c63ae599d59035218954a136061df243d49957171ab9b624821e8a4  reviews/tests/WORKFORCE-CANONICAL-RUNNER-001-v3.md
```

## Контракт и качество RED

Clean fixture явно создаёт database с literal
`DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`. До public CLI тест
независимо читает `information_schema` и требует одновременно:

- database default равен `utf8mb4_unicode_ci`;
- database default не равен текущему charset default для `utf8mb4`.

Поэтому fixture доказывает именно требуемое различие и завершает setup до
behavior assertion. После него тест вызывает только публичный seam
`php bin/fmonitor2-migrate.php`, ожидает exact clean result
`schemaVersion:5`, `appliedVersions:[1,2,3,4,5]`, пустой stderr и literal
11-table catalogue. Independent verifier затем требует database-default
collation для каждой textual column всех четырёх workforce tables.

## Независимое воспроизведение

Свежий isolated Compose project
`fmonitor2-wcr-collation-review-20260902y` использовал отдельный host port
`25341`. RED wrapper завершился ожидаемо:

```text
Expected CLI: exit 0,
  {"ok":true,"schemaVersion":5,"appliedVersions":[1,2,3,4,5]}
Actual CLI: exit 2,
  {"ok":false,"reason":"SCHEMA_MIGRATION_CONFLICT","schemaVersion":5}
Actual catalogue: exact eight v1-v4 tables; v5 history tables absent.
RED_ASSERTION: expected failing behavior observed
```

Это не setup failure: предварительные collation assertions прошли, runner
подключился к database и создал exact v1-v4 catalogue. Конфликт возникает на
v5 seam из-за того, что v2 catalog получил charset-default collation вместо
явного database default. Тем самым RED чувствителен к требуемой production
поправке и не маскируется configuration/availability ошибкой.

## Неослабленная полная матрица

Изменение добавлено только перед прежним clean assertion. В test сохранены без
ослабления все обязательные §6 cases:

- clean literal v1-v5 result, exact catalogue/manifest и initial rows;
- populated repeat с byte-for-byte preservation;
- compatible partial recovery `[5]`;
- v5 conflict и earlier-version short-circuit с zero mutation;
- unexpected v5 failure как `MIGRATION_FAILED`;
- composed 25-byte success и 26-byte pre-DB rejection;
- direct family-local 37/38 boundary;
- runtime migration/DDL ownership и architecture ratchet.

## Final verification и cleanup

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

Test `finally` удалил collision-resistant review database; Compose container,
network и volume удалены reviewer cleanup.

## Verdict

`APPROVED`

Новый RED является qualifying behavior evidence через утверждённый public CLI:
он отдельно доказывает exact database-default semantics на clean compatible
database и не ослабляет ранее независимо принятую полную матрицу.
