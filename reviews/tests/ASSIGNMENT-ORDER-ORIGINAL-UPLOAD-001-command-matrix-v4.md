# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 command-matrix review v4

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_command_gate3_v4`
- Correction/test author: `/root/assignment_command_gate3`
- Reviewed commit: `842edc13144e5534389bedd9fc7009196f994555`
- Prior reviews: `e801a835`, `18aaf2d`, and `47bfdfe`, all `CHANGES_REQUESTED`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v52
- Verdict: **APPROVED**

Reviewer did not author the executable specification, OpenSpec artifacts,
production, tests, support oracles, RED/correction evidence, or prior reviews.
This append-only review record is the only authored artifact.

## Reproduction outcome

All eight focused suites were independently reproduced through the approved
RED wrapper. Every wrapper exited `0` and classified its one intended missing
production seam: verification factory (upload and validation), passive PDF
inspector, evidence-reader factory, private-orphan fixture factory, worker
bootstrap, worker protocol seam, and lease-race worker seam. The shared oracle
emitted exactly `ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_SHARED_ORACLES_OK`, PHP syntax
validation passed, and `git diff --check` produced no output.

Independent post-run inspection found `0` schemas and `0` connections whose
database name begins `t_aoou_`. No matching worker/protocol/lease/reader/
maintenance roots remained under `/tmp` or `/private/tmp`, and no related
worker process remained. RED cause, bounded cleanup, and isolation are valid.

## Findings

### 1. Full fresh-race inventories close the v3 blocker

The corrected helper compares the complete eight-part evidence snapshot as
literal canonical JSON for both independent fresh databases: domain,
requests, fingerprints, events, audits, the six-family process snapshot,
private blobs, and safe logs. The initial baseline is fully enumerated for both
races; the different-race revision-2 prerequisite is likewise fully
enumerated. Assertions cover every revision field, every result field,
fingerprint value and request linkage, complete event/audit fields, canonical
ordering, and exact absence of extra facts.

At both READY barriers the whole snapshot must remain byte-equal to its exact
baseline. Releasing A first must expose the exact complete winner inventory
while B remains wholly absent. Final identical-race evidence contains only
initial plus A and requires an exactly empty log; retrying B returns the exact
replay line and preserves all eight inventories byte-for-byte. Final
different-race evidence contains the exact accepted A plus terminal stale B
request/audit, no B domain/fingerprint/event/blob fact, and exactly one B-safe
`commit_conflict` release-failure log. This closes finding 2 of `47bfdfe` and
the remaining race-sensitivity portion of the earlier reviews.

### 2. Canonical v52 identical fixture is restored exactly

Both identical workers now use clock `2026-09-02T09:16:00Z`, unused root
sequence `original-0099`, and revision sequence `revision-0002`. The exact A
accepted and B replay result lines carry the normative revision-2 evidence and
time. A is released and observed before B. This closes finding 3 of
`47bfdfe` without weakening isolation or any prior assertion.

### 3. Earlier command-matrix findings remain closed

No regression was found in the application, validation, PDF, evidence-reader,
maintenance, worker transport/protocol, or lease-race surfaces. The executable
matrix still covers the `29,000,001`-byte overlong frame, reordered top-level
and upload keys, transport-valid empty base64 reaching `REJECTED/NOT_PDF`, and
directory/FIFO descriptor rejection with exact channels and bounded cleanup.
The command rejection/precedence/fingerprint families, maintenance outcome
matrix, lease READY/final evidence, and worker fault mappings continue to reach
their approved public seams under RED. Thus the findings first recorded in
`e801a835` and `18aaf2d` remain closed.

## Gate decision

The command-matrix tests are traceable to v52, exercise the approved public
seams, have independently derived exact expectations, distinguish plausible
race/transport/persistence regressions, remain deterministic and isolated, and
demonstrably fail only because the approved production seams do not yet exist.
Gate 3 is **APPROVED**. OpenSpec task 4.2 may be marked complete and minimal
command GREEN is authorized.

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
8a88d3f93da76186296610c8d54567d1b00d3a81ccb311f25b16ebd584318fdd  tests/Support/assignment_order_original_isolated_races.php
d8f9afc833b7cae9c44dda43aee2fc8677d436b69486c3093a2483eb6d296a71  docs/operations/assignment-order-original-command-matrix-red-correction-v4-2026-09-05.md
```
