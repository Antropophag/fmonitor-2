# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 command-matrix review v2

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_command_gate3_v2`
- Correction/test author: `/root/assignment_command_gate3` after its v1 review
- RED author: `/root/assignment_original_red2`
- Reviewed commit: `c30484789efcac14833a29725f47a85fa7e42c05`
- Prior review: commit `e801a835`, verdict `CHANGES_REQUESTED`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v52
- Verdict: **CHANGES_REQUESTED**

Reviewer не писал executable specification, OpenSpec artifacts, production,
tests, support or correction evidence и не является автором v1 review. Этот
append-only review record — единственный изменённый artifact.

## Reproduction outcome

Все восемь focused suite воспроизведены через approved RED wrapper. Каждый
wrapper завершился exit `0` и классифицировал единственную фактическую причину
как отсутствие утверждённого production seam:

```text
assignment_order_original_upload_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed

assignment_order_original_upload_validation_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed

assignment_order_original_pdf_parser_001_test.php
INTENDED_RED: approved FMonitorPassivePdfInspector production seam is absent.
RED_ASSERTION: expected failing behavior observed

assignment_order_original_evidence_reader_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalEvidenceReaderFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed

assignment_order_original_maintenance_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalPrivateOrphanFixtureFactory seam is absent.
RED_ASSERTION: expected failing behavior observed

assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed

assignment_order_original_worker_protocol_001_test.php
INTENDED_RED: approved worker protocol seam is absent.
RED_ASSERTION: expected failing behavior observed

assignment_order_original_lease_race_001_test.php
INTENDED_RED: approved lease-race worker seam is absent.
RED_ASSERTION: expected failing behavior observed
```

Отдельный shared-oracle executable завершился точной строкой
`ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK`. После воспроизведения
independent MariaDB inventory показал `0` схем и `0` соединений с
`t_aoou_%`; independent `/tmp` и `/private/tmp` inventory не нашёл
`aoou-worker-*`, `aoou-worker-protocol-*`, `aoou-lease-*`, `aoou-reader-*` или
`aoou-maint-*`. `git diff --check` завершился без output.

## Findings against v1 review

### 1. Lease-race evidence contract is closed

Finding 1 из `e801a835` закрыт. Исправленный lease test создаёт fresh evidence
reader, при READY доказывает exact finalized blob и отсутствие command
root/request/event, неизменный six-family process snapshot; затем проверяет
exact LOCKED maintenance result, exact accepted worker channels, полные
domain/request/fingerprint/event/audit/blob/log inventories, обе maintenance
request/audit пары и mutation-free replay. Blob остаётся byte-identical и stage/
orphan отсутствуют.

### 2. Race assertions are stronger, but the required isolated oracle is not closed

Blocking. Finding 2 закрыт только частично. Исправленный worker test теперь
снимает fresh-reader snapshots после обоих READY, проверяет exact winner/loser
Result lines и request/audit/fingerprint/event/blob/process/log deltas. Это
действительно обнаруживает loser request/orphan/downstream mutations.

Однако обе гонки всё ещё выполняются в одной БД после initial, accepted
correction и длинной fault-recovery цепочки (`revision-0002` …
`revision-0006`), затем используют `revision-0070`/`revision-0071`. Это не
канонический изолированный v52 oracle initial + revision-2 и не выполняет
required change 2 из v1 review: «exact isolated ... inventory». Ошибка,
зависящая от пустой/короткой lineage, revision number 2 или отсутствия прежних
request/audit/log rows, может пройти только delta-проверки на загрязнённой
shared sequence. Identical и different race должны иметь независимый fresh
DB/root либо эквивалентный доказанный reset к точному approved baseline и
проверять полную exact inventory, а не только filtered delta.

### 3. Worker transport still omits normative executable cases

Blocking. Finding 3 закрыт частично: новый public worker bootstrap test
исполняет negative/range/stdio/closed/duplicate/alias FD cases и regular/device
type rejection; worker transport исполняет EOF-before-LF, bytes after LF,
malformed JSON, invalid UTF-8, missing/extra key, one wrong type, several
noncanonical base64 values, decoded `20,971,521`, exact result failure bytes и
durable replay after result publisher failure.

Остаются прямо нормативные v52 cases без executable sensitivity:

- отсутствует command frame размером `29,000,001` bytes, поэтому bounded
  overlong rejection и отсутствие secret/DB/barrier/result access не доказаны;
- отсутствует перестановка top-level или upload keys, поэтому literal key-order
  contract не отличён от merely complete key set;
- empty decoded base64 доказан application-тестом через direct stream, но не
  transport-valid worker command, поэтому worker может ошибочно отвергнуть его
  как framing/base64 failure;
- FD type contract прямо перечисляет FIFO и directory, но новый protocol test
  исполняет только regular file и device; реализация, ошибочно принимающая FIFO
  или directory, не обнаруживается.

Следовательно required change 3 из v1 review и точная task 4.1 worker
framing/FD matrix ещё не закрыты.

### 4. Command and maintenance families are materially closed

Finding 4 закрыт в части application/maintenance публичных seams. Upload
validation теперь вызывает seam для exact authorization, DTO/order/composition,
date/MIME/magic/empty/size behavior. Worker suite вызывает wrong-root,
composition-drift, second-initial, correction precedence, unknown outcome,
cleanup/release/result faults. Maintenance вызывает real factory для pagination,
invalid UUID/cursor/batch/cutoff, principal/capability denial, exact fault
reason/counts, request+audit/blob evidence, replay и cleanup convergence.
Former constant-only result/fault families удалены; remaining-contract test
оставлен только как честный shared-literal oracle.

Эти улучшения не компенсируют blocking isolation и transport gaps выше.

## Required changes

1. Выполнить identical и different races на independently fresh exact
   initial-plus-revision-2 baselines и сравнить полные exact inventories для
   каждой гонки, включая loser absence/conflict, orphan/blob и six-family
   process evidence.
2. Добавить public worker executable cases для overlong `29,000,001` frame,
   reordered exact keys и empty decoded base64, с exact channel/access
   assertions.
3. Добавить FIFO и directory FD type cases (либо меньший executable type
   discriminator, который доказуемо мутирует каждый перечисленный category и
   поймает их принятие), сохранив bounded cleanup.
4. Воспроизвести восемь RED suites, shared oracle, DB/process/temp cleanup и
   запросить новый fresh independent Gate 3 review.

## Exact reviewed hashes

```text
4891acd83990b9f1b93aee94b7b1326572f8259d332d1e48a9a1567ea57dad40  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
37cca851a6d5983f78f0185c6174e51f6aadb72f14347182ec4272fdfd2382bc  openspec/changes/replace-pilot-registration-with-original-upload/design.md
9102459b06f42454c5e5c1ef5fcbd9577782cb3a81f30c2d0979fcc22b4dad2b  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b686e65052a3948c3daa837f416868ca4d4acb173e72cb1c0a48c74c083056c3  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
57b1e0e18f53baaf632bcb1fd7893a1370dd0a0c181d649772160cecf53a3bef  tests/InstallationProcess/assignment_order_original_upload_001_test.php
e17299de03bd667fcd263de2af8398800450a30eb314ed9c2cb861e38cd433a1  tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
40181da226d83a7a0af0b46558810be9997e046d33773fb1b2155b77c1f5deb4  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
fb687d2682c8cf4ed45420b5372c63ec0b69210866b23d5d975a9ae305876076  tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php
aa691c26e4c46f545b29e8c435d6cf9607d7bcc6782e75281f554ca2a4617ba5  tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
0d9e3a93f97666da47c3c9e88cbc2146c0e9d977362c06d3abeb61fbb0d8cbd0  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
cb8e84315339b31389720d4819e98251933728b76283359498d99ab8aa28a547  tests/InstallationProcess/assignment_order_original_worker_protocol_001_test.php
5889edc881ee0585e6d5ca987c02b7a50f9968ef200850c028fe9a72e5329c6b  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
3f006e89be2967d511cf8c0a00828d38ebc20d83240fc5d428738a3e2c2a2716  tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
e862136c5d21b9a3291f38aee47f344cdb1ed2a512d47ae859bd30fa5f29ed10  tests/Support/AssignmentOrderOriginalInitialFixture.php
ffec151ff4d11b66184ffb88f8860fac9bac7a2d5166faa1a1f6a736d96f76fa  tests/Support/AssignmentOrderOriginalInitialProcessState.php
1e7b2da6f68c569c8d2fb28dd75f94ec139ece40b8143e2e7681cdb651b22829  tests/Support/AssignmentOrderOriginalMatrixInputs.php
e483729360fb99db68fc8efb64259a39a049ad6c1b2823911c80269faf5bbad6  tests/Support/AssignmentOrderOriginalPdfCorpus.php
3fe18be313bf66286ea9f7669bfac2828925754665fe1437637a799f78bf82c3  tests/Support/AssignmentOrderOriginalRemainingMatrix.php
8e1777da035ffa63e9b09d4227cbfbb438bcc5d59b1ff2291f123a6a3094de12  tests/Support/assignment_order_original_worker_entry.php
2804de531b21018e7d44cca72e8e45eebacbe7fb7110fc984bf2d3158ffaee89  docs/operations/assignment-order-original-command-matrix-red-correction-v2-2026-09-05.md
```

Gate 3 is **CHANGES_REQUESTED**. OpenSpec task 4.2 должен остаться unchecked;
command minimal GREEN не авторизован.
