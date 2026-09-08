```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"REPLACE-SPEC-ID","reviewer":"agent:/REPLACE-REVIEWER","verdict":"CHANGES_REQUESTED","specSha256":"REPLACE-SPEC-SHA256","tests":[],"redCommit":"REPLACE-EXACT-RED-COMMIT","recordedAt":"REPLACE-ISO8601-TIMESTAMP"}
```

# Test review: SPEC-ID

- Reviewer:
- Test author:
- Reviewed commit:
- Specification:
- Public seam:
- Red command and intended failure:
- Verdict: `APPROVED` | `CHANGES_REQUESTED`

## Findings

Record specification traceability, sensitivity to missing behavior, independent expected values, rejected cases, determinism, and setup isolation. Use `None` only after checking every item.

## Required changes

List blocking changes or `None`.


For a receipt-governed slice, replace every placeholder and derive the complete
ordered Git path sets using [the canonical contract](../../specs/QUALITY-GRAPH-GOVERNANCE-001.md).
Empty arrays here are placeholders, not authorization to omit changed files.
Record APPROVED only after independent review of the named committed stage.
Corrections use a new review record; preserve the previous record.
