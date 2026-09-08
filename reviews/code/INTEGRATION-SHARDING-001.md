# Gate 5 code review: INTEGRATION-SHARDING-001

- Date: `2026-09-08`
- Reviewer: independent agent `/root/shard_code_review`; reviewer did not author the implementation or tests
- Reviewed base: `2009c9bed8003cff492b70befeb67d4f582229e5`
- Reviewed source: `133afcc08adac7bd0a93c7c42309deb73d36ca4b`
- Owner contract: `docs/operations/integration-sharding-owner-decision-2026-09-08.md`
- Approved test SHA-256: `66a189937d55b9f0a1d9701cc8bd7ab3f89f02453657f17eb96683d89a22bc26`
- Verdict: `APPROVED`

## Standards

No findings.

The change is confined to the existing verification runner, its Make/shell route,
the standard GitHub Actions workflow, focused verification, and supporting records.
`category_items` keeps inventory validation centralized and preserves the prior
catalogue order when no shard is supplied. The bounded selector does not introduce
a balancing framework, timing manifest, shared result file, new permission, or
product/data mutation. Child test processes receive neither `CATEGORY`, `SHARD`,
nor the existing Make control variables. The implementation is small and does not
exhibit a material smell from the review baseline.

## Spec

No findings.

Both CLI entry points accept only `1/2` or `2/2`, and `category_items` additionally
rejects any shard for a category other than integration before the database probe
or test runtime. It validates the complete inventory, sorts selected integration
entries by path, and takes offsets `0::2` and `1::2`. Unsharded category selection
retains the old inventory order. The execution loop attempts every assigned path
and returns nonzero after collecting failures.

The Make wrapper forwards optional `SHARD` through the same CLI. The workflow uses
the standard two-value matrix with `fail-fast: false`; each matrix child has its own
runner and database reset/migration, with `always()` teardown. The aggregate job
still depends on the integration matrix job through `needs.integration.result`, so
failed, cancelled, skipped, or absent required full-run integration evidence cannot
produce `VERIFY_OK`. The required aggregate job remains named `verify`, and docs-only
selection still skips integration as a whole and produces only `DOCS_VERIFY_OK`.

## Verification evidence

At exact source `133afcc08adac7bd0a93c7c42309deb73d36ca4b`:

```text
$ sha256sum tests/Verification/verification_ci_001_test.py
66a189937d55b9f0a1d9701cc8bd7ab3f89f02453657f17eb96683d89a22bc26

$ python3 tests/Verification/verification_ci_001_test.py
Ran 15 tests in 22.577s
OK

$ python3 tools/verification/ci.py list integration [unsharded and both shards]
inventory=177 shard1=89 shard2=88 exact_union=yes

$ python3 -m py_compile tools/verification/ci.py
PASS

$ git diff --check origin/main...HEAD
PASS
```

The supplied delivery evidence additionally records the unchanged 177-entry real
catalogue, the exact 89+88 union, inventory and harness aggregation checks, lint,
focused verification, and the architecture check (`7/7`) as passing. No real database or full CI run was performed
as part of this independent review. The owner contract separately requires the
authoritative exact-head GitHub Actions run and its two-log union evidence before
merge; this approval does not claim that pending external evidence has completed.

## Verdict

`APPROVED`

Standards: 0 findings. Spec: 0 findings. No blocking issue was found in the reviewed
source; exact-head CI proof remains a pre-merge delivery step outside this review.
