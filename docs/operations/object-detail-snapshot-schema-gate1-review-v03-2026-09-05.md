# OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.3 — independent Gate 1 review

- Review date: `2026-09-05`
- Reviewer role: fresh independent Gate 1 technical reviewer
- Reviewed commit: `b7df9c70f66306e2201675852563c683f31c02d6`
- Scope: executable schema contract and four reconciled OpenSpec artifacts only;
  no RED, production/test/spec/planning changes, importer execution or external data
- Verdict: **CHANGES_REQUESTED**

## Review result

The v0.3 package closes the two prior readiness findings in substance. The
database/prefix-scoped named lock makes the exact creator/repeater and timeout
outcomes deterministic. Holding it through whole-family preflight, both
independently committed CREATEs and final verification prevents two callers
from both claiming creation. Release failure and connection loss fail as
`DatabaseUnavailable`; the durable exact partial or complete family is then
recoverable by a fresh ordinary invocation.

The two failure proofs are constructible without a production runtime selector.
A principal with metadata access and table-scoped CREATE authority only for the
details table permits the first real CREATE and deterministically denies the
second. An administrative observer can prove exact details, absent quarantine
and unchanged decoys before granting the missing authority for retry. In the
separate public verification composition, closing the passed owned connection
on `QUARANTINE_CREATED` occurs after both real CREATEs and before final
inspection. Neither final inspection nor lock release can turn that call into
success; a fresh administrative connection can prove the complete durable
family, and an ordinary retry must report an exact no-op. The bounded two-worker
READY/RELEASE construction is also sufficient to distinguish lock
serialization, timeout and retry from scheduler coincidence.

The manifest, normalized metadata fields, database-default utf8mb4 collation,
prefix isolation, sorted result lists and CLI mappings remain coherent with the
inspected public runner. The OpenSpec proposal, design, delta specification and
tasks now carry the same concurrency/final-verification boundary. Pending
importer characterization continues to block importer-DML preservation claims
and changes, but does not block this independently knowable data-free schema
contract.

## Blocking finding — normative public PHP surface is not constructible

Section 3 labels the following as the exact public API candidate while using a
concrete final class with method declarations ending in semicolons:

```php
final class ObjectDetailSnapshotSchemaMigration
{
    public static function apply(\mysqli $connection, string $tablePrefix = ''): array;
    public static function isCompleteCompatible(\mysqli $connection, string $tablePrefix = ''): bool;
}
```

That is not valid PHP: non-abstract methods of a concrete class require bodies,
and a final class cannot repair this by making the methods abstract. Gate 1
cannot leave an exact API block executable only by interpreting it as informal
signature notation. Section 8 similarly names the public verification method,
observer and enum, but does not give an exact constructible declaration for the
observer type used by that method.

Revise the normative surface coherently to valid exact PHP declarations—for
example, give the two concrete migration methods bodies in the class contract
and declare the observer as an exact interface with its typed `observe` method,
or explicitly present signatures in non-PHP notation while separately fixing
the exact PHP declarations. Preserve the public verification composition, inert
production observer, enum literals and no-runtime-selector boundary. Any
normative correction changes the reviewed hash and requires a fresh independent
Gate 1 review.

## Authority boundary

This is a technical review only. The synthetic/native data decision and the
dependency review are inherited evidence for their stated scopes. Neither they
nor this record constitute the explicit owner approval required by task 1.2 and
the development process. No owner approval is inferred, no migration version is
reserved or registered, and RED remains unauthorized.

## Exact reviewed SHA-256 hashes

### Governing documents

- `AGENTS.md`: `cee5f61943c18cff18d730f0afdd69ff187431ad4295594742eaa91b5bda7dd8`
- `PRODUCT.md`: `9e8665c4eca504c0e27f460b5ad34e38852c9720e139772b21675b6a40fc08bf`
- `CONTEXT.md`: `3301224017ecdb616644d7efcf79ea1e5cc0ab06a99770ab89c9e25be007bb09`
- `docs/development-process.md`: `a0e448c888ab7a25041d615fd7f2bab855047a5247d8ae42900e8c3d9d1c7504`
- `docs/fmonitor-2-pilot-spec.md`: `25b0ab7a4ba6a5bad48eedd940e03e65195fa6871c0f6ab02cb737bdd6a8defb`
- `docs/fmonitor-2-pilot-data-model.md`: `10a0e0e7a701dad6a91be6a4c8dc992eb1548923b66b0716e902d408ccf9273d`

### Candidate and planning package

- `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.3: `b97c55a843557d71c60f0c6a2d2f246a848b6e8e78718744802b642be917e257`
- OpenSpec `proposal.md`: `765f5c1980d2be241ee1732b93f260196b35f5be24ad6a21543197ed6ad303f3`
- OpenSpec `design.md`: `71e4dd6bd50270c71eff2459bb45019e67b9cd206944bc5c4d2648c6f838e799`
- OpenSpec `tasks.md`: `0c9321443e825bc888eb94bf316160e679f6e4f14070b4acd8544c96a526ab5f`
- OpenSpec delta `spec.md`: `6790151d50dc9425d12e9d7e7dbf693827d6688c7a2ad052e08d5a93e784665a`

### Prior evidence/reviews and inspected runtime seams

- prior readiness review: `a611939d4cfc81ad146b0aa4d5179bc48bfcc4083623a682dd6c2c5d796aed81`
- dependency review: `49faf5ae3da3586e09c974caca5b71c30d41534a8226e31be6f856e865f4e87d`
- schema evidence: `cc8d66a09d156c7d5cead80ee5d40b36bcf52f2a5f0ed8cda78b1895989a97e2`
- `app/InstallationProcess/CanonicalMigrationApplication.php`: `5ca58842cf4b0f1e7107cb277afa349251b6f971b1ace2d1527c841f34348007`
- `bin/fmonitor2-migrate.php`: `e9caa610a952ba9bcbef28dd6e17996e3c83cc5a51c82f527e7b44a99625acf9`

## Final verdict

**CHANGES_REQUESTED** for exact commit
`b7df9c70f66306e2201675852563c683f31c02d6`. The concurrency, retry and
deterministic DB-denial/closed-connection verification design is technically
ready, but the normative public PHP declarations must be made syntactically and
type-completely constructible before Gate 1 can be approved. Owner approval is
still a separate explicit action after a fresh technical approval.
