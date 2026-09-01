# Test rereview: WORKFORCE-CANONICAL-RUNNER-001 v2

- Дата: `2026-09-02`
- Reviewer: отдельный свежий агент
  `workforce_runner_test_rereview_20260902o`
- Автор исправленного теста/RED: отдельный агент
  `workforce_runner_red_correction_20260902n`
- Предыдущий reviewer: отдельный агент
  `workforce_runner_test_review_20260902l`
- Reviewed worktree: dirty authoritative worktree at
  `932662938837b28309fef2bf0fe3cadb2ce86e41`
- Executable spec: `WORKFORCE-CANONICAL-RUNNER-001 v0.1`
  (`APPROVED — GATE 1 PASSED`), наследует
  `BITRIX-WORKFORCE-SCHEMA-001 v0.3`
- OpenSpec change: `register-workforce-history-canonical-v5`
- Public seam: `php bin/fmonitor2-migrate.php`, вызываемый тестом через
  `proc_open` с argv и очищенным environment
- Superseding RED evidence:
  `docs/operations/workforce-canonical-runner-red-evidence-v2.md`
- Verdict: `APPROVED`

Этот append-only rereview supersedes только verdict предыдущего test review
`reviews/tests/WORKFORCE-CANONICAL-RUNNER-001.md`. Исходный review и исходный
RED record не переписывались.

## Reviewed identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
230edabc273047a71eb8145586abaf2a34072ab09558b0a2dd92bba252c01da8  tests/InstallationProcess/workforce_canonical_runner_001_test.php
e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
36443dd3ed01b95d6d3f70888e2d8f3c7edca5ad12f2a98f136936976410ad19  bin/fmonitor2-migrate.php
af1ec4eb6eb71a99e3b13d870951b19fc163d41ab96ccb74c380532650fca620  docs/operations/workforce-canonical-runner-red-evidence.md
11bf83a8f1df9e40214ebc1ba8cda9b2d874035a33f31d6543074a2489619319  docs/operations/workforce-canonical-runner-red-evidence-v2.md
d797dceb4762f56d60c2b68acade43e09ae0c121e9f823ab775efd4fdc17e9a3  reviews/tests/WORKFORCE-CANONICAL-RUNNER-001.md
475063c8447a2037a116d2ad70ef623a6d975e47a6757afb48ef34801fec7796  openspec/changes/register-workforce-history-canonical-v5/proposal.md
42e22447cceab324b536001369f2b20cfdeb960e85c0e04ff51bb0b774897837  openspec/changes/register-workforce-history-canonical-v5/design.md
93b5ce9138cff17240592613fc08646e5b7115c2300b06e884859f0a35c68dfc  openspec/changes/register-workforce-history-canonical-v5/tasks.md
0744c5c99c7a8aa4dbce70cf5b26d86a2f3497aca9b92553490d680d75acfae0  openspec/changes/register-workforce-history-canonical-v5/specs/deployment/canonical-workforce-migration/spec.md
```

## Closure прежних findings

1. **Exact 26-byte pre-DB proof — закрыто.** Исправленный test передаёт все
   обязательные и синтаксически полные DB variables, но использует независимо
   недоступный endpoint `127.0.0.1:1`. Exact result остаётся exit `64`,
   `CONFIGURATION_INVALID`, пустой stderr. Любая попытка connection/access до
   prefix rejection дала бы наблюдаемо иной `DATABASE_UNAVAILABLE`; отдельный
   snapshot reachable database одновременно доказывает zero ambient mutation.

2. **Обязательная матрица §6 — закрыто.** После первого clean tracer тест
   содержит literal public-CLI assertions для populated repeat `[]`, compatible
   partial recovery `[5]`, exact workforce conflict/version `5`, earlier
   conflict/version `1` с short-circuit, unexpected v5 DDL failure как
   `MIGRATION_FAILED`, composed `25/26`. Direct seam используется только для
   fixture preparation и отдельного family-local `37/38` verifier. Source
   ownership scan и реальный `make architecture-check` покрывают runtime DDL
   ownership. Таким образом минимальный GREEN не сможет пройти только подменой
   clean response: после регистрации v5 будет исполнена вся оставшаяся матрица.

3. **Fresh v5 data manifest — закрыто.** Тест literal проверяет пустые
   `fm2_workforce_sync_runs`, пустые `fm2_workforce_observations` и единственную
   metadata row `(1, null, null)`. Exact DDL/catalogue expectations находятся в
   test-owned `BitrixWorkforceSchemaV5Contract`; expected CLI JSON, версии,
   catalogue, prefixes и строки заданы независимо от runner catalogue и
   production migration constants.

## Traceability и чувствительность теста

- Clean exact 25-byte case ожидает literal ordered result
  `[1,2,3,4,5]`, exact 11-table catalogue и отдельно ограничивает каждый
  derived identifier максимумом MariaDB `64` bytes.
- Exact v5 verifier проверяет engine/collation, ordered columns, indexes,
  CHECK constraints и foreign keys; fresh rows проверяются отдельно.
- Repeat сравнивает полный `SHOW CREATE TABLE` и все строки всех v1–v5 tables
  до/после public CLI byte-for-byte.
- Partial recovery сохраняет каждую существовавшую definition/row и требует
  создать только отсутствующий compatible target с result `[5]`.
- Оба conflict cases сравнивают полный namespace до/после; early v1 conflict
  не может создать v5 targets.
- Unexpected failure создаётся отдельным SELECT-only principal после
  успешной fixture preparation v1–v4, поэтому failure принадлежит первому v5
  DDL и обязан отличаться от conflict.
- Direct 38-byte case на закрытом `mysqli` обязан дать
  `InvalidArgumentException` до DB access; direct 37-byte case обязан пройти
  validation и достичь закрытого connection. Это не подменяет composed CLI
  proof.
- Database и temporary principal получают collision-resistant suffix;
  principal удаляется во внутреннем `finally`, database — во внешнем
  `finally`. Production credentials и данные не используются.

## Независимое воспроизведение qualifying RED

```text
$ FMONITOR_TEST_DB_PORT=26326 docker compose \
    -p fmonitor2-wcr-rereview-20260902o \
    -f compose.test.yaml up --detach --wait test-db
Container fmonitor2-wcr-rereview-20260902o-test-db-1 Healthy

$ FMONITOR_TEST_DB_HOST=127.0.0.1 \
    FMONITOR_TEST_DB_PORT=26326 \
    FMONITOR_TEST_DB_ADMIN_USER=root \
    FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    bash tools/verification/run.sh red \
      tests/InstallationProcess/workforce_canonical_runner_001_test.php
Expected CLI result: exit 0,
  {"ok":true,"schemaVersion":5,"appliedVersions":[1,2,3,4,5]}
Actual CLI result: exit 0,
  {"ok":true,"schemaVersion":4,"appliedVersions":[1,2,3,4]}
Expected catalogue: literal 11 v1-v5 tables
Actual catalogue: exact eight v1-v4 tables; all three v5 tables absent
RED_ASSERTION: expected failing behavior observed in
  tests/InstallationProcess/workforce_canonical_runner_001_test.php
Wrapper exit: 0
```

CLI дошёл до healthy MariaDB и успешно применил точный v1–v4 catalogue.
Падение произошло на первом assertion, чувствительном ровно к отсутствующей
регистрации v5 после v4. Это qualifying behavior RED, а не configuration,
fixture или environment failure. Остановка на первом assertion до GREEN
нормальна: оставшаяся утверждённая матрица уже executable и будет достигнута
после минимальной composition change без ослабления expectations.

После запуска внешний запрос подтвердил отсутствие database с именем
`t_wcr_001_%`. Review Compose project был удалён с volumes и orphan resources;
повторный `docker compose ... ps --all` вернул только пустой header.

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

$ docker compose -p fmonitor2-wcr-rereview-20260902o \
    -f compose.test.yaml down --volumes --remove-orphans
Container and network removed

$ docker compose -p fmonitor2-wcr-rereview-20260902o \
    -f compose.test.yaml ps --all
NAME IMAGE COMMAND SERVICE CREATED STATUS PORTS
```

## Verdict

`APPROVED`

Исправленный test соответствует owner-approved executable contract, закрывает
все блокирующие findings предыдущего review и даёт воспроизводимый
qualifying RED. Gate 3 пройден. Следующий допустимый этап — минимальный GREEN с
неизменными test expectations, затем regression/architecture checks и новый
отдельный независимый code review.
