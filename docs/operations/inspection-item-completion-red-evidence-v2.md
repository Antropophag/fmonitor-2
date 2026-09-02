# INSPECTION-ITEM-COMPLETE-001 — Gate 2 incremental RED evidence v2

Date: 2026-09-01  
Mission: `TEST-USER-READY`  
Test author: `/root/item_red_author`

This record is append-only follow-up evidence after example A became minimally
green. It does not replace
`docs/operations/inspection-item-completion-red-evidence.md` and does not alter
the reviewed example-A expectation.

## Approved basis

- `specs/INSPECTION-ITEM-COMPLETE-001.md`, SHA-256
  `64acbd76b339ac2795e3e7cf9d2508ac4dabf62027e083d91ab25dacdb75c92a`.
- Exact public seams under test:
  `InspectionRecording::completeItem` and
  `InspectionEvidenceView::getItemCompletion`.

## Incremental focused artifacts

- `tests/InstallationProcess/inspection_item_complete_001_replay_test.php`
  (`c91eaa260e3d81266c76b9f889ce857564814a696bfd9b8ec95a0750154eac03`):
  exact replay after case closure, template replacement, engineer reassignment
  and crew replacement; original actor/assignment/installer evidence remains
  immutable and repeated query is read-only.
- `tests/InstallationProcess/inspection_item_complete_001_authorization_test.php`
  (`fc7d89edb6767996206eeadc35a3d11e337d3ffd69cdd1edd326a52aa805f815`):
  reassignment-only acceptance followed by deterministic rejection after exact
  capability revocation and user blocking despite earlier device time; rejected
  operation ids have no query-visible evidence.
- `tests/InstallationProcess/inspection_item_complete_001_conflict_revision_test.php`
  (`fb22111119c6e052680bc5ce3725a02223d36f51de27302eb3a3e35a9dda5dce`):
  changed installer payload under an existing operation id conflicts without
  replacing evidence; a distinct stale operation returns locked current
  revision and has no loser evidence.
- `tests/InstallationProcess/inspection_item_complete_001_rejections_test.php`
  (`4bed58e616a76c4f5818e2151e3a44710f37d964674cae8fdc2e2298b5019b72`):
  command syntax matrix and stable typed schema/case/template/item/installer
  rejection results, each with no public-query evidence.
- Expanded deterministic fixture
  `tests/Support/InMemoryInspectionEvidenceEnvironment.php`
  (`aa656f5bff91303991b67dc44eb5906c2832e925eeafd7e74bea346f33974549`).

The fixture changes add only deterministic setup controls for mutable case facts
and schema readiness. Behavioral assertions remain exclusively at the approved
command/query seams; no test reads a repository, SQL table, HTTP adapter or
rapid-pilot internal.

## Exact RED commands and relevant results

All four files passed `php -l`. Each command below caused the PHP test to fail
for missing approved behavior and caused the RED runner to emit its qualifying
`RED_ASSERTION`:

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_test.php
```

```text
Uncaught DomainException: CASE_NOT_WORKING
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_replay_test.php
```

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_authorization_test.php
```

```text
Uncaught DomainException: ACTOR_NOT_AUTHORIZED
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_authorization_test.php
```

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_conflict_revision_test.php
```

```text
One operation id cannot bind changed installer attribution.
Expected: 'OPERATION_PAYLOAD_CONFLICT'
Actual: 'ACCEPTED'
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_conflict_revision_test.php
```

```sh
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_rejections_test.php
```

```text
malformed operation UUID has stable INVALID_COMMAND result.
Expected: 'INVALID_COMMAND'
Actual: 'ACCEPTED'
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_rejections_test.php
```

These are behavioral failures after deterministic in-memory setup. None is a
database, service, autoload or other setup failure.

## Concurrency testability boundary

Approved example F requires a real overlap of two distinct commands at the same
locked base revision. The current in-memory public-seam composition has neither
a transactional case lock nor a controllable pause/yield point inside that
lock. Two sequential calls would only test stale revision and would falsely
label serialization as concurrency. No such fake overlap was added.

Example F remains a mandatory later RED against the MariaDB persistence adapter
using two independent connections/processes and a deterministic overlap
barrier. That test must assert the unordered result set
`{ACCEPTED(1), STALE_REVISION(1)}` and public-query evidence for only the winner.
The existing stale test proves the loser contract but does not claim to prove
real serialization.

## Gate state

These increments are demonstrated RED only. They require independent Gate 3
test review before implementation. No production code, approved specification,
OpenSpec task state or unrelated dirty file was changed by this author.
