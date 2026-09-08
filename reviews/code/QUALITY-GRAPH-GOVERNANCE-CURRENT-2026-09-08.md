```delivery-metadata
{"schemaVersion":1,"kind":"code-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/migration18_collation","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"implementationCommit":"ae8cb079520470afb02d39e4c41e54fcb4f5bcd1","implementationFiles":[{"path":".github/workflows/quality-graph-publish.yml","status":"A","sha256":"d4e12b69d59b5fd818bd177f0467b91159db841323f6964803ae9c33d7b7ba15"},{"path":".github/workflows/quality-graph-push.yml","status":"A","sha256":"c888ed5e6023c814e104c42e8f76baea503d2db372ee0698720e4c9ced257905"},{"path":".github/workflows/quality-graph.yml","status":"A","sha256":"f124d80f40029f1333611ed60b03b774bb5c55f37f2d597082f93e71ffabdb75"},{"path":".github/workflows/repository-verification.yml","status":"A","sha256":"787f9d1618ffb829d049dd326eef30aae413e61837582284a1731f1bc24d1fb0"},{"path":".gitignore","status":"M","sha256":"51b7820f26c33b4678b8862b3955be95d485e30ba86b151cfa14262689e750f5"},{"path":".prettierignore","status":"A","sha256":"016dcf5e7b56c6e2e785286c82b19087f012ef353a24023fdb0ffafd68bd782b"},{"path":".quality-graph/generated-publisher-v0.1.7.yml","status":"A","sha256":"a5de72afaafb023a7f9fde24fc1be3872ea96dda1d16d23fb385e8fe58e2a8ee"},{"path":".quality-graph/manifest.json","status":"A","sha256":"2d9c8cd07626100eef69f9603240baedcae6676323874241d69c37c6e1b0aead"},{"path":"Makefile","status":"M","sha256":"27d32fc088c6f0ac4a042bcd634ba2e773f20cbba1f11b39feba7f6de7f681c9"},{"path":"pyproject.toml","status":"A","sha256":"8a15b864be78444b2c0164f35d86e01129cfc23596f5ba00080425132c163ac2"},{"path":"quality-graph.yml","status":"A","sha256":"37ce612919d44033a687c0b9f708cb6adc25f1c8b625da8279168e2962423a10"},{"path":"reviews/code/TEMPLATE.md","status":"M","sha256":"4c8b9be63326b841629df99aaeeac08cdfe218d352ea1a9135b6445b8bccc8b6"},{"path":"reviews/tests/TEMPLATE.md","status":"M","sha256":"33be23165add1b7080d1d76ce56f91126ae8002594b99823bf9e8df5d13171bd"},{"path":"tools/delivery/README.md","status":"A","sha256":"52b6b4a47a03f42fae05c3683c6e96fc5152ce3ab2aefb22ea7be0be2b654f73"},{"path":"tools/delivery/check-evidence.php","status":"A","sha256":"5a48cf5dc21431b2635f26378c62610c6b3ab6c5a779a0bea05c5710b79a399c"},{"path":"tools/delivery/check-quality-graph.php","status":"A","sha256":"18b64b0d8f9ed87f1c5d3e129af0a122fb56454951af7089b4584a747c43ac65"},{"path":"tools/delivery/ci-setup.sh","status":"A","sha256":"b11973295d3ffd467eed608448d0e0cf89e78f4d9922ddaf07cd5208b9a80b83"},{"path":"tools/delivery/quality_graph_publisher.py","status":"A","sha256":"989944eff52f7babbf15be395c61a3d8f4fc0efd81b33a58046cc74a4ec80549"},{"path":"tools/delivery/test.sh","status":"A","sha256":"39ce79808fe652437af161c4be172c57af02f9b360abfb6ccf9dd3f2e1ae5361"},{"path":"tools/verification/run.sh","status":"M","sha256":"dfa1479ab09ad59cec121c7169d2a8b16f8c4ab8403e01f1af691568e0aa6049"},{"path":"uv.lock","status":"A","sha256":"ce183121f447bf472561709d975e9ef04f4ec58d1c8d1718b7e30a2aad6c5759"}],"recordedAt":"2026-09-08T00:38:07+00:00"}
```

# Gate 5 code review: current Quality Graph governance

- Reviewer: `agent:/root/migration18_collation`
- Reviewed implementation: `ae8cb079520470afb02d39e4c41e54fcb4f5bcd1`
- Test review: `c414b032bf208243b99634371d8daeb83f266ddd`
- Specification: `QUALITY-GRAPH-GOVERNANCE-001` v0.6, SHA-256 `189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859`
- Verdict: **CHANGES_REQUESTED**

## Blocking finding

`tools/delivery/check-evidence.php:146` implements the post-review evidence
envelope with a single net `git diff GREEN..HEAD`. This proves only that the final
trees differ on allowlisted paths. It does not prove that every intervening
evidence-envelope commit changed only those paths.

For example, a commit after GREEN can modify a governed source, test, or executable
specification and a later commit can restore the original bytes. The net diff is
empty for that path, so `deliveryEnvelope()` accepts the chain without a fresh
GREEN or Gate 5 review. Receipt edit/revert is rejected separately by
`deliveryImmutableReceipt()`, but that protection does not cover the governed
implementation, tests, or specification.

This violates the v0.6 requirement that evidence-envelope commits may change only
the allowlisted review/receipt/task/parity paths and that any governed path change
after the reviewed implementation commit requires new GREEN and Gate 5 evidence.

Required correction: inspect the paths touched by every commit after GREEN, using
NUL-safe raw Git output and no rename inference, and reject any non-allowlisted
path even when a later commit reverts it. Add a public-seam sensitivity case that
edits and then restores a governed file after GREEN and still requires
`commit_mismatch`. Because this changes an approved test, return through RED and
independent Gate 3 review before implementation.

## Other review results

No additional blocker was found. The reviewed implementation derives complete
bytewise Git test and implementation sets, checks exact raw blobs and immutable
receipt ancestry, preserves stable failure priority for the tested cases, disables
Quality Graph approvals, retains the existing full verification workflow, pins the
toolchain and actions, and deploys only the exact reviewed privilege-removal
publisher transform.

Focused verification completed during review:

- `make governance-test quality-graph-validate` — PASS, all five focused suites;
- Quality Graph digest — `95ab7381b6ce103c5ab3cce6fbb54826cf227470f4a7288894e30d74949ea325`;
- PHP, Python and shell syntax checks — PASS;
- `git diff --check c414b032..ae8cb079` — PASS.

The root-owned full `make verify` was still running when the blocking finding was
identified. Its eventual result cannot override this governance bypass.
