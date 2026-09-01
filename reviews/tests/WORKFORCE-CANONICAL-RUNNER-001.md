# Test review: WORKFORCE-CANONICAL-RUNNER-001

- Reviewer: отдельный агент `workforce_runner_test_review_20260902l`
- Test author: отдельный агент `workforce_runner_red_author_20260902i`
- Reviewed worktree: dirty authoritative worktree at `932662938837b28309fef2bf0fe3cadb2ce86e41`
- Specification: `WORKFORCE-CANONICAL-RUNNER-001 v0.1` (`APPROVED — GATE 1 PASSED`), наследует `BITRIX-WORKFORCE-SCHEMA-001 v0.3`
- OpenSpec change: `register-workforce-history-canonical-v5`
- Public seam: `php bin/fmonitor2-migrate.php`, реально запущенный тестом через `proc_open` с argv и очищенным environment
- Red command and intended failure: `FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 FMONITOR_TEST_DB_ADMIN_USER=root FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local bash tools/verification/run.sh red tests/InstallationProcess/workforce_canonical_runner_001_test.php`; исправный canonical CLI применяет v1–v4, но возвращает `schemaVersion=4`, `[1,2,3,4]` и не создаёт три v5 tables
- Verdict: `CHANGES_REQUESTED`

## Reviewed identities

```text
44d977d6f3f05752859a28c4250b259ded2df017b408316b88d6d610a688750e  specs/WORKFORCE-CANONICAL-RUNNER-001.md
8e3e2728ad1646092e37460d973b7f15bccdcb60fe6ef317019ab620e083a4ce  tests/InstallationProcess/workforce_canonical_runner_001_test.php
e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
36443dd3ed01b95d6d3f70888e2d8f3c7edca5ad12f2a98f136936976410ad19  bin/fmonitor2-migrate.php
af1ec4eb6eb71a99e3b13d870951b19fc163d41ab96ccb74c380532650fca620  docs/operations/workforce-canonical-runner-red-evidence.md
475063c8447a2037a116d2ad70ef623a6d975e47a6757afb48ef34801fec7796  openspec/changes/register-workforce-history-canonical-v5/proposal.md
42e22447cceab324b536001369f2b20cfdeb960e85c0e04ff51bb0b774897837  openspec/changes/register-workforce-history-canonical-v5/design.md
93b5ce9138cff17240592613fc08646e5b7115c2300b06e884859f0a35c68dfc  openspec/changes/register-workforce-history-canonical-v5/tasks.md
0744c5c99c7a8aa4dbce70cf5b26d86a2f3497aca9b92553490d680d75acfae0  openspec/changes/register-workforce-history-canonical-v5/specs/deployment/canonical-workforce-migration/spec.md
```

## Findings

1. **Blocking — exact 26-byte case does not prove pre-DB-access rejection.** The test snapshots schema and rows before and after invoking the CLI against the same reachable database. This proves zero mutation, but a regression that opens a DB connection, performs reads, and only then returns `CONFIGURATION_INVALID` would still pass. Section 6 example 8 explicitly requires a connection observer proving zero DB connection/access. Run the 26-byte case with independently unusable DB connection coordinates/credentials (while keeping every required variable syntactically present), or add an equivalent observable connection-denial mechanism; retain the ambient-state assertion separately.

2. **Blocking — the approved executable matrix is materially incomplete.** Section 6 says the Gate 2 test must prove at minimum completed repeat, compatible v5 partial recovery, incompatible workforce conflict with zero workforce mutation, earlier-version short-circuit, unexpected-v5-failure classification, direct-family 37/38 behavior, and the runtime ownership architecture rule. The reviewed test covers only clean composition/exact catalogue and part of composed 25/26. An implementation that always fabricates the clean success path but breaks repeat/recovery/conflict/failure ordering or direct-family boundaries can therefore go green. Add deterministic public-seam cases for items 2–6 and independent verification for items 9–10 before Gate 3 approval. If these are intentionally separate RED increments, the approved executable spec and OpenSpec gate tasks must first define that decomposition rather than claiming this file satisfies the current minimum matrix.

3. **Blocking — clean v5 data manifest is under-asserted.** `wcrAssertExactV5()` checks tables, columns, indexes, CHECKs and FKs but never verifies the mandatory fresh metadata singleton `(1, null, null)`, nor explicitly proves empty runs/observations. A v5 implementation that creates exact DDL but omits or fabricates initial rows would pass this test. Add literal test-owned row expectations from `BITRIX-WORKFORCE-SCHEMA-001 v0.3`; do not derive them from migration constants or output.

4. **RED and setup are valid.** Independent reproduction in a fresh Compose project reached healthy MariaDB, ran the real CLI, created the exact eight-table v1–v4 catalogue and failed only because the result remained `schemaVersion=4`/`[1,2,3,4]` with all three new v5 tables absent. The wrapper recognized the intended assertion failure. This is qualifying RED, not an environment/setup failure.

5. **Determinism and cleanup are otherwise acceptable.** The test owns a random `t_wcr_001_<12 hex>` database, uses no production credentials/data, drops it in `finally`, and the independent post-run query found no matching database. The review Compose project was removed with volumes/orphans and subsequent `ps --all` was empty.

## Exact verification evidence

```text
$ docker compose -p fmonitor2-wcr-review-20260902l -f compose.test.yaml up --detach --wait test-db
Container fmonitor2-wcr-review-20260902l-test-db-1 Healthy

$ FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 FMONITOR_TEST_DB_ADMIN_USER=root FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local bash tools/verification/run.sh red tests/InstallationProcess/workforce_canonical_runner_001_test.php
Expected: exit 0, schemaVersion=5, appliedVersions=[1,2,3,4,5], exact 11-table catalogue
Actual:   exit 0, schemaVersion=4, appliedVersions=[1,2,3,4], exact eight-table v1-v4 catalogue
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/workforce_canonical_runner_001_test.php
Wrapper exit: 0

$ docker compose -p fmonitor2-wcr-review-20260902l -f compose.test.yaml exec -T test-db mariadb -uroot -p... -Nse "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 't_wcr_001_%' ORDER BY SCHEMA_NAME"
<empty>

$ docker compose -p fmonitor2-wcr-review-20260902l -f compose.test.yaml down --volumes --remove-orphans
Container and network removed

$ docker compose -p fmonitor2-wcr-review-20260902l -f compose.test.yaml ps --all
NAME IMAGE COMMAND SERVICE CREATED STATUS PORTS
```

Final repository checks are recorded below after this review artifact was added.

```text
$ git diff --check
exit 0, no output

$ make architecture-check
ARCHITECTURE CHECK PASSED (6 rules)

$ openspec validate register-workforce-history-canonical-v5 --strict
Change 'register-workforce-history-canonical-v5' is valid
```

## Required changes

- Add an observable zero-connection proof for composed prefix 26, separate from zero mutation.
- Complete the approved Section 6 runner/recovery/rejection/failure/direct-family/architecture matrix, or first obtain an approved spec decomposition that makes later independent RED slices explicit.
- Assert literal initial v5 row state, including the metadata singleton and empty run/observation tables.
- Capture a new qualifying RED and send the corrected artifacts to a new fresh independent test reviewer.
