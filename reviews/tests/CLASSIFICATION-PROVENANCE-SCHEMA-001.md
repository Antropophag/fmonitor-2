# CLASSIFICATION-PROVENANCE-SCHEMA-001 — независимый Gate 3 test review

Дата: 2026-09-02  
Reviewer: fresh separately tasked agent `/root/classification_test_review`  
Verdict: **CHANGES_REQUIRED**

## Зафиксированный review input

- owner-approved executable spec SHA-256:
  `a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed`
  (`specs/CLASSIFICATION-PROVENANCE-SCHEMA-001.md`);
- reviewed verifier SHA-256:
  `0880255cb88581b3597eb697fed7721592ee74e95dedf483765ea66cdbe80291`
  (`tests/InstallationProcess/classification_provenance_schema_001_test.php`);
- reviewed RED evidence SHA-256:
  `feb38f7c86103c61ce0246bd712766b1a7f3d579b978b626dbfaad9ae8e08c69`
  (`docs/operations/classification-provenance-schema-red-evidence.md`);
- owner decision SHA-256:
  `485a1140343e4f7922e0682ba338e87942bf0a3a38b9ac612ac92c5ed21e40c1`
  (`docs/operations/morning-owner-approval-decision-2026-09-02.md`).

Approved spec hash совпадает с owner batch/decision и с test-owned hash guard.
RED действительно достигает работающей MariaDB и успешно применяет canonical
v1–v10, поэтому основное падение на terminal v10 является behavior RED, а не
setup failure. Но verifier не трассирует несколько обязательных Gate 1
сценариев, поэтому Gate 3 пока не может разрешить GREEN.

## Gate-blocking findings

1. **Обязательный `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` contrast отсутствует.**
   Approved spec §6 требует реальный public native CLI invocation с одним ранее
   отсутствующим eligible object, injected conflicting proof после readiness,
   exact exit/stdout/stderr и доказательством ровно одного нового case при
   неизменном conflict row и отсутствии matching proof. В verifier нет ни
   соответствующей fixture/orchestration, ни обязательного transcript marker.
   Это не optional future coverage: Gate 1 прямо делает contrast частью Gate 2.

2. **Три missing/drift runtime CLI boundary не исполняются.**
   `cpsSourceOrdering()` (строки 27–30) читает PHP source и сравнивает позиции
   двух строк. Он не запускает ни один из трёх batch CLI, не создаёт missing и
   drifted schemas, не ставит независимые source connection/query sentinels и не
   проверяет exact exit 2/JSON/empty stderr, zero output/provenance DML, zero
   ready publication, schema/counter/decoy preservation или diagnostic
   redaction из §5.2. Статический `strpos('new mysqli')` также нечувствителен к
   source access через helper/factory либо к guards, которые существуют в
   тексте, но не управляют фактическим CLI path.

3. **Exact manifest oracle не проверяет indexes.** `cpsState()` собирает
   `information_schema.STATISTICS`, но `cpsAssertManifest()` (строка 26) никогда
   не сравнивает `indexes`. GREEN с missing/extra/duplicate/prefix/descending/
   invisible/non-BTREE/изменённым ordered index пройдёт clean assertion, хотя
   approved §3 называет каждую такую форму conflict. Тест также имеет только
   один conflict mutant (`category VARCHAR(41)`), поэтому не доказывает
   чувствительность preflight к index, default/extra, engine, collation и
   FK/CHECK drift.

4. **Prefix contract покрыт не полностью.** Есть 26-byte valid и non-ASCII
   rejection через недоступный DB endpoint, но нет успешного public-runner
   сценария с exact 25-byte prefix и нет syntactically invalid ASCII prefix.
   Следовательно verifier может принять off-by-one validator либо validator,
   который ошибочно допускает ASCII punctuation, вопреки §2.

5. **Bounded race fixture не соответствует approved initial state и не
   проверяет preservation.** Строка 53 удаляет target из базы, использованной
   clean/repeat тестом, но не создаёт и не snapshot-ит требуемый exact populated
   v1–v10 predecessor и ambient decoys непосредственно перед двумя public
   runners. После race проверяются только target manifest/empty rows/repeat;
   неизменность predecessor schema/data/counters и decoys из §4 остаётся
   недоказанной. Нужна отдельная race-owned disposable fixture и before/after
   snapshot, чтобы результат не зависел от предыдущих сценариев.

6. **Isolation/cleanup не fail-safe для всех создаваемых ресурсов.** Conflict DB
   удаляется только на happy path внутри строки 52, а не во внешнем `finally`;
   падение любой assertion после её создания оставляет database. DDL-denied user
   очищается корректно, main DB тоже, но race child processes не имеют bounded
   timeout/termination. Зависший runner способен повесить автономный verify и
   оставить ресурсы. Все auxiliary DB/process resources должны иметь
   unconditional cleanup и bounded wait с явной `SETUP_FAILURE` классификацией.

## Reproduced evidence

```text
php -l tests/InstallationProcess/classification_provenance_schema_001_test.php
No syntax errors detected ...

CPS_SCENARIO=source-order php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: native exact provenance precondition precedes source connection sentinel
Expected: true
Actual: false

CPS_SCENARIO=runtime-ddl php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: classification provenance runtime owner contains no DDL
Expected: false
Actual: true

php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: clean public runner reaches v11 canonical owner
Expected: exit 0 / schemaVersion 11 / appliedVersions [1..11]
Actual: exit 0 / schemaVersion 10 / appliedVersions [1..10]
```

Все три воспроизведённых падения являются ожидаемыми RED assertions текущего
production behavior, а не environment/setup failures. Однако они не заменяют
неисполненные обязательные сценарии выше.

## Required correction before fresh review

- добавить real-process native output-without-provenance contrast и exact
  `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` transcript;
- заменить source-text ordering surrogate на реальные independently sentineled
  missing/drift invocations всех трёх public CLIs с zero-mutation/redaction
  assertions;
- сравнивать полный ordered semantic index manifest и добавить representative
  independent conflict mutants для всех fingerprint dimensions;
- доказать exact 25-byte success и invalid-ASCII pre-DB-access rejection;
- изолировать race на populated v1–v10 fixture, snapshot-ить predecessors/
  counters/decoys и сделать child waits bounded;
- перенести все auxiliary DB/process cleanup в unconditional `finally` paths и
  после исправлений сохранить superseding RED evidence с новым verifier hash.

OpenSpec task `2.2` не отмечена: verdict не APPROVED. После исправления нужен
новый fresh independent Gate 3 reviewer; текущий reviewer не должен утверждать
собственные замечания.
