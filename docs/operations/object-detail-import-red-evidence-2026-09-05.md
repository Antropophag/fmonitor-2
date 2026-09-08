# Object-detail import characterization — Gate 2 RED evidence

Дата: 2026-09-05. Роль: отдельно назначенный test author.

Owner Gate 1: `docs/operations/object-detail-import-v02-owner-approval-2026-09-05.md`.
Проверенная executable spec: `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2`.

## Exact inputs

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
22b2fc642c247d5f1d748534524aceed82eac7c727f153cb2527cbf1e82d82a2  tests/Verification/characterize_object_detail_import_001_test.php
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
2bc47395cdaf61974c493907a84d8f8f25dc034e10c140a8fd851ca4845ace8a  app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
39596f079862334be04f4231664862e55d4febe54309cc62f750f2297de85b06  local immutable MariaDB image ID
```

OpenSpec immutable planning hashes observed for this Gate 2 run:

```text
bc6c8ec30aa9a17d0074ebe3e5273febf3961534da1467d65fd7324ba0da3691  openspec/changes/characterize-object-detail-import/proposal.md
cf72aa312f86f1e2923b531fdad9c741dd5b78a45a50faeba6740e3749dc9c11  openspec/changes/characterize-object-detail-import/design.md
5fdfc6e1b039ed9671c63da9a4772cb9c2280f91363c35612a0b41e86c6d40e9  openspec/changes/characterize-object-detail-import/tasks.md
f69b02e090df4a888a69b2e9c53cce262162b16314be2d4d8e37327ea7cfac0c  openspec/changes/characterize-object-detail-import/specs/verification/object-detail-import-characterization/spec.md
```

Support helper files added: none. The test hash above covers the complete new
test-side harness and oracle.

## Commands and observed RED

Syntax and patch hygiene:

```text
$ php -l tests/Verification/characterize_object_detail_import_001_test.php
No syntax errors detected in tests/Verification/characterize_object_detail_import_001_test.php

$ git diff --check
[exit 0, no output]
```

Canonical Gate 2 wrapper:

```text
$ tools/verification/run.sh red tests/Verification/characterize_object_detail_import_001_test.php
REGRESSION_FAILURE: real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal
RED_ASSERTION: expected failing behavior observed in tests/Verification/characterize_object_detail_import_001_test.php
[exit 0: wrapper observed the intended failing behavior]
```

Direct verifier observation, captured separately so wrapper success cannot hide
the verifier classification:

```text
$ php tests/Verification/characterize_object_detail_import_001_test.php
[stdout: 0 bytes]
REGRESSION_FAILURE: real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal
[exit 1]
```

This is qualifying real-importer RED. Before the failure, the test created a
fresh labeled MariaDB container from the pinned image ID, verified its exact
container identity and loopback endpoint, created only private synthetic source
and guarded `fmonitor2_demo`, applied public v12 to the exact prefixed family,
and verified distinct source SELECT-only and target SELECT/INSERT-only
principals through `CURRENT_USER()` and `SHOW GRANTS`. The real child then read
the source and reached its first production runtime `CREATE TABLE IF NOT EXISTS`;
MariaDB denied it. Therefore the failure is neither unavailable infrastructure
nor the absence of a verifier.

The test also contains reviewed-input assertions for literal six-field material
and fixed hashes, clean/replay capture preservation, whole-batch conflict,
metadata/dictionary source rejection, clean dry-run, and the eight-call
absent/drift × apply/dry-run pre-source listener matrix. Those later assertions
remain RED-reachable only after the separately gated no-DDL correction removes
the first demonstrated breach; this record does not claim GREEN.

## Cleanup proof

After both the wrapper and direct run:

```text
$ docker ps -a --filter 'label=fmonitor2.object-detail-token' --format '{{.Names}}'
[no output]

$ find .test-artifacts -maxdepth 3 \( -name 'object-detail-*' -o -name 'ambient-decoy.txt' \) -print
[no output]
```

Each run verified the exact label before removal. No production/source secret,
real identifier, shared database, bind mount, volume, or persistent transcript
was used. The repository-owned artifact root and its ambient decoy were removed
only after the per-run child cleanup; unrelated repository state was preserved.

## Gate state

Gate 2 RED is demonstrated for the real importer no-DDL boundary. No OpenSpec
task is marked complete here. Gate 3 still requires a fresh independent test
review with exact spec/test/transcript hashes before any importer GREEN change.
