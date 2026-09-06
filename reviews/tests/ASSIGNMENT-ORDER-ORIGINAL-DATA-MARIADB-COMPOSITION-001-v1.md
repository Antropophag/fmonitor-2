# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-COMPOSITION-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit `8ff730b100866d670dd1ab6ba473c6f6426eb666`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Gate 1: `APPROVED`, review SHA256 `ce9a24c650c10cd71f3f71203bf900cc733978d25e862b28ee9b18a62280153b`
- Test: SHA256 `23c24e4e3fc5f7977d8d60af765c2cbe38365088fffe567e6208dc33c2336bfc`
- Public seam: `AssignmentOrderOriginalMariaDbCompositionReader::find`
- Red: 38 cases, 29 intended failures, 9 controls, exit `255`
- Verdict: `CHANGES_REQUESTED`

## Exact supporting hashes

```text
7c1de9364e9f57ace5bfca3778a03cf46d368759e91be0be5c52b79d1add98e1  tests/Support/AssignmentOrderOriginalIntegrityDatabase.php
5c8f528db01187e27786bb5ce4944adecbac9101a1ac4bc2e8fd51ca96267ba6  tests/Support/AssignmentOrderOriginalIntegrityPersistenceObserver.php
65a28345138929b7b168e030d71f679a7ff71d8195b84d16593d900f73fe2e89  complete group-C v1 archive evidence.json
10d9ef5aa9f9cd89544d80d109080b0149515e872b123d04dae50c861e7f80e5  composition log
```

## Blocking finding

### G3-COMPDB-01: query failure and closed-reader outcome are not exercised

The contract requires any order/member query failure to return the exact UNAVAILABLE snapshot and release any owned read transaction. The test covers active caller transaction, read-release observer Throwable and no-member-probe for genuine absence, but no valid FOUND path forces an order or member SELECT to fail, and no reader is invoked on a closed connection.

Add two deterministic owned cases:

1. construct the reader on a separately closed mysqli and call `find(4512,81)`; require UNAVAILABLE with exact echoed case/order and null identity/hash/engineer plus empty installers, with no Throwable;
2. seed a canonical order, remove/rename only the owned loose member table, then call `find(4512,81)`; require the same UNAVAILABLE snapshot and no partial FOUND projection. Restore or rely on exact owned-database teardown through attempt-all cleanup.

These cases distinguish genuine query containment from the existing NOT_FOUND test, which deliberately drops the member table only after changing the order to another case and therefore proves that no member query occurs.

## Covered assessment

The loose VARCHAR projection tables validly make padded, signed, fractional, exponent, overflow and malformed date strings constructible without relying on production DDL coercion. Canonical string controls, protocol versus business-invalid classification, member ownership/action/date coverage, duplicates, no-repair snapshots and allowed release/expired behavior are exact. The public observer declaration is an explicit RED. The two-connection callback causally commits a complete new order/member set after the first read and requires one old consistent snapshot, then a new complete snapshot; lock timeouts bound a wrong locking implementation. Active caller transaction and release-observer failure prove ownership cleanup.

Independent reproduction matched `passes=9 failures=29 cases=38`, exit `255`. Fixture setup and exact cleanup completed cleanly.

## Required changes

Add closed-connection and valid-order/member-query-failure cases with exact UNAVAILABLE snapshots, preserve fresh RED and request Gate 3 rereview.
