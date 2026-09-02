```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"c24a9f2dd4c009608b0fd75fbeb43ed38cb3cc83f92a8274a86231d768fb9843"}],"redCommit":"9fcc4181210c027d13442e72f6c3983addd0e1a0","recordedAt":"2026-09-03T05:48:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — restored toolchain contract v24

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed repository state: `7ba819bb9868f0a64d866626ecddb0ec5976904f`
- Exact current test blob first appears at: `9fcc4181210c027d13442e72f6c3983addd0e1a0`
- Verdict: `CHANGES_REQUESTED`

## Blocking provenance finding

1. **The prior v9 behavioral approval remains valid, but it does not approve the current exact test blob under the repository's own lineage contract.** v9 approved hash `ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863`. The current test has hash `c24a9f2dd4c009608b0fd75fbeb43ed38cb3cc83f92a8274a86231d768fb9843`, first introduced at `9fcc418...` by removing the unapproved v23 expectations and changing the header from v0.5 RED to v0.6 contract. At that commit the exact test is already GREEN; there is no demonstrated RED evidence for this blob. Canonical review metadata requires an exact test set tied to `redCommit`, so marking it approved would create the provenance gap this change is intended to prevent.

   Resolve this by either restoring the exact v9-approved test blob (if the non-normative header can remain), or by demonstrating the current exact blob RED against the pre-toolchain implementation in a valid Gate 2 history and recording evidence before requesting review. Do not represent `9fcc418...` as a demonstrated RED merely because it is the blob's first commit.

## Behavioral review

No behavioral finding remains. Ignoring the comment-only version label, the current assertions are the same v9 occurrence-set contract:

- exactly one approved Quality Graph runtime SHA;
- exactly one CLI `0.1.7` and provider `0.1.7` package occurrence;
- rejection of an additional floating runtime, ranged CLI declaration, and mixed provider version.

The removed setup-uv and exact-pip expectations are correctly absent. The current test is syntax-clean and passes against the current declaration/project. Thus v9's independent analysis remains substantively applicable, but exact-hash approval cannot be inferred from semantic similarity.

## Verification

```text
php -l tests/Verification/quality_graph_toolchain_001_test.php
php tests/Verification/quality_graph_toolchain_001_test.php
```

Both commands pass in repository state `7ba819b...`.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c  tests/Verification/quality_graph_governance_001_test.php
b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64  tests/Verification/quality_graph_publisher_001_test.php
c24a9f2dd4c009608b0fd75fbeb43ed38cb3cc83f92a8274a86231d768fb9843  tests/Verification/quality_graph_toolchain_001_test.php
```

Gate 3 approval for the current exact hash is withheld pending exact RED provenance. The v9 approval remains the valid review for its original exact test blob and behavior.
