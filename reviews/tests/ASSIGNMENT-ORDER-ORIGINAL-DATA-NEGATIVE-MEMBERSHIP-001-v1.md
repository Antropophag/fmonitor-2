# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-NEGATIVE-MEMBERSHIP-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`
- Reviewed implementation HEAD: `94a17bfef8175669a2265ebd03a33e77c143bfee`
- Specification: DATA-INTEGRITY-001 v0.6 section 4, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Test: SHA256 `d662af9a70982782284285bcb392b98018bb5b757ee223d271b0fbdc2ebbb0c7`
- Public seam: complete negative lineage values consumed by `submitAssignmentOrderOriginal`
- Red: 12 cases, 8 intended failures, 4 controls, exit `255`
- Verdict: `APPROVED`

## Exact evidence

```text
47e68fcfa89796be94f25b3b09dbce1b30c5c314a5d97eb6f7f113e12d926b8b  private evidence.json
73cab0fc48b69347aadaa0288c0e1244f83c0258ebac3fe14ce727181ff41759  private red.log
be25ce4d572486d71133d1c6ad7f1e8c793443987be8ac64dd112f2767dae801  reviewed LineageSnapshot source
```

## Findings

Traceability is exact. DATA-INTEGRITY section 4 requires NOT_FOUND and UNAVAILABLE lineages to have empty immutable membership and requires `containsRevision` to agree with that list for current/target identifiers used by the command. The test exercises the correction root lookup and the later revision-owner lookup, the two application positions where a known target exists.

Each position crosses NOT_FOUND/UNAVAILABLE with false, true and Throwable membership. False NOT_FOUND controls preserve the existing exact business reasons: root absence gives SEMANTIC_COLLISION and globally absent revision ownership gives TARGET_NOT_FOUND, each with one terminal attempt. False UNAVAILABLE controls preserve persistence failure. These controls prevent blanket rejection from satisfying the matrix.

True and Throwable variants retain otherwise exact negative metadata and empty `revisionIds`. They must select retryable PERSISTENCE_FAILURE before acceptance or ID allocation. The test asserts the exact known target (`revision-0001` for root, `revision-0099` for revision-owner) is the sole membership call. It neither invents an arbitrary probe nor infers membership from caller data.

Revision-owner cases also prove the exact owner-query path and occur after stream acquisition as required; root cases remain pre-stream. Shared integrity helpers are frozen and define no production alias. Production source is unchanged, so the eight failures demonstrate the identified missing negative-membership validation rather than setup failure.

Independent execution reproduced `passes=4 failures=8 cases=12`, exit `255`, matching the archive. No blocking traceability, expected-value independence, control strength, sensitivity, determinism or public-seam finding remains.

## Required changes

None. Minimal implementation may validate negative membership for only the invocation-known target/current/query values exactly once. Real MariaDB and combined Gate 5 remain separate.
