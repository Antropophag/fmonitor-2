```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"ad6de2da4d486720df473b5a030ead86dc59b45a52cd7649ef74d8d12a0d5174","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"b75f00dd568c8bcd9d497aea00862d81030d3178ee6f278594340e1cc3059b3c"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"missing public make target prevents required missing_receipt classification","recordedAt":"2026-09-02T23:00:00+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED

## Command

```text
php tests/Verification/quality_graph_governance_001_test.php
```

Exit status: `255`.

## Relevant output

```text
PHP Fatal error: Uncaught TestFailure: RED_ASSERTION: public seam must classify the absent opt-in receipt root
evidence={"status":2,"stdout":"","stderr":"make: *** No rule to make target 'delivery-evidence-check'. Stop.\n"}
Expected: 1
Actual: 0
```

## Why this is intended RED

The test first proves that the repository is a valid Git checkout and that `make` starts. The command exits nonzero, but the assertion remains RED because the specified repository-owned seam does not exist and therefore cannot emit the stable fail-closed `missing_receipt` category. This is missing behavior rather than broken infrastructure.
