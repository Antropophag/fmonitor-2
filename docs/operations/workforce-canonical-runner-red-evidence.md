# WORKFORCE-CANONICAL-RUNNER-001 — Gate 2 RED evidence

- Дата: `2026-09-02`
- Автор теста/RED: отдельный агент `workforce_runner_red_author_20260902i`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`, owner-approved
- Публичный seam: `php bin/fmonitor2-migrate.php`
- Verdict автора: `QUALIFYING_RED_CAPTURED — AWAITING_INDEPENDENT_TEST_REVIEW`

## Срез теста

Добавлен минимальный runner-level tracer
`tests/InstallationProcess/workforce_canonical_runner_001_test.php`. Он не
вызывает migration classes напрямую и проверяет через canonical CLI:

1. чистый namespace с exact 25-byte ASCII prefix возвращает ordered
   `schemaVersion=5`, `appliedVersions:[1,2,3,4,5]`;
2. полный список таблиц содержит v1–v5 и не содержит лишних таблиц;
3. workforce v5 независимо сверяется с literal test-owned manifest
   `BitrixWorkforceSchemaV5Contract`: engine/collation, ordered columns,
   indexes, CHECK, foreign keys и предел MariaDB identifier `64`;
4. после прохождения clean tracer exact 26-byte prefix обязан вернуть
   `CONFIGURATION_INVALID` и оставить всю schema/rows неизменными.

Последовательность намеренна: текущий RED сначала доказывает отсутствующую v5
composition. После минимального GREEN тот же тест без изменения ожиданий
проверит независимый exact v5 manifest, а затем composed boundary `25/26`.

## Изоляция и setup proof

Использован отдельный Compose project и отдельный host port:

```text
FMONITOR_TEST_DB_PORT=24326 docker compose \
  -p fmonitor2-wcr-red-20260902i \
  -f compose.test.yaml up --detach --wait test-db

Container fmonitor2-wcr-red-20260902i-test-db-1 Healthy
```

Каждый запуск теста создаёт случайную database `t_wcr_001_<12 hex>`, удаляет её
в `finally` и не использует production credentials/data.

## Qualifying RED command

```sh
FMONITOR_TEST_DB_HOST=127.0.0.1 \
FMONITOR_TEST_DB_PORT=24326 \
FMONITOR_TEST_DB_ADMIN_USER=root \
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
bash tools/verification/run.sh red \
  tests/InstallationProcess/workforce_canonical_runner_001_test.php
```

Существенный exact output:

```text
Expected result:
  exitCode: 0
  stdout: {"ok":true,"schemaVersion":5,"appliedVersions":[1,2,3,4,5]}
  stderr: <empty>
Expected tables: exact 11-table v1-v5 catalogue, including
  aaaaaaaaaaaaaaaaaaaaaaaaafm2_workforce_observations
  aaaaaaaaaaaaaaaaaaaaaaaaafm2_workforce_sync_metadata
  aaaaaaaaaaaaaaaaaaaaaaaaafm2_workforce_sync_runs

Actual result:
  exitCode: 0
  stdout: {"ok":true,"schemaVersion":4,"appliedVersions":[1,2,3,4]}
  stderr: <empty>
Actual tables: exact eight v1-v4 tables; all three new v5 tables absent.

RED_ASSERTION: expected failing behavior observed in
tests/InstallationProcess/workforce_canonical_runner_001_test.php
```

Wrapper exit: `0`, то есть тест действительно упал, а RED harness подтвердил
ожидаемый failure. Сам CLI успешно подключился к MariaDB и полностью применил
v1–v4. Поэтому причина RED — отсутствующая регистрация v5 после v4, не setup,
configuration, DB availability или ранняя migration regression.

## Cleanup proof

```text
docker compose -p fmonitor2-wcr-red-20260902i -f compose.test.yaml \
  down --volumes --remove-orphans

Container fmonitor2-wcr-red-20260902i-test-db-1 Removed
Network fmonitor2-wcr-red-20260902i_default Removed
TEARDOWN_EXIT=0
```

Повторный `docker compose ... ps --all` вернул пустой список.

## Reviewed-input hashes

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
8e3e2728ad1646092e37460d973b7f15bccdcb60fe6ef317019ab620e083a4ce  tests/InstallationProcess/workforce_canonical_runner_001_test.php
e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
36443dd3ed01b95d6d3f70888e2d8f3c7edca5ad12f2a98f136936976410ad19  bin/fmonitor2-migrate.php
```

Production code, executable spec, OpenSpec planning, status и review records
этим Gate 2 автором не изменялись. Следующий обязательный gate — отдельный
независимый test review; автор этого документа не утверждает собственный тест.
