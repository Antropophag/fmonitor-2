# Object-detail import characterization — corrected Gate 2 RED v6

Дата: 2026-09-05. Append-only continuation after committed v5 and the single
acquisition finding in `reviews/tests/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001-v5.md`.

## Exact inputs

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
fae2d9a5e3ba184663e7850ca83fdfd14125d71d22b8970b5d95e45572e19bcc  tests/Verification/characterize_object_detail_import_001_test.php
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
```

## V6 acquisition correction

- Exact token/root/child/container/image context and vacancy are established
  before the first mutation. `mkdir`, ownership latch, `chmod`, Docker create,
  storage inspection and start all execute inside one protected acquisition.
- Docker-create attempt is recorded before invoking the CLI. If its response is
  lost after daemon creation, recovery inspects only the exact previously vacant
  name and accepts it only when token label and immutable image match; it then
  captures the exact container ID and volume mount IDs.
- Container absence requires Docker's exact not-found tuple: exit nonzero,
  stdout `[]` and stderr `Error response from daemon: No such container: <exact>`.
  Other inspect failures are setup failures, never absence proof.
- Removal uses the captured container ID after exact name/ID/token proof.
- Live `after-child` and `after-create-response-loss` faults prove exact child,
  container and volume cleanup plus ambient-decoy preservation.

## Intended two-token RED

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

Both acquisition probes and the parent-owned timeout/descendant containment
probe complete before the ordinary two distinct-token workers. Both ordinary
workers retain the genuine real-importer CREATE-denied RED.

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

No old volume was inspected or removed. No production, specification, runner,
OpenSpec or Support file changed. Fresh Gate 3 review is still required.
