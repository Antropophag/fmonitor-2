# Object-detail import characterization — corrected Gate 2 RED v4

Дата: 2026-09-05. Append-only continuation after committed v3 evidence and two
root-confirmed remaining test gaps. No specification or production change.

## Exact inputs

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
e92dca0bbf7ce5050245c9b4e9338af26699aea8c3482b8c8c9b9ccc147e38f2  tests/Verification/characterize_object_detail_import_001_test.php
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
```

Новых Support helpers нет.

## V4 corrections

- Fixture setup требует exact public v12 result: `applied=true`, version `12`
  и два точных prefixed table names.
- После exact table-level grants отдельное DDL-denied target connection обязано
  получить `true` от public `ObjectDetailSnapshotSchemaMigration::isCompleteCompatible`.
  Полный five-table snapshot до и после этого read-only call byte-equivalent.
  Поэтому последующий denied `CREATE` нельзя ошибочно принять за отсутствующую
  или конфликтную family prerequisite.
- Grant parser сначала отвергает `WITH GRANT OPTION`, включая global `USAGE`, и
  только затем распознаёт разрешённые grants. Выполняемый sensitivity case
  доказывает rejection делегирующего `USAGE`.

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

Meta-test выполнил и сравнил два distinct-token worker outcomes. Оба RED
возникли только после exact v12 apply result, compatible read-only observation и
unchanged prerequisite snapshot.

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

Pre-existing volumes were neither inspected nor removed. Common artifact root
survived. Gate 3 still requires a fresh independent review of the new exact
test hash; this record does not claim approval or GREEN.
