# Quality Graph representative PR — graph drift

Date: 2026-09-03 05:57 MSK

- PR: `#1`
- Negative head: `037805933da91901c64621a78ae1efa6cca70061`
- Workflow run: `33709545143`
- URL: `https://github.com/Antropophag/fmonitor-2/actions/runs/33709545143`
- Mutation: canonical manifest `graphDigest` first nibble changed while the
  declaration and generated workflows remained unchanged.

Local public command returned non-zero with exact category:

```text
QUALITY_GRAPH_VALIDATION_FAILURE category=generated_drift detail=stale generated file: .quality-graph/manifest.json
```

The actual PR graph-validation job failed. Both dependent jobs, `SSD/TDD
delivery evidence` and `Repository verification`, were skipped. The result
proves fail-closed dependency ordering for graph drift; it is not a positive
parity result. The following commit restores the canonical manifest bytes.
