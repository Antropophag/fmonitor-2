# Object-detail import characterization — corrected Gate 2 RED v2

Дата: 2026-09-05. Автор теста исправил findings из
`reviews/tests/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001-v1.md`. Этот append-only
record не изменяет и не заменяет первоначальный RED record.

## Exact reviewed inputs

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
eb0988403082e823bb53fb5f6d5d315ebda3376ed3878835d1de3c91664c8c2c  tests/Verification/characterize_object_detail_import_001_test.php
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
```

Новых Support helpers нет. Test hash охватывает весь test-side verifier.
MariaDB image ID не зашит в portable test: каждый run один раз разрешает
ожидаемый локальный tag `mariadb:11.4.7-noble` в immutable `sha256:` ID,
создаёт container только по этому ID и сверяет тот же ID через inspect.

## Corrected RED command

```text
$ tools/verification/run.sh red tests/Verification/characterize_object_detail_import_001_test.php
REGRESSION_FAILURE: real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal
RED_ASSERTION: expected failing behavior observed in tests/Verification/characterize_object_detail_import_001_test.php
[wrapper exit 0]
```

Один meta-test передал два разных 12-hex token двум отдельным worker runs.
Оба создали fresh private container, precreated exact public v12 family,
проверили точный table-level grant allowlist и завершились одинаковым безопасным
RED category на настоящем importer CLI. Сравнение двух normalized результатов
выполнено до публикации категории.

Исправленный oracle также:

- использует `--tmpfs /var/lib/mysql`, требует empty `.Mounts` и exact
  `/var/lib/mysql` entry в `.HostConfig.Tmpfs`;
- применяет разные root/source/target passwords и никогда не передаёт root
  credential importer;
- сравнивает exact grants: только global `USAGE`, target table SELECT/INSERT и
  source four-table SELECT; broad/schema/global role/grant-option формы rejected;
- сохраняет общий repository-owned artifact root, владеет decoy через exclusive
  create плюс inode/device latch и удаляет только exact decoy после двух runs;
- снимает full `SHOW CREATE TABLE`, engine/collation/options и полные rows всех
  пяти target fixtures, включая absent members, вокруг каждого CLI call;
- проверяет complete exact clean rows, first capture во всех случаях кроме
  serial replay, atomic conflict и source rejections;
- закрывает listener и все DB connections через attempt-all cleanup; каждый
  process timeout/overflow выполняет TERM/KILL/drain/close/bounded reap, причём
  обе ветви имеют выполняемые sensitivity probes.

## Residual cleanup proof

Перед corrected command и после него были сохранены и отсортированы только
Docker volume IDs; `diff -u` вернул exit `0` без вывода. Старые anonymous
volumes не инспектировались и не удалялись этим автором.

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
[exit 0: common root survived]
```

Syntax and patch hygiene:

```text
$ php -l tests/Verification/characterize_object_detail_import_001_test.php
No syntax errors detected in tests/Verification/characterize_object_detail_import_001_test.php
$ git diff --check
[exit 0, no output]
```

Это всё ещё RED, не GREEN. Production importer, schema owner, runner, specs и
OpenSpec artifacts не изменялись. Нужен новый fresh Gate 3 review exact v2 hash
до любой production correction.
