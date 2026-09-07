# Original application reference — scoped native GREEN

Spec ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001 v0.1; Gate1 APPROVED,
Gate3 initial+v2 APPROVED. Test/RED source f60dcea, production source
f68fa7a333e66b74c4497320eb2ca1e21f0cd968. No tests changed during GREEN.

Public metadata lookup owns only an idle read-only snapshot. WeakMap issuance
binds exact reference object to this reader, database/native connection/charset
and private immutable-source seal. Metadata whitelist has14fields and no private
storage identity. No actor grant, filesystem bytes or application fact is inferred.

Borrowed guard locks case first. Existing owning-module submission/StoredReader/
lineage/audit/event helpers gained default-false explicit locking options; old
callers retain snapshot behavior. No SQL text rewriting/interception. Guard uses
current reads throughout validation even when caller has an old RR snapshot.
Seal retains root immutable fields, originally referenced revision, registry,
selection header and ordered members; root current pointer is compared separately.
Thus a valid correction returns changed while original immutable-source drift
fails closed. Guard never begins/commits/rolls back, changes wait policy or closes.

## Verification

Synthetic environment from RED record; `php tests/AssignmentOrderComposition/original_application_reference_001_test.php`:
14/14 PASS,14SETUP_OK/14CLEANUP_OK, exit0. At prefixes0and25, staleRR ordinary reads
stay old after other-connection original correction while guard sees changed.
Real production correction workers both observed waiting on canonical case row,
then accepted revision2 after caller rollback. Sentinel assertions prove no hidden
caller commit/rollback. Corrupt data unchanged, private root restoration exact.
Raw external original-reference-20260907/green-first.log.

Nine regressions all exit0, raw per-test logs + regression-0..3.json in same root:
- assignment_order_original_data_mariadb_reads_001;
- assignment_order_original_data_lineage_001;
- assignment_order_registered_composition_reader_001;
- selected_original_binding_001;
- selected_original_lifecycle_001;
- original_upload_http_flow_001;
- original_upload_http_prefill_001;
- selection_native_accepted_001;
- selection_native_concurrency_001.

`make architecture-check`: PASS7rules; `make lint`: PASS exit0;
`git diff --check`: PASS exit0. No boundary baseline or new>=150line
production file. Native test uses canonical runner's existing db discovery.

Independent Gate5 APPROVED:
reviews/code/ASSIGNMENT-ORDER-ORIGINAL-APPLICATION-REFERENCE-001.md.
External green-manifest.json pins source hashes,14cases/2workers/9regressions.
OpenSpec validation PASS, canonical db list contains new test. This is a bounded dependency, not completion of parent
application/original-history HTTP/opening. No full VERIFY_OK or launch approval.
Protected E2E remains SHA2568f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b.
No remote mutations, preview deployment, real Bitrix or production imports.
