# Object-detail import characterization — corrected Gate 2 RED v3

Дата: 2026-09-05. Этот append-only record дополняет v1/v2 evidence после
`CHANGES_REQUESTED` в `reviews/tests/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001-v2.md`.

## Exact inputs

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
f85dd75c952afd8b393da33de71cd68547de3efd01fb9255abac2bab091ec68c  tests/Verification/characterize_object_detail_import_001_test.php
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
```

Новых Support helpers нет. Production, spec, runner и OpenSpec artifacts не
изменялись.

## V3 corrections

- Process stdout/stderr во всех main/TERM/KILL/final-drain loops читаются только
  bounded chunks до отдельного ceiling 256 KiB.
- После bounded KILL/reap process с `running=true` никогда не передаётся в
  blocking `proc_close`; executable branch check фиксирует это правило.
- Behavioral failure и cleanup failures сохраняются отдельно. Все независимо
  безопасные connection/container/volume/manifest/artifact phases выполняются
  attempt-all. Established regression плюс cleanup failure сохраняет exit `1`
  и добавляет safe `SETUP_FAILURE: cleanup phases failed: ...` в stderr.
- Outer decoy/common-root cleanup использует ту же precedence, сохраняя foreign
  decoy при latch mismatch.
- Выполняемые self-checks доказывают regression+cleanup exit `1`, сохранение обеих
  categories, вызов всех трёх probe cleanup phases после двух failures, а также
  запрет `proc_close` для running child.
- Pre-clean snapshot теперь сравнивается с post-clean: cases, generation sentinel
  и SQL decoy сохраняют полную shape/rows, обе family schemas сохраняют full
  DDL/table metadata, а complete family rows проверяются отдельными literal
  assertions.

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

Meta-test выполнил два distinct-token workers и сравнил их full normalized
status/stdout/stderr до публикации одной safe RED category. Это real importer
CLI failure после healthy private setup и exact v12 precreate.

## Owned cleanup proof

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

Ни один ранее существовавший volume не инспектировался и не удалялся. Common
artifact root сохранён. Gate 3 по новому exact test hash всё ещё требуется;
этот RED record не утверждает test approval или GREEN.
