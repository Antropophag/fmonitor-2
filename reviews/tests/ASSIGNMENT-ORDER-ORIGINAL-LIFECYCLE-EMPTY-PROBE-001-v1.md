# Test review: ASSIGNMENT-ORDER-ORIGINAL-LIFECYCLE-EMPTY-PROBE-001 v1

- Reviewer: separately tasked agent `/root/selection_v04_readiness`
- Test/patch author: `/root`; reviewer authored neither reviewed tests nor production
- Reviewed commit containing unapplied patch: `927acdbe0337d92cc6b432d88070f2fbe6d82e6c`
- Specification: `specs/ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001.md`
  v0.1, SHA256
  `4ac2e79f8e21c1235f37a5578cda21cfd192e47d02d6627a3a832e8cf2b4f332`
- Candidate patch SHA256:
  `478c3ece041c89affa79f780be6e3920037e2865fcd31bce4a98f2b0baf7a3c8`
- Verdict: **APPROVED**

## Exact reviewed evidence

```text
4a83de1d2770ccd8f5c70604a3f678d9e9f24298317f4291e6dcfad8f757df86  tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php before patch
f788e80143c25cda53fb02d79a4089248ce6079fcf1586b6aeb65b53d5ba6486  tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php before patch
e3f7d4af232e579b24b71dc5074a9aaa00e71b3d8ee317cf3f41c9ab0cadace9  candidate-snapshot/tests/InstallationProcess/assignment_order_original_dynamic_ports_001_test.php
66f334a9cf27897164c4c5bf42a58b6228c1336f01feeb8076fd8c8151bebe79  candidate-snapshot/tests/InstallationProcess/assignment_order_original_gate5_domain_red_001_test.php
a6b9031df433bd11a14becadce6fd6e9f3501c843f0f75e8e97af6c06ea8a79f  patch-candidate-0.log
be4d221d01de8290c34c7d1a4eddbe07337ef6617ee8a97a182777df73e0cd32  patch-candidate-1.log
13f7d9a9aac41a749c649ff7ed0814718a97153d16b399d0515546c7b21e636e  patch-evidence.json
```

Private evidence root:
`/Users/antropophag/.local/state/fmonitor2-verification/original-lifecycle-red-61vhs_tf`.
The actual worktree test files remain at their pre-patch hashes.

## Scope and traceability

The patch changes exactly three assertions in two existing public-seam tests. It
does not alter setup, fixtures, commands, expected Result tuples, authorization,
fact/evidence checks, cleanup, delivery, lifecycle events or protected E2E.

The approved lifecycle contract removes the former
`findAcceptedFingerprint('')` availability probe. It retains one semantic
accepted-fingerprint lookup only after completed stream acquisition and PDF
inspection. Therefore the three updated expectations are direct consequences of
section 2, not new application behavior.

## Oracle review

### Dynamic post-stream fingerprint unavailable — PASS

The existing case supplies the fixed 327-byte Example A PDF and asserts exact
semantic fingerprint
`dd356db041181636ce1ecfc619f9055a625d81250e59ad3543c9f5cd5b582a7d`.
After removal of the empty probe, the repository call list must contain exactly
that one value. The patch replaces `['', exactFingerprint]` with
`[exactFingerprint]` and leaves all failure/result/resource assertions intact.

The 327 bytes fit in one requested 65536-byte chunk. The stream contract requires
one BYTES read followed by one empty EOF read before inspection and the semantic
fingerprint lookup. Thus `readCalls=2` remains independently determined and
proves this is the real post-stream failure boundary. The case still requires
retryable `FAILED/PERSISTENCE_FAILURE`, stage abort/close, no ID/finalize/lease/
commit/fact/audit/delivery and no later lifecycle event.

### Invalid clock before every fingerprint lookup — PASS

The lifecycle order validates the single clock before stream acquisition and the
sole semantic fingerprint lookup. With the empty availability probe removed,
every invalid/unavailable clock case must observe an empty fingerprint-call list.
The patch changes `['']` to `[]` and retains clock call one, stream read zero,
storage/ID zero, no lifecycle/storage events and unchanged evidence assertions.

All nine dynamic failures are sensitive to the current implementation's obsolete
empty probe: one real-fingerprint case sees the extra leading empty call and eight
clock cases see the forbidden empty call.

### Domain terminal versus fingerprint boundary — PASS

The existing domain loop varies either terminal-request status or fingerprint
status. Terminal lookup remains before stream and therefore expects zero reads.
Fingerprint status now applies to the sole semantic post-stream lookup and
therefore expects two reads. The ternary expected value pins this distinction
without changing the exact persistence-failure result or other dependency
assertions.

The private candidate run fails with expected 2, actual 0 for the fingerprint
branch because current production still consumes the configured unavailable
status in its early empty probe. This is the intended missing lifecycle behavior,
not fixture or environment failure.

## Source-copy and RED integrity

The evidence manifest records base HEAD
`3f5aa18cedc9bf71e43a16ed29a39da9ad7c1ca1` and the full production source
manifest. The two source test hashes equal the actual unchanged worktree bytes.
The two candidate-copy hashes equal the files in the private candidate snapshot
and correspond to the exact three-hunk patch. No helper or fixture modification
is required.

Both candidate copies exit 255 for the intended assertion mismatches. Dynamic
ports reports exactly nine named failures. The domain test stops at its precise
fingerprint read-count assertion with expected 2 and actual 0. Log and evidence
hashes match the private archive.

## Sensitivity and preservation

The patch would fail if an implementation retained the empty probe, performed
the semantic lookup before clock/stream, skipped EOF, added another fingerprint
lookup, or moved terminal lookup after stream. Existing assertions continue to
catch changed public results, wrong authorization, unexpected facts, missing
cleanup, ID/finalize/commit work after unavailable, lifecycle leakage and evidence
mutation.

The patch does not weaken or delete the real post-stream-unavailable sensor. It
removes only expectations for a nonsemantic call explicitly forbidden by the
approved contract.

## Findings and disposition

No blocking traceability, oracle, source-copy, RED-classification, sensitivity,
determinism or scope finding remains.

**APPROVED** for applying the exact three-assertion patch as part of minimal
lifecycle GREEN after the main lifecycle test receives its separate independent
Gate 3. This approval covers only the stale
empty-probe expectations. It does not establish lifecycle GREEN, implementation
approval, resource-owner Gate 3, combined original-command approval or launch
readiness.
