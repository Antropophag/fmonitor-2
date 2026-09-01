# WORKFORCE-CANONICAL-RUNNER-001 — Gate 2 corrected RED evidence v2

- Дата: `2026-09-02`
- Автор исправленного теста/RED: отдельный свежий агент
  `workforce_runner_red_correction_20260902n`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, owner-approved
- Публичный seam: `php bin/fmonitor2-migrate.php`
- Verdict автора: `QUALIFYING_RED_CAPTURED — AWAITING_FRESH_INDEPENDENT_TEST_REREVIEW`
- Supersedes: `docs/operations/workforce-canonical-runner-red-evidence.md`
- Основание исправления: test review
  `reviews/tests/WORKFORCE-CANONICAL-RUNNER-001.md` с verdict
  `CHANGES_REQUESTED`

Исходный transcript не переписан. Этот append-only v2 record заменяет его как
актуальное Gate 2 evidence.

## Исправления findings

Исправленный
`tests/InstallationProcess/workforce_canonical_runner_001_test.php` сохраняет
первым clean public-CLI tracer и после него содержит полную минимальную матрицу
раздела 6 approved spec:

1. clean exact 25-byte composed prefix, literal ordered v1–v5 result и exact
   11-table catalogue;
2. independent exact v5 DDL manifest плюс literal initial rows: empty runs,
   empty observations и metadata singleton `(1, null, null)`;
3. populated completed repeat с literal `appliedVersions:[]` и byte-for-byte
   неизменным полным schema/rows fingerprint;
4. compatible v5 partial recovery через public CLI с literal `[5]` и
   сохранением всех существовавших definitions/rows;
5. несовместимая workforce target table: exact v5 conflict и zero mutation;
6. несовместимая v1 target table: exact version-1 conflict, zero mutation и
   short-circuit до v5;
7. unexpected v5 DDL denial для отдельного SELECT-only DB principal:
   `MIGRATION_FAILED`, не conflict;
8. exact 26-byte composed prefix запускается с синтаксически полным, но
   независимо недоступным endpoint `127.0.0.1:1`. Ожидаемый
   `CONFIGURATION_INVALID` тем самым наблюдаемо отличает pre-DB rejection от
   любой попытки connection/access, которая дала бы `DATABASE_UNAVAILABLE`;
   отдельный reachable-connection snapshot подтверждает неизменность ambient
   schema/rows;
9. direct-family verifier на закрытом `mysqli` независимо доказывает, что 38
   bytes дают `InvalidArgumentException` до DB access, а 37 bytes проходят
   validation и достигают DB access;
10. machine-checkable source ownership assertion запрещает production runtime
    direct v5 apply/workforce DDL вне canonical migration owner и отдельно
    запускает repository `make architecture-check`.

Expected JSON, версии, prefix values, initial rows, sentinel rows и catalogue
заданы literal в тесте. Они не читаются из runner catalogue или production
migration constants. Все проверяемые runner outcomes проходят через public
CLI; direct migration используется только для fixture preparation и отдельно
оговорённого family-local 37/38 verifier.

## Изоляция

Использован свежий Compose project с отдельным host port:

```text
$ FMONITOR_TEST_DB_PORT=25326 docker compose \
    -p fmonitor2-wcr-correction-20260902n \
    -f compose.test.yaml up --detach --wait test-db
Container fmonitor2-wcr-correction-20260902n-test-db-1 Healthy
```

Тест создаёт collision-resistant database
`t_wcr_001_<12 lowercase hex>`, collision-resistant temporary DB principal
`wcr_<12 lowercase hex>`, удаляет principal и database в `finally` и не
использует production credentials/data. Все prefixes внутри общей private
database уникальны и deterministic внутри одного запуска.

## Fresh qualifying RED

```sh
FMONITOR_TEST_DB_HOST=127.0.0.1 \
FMONITOR_TEST_DB_PORT=25326 \
FMONITOR_TEST_DB_ADMIN_USER=root \
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
bash tools/verification/run.sh red \
  tests/InstallationProcess/workforce_canonical_runner_001_test.php
```

Существенный exact result:

```text
Expected CLI result:
  exitCode: 0
  stdout: {"ok":true,"schemaVersion":5,"appliedVersions":[1,2,3,4,5]}
  stderr: <empty>
Expected catalogue: literal 11 tables, including
  aaaaaaaaaaaaaaaaaaaaaaaaafm2_workforce_observations
  aaaaaaaaaaaaaaaaaaaaaaaaafm2_workforce_sync_metadata
  aaaaaaaaaaaaaaaaaaaaaaaaafm2_workforce_sync_runs

Actual CLI result:
  exitCode: 0
  stdout: {"ok":true,"schemaVersion":4,"appliedVersions":[1,2,3,4]}
  stderr: <empty>
Actual catalogue: exact eight v1-v4 tables; all three v5 tables absent

RED_ASSERTION: expected failing behavior observed in
tests/InstallationProcess/workforce_canonical_runner_001_test.php
Wrapper exit: 0
```

CLI подключился к healthy MariaDB и полностью применил v1–v4. Поэтому RED
остаётся вызван только отсутствующей v5 composition, а не setup, DB
availability, configuration, fixture preparation или более поздним assertion.

## Reviewed-input identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
230edabc273047a71eb8145586abaf2a34072ab09558b0a2dd92bba252c01da8  tests/InstallationProcess/workforce_canonical_runner_001_test.php
e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
36443dd3ed01b95d6d3f70888e2d8f3c7edca5ad12f2a98f136936976410ad19  bin/fmonitor2-migrate.php
af1ec4eb6eb71a99e3b13d870951b19fc163d41ab96ccb74c380532650fca620  docs/operations/workforce-canonical-runner-red-evidence.md
```

Production/OpenSpec/spec/status/review records этим correction author не
изменялись. Следующий gate — новый свежий независимый test rereviewer; автор
этого evidence не утверждает собственный тест.

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

$ docker compose -p fmonitor2-wcr-correction-20260902n \
    -f compose.test.yaml down --volumes --remove-orphans
Container and network removed

$ docker compose -p fmonitor2-wcr-correction-20260902n \
    -f compose.test.yaml ps --all
NAME IMAGE COMMAND SERVICE CREATED STATUS PORTS
```
