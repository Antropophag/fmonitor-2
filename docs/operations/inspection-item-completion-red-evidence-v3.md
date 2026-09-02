# INSPECTION-ITEM-COMPLETE-001 — Gate 2 blocker-closure RED evidence v3

Date: 2026-09-01  
Mission: `TEST-USER-READY`  
Test author: `/root/item_red_author`

This append-only record addresses V2-01 through V2-03 from
`reviews/tests/INSPECTION-ITEM-COMPLETE-001-v2.md`. It supplements, and does not
replace, the v1/v2 RED evidence. Production and the approved specification were
not edited.

## Reviewed basis and artifacts

- Approved spec SHA-256:
  `64acbd76b339ac2795e3e7cf9d2508ac4dabf62027e083d91ab25dacdb75c92a`.
- RED runner SHA-256:
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.
- Normalization test:
  `tests/InstallationProcess/inspection_item_complete_001_normalization_test.php`,
  SHA-256 `5157daa3bcb6a9ed2404d2eee3852a6bfc491c32ec4650dc8d3c280ddbdea98f`.
- Authorization-before-replay test:
  `tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php`,
  SHA-256 `87ea0f64f9bf7505317417b36ef45c48ebb559921e3e7ea82a169bb01dc32795`.
- Combined precedence test:
  `tests/InstallationProcess/inspection_item_complete_001_precedence_test.php`,
  SHA-256 `5a644bc5104172e2f8c8d63c40db7fe50960fbdbd5f311251b3f40a61db7ba39`.
- Typed rejection test, now with an isolated duplicate-id selector:
  `tests/InstallationProcess/inspection_item_complete_001_rejections_test.php`,
  SHA-256 `ea36648180b843dac16e405d69cc73afeef39b8ceec403463fced89c1a16b4e3`.
- Deterministic fixture remains unchanged from v2, SHA-256
  `aa656f5bff91303991b67dc44eb5906c2832e925eeafd7e74bea346f33974549`.

Each scenario selector is only a test-run isolation control. With no selector,
the files execute every contained scenario. All behavior and mutation checks use
only `completeItem` and `getItemCompletion`; fixture methods are setup only.

## V2-01 — numeric normalization

Commands and intended REDs:

```sh
FMONITOR_ITEM_TEST_CASE=ordered_persistence tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
```

```text
Installer snapshots persist in ascending numeric identifier order.
Expected: [1042, 2048]
Actual: [2048, 1042]
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
```

```sh
FMONITOR_ITEM_TEST_CASE=reordered_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
```

```text
Reordered representation of the same installer set is exact replay.
Expected: 'DUPLICATE'
Actual: 'ACCEPTED'
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
```

```sh
FMONITOR_ITEM_TEST_CASE=duplicate_installer tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_rejections_test.php
```

```text
duplicate installer list has stable INVALID_COMMAND result.
Expected: 'INVALID_COMMAND'
Actual: 'ACCEPTED'
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_rejections_test.php
```

Thus a non-canonical valid set must persist ascending and replay equivalently,
while an actually duplicated identifier remains invalid. The existing changed
normalized payload conflict test remains in force.

## V2-02 — current authorization before replay

Both exact replay variants first accept and retain their original public-query
evidence, then change only current authority:

```sh
FMONITOR_ITEM_TEST_CASE=revoked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=blocked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
```

Each produced:

```text
Uncaught DomainException: ACTOR_NOT_AUTHORIZED
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
```

The expected contract is a typed `ACTOR_NOT_AUTHORIZED` result followed by a
public query exactly equal to the original accepted evidence. An exception
cannot satisfy that shape.

## V2-03 — combined-condition precedence

```sh
FMONITOR_ITEM_TEST_CASE=auth_over_syntax tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
```

Current result: thrown `ACTOR_NOT_AUTHORIZED`, rather than the required typed
result, followed by `RED_ASSERTION`.

```sh
FMONITOR_ITEM_TEST_CASE=syntax_over_schema tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
```

Current result: thrown `CHECKLIST_ITEM_UNKNOWN`, rather than typed
`INVALID_COMMAND`, followed by `RED_ASSERTION`.

```sh
FMONITOR_ITEM_TEST_CASE=schema_over_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
```

```text
Schema readiness precedes exact replay.
Expected: 'INSPECTION_SCHEMA_UNAVAILABLE'
Actual: 'ACCEPTED'
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
```

```sh
FMONITOR_ITEM_TEST_CASE=conflict_over_mutable tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
```

Current result: thrown `CASE_NOT_WORKING`, rather than typed
`OPERATION_PAYLOAD_CONFLICT`, followed by `RED_ASSERTION`.

Every scenario also requires the public query to return `null` for a never
accepted operation or remain exactly equal to original evidence for an existing
operation. Those assertions prevent partial mutation once the typed result is
implemented.

## Concurrency boundary and gate state

Real same-base overlap remains honestly deferred to the separately reviewed
MariaDB adapter RED described in v2 evidence; no sequential fake was added.
All changed/new PHP tests passed `php -l`, and every command above produced a
behavioral `RED_ASSERTION`, not setup failure. These blocker-closure tests still
require a fresh independent Gate 3 rereview before implementation.
