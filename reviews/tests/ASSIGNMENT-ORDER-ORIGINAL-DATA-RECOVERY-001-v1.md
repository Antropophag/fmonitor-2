# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-RECOVERY-001 v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed test/source commit: `0bcd8f3a605c3d41556cfc5b842b68f63bb8119e`
- Specification SHA-256: `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Test SHA-256: `ed9a50db4d5c156168f9c89451cc8cbae10014fb585093260a4633c918f9708e`
- Verdict: **APPROVED**

The reviewer authored neither test nor future implementation.

The 50-case public application test is sensitive to the missing fresh-reader API
and to continued reuse of the writer repository. It requires the exact closed
open/close types, optional trailing Dependencies factory, one lazy open only
after ambiguous commit, one reader lookup, one close, and zero ordinary writer
recovery rereads. Omitted factory is explicitly degraded and cannot borrow the
writer connection.

All six lookup status/payload combinations, lookup/getter Throwables and malformed
stored results have independently fixed outcomes. FOUND is snapshotted through
each getter exactly once before reader close; a close-time source mutation cannot
change the returned value. NOT_FOUND maps persistence failure, while open/read/
validation unavailable maps outcome unknown. Close failure preserves the already
selected read and emits one exact phase diagnostic without retry.

Typed OUTCOME_UNKNOWN and thrown/unconfirmed commit paths use the same one-shot
recovery. Valid accepted, rejected and conflict terminal winners are distinguished,
with no second commit/attempt, allocation, clock or stream work. The content lease
remains held through open/read/snapshot/close and releases exactly once afterward.
Accepted recovery preserves delivery and fixed response-loss behavior.

Three positive controls prevent unavailable-all behavior, and non-unknown commit
statuses prove no speculative reader creation. Conditional test marker interfaces
do not define production API symbols; explicit interface/class/reflection checks
remain intended REDs. The test correctly does not claim distinct MariaDB
connection identity, trusted target configuration, native close or real stored-
row integrity; those remain separate production tests.

Exact shared inputs:

```text
a05c4a22f3c0360ecb865c44ffb5043aa55b02e3ed11374c8743a7537114ce75  tests/Support/AssignmentOrderOriginalIntegrityFixture.php
98b9e080f7dbd44bcd7b2f40d905a3aa9842ff807abe79dbeb78012884c15780  tests/Support/AssignmentOrderOriginalIntegrityResources.php
caaeeee7e756b70bfe0bed939858d3a46f6cacf5489b1639a86f5c40687f5907  tests/Support/AssignmentOrderOriginalIntegrityValues.php
dfbf0849e34ea941477b238049b29a7e3cdf3566b8bd8b3fdb9931ac14ee551e  tests/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php
41c3bba37d5ef5604b68f9e7c24c8540bf89c2ef54b57ead18440df7b3b7f313  docs/operations/original-data-integrity-public-red-v1-2026-09-06.md
6d3b170f2964b66746c3f0cd3230b9a78f4a81678edfe6618740839b3b33c0c3  private aggregate evidence.json
```

No changes required. Gate 4 may implement only this approved public recovery behavior.

