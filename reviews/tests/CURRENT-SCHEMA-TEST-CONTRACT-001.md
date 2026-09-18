# Gate 3 test review — CURRENT-SCHEMA-TEST-CONTRACT-001

- Reviewer: `gpt-5.6-sol/low`, independently tasked for Gate 3; authored none of the reviewed specification, tests, helper, or OpenSpec artifacts.
- Review date: 2026-09-18.
- Base: `c55ab016ff7514ccc2982d406ca421ca9bd73ef0`.
- Final reviewed reconstructible source digest: `bae3420cb286888de8419b5e4c5bc87c33233fbc2691f5f3da81a2932e214416`.
- Final review package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T150945Z-b50b12693d/package.json`.
- Specification: `specs/CURRENT-SCHEMA-TEST-CONTRACT-001.md`.
- Initial verdict: `CHANGES_REQUESTED`; superseded by the approved correction rereview below.

## Initial findings

1. **MEDIUM — the current frontier was not fully literal-independent.** `tests/Support/CurrentProductionSchemaContract.php` declared literal `CURRENT_VERSION = 30`, but derived the complete version list with `range(1, CURRENT_VERSION)`. A1 requires both the current version and full contiguous list as literal expectations. A single self-mutation therefore redefined both expected values. The required correction was a separate explicit literal version list plus an internal consistency assertion.

2. **MEDIUM — the selected consumers were not traceably complete for the stated scope.** The initial delta converted only five consumers while equivalent setup-only current-version literals introduced by PR #192 remained in such tests as production process/readiness, runtime browser, initial-owner provisioning, settlement concurrency, construction-control filtering, and pilot-case import. The process artifacts contained neither a complete assertion inventory nor a principled exclusion criterion. The required correction was to classify every #192 PHP current-version assertion, convert all setup/replay-only expectations, and explicitly retain exact historical, recovery, catalog, and migration-specific expectations.

The initial review otherwise found that missing-last and missing-intermediate modes failed before database mutation with addressable frontier mismatches; unexpected mutation-mode success was converted to failure; changed subject assertions, replay emptiness, isolation, cleanup, no-mutation, and concurrency checks remained present; historical recovery and exact catalog checks remained literal; and no production files changed.

## Correction rereview

- Final verdict: `APPROVED`.
- Independence remained unchanged; this reviewer authored none of the corrections.

### Prior findings disposition

1. **Resolved.** `CurrentProductionSchemaContract` now owns separate literal `CURRENT_VERSION` and explicit literal `VERSIONS` values. `assertInternallyConsistent()` verifies exact contiguity and agreement between the list terminal and version, and the frontier test invokes that guard before variant selection. The expected frontier remains independent of the production catalogue, runner output, and database state.

2. **Resolved.** The OpenSpec design records the first-parent PR #192 PHP inventory: 61 current-version expectation lines, of which 31 setup/replay expectations were converted across 18 consumers and 30 exact historical, partial-recovery, bundle-compatibility, catalog/table-inventory, or migration-specific assertions were intentionally retained. The correction includes the consumers cited by the initial finding and the verification input maps the expanded set.

Review of the correction delta found the subject-specific assertions and retained exact recovery/catalog expectations intact. No new finding was introduced by the correction.

## Evidence summary

- The final package records exact-source GREEN evidence for all 17 mapped consumer/recovery commands and intended RED frontier evidence.
- Missing-last and missing-intermediate variants fail at the independent frontier comparison before database mutation and identify the defective variant.
- The helper and frontier test passed focused PHP syntax checks.
- `git diff --check` passed.
- No canonical full local `make test` or `make verify` suite was run; full-matrix verification remains the separate exact-source CI obligation.

## Complete findings

None for reconstructible source digest `bae3420cb286888de8419b5e4c5bc87c33233fbc2691f5f3da81a2932e214416`. Gate 3 is `APPROVED`; later test/spec/OpenSpec changes require delta review, and production/final review remains a separate gate.

This review record is post-review documentation. Its addition was not part of the reviewed reconstructible source digest and does not alter the reviewed candidate tests, specification, helper, or OpenSpec artifacts.
