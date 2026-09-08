# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema-v2 fixture amendments

- Reviewer: separately tasked agent `/root/schema_v2_type_gate3`
- Test author/amendment author: not this reviewer
- Base approved Gate 3: `b65dd92e5de71e673bbb156185402216474dfb4d`
- Reviewed amendment commits: `becc0ab19cc8271529820a2a720e91bc379d3ff9`, `a9b1e056a6ec7c44da17b845c1696fc5842ba101`
- Reviewed range: `b65dd92e5de71e673bbb156185402216474dfb4d..a9b1e056a6ec7c44da17b845c1696fc5842ba101`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Verdict: **APPROVED**

## Scope and findings

This fresh Gate 3 review covers only the two fixture/oracle representation
corrections above. It excludes all production files and unrelated uncommitted
work in the shared worktree.

No blocking finding remains. The first amendment changes only the driver-native
representation asserted for `information_schema.STATISTICS.NON_UNIQUE`: integer
`0` still means the independently specified v1 unique predecessor, and integer
`1` still means the exact v2 non-unique index. The index name, columns,
multiplicity and uniqueness semantics remain unchanged. A live prepared
`mysqli::get_result()` metadata probe independently returned `int(0)`, confirming
the amended assertion matches the same access mode used by the executable spec.

The follow-up amendment removes a broad SQL `LIKE` fixture selector which also
matched the separate engineer-position CHECK. It reads the table's CHECK
metadata, normalizes each clause, selects only a clause starting with exact
`capabilityin`, and retains the existing assertion that exactly one match exists
before using its grammar-validated constraint name. This aligns the fixture
with the already-approved capability classifier semantics and cannot silently
pick either zero or multiple candidates. It changes neither the v4 fixture enum
literal nor any schema-v2 expected result.

Both corrections therefore repair setup/representation defects without
weakening sensitivity to the approved v54 behavior. The previously approved
RED remains the missing production schema-v2 behavior: version `1` versus `2`.
No new product behavior, public seam, schema shape, rejection outcome, or
production requirement is introduced.

## Required changes

None.

## Verification evidence

```text
$ git diff --check b65dd92e5de71e673bbb156185402216474dfb4d a9b1e056a6ec7c44da17b845c1696fc5842ba101
PASS (no output)

$ git show a9b1e056a6ec7c44da17b845c1696fc5842ba101:tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php | php -l
No syntax errors detected in Standard input code

$ prepared mysqli information_schema.STATISTICS metadata probe
NON_UNIQUE type/value: int 0
```

The focused verifier was not executed from the dirty shared worktree because
its uncommitted production implementation is explicitly outside this Gate 3
scope. Gate 3 relies on the already approved qualifying RED and reviews only
these two pre-GREEN fixture corrections.

## Exact reviewed hashes

```text
81de0cb0529f8f5885cf1475aaf2e5f6f0f35afb4b22e4968cda87a4d3a42f2d  tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
454c3a20c16c4448d125b7a5bce251065ab4ea450c80e035e6b7857202fd63c7  docs/operations/assignment-order-original-schema-v2-gate3-amendment-2026-09-05.md
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
d0c6d6de568b2527a87653707c6095b13c13c00270bdced7f1fe394109d094f6  docs/operations/assignment-order-original-schema-v2-red-evidence-2026-09-05.md
06363c98d2034dd2d36d6cb40e2f668ab05351dbd747ca6084bc4b1d7e382827  docs/operations/assignment-order-original-schema-v2-red-evidence-hash-correction-2026-09-05.md
```

This review omits its own circular hash.
