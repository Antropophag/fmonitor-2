# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-COMMIT-SCALARS-001 v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed test/source commit: `0bcd8f3a605c3d41556cfc5b842b68f63bb8119e`
- Specification SHA-256: `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Test SHA-256: `bf4ca86b7e1c2cb97455b6611f9e2b29517fc897053fc84b245173717154c6d4`
- Verdict: **APPROVED**

The reviewer authored neither test nor future implementation.

The 134-case public repository-port test correctly separates scalar/relationship
validation from real database atomicity. Invalid AcceptedCommit and AttemptCommit
values must return ROLLED_BACK before any of the explicitly approved public
mysqli methods are reached. The sentinel records query, escaping, transaction,
commit and rollback calls and throws on each. Five valid controls must reach that
sentinel, so an implementation that rejects every DTO cannot pass.

AcceptedCommit coverage independently mutates canonical request, positive
identities, bounded revision/size, opaque IDs, fingerprint recomputation,
composition identity/order/hash, Gregorian document date, UTC instant, PDF hash,
digest-bound content identity and event/mode relations. Initial and correction
relationships are distinct, including previous=expected, different new revision,
normalized reason and exact event. Attempt coverage fixes UUID, actor/case/order,
REJECTED/CONFLICT reason pairing, retryable=false and canonical attempt time.

Expected values come from literal spec inputs and a test-owned fingerprint
encoder, not production validation. The RED has 129 intended failures and five
controls, with no setup warning/type/argument failure. Real MariaDB locking,
rollback confirmation, authoritative composition and atomicity remain explicitly
outside this group and cannot be inferred from the sentinel.

Exact shared inputs:

```text
dbb17374533cd542757f41d5ed4a0f76eb48c0d1b7adac22a789dab9bbe23794  tests/Support/AssignmentOrderOriginalIntegrityCommits.php
dfbf0849e34ea941477b238049b29a7e3cdf3566b8bd8b3fdb9931ac14ee551e  tests/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php
41c3bba37d5ef5604b68f9e7c24c8540bf89c2ef54b57ead18440df7b3b7f313  docs/operations/original-data-integrity-public-red-v1-2026-09-06.md
6d3b170f2964b66746c3f0cd3230b9a78f4a81678edfe6618740839b3b33c0c3  private aggregate evidence.json
```

No changes required. Gate 4 may implement only this approved validation behavior.

