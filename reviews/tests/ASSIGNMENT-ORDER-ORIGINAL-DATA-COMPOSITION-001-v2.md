# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-DATA-COMPOSITION-001 v2

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit `14dece9479f80a62773d66d3587b486bf8600169`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Test: SHA256 `01e3d41b3657fde35585c923c8c35c9a5f9e6a2a369e288f27a0ea71c2b91ad1`
- Public seam: composition snapshot consumed by `submitAssignmentOrderOriginal`
- Red: 92 cases, 70 intended failures, 22 controls, exit `255`
- Verdict: `APPROVED`

## Exact evidence

```text
69130cd804ffa6b7d742a3f0ee01d86e7713d07f620111dc874c7ff3059d839d  private evidence.json
30c8b0000294d707a5422d922dfbe6d6fe4e31059f0820327e19fcc8f03ee0f7  private 00.log
3b16425edfb25bf32d65b7aceb0c29fa590c27dec177571c98fc50021a783be6  v1 review
```

## Findings

The v1 finding is closed. Sixteen added negatives cross INITIAL/CORRECTION, NOT_FOUND/UNAVAILABLE and wrong/nonpositive case/order independently. All other payload fields are null/empty, so failure cannot be attributed to another contradiction. Each case requires persistence failure before clock, terminal attempt, stream/stage, IDs or delivery; correct-echo negative-status controls remain.

The unchanged matrix still covers FOUND ownership, canonical identity/hash recomputation, list/type/order/duplicate/positive rules, valid initial/correction controls, and lazy-clock terminal persistence for genuine absence and invalid business composition. Shared helpers and the other four public tests retain their frozen hashes.

Independent execution reproduced `passes=22 failures=70 cases=92`, exit `255`, matching the final archive. No blocking traceability, expected-value independence, sensitivity, determinism or public-seam finding remains. Real MariaDB reader proof remains separate.

## Required changes

None.
