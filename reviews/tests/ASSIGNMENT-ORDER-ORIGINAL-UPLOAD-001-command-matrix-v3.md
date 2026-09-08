# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 command-matrix review v3

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_command_gate3_v3`
- Correction/test author: `/root/assignment_command_gate3`
- Reviewed commit: `f3bcb92467c6695a65f8e86f8f45abfcb319c40e`
- Prior reviews: `e801a835` and `18aaf2d`, both `CHANGES_REQUESTED`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v52
- Verdict: **CHANGES_REQUESTED**

Reviewer did not author the executable specification, OpenSpec artifacts,
production, tests, support oracles, RED evidence, or either prior review. This
append-only review record is the only authored artifact.

## Reproduction outcome

All eight focused suites were reproduced through the approved RED wrapper.
Every wrapper exited `0` and identified its single intended missing production
seam: verification factory (upload and validation), passive PDF inspector,
evidence-reader factory, private-orphan fixture factory, worker bootstrap,
worker protocol seam, and lease-race worker seam. The shared oracle emitted
exactly `ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK`.

Independent post-run inspection found `0` schemas and `0` connections whose
database name begins `t_aoou_`. No matching worker/protocol/lease/reader/
maintenance roots existed under `/tmp` or `/private/tmp`. `git diff --check`
passed with no output. RED cause, wrapper behavior and cleanup are valid.

## Findings

### 1. Worker framing and FD findings from v2 are closed

The worker transport now executes the exact `29,000,001`-byte frame, reordered
top-level keys, reordered upload keys, and canonical empty base64. Invalid
frames prove exact exit-70 stdout/stderr/barrier/result channels; empty decoded
bytes reach the application and require exact `REJECTED/NOT_PDF`. The protocol
suite supplies real directory and FIFO descriptors in addition to the prior
range, stdio, closed, regular, device, duplicate and aliased-socket cases, with
bounded process and fixture cleanup. These changes are executable at the public
worker seam and close findings 2–3 of review `18aaf2d`.

### 2. Fresh race isolation is present, but full exact inventories are not

Blocking. The new helper creates separate fresh database/private-root/password/
safe-log fixtures for identical and different races. It proves byte-equal READY
snapshots, deterministic A-before-B release, exact worker result lines, loser
absence before release, exact process/blob preservation, same-loser retry for
the identical case, and the different-loser release log. This closes the
physical isolation defect from `18aaf2d`.

However, its purported `complete exact initial inventory` and `complete
post-race inventories exact` assertions compare only selected counts and
identifiers. They do not compare the complete canonical domain revisions,
request result fields, fingerprint values/request linkage, event fields, or
audit fields. In particular, a worker/repository implementation can persist a
wrong actor, dates, previous revision, content identity, correction reason,
request status/result evidence, fingerprint digest, event type/time, or audit
mode/status/reason/time and still satisfy the final assertion. The identical
final inventory also does not require the whole safe-log inventory to remain
the exact empty baseline; it merely searches for the loser request ID. Thus an
extra winner-correlated log passes.

The baseline is not an independent exact oracle that cures this gap: its
initial assertion likewise samples fields rather than comparing all canonical
JSON, and its revision-2 assertion checks only revision numbers/IDs plus one
fingerprint. Byte-equal READY snapshots consequently prove preservation of an
only partially validated baseline. This does not satisfy the v52 exact evidence
contract, the prior required change for full inventories, or task 4.2's
zero-public-orphan/sensitivity requirement.

### 3. Canonical identical-race fixture still differs from v52

Blocking. V52 fixes both identical-worker clocks to
`2026-09-02T09:16:00Z`, both unused root sequences to `original-0099`, and the
winner evidence/upload time accordingly. The new isolated identical race uses
`2026-09-02T09:17:00Z` and unused roots `original-0098`/`original-0097`.
Although unused roots should not be consumed by a correct implementation, they
are deliberately fixed sensitivity inputs; the test must detect erroneous ID
consumption and reproduce the independently approved exact result/inventory,
not establish a different oracle. The one-minute clock difference also makes
the asserted winner/loser result lines disagree with the normative literal.

### 4. Prior task-4.1 surfaces remain materially covered

No regression was found in the previously reviewed application, validation,
PDF, evidence-reader, maintenance or lease-race suites. They still reach their
approved missing seams under RED; review `18aaf2d` already found their command,
maintenance and lease families materially closed. The shared-oracle test is
properly limited to literal integrity rather than claiming public behavior.
These strengths do not compensate for the blocking race-oracle gaps above.

## Required changes

1. Build independently derived complete canonical initial, revision-2, READY
   and final expected inventories for each fresh race and compare every domain,
   request, fingerprint, event, audit, process, blob and log field, including
   ordering and exact absence of loser/orphan/downstream evidence.
2. Restore the exact v52 canonical identical fixture: clock `09:16:00Z`, both
   unused root sequences `original-0099`, revision `revision-0002`, and exact
   normative result/evidence literals. Keep different-race ID sequences
   `original-0098`/`original-0097`, revisions `revision-0003`/`revision-0004`.
3. Reproduce all eight RED wrappers, shared oracle and independent DB/process/
   temp cleanup, append correction evidence, and request a fresh independent
   Gate 3 review.

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
13dedd0a6d8b06e419b3d5c61e690d637768899b0e845f11584b4b55538cee1c  tests/InstallationProcess/assignment_order_original_worker_transport_001_test.php
a120ab2f22af2dc10a36d09dd047735bb87efc2129b4b7e01d40de19de56a759  tests/InstallationProcess/assignment_order_original_worker_protocol_001_test.php
5889edc881ee0585e6d5ca987c02b7a50f9968ef200850c028fe9a72e5329c6b  tests/InstallationProcess/assignment_order_original_lease_race_001_test.php
3f006e89be2967d511cf8c0a00828d38ebc20d83240fc5d428738a3e2c2a2716  tests/InstallationProcess/assignment_order_original_upload_remaining_contract_001_test.php
e862136c5d21b9a3291f38aee47f344cdb1ed2a512d47ae859bd30fa5f29ed10  tests/Support/AssignmentOrderOriginalInitialFixture.php
ffec151ff4d11b66184ffb88f8860fac9bac7a2d5166faa1a1f6a736d96f76fa  tests/Support/AssignmentOrderOriginalInitialProcessState.php
1e7b2da6f68c569c8d2fb28dd75f94ec139ece40b8143e2e7681cdb651b22829  tests/Support/AssignmentOrderOriginalMatrixInputs.php
e483729360fb99db68fc8efb64259a39a049ad6c1b2823911c80269faf5bbad6  tests/Support/AssignmentOrderOriginalPdfCorpus.php
3fe18be313bf66286ea9f7669bfac2828925754665fe1437637a799f78bf82c3  tests/Support/AssignmentOrderOriginalRemainingMatrix.php
8e1777da035ffa63e9b09d4227cbfbb438bcc5d59b1ff2291f123a6a3094de12  tests/Support/assignment_order_original_worker_entry.php
257f7399e5dc35c8f568c5a865e07722f45e85b65ccc6410fe34652e3821dc25  tests/Support/assignment_order_original_isolated_races.php
9494f180eeff537422094f401ca6074d9734b563592e941234d30cfeaa3b62f4  docs/operations/assignment-order-original-command-matrix-red-correction-v3-2026-09-05.md
```

Gate 3 is **CHANGES_REQUESTED**. OpenSpec task 4.2 must remain unchecked and
command minimal GREEN is not authorized.
