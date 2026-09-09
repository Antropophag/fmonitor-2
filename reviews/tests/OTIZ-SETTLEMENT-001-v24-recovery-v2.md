# OTIZ-SETTLEMENT-001 — canonical v24 recovery Gate 3 v2

- Date: `2026-09-10`
- Reviewer: separately tasked agent `/root/settlement_review`
- Test author: root
- Reviewed correction: `600c4048`
- Prior review: `reviews/tests/OTIZ-SETTLEMENT-001-v24-recovery-v1.md`
- Verdict: **CHANGES_REQUESTED**

The behavioral v1 finding is closed. Default execution forks isolated v22 and
v23 rehearsals. Each uses a fixed full source SHA, Git archive, independently
built historical image and verified OCI revision; exports with that source's own
fixture; restores with the same image and attestation; preserves all old rows,
AUTO values and private bytes through forward migration to24; and requires the
old image to reject a v24 bundle before target mutation. The observed v23 run
passes export/restore/forward preservation and reaches the intended current-v24
backup RED. Cleanup of v23 fixture databases is explicit.

One narrow process finding remains. The committed and dedicated verification
input still describes the acceptance seam as “historical exact v22 image ->
current canonical migration.” The accepted contract and corrected test now cover
both exact v22 and v23 images. Update the seam text to v22/v23 and regenerate the
frozen review plan so mapping completeness is explicit. The shared plan may later
be stale due parallel WIP; the correction needs an exact frozen-plan check.

Optional diagnostic correction: the common image-build assertion says “build
exact v22 image” even in the v23 branch; “build exact historical image” would
report failures accurately.

Reviewed identities:

```text
6e6e05fb03d4ac2b5ea7f642789b36038e87ded490b6c9c70c14022732e09ef0  tests/Runtime/runtime_recovery_forward_update_001_test.php
84f5a8ff5a85caa33791be7ee269f0344036ad8d9cc1aabcc814d042bf252feb  docs/operations/otiz-v24-recovery-red-2026-09-10.md
```

Recovery Gate 3 remains **CHANGES_REQUESTED** only for the mapping correction.
No production recovery implementation is authorized by this record.
