# Test review: HARNESS-OTIZ-CANONICAL-COMPAT-001

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/otiz_canonical_compat_test_review_v3`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed ancestry: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`; dirty-tree bytes pinned below
- Specification: `specs/HARNESS-OTIZ-CANONICAL-COMPAT-001.md`, version `0.1`
- Test: `tests/Verification/harness_otiz_canonical_compat_001_test.php`
- Public seam: `php tests/Verification/harness_otiz_isolation_001_test.php` after public `make migrate`
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

Traceability and seam choice pass. The test cites the specification identifier, establishes the canonical database exclusively through `make migrate`, and invokes the existing isolation harness as a subprocess twice. Its direct database observations are limited to ambient-state preservation, sentinel ownership, leak detection, and final cleanup—the harness contract under review—not OTIZ product outcomes.

The intended RED is reproduced twice. Both executions successfully run canonical migrations to schema version 4, install compatible deterministic sentinels, and reach the public isolation seam. Both fail because that seam still rejects the pre-existing canonical `fm2_order_installers` table. This is the missing compatibility behavior, not an environment/setup failure. In both attempts the two internal invocations fail identically, canonical fingerprints remain identical around them, and no private tables leak.

Migration and repeated-success sensitivity pass. Migration JSON must report `ok=true`, `schemaVersion=4`, and either no newly applied versions or a unique ordered subset of versions 1–4. Once the compatibility behavior exists, the test requires two zero-status runs, empty stderr, byte-identical transcripts, the exact two-line isolation-harness success shape, byte-identical canonical definitions and rows after each run, and an empty private namespace after each run. A no-op, single-run-only implementation, schema reset, canonical mutation, or transcript weakening cannot satisfy these assertions.

Failure-path sensitivity passes. The test injects one harness-only post-fixture failure through the public subprocess environment. This is implementation-independent: it does not name a helper, private function, table, or cleanup algorithm, and its expectation comes directly from the failure-cleanup acceptance scenario. It requires a nonzero exit, exactly empty stdout (therefore no false success transcript), exactly one stable `REGRESSION_FAILURE: injected after fixtures` stderr verdict, unchanged canonical definitions and rows, and no private or harness-owned noncanonical artifacts. The injection is deliberately after fixture installation, so an implementation that only cleans the normal path cannot pass.

Partial-install cleanup safety passes. All deterministic sentinel identities are known before the first insert, and the test first proves every owning identity is absent. The outer `finally` therefore removes only those pre-authorized identities in dependency-safe child-to-parent order even if installation throws halfway through. Related artifact/installer rows use the pre-cleared assignment-order identity. It never discovers cleanup targets from post-failure production output and never drops a canonical table. Original auto-increment values are recorded before sentinels and restored after cleanup; final full canonical-state equality proves the ambient schema and rows were restored.

Expected values and determinism pass. Schema version 4, allowed migration versions, sentinel values, exact success transcript grammar, exact injected-failure verdict, and ownership prefixes are fixed by the reviewed harness contract. Expected state is captured before the subprocess under test and compared structurally; it is not recomputed from subprocess output. Random verifier-private names are observed only for absence/cleanup and cannot affect expected transcript values.

Cleanup was independently checked after both reproduced RED executions. Catalog queries returned no `otiz_verify_%` or `harness_otiz_isolation_decoy_%` tables, and all six deterministic canonical sentinel owner keys had row count zero.

## Reproduced RED

Command, executed twice:

```text
php tests/Verification/harness_otiz_canonical_compat_001_test.php
```

Each execution exited `255` at the intended assertion:

```text
RED_ASSERTION: OTIZ isolation public seam must coexist with pre-existing canonical v1-v4 tables on its first invocation
Expected: 0
Actual: 255
```

The nested seam's stable cause in both runs was:

```text
SETUP_FAILURE: native decoy target `fm2_order_installers` must not pre-exist in the disposable database.
```

For both attempts, the evidence recorded by the test showed:

```text
first status: 255
second status: 255
before/after-first/after-second canonical sha256: 0bdc026417ab0f9ba0595e98204071859e18adab30b167f22ba3dbacfc46c9cc
leaks after first: []
leaks after second: []
```

Static validation also passed:

```text
php -l tests/Verification/harness_otiz_canonical_compat_001_test.php
git diff --check -- specs/HARNESS-OTIZ-CANONICAL-COMPAT-001.md tests/Verification/harness_otiz_canonical_compat_001_test.php
```

## Reviewed hashes

The reviewed bytes were unchanged before and after review execution:

```text
fa623b9ddef906f3d621e58f0b1e0015d62acc25e5b9f70a7adee79a9ab284b8  specs/HARNESS-OTIZ-CANONICAL-COMPAT-001.md
93e6e1105f99445556a412e3a1b458d07ffea6616480387dccf63fcf69e76ded  tests/Verification/harness_otiz_canonical_compat_001_test.php
d39afb092702619249372376568d04e7708fce4d2f74a901546c579772e08249  tests/Verification/harness_otiz_isolation_001_test.php
dfd21296c5ca0cfe8cf09c43311384a9716d79f5be73b2060916a8e678f0d556  rapid-pilot/verify-otiz-workflow.php
```

Gate 3 is approved. Gate 4 may add only canonical/owned-table distinction, failure-safe cleanup, and the harness-only post-fixture failure seam required by the reviewed contract; the reviewed expectations must not change.
