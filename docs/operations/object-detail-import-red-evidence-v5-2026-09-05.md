# Object-detail import characterization — corrected Gate 2 RED v5

Дата: 2026-09-05. Append-only continuation after committed v4 and
`reviews/tests/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001-v4.md`.

## Exact inputs

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
cc9d604301e50d28eb8908380381484ce6dfec9ed56cfd6202924a91c9d5c201  tests/Verification/characterize_object_detail_import_001_test.php
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
```

No Support, production, spec, runner or OpenSpec file changed.

## V5 lifecycle correction

- Parent proves exact token/container/artifact names vacant, creates each
  artifact child and tmpfs-backed container itself, and retains exact container
  ID, immutable image ID, token, port and any observed volume IDs.
- Worker receives that internal setup context, independently verifies the exact
  ID/image/label, and owns only fixture connections and behavioral execution.
  It cannot adopt a foreign resource and does not remove parent-owned resources.
- Parent releases its exact container, volumes, manifest and artifact child in
  attempt-all cleanup after every worker result or exception. Acquisition
  failures also run exact cleanup for every resource already acquired.
- Each worker starts by calling `posix_setsid`; parent verifies PID equals PGID.
  TERM/KILL target the negative PGID, so importer descendants are contained.
- A live process probe creates a real PHP grandchild, proves its group identity,
  terminates the group and proves the grandchild PID is gone. A separate live
  supervisor timeout probe acquires a real labeled tmpfs container/artifact
  child, times out a grouped worker with a nested child, then proves parent
  container/artifact cleanup and ambient-decoy preservation.
- Listener cleanup guard now begins immediately after resource variables are
  initialized and covers server creation, address parsing, positive connection,
  accept and behavioral failures; every acquired socket is closed attempt-all.

## Intended RED

```text
$ php -l tests/Verification/characterize_object_detail_import_001_test.php
No syntax errors detected in tests/Verification/characterize_object_detail_import_001_test.php
$ git diff --check
[exit 0, no output]
$ tools/verification/run.sh red tests/Verification/characterize_object_detail_import_001_test.php
REGRESSION_FAILURE: real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal
RED_ASSERTION: expected failing behavior observed in tests/Verification/characterize_object_detail_import_001_test.php
[wrapper exit 0]
```

After the controlled timeout probe, the normal path ran two distinct parent-owned
workers and compared their exact status/stdout/stderr before publishing the same
genuine importer CREATE-denied RED.

## Cleanup proof

```text
$ docker volume ls -q | sort > before
$ tools/verification/run.sh red tests/Verification/characterize_object_detail_import_001_test.php
$ docker volume ls -q | sort > after
$ diff -u before after
[exit 0, no output]
$ docker ps -a --filter 'label=fmonitor2.object-detail-token' --format '{{.Names}}'
[no output]
$ find .test-artifacts/object-detail-import -maxdepth 2 \
    \( -name 'object-detail-*' -o -name 'ambient-decoy.txt' \) -print
[no output]
$ test -d .test-artifacts/object-detail-import
[exit 0]
```

No pre-existing volume was inspected or removed. Gate 3 still requires a fresh
independent review of the exact v5 test hash.
