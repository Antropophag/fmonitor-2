```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root/qg_publisher_provenance_red","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"e97218eac87e079a16dc7e3c090a83080e31bb98","tests":[{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"2c2bec078d430f5ef22a79c020c67899c3ad35eb9840375fe2ddcb684031ae93"}],"command":"python3 tests/Verification/quality_graph_publisher_provenance_001_test.py","observedFailure":"repository-owned offline publisher artifact-validation seam is absent","recordedAt":"2026-09-04T06:00:11+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v34

This executable publisher-provenance slice exercises a repository-owned offline
seam with the pinned Quality Graph `0.1.7` `MemoryGitHubPort`, artifact descriptor
contract, ZIP transport and Result v0 API. The expected repository, PR, exact
head, workflow run, attempt, graph digest and node IDs are independent literals.

The fixture requires a valid artifact for every expected node and isolated
rejection of a wrong repository, PR, head SHA, workflow run ID, run attempt or
graph digest. It also covers an omitted expected node, an unexpected node,
duplicate same-attempt artifact, expired artifact and archive digest drift.

## Honest RED

At exact base `e97218eac87e079a16dc7e3c090a83080e31bb98`, production and tooling
implementation were untouched. Commands at `2026-09-04T06:00:11+03:00`:

```text
sha256sum specs/QUALITY-GRAPH-GOVERNANCE-001.md tests/Verification/quality_graph_publisher_provenance_001_test.py
189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859  specs/QUALITY-GRAPH-GOVERNANCE-001.md
2c2bec078d430f5ef22a79c020c67899c3ad35eb9840375fe2ddcb684031ae93  tests/Verification/quality_graph_publisher_provenance_001_test.py

python3 tests/Verification/quality_graph_publisher_provenance_001_test.py
Traceback (most recent call last):
  ...
AssertionError: RED_ASSERTION: repository-owned offline publisher validation seam is absent
exit=1
```

The failure occurs before any case can be accepted and is caused solely by the
missing repository seam `tools/delivery/quality_graph_publisher.py`. This is the
intended Gate 2 RED, not an environment or fixture setup failure. No Gate 3
review is claimed by this record.
