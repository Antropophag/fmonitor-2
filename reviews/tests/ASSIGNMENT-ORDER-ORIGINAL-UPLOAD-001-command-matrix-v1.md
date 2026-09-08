# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 command-matrix review v1

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_command_gate3`
- Test author: `/root/assignment_original_red2` (cumulative history also includes earlier separately recorded RED authors)
- Reviewed commit: `3ee1a1166387f251a957e5a493bca930504757ea`
- Reviewed test/evidence history: `ef991bd784983d175bcb18c311fd5a1fc2528856..3ee1a1166387f251a957e5a493bca930504757ea`, including command-matrix start `c954714c2722bcf63f28b6df3d783a2cbb548dca`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v52
- Public seams: `submitAssignmentOrderOriginal`, owned PDF inspector, fresh evidence reader, verification worker bootstrap, and real maintenance verification application
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the executable specification, OpenSpec package,
tests, support oracles, RED evidence, setup implementation or command
production. This append-only review record is the only authored artifact.

## Findings

### 1. The v52 lease race does not execute the approved evidence contract

Blocking. `assignment_order_original_lease_race_001_test.php` imports the fresh
evidence reader but never constructs it. At READY it asserts only the barrier
line, then checks the maintenance result vector. It does not prove the exact
finalized blob identity/time/size, absence of a command commit, unchanged
process projections, or exact maintenance request+audit while the lease is
held. After RELEASE it accepts any result containing `status":"accepted"` and
does not assert the exact result line, child exit/stderr/stdout, domain/request/
fingerprint/event/audit inventories, committed content reference, safe logs, or
byte-identical blob retention.

A faulty implementation can therefore publish wrong IDs/evidence, commit
before READY, mutate opening/composition/tasks/checklist, create extra public
facts, or delete and recreate the accepted content and still pass this test.
This contradicts the v52 exact after-finalize contract and task 4.2's explicit
zero-public-orphan requirement.

### 2. Both two-worker races have insufficient observable sensitivity

Blocking. The worker test proves two READY lines and deterministic release
order, but validates winner/loser outcomes mostly with `str_contains`. It never
compares the approved exact post-race requests/domain/fingerprint/events/audits/
blob/process inventories. The identical race consequently does not detect a
loser request row, audit, event, revision, fingerprint, orphan blob or
downstream mutation. The different race does not prove the required terminal
loser conflict/audit or exactly-once release-failure log. It also runs after a
long shared sequence of accepted corrections, so it does not reproduce the
canonical isolated initial-plus-revision-2 inventory stated by v52.

### 3. Exact worker transport requirements are not exercised

Blocking. The worker suite uses four valid socketpairs and checks a small
invalid-config sample, but has no behavioral cases for invalid FD integer/range,
stdio, closed FD, regular/FIFO/device FD, duplicate integer or aliased socket
identity. It also has no executable malformed command framing/EOF/extra-byte/
timeout, exact-key/type, UTF-8, standard-base64 canonicality, decoded oversize,
or result-parent rejection cases. `assignment_order_original_upload_remaining_contract_001_test.php`
only checks constants and source-data lists; it does not call these public
seams and is not one of the seven reproduced suites. Those assertions cannot
substitute for behavioral sensitivity.

### 4. Large command and maintenance acceptance families remain declarations

Blocking. The upload validation test executes parity and exact upload/correct
denial only. It does not execute DTO shape, order-not-found, composition/date,
MIME/magic/size/chunking, request/fingerprint collision, initial-existing,
wrong-root/composition-drift, or the full correction precedence matrix. Several
of those names exist only in `AssignmentOrderOriginalRemainingMatrix` and are
checked merely with `isset`.

The maintenance suite executes the happy path, pagination and three injected
fault points, but has no denied-principal/exact-capability or invalid UUID,
cursor, batch and cutoff cases. Its fault loop asserts only `PARTIAL`, closed
arithmetic, and eventual cleanup; the declared expected reason is unused, and
exact per-fault counts, request/audit evidence and retained/deleted blob
inventories are not checked. A swapped `LOCKED`/`STORAGE_FAILURE` mapping or
wrong per-item mutation can pass.

### 5. RED cause and setup isolation are valid, but do not cure sensitivity

All seven requested invocations reach their respective absent approved
production class after the Gate-5-approved MariaDB migrations/fixture and are
intended RED, not setup failures. Cleanup is bounded, and the independent
post-run inspection found zero `t_aoou_%` schemas, zero connections using such
schemas, and no matching verifier temp roots. This satisfies RED-cause and
isolation checks, but Gate 3 cannot approve tests that would allow the plausible
regressions above once the missing classes exist.

## Required changes

1. Strengthen the lease-race test with exact fresh-reader snapshots at READY,
   after locked maintenance, and after upload/retention; assert exact worker
   channels/result and every protected/public/process/blob inventory required
   by v52.
2. Make identical and different races assert exact isolated winner/loser
   results and complete post-race evidence, including absence of loser facts/
   orphan content, required conflict audit/log, and unchanged six-family
   process evidence.
3. Add executable worker FD, command framing/JSON/base64/bounds and parent
   result-channel cases at the public worker seam. Do not treat constant/list
   assertions as coverage.
4. Execute the currently declarative command rejection/precedence/fingerprint
   families and maintenance authorization/invalid/fault mappings, with exact
   results and mutation/evidence inventories.
5. Re-run all focused suites and independent cleanup inspection, append a new
   truthful completion record, and request a fresh independently assigned Gate
   3 review.

## Reproduced RED and isolation evidence

Each command exited `0` through the approved RED wrapper:

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
INTENDED_RED: approved FMonitorPassivePdfInspector production seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalEvidenceReaderFactory production seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalPrivateOrphanFixtureFactory seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
INTENDED_RED: approved AssignmentOrderOriginalVerificationWorkerBootstrap seam is absent.
RED_ASSERTION: expected failing behavior observed

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
INTENDED_RED: approved lease-race worker seam is absent.
RED_ASSERTION: expected failing behavior observed

$ independent information_schema SCHEMATA / PROCESSLIST query for t_aoou_%
0
0

$ find /tmp /private/tmp -maxdepth 1 -name 'aoou-{worker,lease,reader,maint}-*'
no output

$ git diff --check
PASS (no output)
```

## Exact reviewed hashes

```text
4891acd83990b9f1b93aee94b7b1326572f8259d332d1e48a9a1567ea57dad40  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
37cca851a6d5983f78f0185c6174e51f6aadb72f14347182ec4272fdfd2382bc  openspec/changes/replace-pilot-registration-with-original-upload/design.md
9102459b06f42454c5e5c1ef5fcbd9577782cb3a81f30c2d0979fcc22b4dad2b  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
b686e65052a3948c3daa837f416868ca4d4acb173e72cb1c0a48c74c083056c3  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
57b1e0e18f53baaf632bcb1fd7893a1370dd0a0c181d649772160cecf53a3bef  tests/InstallationProcess/assignment_order_original_upload_001_test.php
1238f9ed0aa0adf32fc13f9e1b6b9d0598f81711503792c54f9da4ff1adb71a4  tests/InstallationProcess/assignment_order_original_upload_validation_001_test.php
40181da226d83a7a0af0b46558810be9997e046d33773fb1b2155b77c1f5deb4  tests/InstallationProcess/assignment_order_original_pdf_parser_001_test.php
fb687d2682c8cf4ed45420b5372c63ec0b69210866b23d5d975a9ae305876076  tests/InstallationProcess/assignment_order_original_evidence_reader_001_test.php
abf201b13c2cb1db0a3c66118fb04dd878614ba91b33765de3045980586cc8f7  tests/InstallationProcess/assignment_order_original_maintenance_001_test.php
a6e4cc2ce40fa1a89935590913b2ad9876049a09bbc5dad44e477e94474c7131  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
54c370e3ba8ec8ae2fdba244e4e1274b2bd474865e31f73950f00541d6dc6efd  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
28844c71d01e37309a806bc7d6afc4bb77d0253d850070364be5061528fdfc39  tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
e862136c5d21b9a3291f38aee47f344cdb1ed2a512d47ae859bd30fa5f29ed10  tests/Support/AssignmentOrderOriginalInitialFixture.php
ffec151ff4d11b66184ffb88f8860fac9bac7a2d5166faa1a1f6a736d96f76fa  tests/Support/AssignmentOrderOriginalInitialProcessState.php
1e7b2da6f68c569c8d2fb28dd75f94ec139ece40b8143e2e7681cdb651b22829  tests/Support/AssignmentOrderOriginalMatrixInputs.php
e483729360fb99db68fc8efb64259a39a049ad6c1b2823911c80269faf5bbad6  tests/Support/AssignmentOrderOriginalPdfCorpus.php
6dfde57f38f48a748b3854cc55b1d53c8247b11988311789fd0a3d7c31e5c998  tests/Support/AssignmentOrderOriginalRemainingMatrix.php
a707b2db6307e7376ecfab2f00b593383d7523aef54f919e4a92005059e5fba4  tests/Support/assignment_order_original_worker_entry.php
d30d2b9c1191ee23c91e22a2822404c0c4b892651770498c162055af15e87b8f  docs/operations/assignment-order-original-command-matrix-red-complete-2026-09-05.md
```

Gate 3 is **CHANGES_REQUESTED**. OpenSpec task 4.2 remains unchecked and command
minimal GREEN is not authorized.
