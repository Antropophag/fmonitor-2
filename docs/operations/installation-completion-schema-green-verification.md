# INSTALLATION-COMPLETION-SCHEMA-001 — Gate 4 verification

Date: 2026-09-02
Status: **GREEN / awaiting independent Gate 5 code review**

## Approved tests

После final independent Gate 3 approval
`reviews/tests/INSTALLATION-COMPLETION-SCHEMA-001.md` оба exact executable
verifier проходят без изменения approved expectations:

```text
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
PASS: INSTALLATION-COMPLETION-SCHEMA-001 DML-only runtime matrix
```

Schema matrix доказывает literal v10, clean/repeat/root-only recovery,
preservation, full-family conflicts, prefix/collation и correction-chain
constraints. Runtime matrix доказывает exact/missing/drift для queue, card,
checklist, completion POST и bootstrap под DML-only principal; exact POST
добавляет ровно один PTO fact, missing/drift не выполняют repair или DML.

## Focused integration

GREEN воспроизведён для production migration runner, identity/access,
inspection evidence, inspection planning schema/runtime, inspection-item
MariaDB, checklist template, pilot case import, workforce canonical runner,
HTTP auth, calendar, completion и OTIZ canonical compatibility. Durable
independent review v10 fixture alignment:
`docs/operations/installation-completion-v10-integration-fixture-review.md` —
`APPROVED`.

Built-image verifier также GREEN после independent rereview
`reviews/tests/HARNESS-IMAGE-CANONICAL-RUNNER-001-v2.md`: packaged non-root
image содержит canonical runner и текущий bootstrap, сохраняет exact
configuration failure/redaction contract.

## Architecture and hygiene

```text
ARCHITECTURE CHECK PASSED (7 rules)
make lint: exit 0
git diff --check: exit 0
openspec validate canonicalize-installation-completion-schema --strict: valid
```

Completion request-time DDL удалён. Architecture baseline уменьшен только на
obsolete completion DDL/mutation fingerprints; существующий queue SQL debt
сохранён 1-for-1 после изменения readiness call. Новый migration owner имеет
149 строк и не образует новый hotspot.

## Full and fresh verification

Повторный `make verify` после v10 fixture alignment:

```text
VERIFY_STAGE test-db-reset PASS
VERIFY_STAGE migrate PASS (schemaVersion=10, appliedVersions=[1..10])
VERIFY_STAGE architecture-check PASS
VERIFY_STAGE lint PASS
VERIFY_STAGE unit-test PASS
VERIFY_STAGE db-test FAIL
VERIFY_STAGE characterization-test PASS
VERIFY_STAGE e2e-test FAIL
VERIFY_STAGE diff-check PASS
FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test
```

Completion-owned, planning, migration catalogue и characterization checks
GREEN. DB residual состоит из уже отдельно owned local-RBAC/UI fixtures,
host-only login session setup и двух наблюдений одного combined-PDF E2E
contract. E2E stage повторяет тот же combined-PDF failure. Assertions не
ослаблялись и эти failures не считаются completion behavior.

`make fresh-test-verify` воспроизвёл тот же terminal result и затем успешно
выполнил обязательный teardown:

```text
FRESH_TEST_VERIFY_FAILURE verify_status=2 teardown_status=0
docker compose -f compose.test.yaml ps --all: empty
```

## Reviewed hashes

```text
ebd59ff1ca8c9968a6726da4f9d45f133477951f81f11a529e61fd61079ac1f7  app/InstallationProcess/InstallationCompletionSchemaMigration.php
c70223a44d716e967ce317d948871614d36c25ee902ba36c0296a55c516d153a  app/InstallationProcess/InstallationCompletionDefinitionSchemaMigration.php
846f05e1aedd215ab824512e159f54c110553c2d929639f4fdaf92293ca5cd54  app/InstallationProcess/MariaDbPilotLegacyObjectSchemaReadiness.php
df1c58fa0437fc51868db2e91bcba35793d07957f6359aa36407552e8a17319d  bin/fmonitor2-migrate.php
2f162a78f05d2d6efd70e512211ad3fc06b25900e024adfca67c258b286e1dc8  rapid-pilot/CompletionFlow.php
1244e6d550594453d32e21c49d351410b127d82b5c7812e512cc240f573125fc  rapid-pilot/Otiz.php
700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f  rapid-pilot/docker-bootstrap.php
b70ef939e67152afd4059f419464d3d5c3cc5644448e415ca76348b1759e118d  tests/InstallationProcess/installation_completion_schema_001_test.php
8427a39674cf8d4c0e710f164bca14487b88a6fb54815fca699fc5119064618d  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
3f139844be5fbf9898474f3075612ff9e8403e8819b29f43236c4f6cd3a82ebe  reviews/tests/INSTALLATION-COMPLETION-SCHEMA-001.md
```

Это evidence разрешает Gate 5 review, но само не является code approval или
Done.

## Gate 5 correction cycle

Первый Gate 5 review вернул `CHANGES_REQUESTED`: reserved supporting index
ошибочно фильтровался как exact, `FOREIGN_KEY_CHECKS` не сохранял caller state,
а migration был механически сжат до hotspot threshold. После нового RED,
нескольких fresh Gate 3 rereviews и correction GREEN:

- crash-state и hostile reserved-name index дают deterministic conflict без
  mutation;
- session `FOREIGN_KEY_CHECKS` сохраняется для `0→0`, `1→1` и failure path;
- public orchestrator уменьшен до 32 строк, coherent definition/fingerprint
  helper — до 42 строк без duplicated manifests;
- schema/runtime matrices, architecture 7/7, lint, diff-check, strict OpenSpec
  и built-image verifier снова GREEN;
- повторный `make verify` сохранил тот же unrelated terminal result
  `count=2 stages=db-test,e2e-test`; completion-owned checks GREEN.

Correction hashes:

```text
34e8e941fc71fe9bf2db1b2690378a4cd3ca1fed9c50cb8c012d1c015f1ef071  app/InstallationProcess/InstallationCompletionSchemaMigration.php
a275a7f060612045a75dffa460cfe7836c84fdb976a1acea3b7487bf1692e690  app/InstallationProcess/InstallationCompletionDefinitionSchemaMigration.php
98f9dceb0c35c15a90e0c6349854a6299dfe8727dd8249df5133f2b654579e1a  tests/InstallationProcess/installation_completion_schema_001_test.php
f2edf274cad89b70ebac406004b3e235fa5214ff1994be1d0bce6b0f9105e310  reviews/tests/INSTALLATION-COMPLETION-SCHEMA-001.md
```

Требуется fresh Gate 5 code rereview; прежний `CHANGES_REQUESTED` не считается
автоматически закрытым.

## Final ST1 maintainability correction

Второй Gate 5 rereview справедливо признал предыдущую фразу «без duplicated
manifests» недоказанной: split 32/42 строки содержал строки до 4037 символов и
дублировал hand-written DDL с PHP manifest. Этот промежуточный design
superseded.

Final production design:

- public migration orchestrator — 71 строка;
- structured schema definition + readable DDL renderer — 137 строк;
- MariaDB exact fingerprint derived из той же definition — 116 строк;
- максимальная длина production-строки — 119;
- hand-written DDL/manifest duplication отсутствует.

Approved schema/runtime tests, production runner, architecture 7/7, lint и
diff-check повторно GREEN. Final ST1 hashes:

```text
09055ed8b3d2521d925ce55001a83b34a3f5f49edbb063b4f11a4b80450e8583  app/InstallationProcess/InstallationCompletionSchemaMigration.php
167c5668cd7db12fc564fd8a73f883d66926300fbd81cd0cd4482ffe9d752674  app/InstallationProcess/InstallationCompletionDefinitionSchemaMigration.php
abf1fa34b8f04f20138daee5671a4133ed160d37209d175ffb666d0f6e7ddd7a  app/InstallationProcess/MariaDbInstallationCompletionSchemaFingerprint.php
```

Нужен новый independent Gate 5 rereview exact final bytes.
