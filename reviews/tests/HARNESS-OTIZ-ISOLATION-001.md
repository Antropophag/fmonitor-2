# Test review: HARNESS-OTIZ-ISOLATION-001

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/otiz_isolation_milestone_test_review`
- Independence: this reviewer did not author the specification, test, or implementation
- Reviewed ancestry: HEAD `932662938837b28309fef2bf0fe3cadb2ce86e41`; dirty-tree bytes pinned below
- Specification: `specs/HARNESS-OTIZ-ISOLATION-001.md`, version `0.1`
- Test: `tests/Verification/harness_otiz_isolation_001_test.php`
- Public seam: `php rapid-pilot/verify-otiz-workflow.php` against the disposable verification database
- Date: `2026-09-01`
- Verdict: `APPROVED`

## Findings

No blocking findings.

Traceability and seam choice pass. The test cites `HARNESS-OTIZ-ISOLATION-001 v0.1` and launches the specified verifier as a separate PHP process twice. It does not call planned fixture helpers or inspect private production methods. Database queries are confined to harness isolation observations: private-prefix leak discovery and complete ambient-decoy state capture.

Sensitivity passes. The reviewed test requires all eight exact specification milestone literals on each run. `hoiMilestonePositions()` advances its search offset after every match, so a later milestone printed early, a missing milestone, a reordered transcript, an empty/no-output no-op, or a verifier that merely exits zero cannot pass. It separately requires both process statuses to be zero. The current production verifier reaches the first two milestones on both runs but fails before the third; the test reports that exact absent ordered milestone before its later exit-status assertions. Thus the RED is caused by missing blocker fixture behavior rather than a generic nonzero exit.

Expected-value independence passes for this harness slice. The eight transcript expectations are copied literally from the normative milestone list, in normative order. The two-run equality, clean namespace, no leaks, and byte-identical ambient-state expectations are direct contract outcomes. The test does not derive expected amounts, statuses, hashes, milestones, or decoy state from OTIZ output. It intentionally leaves the pre-existing characterized financial assertions inside the verifier, consistent with the specification's explicit non-approval of financial semantics.

Rejected-case coverage passes. The first run must expose `open blockers prevent acceptance` before the later blocker-free milestone, and both runs must contain the same ordered sequence. This rejects an implementation that skips the blocked draft, silently accepts it, jumps directly to ready, or omits the later acceptance path. Existing verifier milestones additionally retain authorization, immutable acceptance, append-only reversal, and XLSX structure characterization.

Isolation, decoys, and cleanup pass. Before either run the test rejects an existing `otiz_verify_%` namespace. It creates nine native-looking unprefixed decoy tables plus a random unrelated table containing binary sentinel data, fingerprints their `SHOW CREATE TABLE` and complete rows, and requires strict equality after each run. The native decoys use plausible operational IDs and evidence, so accidentally reading ambient unprefixed state affects the verifier behavior rather than being a decorative sentinel. The second run begins only after the test has discovered and dropped first-run private leaks. The outer `finally` drops all observed/current private-prefix tables and only the exact random/native decoys it created, including assertion unwinding. An independent post-run catalog query found no remaining random harness or private-prefix table.

Determinism and setup handling pass. IDs, dates, evidence payloads, milestone expectations, and decoy contents are fixed; only private table prefixes and the unrelated decoy table name are random and excluded from transcript equality. Both transcript SHA-256 values were identical. `hoiConfig()` deterministically falls back from verification variables to test variables and then to the documented local disposable-test defaults. `hoiDb()` reports unavailable connectivity as `SETUP_FAILURE` with exit 2. The reproduced command removed all ten explicit verification/test DB variables and still reached the intended behavior RED through the local defaults.

## Reproduced RED

Command:

```text
env -u FMONITOR_VERIFY_DB_HOST -u FMONITOR_VERIFY_DB_PORT \
  -u FMONITOR_VERIFY_DB_NAME -u FMONITOR_VERIFY_DB_USER \
  -u FMONITOR_VERIFY_DB_PASSWORD -u FMONITOR_TEST_DB_HOST \
  -u FMONITOR_TEST_DB_PORT -u FMONITOR_TEST_DB_NAME \
  -u FMONITOR_TEST_DB_USER -u FMONITOR_TEST_DB_PASSWORD \
  php tests/Verification/harness_otiz_isolation_001_test.php
```

Result: exit `255`, with the intended assertion:

```text
RED_ASSERTION: first verifier transcript contains ordered harness milestone `open blockers prevent acceptance`
Expected: true
Actual: false
```

Captured evidence for both verifier runs:

```text
status: 255 / 255
stdout sha256: 6996983c04372d00c441f9d9e1ce6f33d0257b4d7a83c201c43c0a7ae01d9a5e / identical
authorization milestone position: 5 / 5
deterministic-draft milestone position: 136 / 136
open-blocker milestone position: absent / absent
clean before second run: []
ambient decoy sha256: 5c05b258de4f056681d9fc999a7f048b46f8e911e37e6acd77e6c89dd9798485
```

The verifier failure is `RuntimeException: open blockers prevent acceptance`. It leaves six additional private native-evidence tables per run, which the test detects as `leaksAfterFirst`/`leaksAfterSecond`; the harness cleanup removes them. This is additional valid RED evidence for the missing implementation, not broken test setup.

## Reviewed hashes

The bytes were unchanged before and after review execution:

```text
8952624654b5ef1c2006af0fbcf2ba090c3a8c8cc8c7e817f01d4565cf25e978  specs/HARNESS-OTIZ-ISOLATION-001.md
d39afb092702619249372376568d04e7708fce4d2f74a901546c579772e08249  tests/Verification/harness_otiz_isolation_001_test.php
78bb477a4ba686777f15b5dda014f1d62c36f92a011762d2828ed0545119bc84  rapid-pilot/verify-otiz-workflow.php
```

Gate 3 is approved. Gate 4 may add only the private native operational fixtures, explicit missing-evidence transition, and complete private-table cleanup required to satisfy the reviewed contract; the reviewed expectations must not change.
