# INSPECTION-ITEM-COMPLETE-001 — Gate 2 value-projection RED evidence v4

Date: 2026-09-01  
Mission: `TEST-USER-READY`  
Test author: `/root/item_red_author`

This append-only record addresses V3-01 from
`reviews/tests/INSPECTION-ITEM-COMPLETE-001-v3.md`. It supplements v1–v3 RED
evidence. The approved specification remains SHA-256
`64acbd76b339ac2795e3e7cf9d2508ac4dabf62027e083d91ab25dacdb75c92a`;
production was not edited.

## Test-only correction

`tests/Support/InspectionItemCompletionEvidenceProjection.php`
(`859891d0477358c5b1df6b2be4c2e48c5d25a9226eb5257ac57fde659b3e6d01`)
now independently projects the public query DTO to strict scalar/array values:

- client operation and installation case identifiers;
- section and item identifiers;
- actual actor and nullable assigned engineer at receipt;
- device and server receipt timestamps;
- base and accepted revisions;
- immutable template id, version and SHA-256;
- every installer snapshot's tab id, full name and position, preserving order.

The helper consumes only `ItemCompletionEvidence` returned by the public query.
It does not call production serialization, fixture/storage methods, repository
or SQL. Before replay/mutation, tests capture this value projection; afterwards
they project a freshly queried DTO and compare arrays strictly. A correct query
may therefore hydrate a different PHP object instance without failing.

Affected test hashes:

- replay: `56e2b2d21aeae8fa1dbddae413a95fbdfd5689d8d4be4824bc0f62a7cc5f12d5`;
- normalization: `46347e51dac4ee569de9378d86eb2b201650c0063a07528317b4ec586c6f305f`;
- replay authorization: `73c2887b71e48fcc2c592218414a7e170962604c4048027c9106b4aedf0a8a6d`;
- precedence: `d359f98146a9e44ef68721605e59154c09259f1b74e6dad451667225299aa4d1`.

All five affected PHP files passed `php -l`.

## Reproduced intended REDs

The RED runner remains SHA-256
`edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

```sh
FMONITOR_ITEM_TEST_CASE=reordered_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_normalization_test.php
```

Still fails on `Expected: 'DUPLICATE'; Actual: 'ACCEPTED'`, then emits
`RED_ASSERTION`.

```sh
FMONITOR_ITEM_TEST_CASE=revoked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
FMONITOR_ITEM_TEST_CASE=blocked tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_authorization_test.php
```

Both still fail on the current thrown `DomainException: ACTOR_NOT_AUTHORIZED`
instead of the required typed result, then emit `RED_ASSERTION`.

```sh
FMONITOR_ITEM_TEST_CASE=schema_over_replay tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
```

Still fails on `Expected: 'INSPECTION_SCHEMA_UNAVAILABLE'; Actual: 'ACCEPTED'`,
then emits `RED_ASSERTION`.

```sh
FMONITOR_ITEM_TEST_CASE=conflict_over_mutable tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_precedence_test.php
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_replay_test.php
```

These still fail respectively on current `CASE_NOT_WORKING` before conflict and
before exact replay, then emit `RED_ASSERTION`.

Thus replacing object identity with value comparison did not change any intended
behavioral RED reason. The previously approved example-A command remains green:

```sh
php tests/InstallationProcess/inspection_item_complete_001_test.php
```

```text
PASS: INSPECTION-ITEM-COMPLETE-001 example A
```

Concurrency remains honestly deferred to the MariaDB overlap RED described in
v2/v3. V3-01 is returned for fresh independent Gate 3 rereview; this record does
not authorize implementation.
