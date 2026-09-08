# VERIFICATION-PR-CYCLE-001 — independent Gate 3 test review

- Gate: 3 — executable contract and RED evidence
- Reviewer: separately tasked agent `/root/cycle_review`
- Independence: reviewer did not author the specification, tests, production implementation, or RED evidence
- Date: 2026-09-08
- Verdict: **CHANGES_REQUESTED**

## Review result

The tests use real temporary Git histories for selection and isolated temporary
runtime/trace fixtures for command execution. They correctly establish RED at the
public seams: `ci.py` is absent, the E2E path remains duplicated, bootstrap still
starts five independent children, and the canonical `make test` / `make fresh-test`
targets are absent. The inventory reconstruction preserves the previous catalog
digest while making the intended E2E removal explicit. The full and fresh harness
tests cover the compatibility aliases and preserve ordered failure aggregation and
mandatory teardown, including `MAKEFLAGS=-j4` for the canonical fresh target.

Two executable-contract gaps remain.

1. `test_category_run_continues_and_reports_real_exit` gives every catalog row the
   `php` runtime. The specification requires `runtime TAB path` execution and the
   real union contains PHP, Node, and Python entries. With the present test, the new
   category seam could ignore the runtime column and always invoke PHP while still
   passing. Give the isolated category at least two different traced runtimes and
   assert each receives its assigned path, continuation, output, timing, and exit.

2. The amended `test_aggregate_requires_exact_expected_evidence` now rejects a
   `failure` for every docs-only result and an unexpected successful E2E result,
   but still does not exercise `cancelled` or a missing result for `full=false`.
   Both are explicit fail-closed requirements. It also does not assert that rejected
   docs-only inputs print neither `DOCS_VERIFY_OK` nor `VERIFY_OK`. Add the
   cancelled/missing cases for `plan`, `fast`, and categories and assert the absence
   of both success markers.

The conservative selection probes otherwise cover docs-only, code/test/CI/policy/
unknown paths, invalid or absent base, empty diff, non-PR events, and code-to-docs
rename with `--no-renames`. Catalog/mapping checks cover incomplete, extra, invalid,
duplicate, and conflicting-runtime cases before execution when combined with the
existing inventory contract. The real composition assertion covers a disjoint
union, the single E2E category, all five former child contracts plus bootstrap once,
and removal of direct child filenames from bootstrap.

## RED evidence

- `fmonitor25-cycle-red.log`: qualifying feature RED; all eight new CLI scenarios
  fail because the public `tools/verification/ci.py`/category seam is absent.
- `fmonitor25-dedup-red.log`: qualifying focused RED; only the repository baseline
  assertion fails because E2E remains in `db` as well as `e2e`.
- `fmonitor25-test-name-red.log`: qualifying focused RED; `make test` is absent.
- `fmonitor25-fresh-name-red.log`: qualifying focused RED; `make fresh-test` is absent.

All test files parse, `git diff --check` is clean, and
`openspec validate optimize-verification-pr-cycle --strict` passes. No production
file was changed or executed by this review.

## Reviewed hashes

```text
b5eebf958d546ea68c40ac814a9dcffec20fa8c12c22662f90219d1a3edda7f7  openspec/changes/optimize-verification-pr-cycle/proposal.md
099684ce8d2c68083f79b592208b8491966ddf837bb6b74ee4acd601f1dabd8e  openspec/changes/optimize-verification-pr-cycle/design.md
804ae6fabcd840f9e23544de677f74c123cb22335341adac6393234d0f2ef9ea  openspec/changes/optimize-verification-pr-cycle/tasks.md
20a09ff9227576c10c2362ddf44b88043f49dc48677381f512bfe47aecc22dd5  openspec/changes/optimize-verification-pr-cycle/specs/verification/pr-cycle/spec.md
41ce5d856822dc2e9aadc12accce09b6cc26f92fc0035679a0a84624cf1b1422  tests/Verification/verification_ci_001_test.py
92fd0e9bc9b263d86dae7681cae077d1bc144856ef970768ba12660aed45c2da  tests/Verification/verification_inventory_001_test.py
81321ea983b0069b34b53eef4adc43c2d6c3bf3da4378d30978e0e46af52e4a1  tests/Verification/harness_full_aggregation_001_test.php
2b1d826b13e277b21d769a98e251da7625c1bb21e7276b1e4894ac1dc296fe34  tests/Verification/harness_fresh_test_lifecycle_001_test.php
ba57d438224deb1736f0437e87161be17dbf36953f767ae5555521dc4d0c6540  /tmp/fmonitor25-cycle-red.log
85212dfbc304e33bb2774dd920ef1315bfdb92c95e6fc78cba35515629d9e4be  /tmp/fmonitor25-dedup-red.log
ea0b4e19e111761d5eda28542cf9c4155c531f9469aeaa82680bcc282063e6d3  /tmp/fmonitor25-test-name-red.log
a6d66c20e9d2b08b98c77626cb7230a1ed2342154e7fcebba2cd2a3164dcb6a3  /tmp/fmonitor25-fresh-name-red.log
```

Gate 3 remains **CHANGES_REQUESTED**. Add the two missing assertions, reproduce
focused RED, and request a fresh independent rereview before production work.

## Focused re-review of the two requested corrections

- Reviewer: separately tasked agent `/root/audit`; did not author the specification, tests, implementation, or RED evidence.
- Scope: only `test_category_run_continues_and_reports_real_exit` and `test_aggregate_requires_exact_expected_evidence`, plus their normative requirements and the prior two findings.
- Superseding verdict: **APPROVED**.

Both blocking gaps are closed.

The category execution case now dispatches three real runtimes in one category: PHP is assigned the first path and returns exit 7, Node receives the next path, and a real Python fixture receives the third. The exact trace proves runtime-to-path dispatch and continuation after the PHP failure. Fixed child-output assertions prove stdout preservation for all three, while timing assertions prove the failed PHP exit and successful Node/Python exits remain visible. A runner that ignores the runtime column, stops at the first failure, loses child output, or fabricates a successful status cannot satisfy the test.

The aggregation case now exercises every required result key in both modes. For docs-only it rejects `failure`, `cancelled`, and missing evidence for plan, fast, and every category; it also rejects skipped plan/fast and unexpectedly successful categories. For full mode it rejects failure, cancelled, skipped, and missing evidence for every key. Every rejected case requires a nonzero exit and absence of the `VERIFY_OK` substring, which excludes both `VERIFY_OK` and `DOCS_VERIFY_OK`. Invalid JSON shapes and invalid `full` remain rejected. The two exact success paths still distinguish full `VERIFY_OK` from docs-only `DOCS_VERIFY_OK`.

The updated `/tmp/fmonitor25-cycle-red.log` remains qualifying RED: all eight public CLI scenarios fail because `tools/verification/ci.py` and the category seam are absent. In particular, the category case fails before any trace because the old runner rejects `category`, and aggregation fails because the CLI file is absent. These are the intended missing behaviors, not fixture or assertion setup failures.

No blocking findings remain from the prior review. Gate 3 is **APPROVED** for implementation without changing the reviewed expectations.
