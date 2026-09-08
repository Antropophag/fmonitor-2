# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v32 maintenance evidence — independent Gate 1 review

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/assignment_maintenance_evidence_gate1`
- Reviewed commit: `7aecb20260447f471bcd2f8bc684478e49ec1868`
- Triggering gap: `396dc67` (recorded as
  `docs/operations/assignment-order-original-maintenance-evidence-gate1-gap-2026-09-05.md`)
- Scope: maintenance request/audit evidence amendment and coherence of the
  current executable specification/OpenSpec package; no tests or production
  implementation reviewed
- Verdict: **APPROVED**

The reviewer authored none of the reviewed specification, OpenSpec artifacts,
tests or production implementation. This append-only review is the only
authored artifact.

## Review result

The amendment closes the recorded maintenance observability gap through two
exact additions to the existing public read-only evidence interface:

```php
public function maintenanceRequestsCanonicalJson(): string;
public function maintenanceAuditsCanonicalJson(): string;
```

Both methods inherit the already approved fresh-connection factory boundary:
they know only the canonical tables introduced by this change, issue no
DDL/DML, do not use `information_schema`, command repositories, private test SQL
or callbacks, and cannot feed candidate enumeration or mutation. They therefore
make real MariaDB maintenance acceptance observable without introducing a
second state-changing seam.

The returned shapes are closed and versioned. Recursive binary key sorting
produces the exact request key order
`attemptedAt,deleted,failed,nextCursor,reasonCode,requestId,retained,retryable,scanned,status,systemPrincipalId`
and audit key order
`attemptedAt,auditId,deleted,failed,reasonCode,requestId,retained,retryable,scanned,status,systemPrincipalId`;
top-level order is `items,schema`. Nulls remain explicit, integers and UTC
strings retain the common canonical grammar, request rows are ordered by binary
request ID, and audit rows by numeric/binary audit ID as declared. No optional
or additional fields remain implementation choices.

The two inventories carry the common request ID plus all terminal status,
reason, retryability, counts, principal and attempt-time evidence. The request
inventory additionally exposes `nextCursor`, while the audit inventory exposes
its immutable audit identity, matching their canonical table contracts. A
successful terminal maintenance attempt must therefore become visible in both
inventories or neither; an audit-commit failure is observable as absence from
both. Repeating the authorized request changes neither inventory, so a test can
compare exact before/after canonical strings and detect an inserted request,
audit, changed count, changed order, changed null, or duplicate audit.

Construction and every method remain total under the existing fixed
`AssignmentOrderOriginalEvidenceUnavailable` contract: a failure yields no
partial JSON and no path, SQL, password, exception or other secret diagnostic.
The existing idempotent `close()` contract attempts every live descriptor and
connection exactly once, caches the first success/failure, and performs no I/O
on later calls. Adding the two database reads does not weaken that ownership or
resource lifecycle.

The executable specification, OpenSpec design, delta scenario and task ledger
agree on the same maintenance request/audit evidence surface. The amendment
changes no actor, user workflow, authorization, HTTP contract, storage policy,
schema, original lineage, composition/opening behavior, runtime DDL or blocked
legacy E2E behavior.

Gate 1 for the v32 maintenance evidence amendment is therefore **APPROVED**.
This review does not approve a RED test or production implementation.

## Verification

```text
$ git rev-parse 7aecb20260447f471bcd2f8bc684478e49ec1868
7aecb20260447f471bcd2f8bc684478e49ec1868

$ git diff 7aecb20^ 7aecb20 --check
PASS (no output)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid
```

## Exact reviewed hashes

```text
cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8  AGENTS.md
9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf  PRODUCT.md
3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09  CONTEXT.md
25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb  docs/fmonitor-2-pilot-spec.md
10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d  docs/fmonitor-2-pilot-data-model.md
a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504  docs/development-process.md
6196700b1812d1487eed54a1549058d241111109019f4bf2b31f0c76bd7530c8  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
0f57d5a958ae5cc6cd1484325810d733a1ab1a162d2f257b3adcc87769990a3a  openspec/changes/replace-pilot-registration-with-original-upload/.openspec.yaml
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
5bb0bc0447ebeb700b10a21348fbd031abd870bd7f3dc4bf833b9cf80c783b52  openspec/changes/replace-pilot-registration-with-original-upload/design.md
c8ccc0e1f59cb0bfc266399e3bd22f43e027934dde433d828d509d1ddec1422f  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
e124168c5f85c0a78b5d17ae3c985a2333905e38b207e8dce13d2679609906ae  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d062162640c64669146e43df6cbd9f74c3c9f0a5bbc428a55e10745ea902b3bc  docs/operations/assignment-order-original-maintenance-evidence-gate1-gap-2026-09-05.md
```

This review record omits its own circular hash.
