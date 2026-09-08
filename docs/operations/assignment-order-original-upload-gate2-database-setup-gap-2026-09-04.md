# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — Gate 2 database setup constructibility gap

Date: `2026-09-04`

RED author: separately tasked agent `/root/assignment_original_red2`

Reviewed base commit: `ef991bd784983d175bcb18c311fd5a1fc2528856`

Outcome: **GATE 1 AMENDMENT REQUIRED FOR REMAINING TASK 2.2**

## Простыми словами

Оставшуюся RED-матрицу требуется выполнить на настоящей MariaDB и затем
проверить через утверждённый независимый reader. Но утверждённый contract не
даёт тесту ни одной разрешённой команды, которая создаёт и наполняет нужную
изолированную схему. Угадать таблицы или написать их вручную означало бы
проверять предполагаемую реализацию, а не публичный contract.

## Exact contradiction

Executable specification v6 одновременно требует:

1. section 13 — fresh isolated DB prefix для MariaDB/CAS/failure/worker matrix;
2. section 16 — `AssignmentOrderOriginalEvidenceReaderFactory` знает exact
   canonical original tables, но MUST NOT query `information_schema`, infer
   schema, issue DDL/DML, use command repository objects или test callbacks;
3. task 2.2 — real evidence-reader snapshots, five-FD two-worker barrier,
   maintenance candidate/lock/replay и commit/response-loss/content-lease
   faults до независимого Gate 3;
4. OpenSpec task 3.1 — additive canonical schema migration создаётся только
   после tasks 2.2 и 2.3;
5. design decision 7 — literal migration version назначается только на
   актуальном frontier.

При этом v6 не определяет:

- canonical table names и column/key grammar, достаточные для test-owned DDL;
- public migration/bootstrap/fixture factory для isolated prefix;
- public seed DTO/seam для exact order, composition, process, checklist,
  request, lineage, event, audit и decoy facts;
- утверждённый способ применить ещё не существующую task-3.1 migration до
  Gate 2.

Поэтому после проверки отсутствующего production factory RED author не может
построить independently specified runnable real-MariaDB branch: reader не имеет
права создавать facts, а private SQL теста был бы implementation-coupled вторым
setup contract и не имел бы независимо утверждённых expected table/column
values.

## Exact normative boundary conclusion

В current v6 **нет** literal предложения, которое вообще запрещает RED-verifier
исполнять setup DDL/DML. Exact запрет section 16 уже и относится к
`AssignmentOrderOriginalEvidenceReaderFactory`/reader: factory `MUST NOT query
information_schema, infer schema, issue DDL/DML, use command repository objects,
or accept test callbacks/selectors`; evidence inventory также observation-only
и `MUST NOT feed maintenance candidate enumeration or mutation`.

Следовательно blocker — не выведенный запрет на любой test SQL. Blocker —
отсутствие нормативных входов, по которым такой SQL можно написать независимо:
v6 не называет original table/column/key grammar, не называет будущий public
`AssignmentOrderOriginalSchemaMigration`, не определяет его constructor/method,
не разрешает применить его до task 3.1 и не задаёт fixture DML contract. Поиск
по executable spec и всем четырём current OpenSpec artifacts не находит
`AssignmentOrderOriginalSchemaMigration` или другого named setup seam.

Если Gate 1 добавит named public schema migration плюс independently specified
fixture DML/typed seed values и bounded cleanup, verifier сможет использовать
их; отдельный fixture factory тогда не обязателен. В текущем approved batch
вызвать такой migration или написать его SQL можно только по догадке, что
нарушает Gate 2 independent-expected-value requirement, даже хотя test setup SQL
сам по себе не запрещён.

Reproduced inventory check:

```text
$ rg -n "AssignmentOrderOriginalSchemaMigration|DatabaseSetup|FixtureFactory|seed DTO|fixture DML" specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md openspec/changes/replace-pilot-registration-with-original-upload/proposal.md openspec/changes/replace-pilot-registration-with-original-upload/design.md openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
NO_NAMED_DATABASE_SETUP_SEAM
```

## Почему существующие generic runners не являются решением

Repository содержит общие DB reset/migration helpers для уже существующих
slices. Ни один из них не назван v6 dependency и ни один не принимает typed
Assignment Order Original fixture/seed contract. Они не знают будущий literal
migration frontier, original lineage tables, terminal request/fingerprint
rows, maintenance audit rows или checklist projection для этого change.
Использование такого runner по догадке либо ничего не создаст под fresh prefix,
либо заставит RED-тест кодировать private schema, которую должна определить
task 3.1.

## Smallest required amendment

Gate 1 должен определить один exact verification-only database setup seam либо
иной исчерпывающий публичный setup contract. Минимально он должен:

- принять serializable DB connection/password-file/prefix configuration с теми
  же ownership/secret правилами;
- создать/recreate только verifier-owned isolated prefix без runtime DDL в
  business application;
- seed exact active actor/capabilities, case/order/composition, unchanged
  process/checklist/decoy facts и при необходимости accepted lineage/orphan
  prerequisites через typed DTO;
- вернуть только setup completion, не application Result и не evidence;
- иметь deterministic cleanup/drop seam или exact bounded cleanup protocol;
- быть доступным RED verifier до reader/worker construction и иметь exact
  failure/close semantics.

Альтернатива — нормативно определить test-owned canonical DDL/seed grammar и
порядок его применения. Простого упоминания будущей migration недостаточно:
Gate 2 должен оставаться исполняемым до Gate 4 и независимым от будущего
implementation diff.

До amendment task 2.2 остаётся unchecked. Part 1 и part 2 сохраняются как
append-only частичный RED, но не разрешают Gate 3 для полной matrix. Production,
specification, OpenSpec artifacts, прежние tests и reviews этим record не
изменены.

## Exact inspected hashes

```text
912b156b8dea9458506d44500c26036a02c5802e9e8c98d2c02bbada90bd93c2  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
4794ca3bddee88cccd51fcb53cce03ef75e69cffa6d967dd7b5c7fde9233067c  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
28844c71d01e37309a806bc7d6afc4bb77d0253d850070364be5061528fdfc39  tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
6dfde57f38f48a748b3854cc55b1d53c8247b11988311789fd0a3d7c31e5c998  tests/Support/AssignmentOrderOriginalRemainingMatrix.php
dd74859a7b0e899e0b1c7a2aa884aa237d06937ce9d87ebfaf469304dca569b2  docs/operations/assignment-order-original-upload-matrix-red-part2-2026-09-04.md
```
