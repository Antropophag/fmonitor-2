```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"d12f34af6e1aa86e9dc0397c1266ce5308dcf80f089f12c388d3ab56c59f1840"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"missing checker prevents required missing_receipt classification in isolated Git fixture","recordedAt":"2026-09-02T23:20:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v2

Supersedes the test arrangement in `quality-graph-governance-red-evidence.md`; the earlier evidence remains retained.

## Command and outcome

```text
$ php tests/Verification/quality_graph_governance_001_test.php
exit=255
PHP Fatal error: Uncaught TestFailure: RED_ASSERTION: isolated test seam must classify the absent opt-in receipt root
evidence={"status":1,"stdout":"","stderr":"Could not open input file: /home/antropophag/code/fmonitor-2/tools/delivery/check-evidence.php\n"}
Expected: 1
Actual: 0
```

The temporary repository was successfully initialized and committed before invocation. Failure is the absent checker behavior, not Git, PHP or filesystem setup. The fixture is removed in `finally` and does not depend on repository receipts.
