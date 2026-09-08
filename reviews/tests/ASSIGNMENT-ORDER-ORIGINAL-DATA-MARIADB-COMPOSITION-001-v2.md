# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-COMPOSITION-001 v2

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit `895f69264c6377a63c8a741bfb94ba0d4a4c1f61`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Gate 1 review: SHA256 `ce9a24c650c10cd71f3f71203bf900cc733978d25e862b28ee9b18a62280153b`
- Test: SHA256 `2bf6a65b35860f325509a33a4878f155e58b68d1e2c1435eddd4be68c91382c6`
- Public seam: `AssignmentOrderOriginalMariaDbCompositionReader::find`
- Red: 40 cases, 29 intended failures, 11 controls, exit `255`
- Verdict: `APPROVED`

## Exact evidence

```text
b1e0c8cfeb5b36c34deeabb101778762754208cbbc92e6437ff36c09fe57172b  final evidence.json
50b45bf5c23166b5237a57cadb53ea98b81cc5c4ea92f6156d5a3bb80c5cf6b0  final composition log
7c1de9364e9f57ace5bfca3778a03cf46d368759e91be0be5c52b79d1add98e1  database helper
5c8f528db01187e27786bb5ce4944adecbac9101a1ac4bc2e8fd51ca96267ba6  persistence observer helper
3b16425edfb25bf32d65b7aceb0c29fa590c27dec177571c98fc50021a783be6  v1 review
```

## Findings

G3-COMPDB-01 is closed. A separately opened then closed mysqli receives a valid `find(4512,81)` and must return the exact seven-field UNAVAILABLE snapshot with echoed case/order and null/empty content, without throwing. A second case keeps a canonical order visible but temporarily renames only the owned member table; the member SELECT failure must return the same UNAVAILABLE snapshot, release the owned read transaction, expose no partial FOUND and mutate no rows. The table is restored in `finally` before outer exact cleanup.

This is independently distinct from the existing NOT_FOUND case, which removes the member table only after changing the order to another case and proves no member query occurs. Together they detect both improper probing and swallowed member-query failure.

The unchanged loose-VARCHAR fixtures keep native malformed numeric/date strings constructible without production schema coercion. Canonical projection, protocol-versus-business-invalid classification, member/action/date coverage, duplicate handling, observer API, two-connection consistent-snapshot race, release-observer failure and caller-owned transaction remain exact. No schema oracle is inferred from the loose tables; only the named production read columns and public result are exercised.

Independent execution reproduced `passes=11 failures=29 cases=40`, exit `255`, matching the final archive. No blocking traceability, expected-value independence, query-failure, transaction-release, setup, cleanup, determinism or public-seam finding remains.

## Required changes

None. Real repository writes/fresh-reader/worker proof remains separately gated.
