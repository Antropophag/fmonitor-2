```delivery-metadata
{"schemaVersion":1,"kind":"green","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"testReviewRecordPath":"reviews/tests/QUALITY-GRAPH-GOVERNANCE-CURRENT-v3-2026-09-08.md","implementationFiles":[{"path":"tools/delivery/check-evidence.php","status":"M","sha256":"a613f6a17e0dfd3df30d82f9f3f244f1f0cc2fc837c6b0162acf4b857f8061e0"}],"commands":["php tests/Verification/quality_graph_history_envelope_001_test.php","make governance-test quality-graph-validate","php -l tools/delivery/check-evidence.php","git diff --check"],"recordedAt":"2026-09-08T00:49:41+00:00"}
```

# History-envelope correction GREEN

The approved new regression now rejects a post-GREEN source edit followed by an
exact restore, while accepting a tree-identical synthetic PR merge whose first
parent predates GREEN. The checker takes the bytewise-sorted union of touched paths
from every new commit; merge comparison uses a parent descended from GREEN when
available. Incoming side-branch commits are also inspected.

- history-envelope-green-001.log: exit0, SHA-256 783f9ab0806f5f37c62682cdf41fb993b80f8b3599ec9fb10617103337242f2d
- governance-v2-final.log: exit0, SHA-256 188c31edabaefa6bad62f03b9a4b2b3c5e72edf04cefa10c8263c1c7aaf942cf

All six governance suites, graph/compiler validation, PHP syntax and diff-check
passed. The prior seven tests and v0.6 specification remain unchanged; the eighth
test was committed at RED83ff618 and independently approved before this fix.

The existing full CI/toolchain implementation remains as reviewed at ae8cb07;
this correction is the complete post-G3 implementation delta above. Prior GREEN
and CHANGES_REQUESTED records remain append-only. No v1 receipt was issued.

The full ae8cb07 run is intermediate evidence only. Final exact-commit full verify,
Gate5v2 and the first immutable receipt remain pending. No remote publication,
merge, cutover or live pilot change has occurred.
