# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-WRITES-001 v1

- Review date: `2026-09-06`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed test/source commit: `a49fd3f0ba3bb88eaf4b8a1effa886119f60b537`
- Specification SHA-256: `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Test SHA-256: `78037ff41e311abeb969bfb83480a30de599861f0a052c09bbebde7577d03d90`
- Verdict: **APPROVED**

The reviewer authored neither test nor future implementation.

The 32-case suite exercises the real public MariaDB repository against a random task-owned schema. Six positive controls prevent reject-all behavior: valid initial and correction commits, valid attempt commit/collision, native query-false handling, caller-transaction preservation and content-reference lookup. Twenty-six missing-behavior cases fail for the intended adapter validation/transaction reasons.

Initial and correction controls verify atomic root/revision/request/event/audit writes, append-only revision/request history, exact current-pointer advancement and unchanged process/composition/opening rows. Authoritative order/member drift is introduced one axis at a time and must return confirmed ROLLED_BACK with byte-identical facts/catalog. Correction gap, ownership and normalized-reason relations likewise prove no partial mutation. Real no-change, stale-current and initial uniqueness controls distinguish semantic CONFLICT from integrity rollback.

The public persistence observer fixes causal boundaries before write begin, before native commit, after native commit and before rollback. Failures before native commit require complete rollback and no transaction leak. Throwable after successful native commit returns OUTCOME_UNKNOWN while independently visible request/audit rows remain durable. Rollback-observer failure cannot suppress native rollback and cannot claim a confirmed business conflict. Native query false and actual caller-owned transaction cases cover false-return and ownership behavior without changing global database settings permanently.

The test uses real MariaDB for atomicity and the previously approved synthetic DTO literals. Temporary baseline tables and constraint-disabled corruption belong only to the random fixture schema; before/after facts prove no repair. It does not infer application results from repository statuses or claim fresh-reader behavior.

Exact supporting hashes:

```text
7c1de9364e9f57ace5bfca3778a03cf46d368759e91be0be5c52b79d1add98e1  tests/Support/AssignmentOrderOriginalIntegrityDatabase.php
5c8f528db01187e27786bb5ce4944adecbac9101a1ac4bc2e8fd51ca96267ba6  tests/Support/AssignmentOrderOriginalIntegrityPersistenceObserver.php
dbb17374533cd542757f41d5ed4a0f76eb48c0d1b7adac22a789dab9bbe23794  tests/Support/AssignmentOrderOriginalIntegrityCommits.php
65a28345138929b7b168e030d71f679a7ff71d8195b84d16593d900f73fe2e89  private real-RED evidence.json
```

No changes required. Minimal implementation may proceed for this approved real-write boundary; cumulative application/fresh/worker evidence remains separate.
