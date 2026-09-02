```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/qg_test_review","verdict":"CHANGES_REQUESTED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"cf82b728a3e33667cafa1f2e418b679c8417ca39cb4df81528ad5b0013f21c29"}],"redCommit":"938b440e800c89276d28df5fb35844ec00eb23ea","recordedAt":"2026-09-03T05:03:00+03:00"}
```

# Test review: QUALITY-GRAPH-GOVERNANCE-001 v0.6 — setup-uv pin RED v22

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, or implementation
- Reviewed RED commit: `938b440e800c89276d28df5fb35844ec00eb23ea`
- Reviewed evidence: `docs/operations/quality-graph-governance-red-evidence-v22.md`
- Verdict: `CHANGES_REQUESTED`

## Blocking finding

1. **The exact setup-uv pin is not part of the approved executable specification.** v0.6 requirement 8 names exact pins only for `quality-graph-cli`, `quality-graph-github`, and `alchemmist/quality-graph`. Neither v0.6 nor its approved publisher amendment records `astral-sh/setup-uv` or commit `37802adc94f370d6bfd71619e3f0bf239e1f3b78`. An upstream lookup can establish provenance, but it cannot introduce a new normative acceptance value after Gate 1. The test's expected value therefore lacks specification traceability.

Return to Gate 1: amend the executable contract with the exact setup action identity/commit, its provenance source, the complete-occurrence/no-mixed-pin rule, and which generated/deployable files it governs. Obtain owner and independent amendment approval, then update the RED metadata to the approved spec hash and recapture RED.

## Checks that passed

- The validator gathers the complete setup-uv occurrence set and exact-compares it with one full SHA rather than blacklisting selected tags.
- Appending an additional `@v7` occurrence makes the occurrence set invalid, so mixed/floating setup refs are detectable.
- Existing Quality Graph runtime and Python package pins remain independently enforced.
- PHP/bootstrap and canonical files are present; RED occurs because setup-uv is absent from the declaration, not because of setup failure.
- Evidence v22 contains the complete sorted base-to-RED test set and matching current hashes.

## Reproduced RED

```text
php -l tests/Verification/quality_graph_toolchain_001_test.php
php tests/Verification/quality_graph_toolchain_001_test.php
```

Syntax passed. The test exited `255` at `toolchain occurrence sets must contain only the audited exact release set`: current runtime/package pins are present, while the newly expected setup-uv occurrence is absent.

## Reviewed hashes

```text
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
b3dcb8238d898a89a53f6e153ae9043d6db0c00955d9ded76df9dcc8ccfdd50c  tests/Verification/quality_graph_governance_001_test.php
b690aead82e854529740fb9e835e94a682a0d0f01d1aa6e54ceb49c1e7fb7c64  tests/Verification/quality_graph_publisher_001_test.php
cf82b728a3e33667cafa1f2e418b679c8417ca39cb4df81528ad5b0013f21c29  tests/Verification/quality_graph_toolchain_001_test.php
```

After an approved amendment, this tracer may authorize only one exact immutable setup-uv occurrence and rejection of any additional mixed/floating occurrence. It does not authorize other runner behavior or CI execution.

Gate 3 is not approved; no setup-uv declaration change is authorized from v22.
