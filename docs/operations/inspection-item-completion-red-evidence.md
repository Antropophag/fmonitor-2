# INSPECTION-ITEM-COMPLETE-001 — Gate 2 RED evidence

Date: 2026-09-01  
Mission: `TEST-USER-READY`  
Test author: `/root/item_red_author`

## Approved basis

- Executable specification: `specs/INSPECTION-ITEM-COMPLETE-001.md`
  (`64acbd76b339ac2795e3e7cf9d2508ac4dabf62027e083d91ab25dacdb75c92a`).
- Explicit Gate 1 owner approval:
  `docs/operations/inspection-item-completion-gate1-owner-approval.md`
  (`10d64837e94181f39a58972f4a38170ce96120bd883b0895b9cc6e2b54b3343f`).
- Acceptance statement under test: example A, an active actor `7301` with exact
  capability `inspection.item.complete` completes item `28` on working case
  `4512`, although engineer `7302` is assigned to the case. The command seam
  returns `ACCEPTED(1)` and the public evidence seam preserves the actual actor,
  assigned engineer and installer `1042` as separate facts.

## Focused artifacts

- `tests/InstallationProcess/inspection_item_complete_001_test.php`
  (`b82775a4b93092f25d61cd8a8f5ac27dedb1155f90f1cc7e9feb392e6f0080ff`).
- `tests/Support/InMemoryInspectionEvidenceEnvironment.php`
  (`269a7e622a2fbd9fc3c281dd641e8dc5eb43e5ce7aa371911a8adcb74365e8f6`).
- RED runner: `tools/verification/run.sh`
  (`edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`).

The fixture is deterministic and in-memory. The test invokes only the approved
`InspectionRecording::completeItem` and
`InspectionEvidenceView::getItemCompletion` application seams for behavior and
does not inspect SQL, repositories, HTTP or rapid-pilot internals.

## Exact RED command and result

Command:

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_test.php
```

Relevant output:

```text
PHP Fatal error:  Uncaught TestFailure: INSPECTION-ITEM-COMPLETE-001 approved public application seam is missing: FMonitor2\InspectionEvidence\InspectionRecording in /home/antropophag/code/fmonitor-2/tests/InstallationProcess/inspection_item_complete_001_test.php:25
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_test.php
```

The RED runner exited successfully because it observed the intended failing
assertion. PHP failed on the absent approved production application seam, before
any external setup or infrastructure access. Both new PHP files also passed
`php -l` before the RED run.

## Gate state

Gate 2 has demonstrated the intended RED for example A. No production code or
approved expectation was changed. Gate 3 still requires a separately tasked
independent test reviewer; this record does not approve the test or authorize
implementation by itself.
