# INSPECTION-ITEM-COMPLETE-001 — Gate 2 current-revision RED evidence v5

Date: 2026-09-01  
Mission: `TEST-USER-READY`  
Test author: `/root/item_red_author`

This append-only record addresses V4-01. It supplements v1–v4 evidence. No
production or specification artifact was edited.

## Test-only correction

The public DTO value projection now contains a distinct
`currentChecklistRevision` key in addition to `baseRevision` and
`acceptedRevision`:

- `tests/Support/InspectionItemCompletionEvidenceProjection.php`, SHA-256
  `d3f876a6d362c25fc778c24d2a58ff3e3b99c64e992601952116b631e2d164e9`.

The projection returns `null` when current production omits the approved public
property. This cannot pass accidentally because accepted/replay tests assert the
independently fixed worked-example literal
`currentChecklistRevision === 1` before comparing before/after projections.
Their complete scalar value comparisons remain in place after replay or
rejection.

Affected test hashes:

- replay: `eee12e50afbc568b43538c880665d0c4ed40c3fe4ed012f1f5281bb24ab1da9b`;
- normalization/current-revision selector:
  `3274a37db5404be397c40e9bf4f44a3164c5d8b76b086839fe896c5ec698914f`;
- replay authorization:
  `9506fca497e3aa34227244696591d3089ce6530a0bfe550b54c07a33672f948e`;
- precedence:
  `d73302fe980d00e31f2bb81f2ba21caa965d4e7cae2595eab20ecb3211a21bcd`.

The previously approved example-A file was deliberately not edited; its hash
remains `b82775a4b93092f25d61cd8a8f5ac27dedb1155f90f1cc7e9feb392e6f0080ff`.

## Syntax and focused RED

All five affected PHP files passed `php -l`.

The independent accepted-result probe is:

```sh
FMONITOR_ITEM_TEST_CASE=current_revision tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
```

```text
Accepted query exposes current case checklist revision 1 independently of base/accepted fields.
Expected: 1
Actual: NULL
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
```

The accepted baseline literal was also reproduced through every affected replay
path:

```sh
FMONITOR_ITEM_TEST_CASE=reordered_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
FMONITOR_ITEM_TEST_CASE=revoked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=blocked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=schema_over_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
FMONITOR_ITEM_TEST_CASE=conflict_over_mutable tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_test.php
```

Each command failed on its named independently fixed revision assertion with
`Expected: 1`, `Actual: NULL`, then emitted `RED_ASSERTION`. These are public DTO
contract failures after successful deterministic acceptance, not setup failures.
Once production exposes the field, the preserved replay/authorization/
precedence assertions and complete before/after value comparisons remain next
in the same tests.

The prior approved behavior remains green without changing its hash:

```sh
php tests/InstallationProcess/inspection_item_complete_001_test.php
```

```text
PASS: INSPECTION-ITEM-COMPLETE-001 example A
```

Concurrency remains deferred to the honest MariaDB overlap RED. V4-01 now
requires fresh independent Gate 3 rereview; this evidence does not authorize
implementation.
