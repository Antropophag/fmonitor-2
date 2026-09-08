# OBJECT-DETAIL-NO-DDL-RATCHET-001 v0.1 — independent technical Gate 1 review

- Review date: `2026-09-05`
- Reviewer: separately tasked agent `/root/object_detail_ratchet_review`; did
  not author the reviewed specification, scanner, baseline, importer, parent
  schema contract, or planned test
- Reviewed repository HEAD: `e1b11daba94c09dd18b3af599e791d4fa06bebb3`
- Scope: technical Gate 1 review of the supporting architecture-ratchet
  contract only; no test, importer, scanner, baseline, product behavior, or
  OpenSpec mutation
- Verdict: **APPROVED**

## Findings

The v0.1 contract is exact, isolated, and constructible at the stated public
seam. It requires the test to execute an unchanged copied
`tools/architecture/check.py --json` against a copied real baseline and
task-owned synthetic source tree. The test neither calls nor replaces private
collector/comparator functions. Its SQL is scanner input only and is never
executed, so it needs no database, source system, production data, or secret.

The current scanner already supplies the required policy and observation:

- `ddl_owner` permits DDL only below `app/InstallationProcess/` in a filename
  ending `SchemaMigration.php`, so the named canonical control path is accepted
  without a new parser or exception;
- the same historical statements at
  `rapid-pilot/import-production-object-details.php` are findings under both
  `ddl_ownership` and `rapid_pilot_boundary`;
- `compare` uses multiset subtraction against the baseline, so deleting the
  exact old entries makes each reintroduced historical line a new violation;
- the public JSON result exposes `ok` and complete error strings, while process
  exit status distinguishes rejection from a passing control;
- a fixture containing only
  `ObjectDetailSnapshotSchemaMigration::isCompleteCompatible($db,$prefix)`
  matches no DDL or mutation expression and is a valid read-only control.

The four approved-debt entries named by the specification exist exactly once
each in the current baseline: two `ddl_ownership` entries and the corresponding
two `rapid_pilot_boundary` entries with fingerprints `0869fae855bd5c76` and
`5e45e35f56e1f931`. The contract removes exactly those four entries and
requires every other baseline value and scanner byte to remain unchanged. This
is a reduction of recorded debt after actual production-source removal, not a
new ownership rule or a new exception.

The planned RED is independently sensitive to the missing ratchet. With the
current copied baseline, the two historical runtime CREATE inputs are fully
covered by existing debt and the public CLI returns success; the test requires
exit 1, `ok:false`, and an exact `ddl_ownership` finding for each historical
path/fingerprint. Thus the first assertion fails for the intended absent
baseline reduction. The canonical-owner and read-only controls are expected to
pass against the same unmodified scanner/baseline, separating intended RED
from broken fixture setup. A missing interpreter, timeout, malformed copy, or
unhealthy control is explicitly classified as setup failure.

The sequencing is also sound. Baseline reduction is forbidden while either
runtime CREATE remains and may land only with or after the separately reviewed
importer correction. Gate 3 must review the test, specification, and captured
RED before importer/baseline implementation. This preserves the parent
contract's separate importer characterization and avoids claiming that an
architecture fixture proves serial DML behavior.

## Authority boundary

This approval covers only the technical supporting ratchet for
`canonicalize-object-detail-snapshot-schema` task 3.3. It inherits the already
approved canonical table-ownership decision recorded for
`OBJECT-DETAIL-SNAPSHOT-SCHEMA-001` v0.4 and introduces no product-visible
behavior or owner-policy exception. It does not approve the pending test,
importer correction, baseline correction, importer regression, Gate 3, Gate 5,
full `make verify`, parent completion, or integration readiness.

The parent specification's reviewed bytes still contain their historical
`DRAFT` heading, but the dated owner record explicitly supersedes that status
without changing the approved behavioral hash. This review relies on that
recorded approval and does not reinterpret the heading as missing authority.

## Exact reviewed SHA-256

```text
079944d3797bdd0016d76b1ab2572c9ef48436a06d932b9c9b455c56cd1aa908  specs/OBJECT-DETAIL-NO-DDL-RATCHET-001.md
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
64c1bfbed11df12ab6078e64ea5db71a970b5696403840375db945e528de86b8  tools/architecture/check.py
55704e27d4c6f58152996444e7744bc5c6c42234ffbede92ed3ad6024cd00f1e  tools/architecture/baseline.json
5bbb0d7fa9a36e5acfb6c94db5f60366ed3b99a9130f1b587c011f1211c8d87c  docs/operations/object-detail-schema-owner-approval-2026-09-05.md
df33d0207ef03b4992fc89bb09db19db230f2011347143d15eba995323fa8f0f  docs/operations/object-detail-snapshot-schema-gate1-review-v04-2026-09-05.md
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
0de0578ade9509923a322464c52e5958c5038a3a09ec3063b8be4a6de255918e  rapid-pilot/AGENTS.md
```

## Required changes

None.
